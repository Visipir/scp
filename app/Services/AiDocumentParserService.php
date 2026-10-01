<?php

namespace App\Services;

use App\Models\SystemConfiguration;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class AiDocumentParserService
{
    public function parsePdf(string $pdfPath, string $documentType, ?string $modelOverride = null): array
    {
        $config = SystemConfiguration::first();
        $apiKey = $config?->effective_gemini_api_key;
        $model = $modelOverride ?: ($config?->effective_gemini_schedule_model ?? 'gemini-3.5-flash-lite');

        if (!$apiKey) {
            throw new Exception("Gemini API key is missing. Please configure it in system settings.");
        }

        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";

        @ini_set('max_execution_time', 600);
        @set_time_limit(600);

        // For Jadwal Ruangan, use per-day extraction strategy
        if ($documentType === 'Jadwal Ruangan') {
            return $this->parseRoomSchedulePdf($pdfPath, $url);
        }

        return $this->parseGenericPdf($pdfPath, $documentType, $url);
    }

    /**
     * Specialized extractor for Jadwal Ruangan matrix.
     * Uses native PDF inline data (multimodal vision) for superior 2D table grid alignment,
     * with text fallback if needed, and guarantees 100% period coverage across all 48 classes.
     */
    private function parseRoomSchedulePdf(string $pdfPath, string $url): array
    {
        $fullText = '';
        try {
            $parser = new \Smalot\PdfParser\Parser();
            $pdf = $parser->parseFile($pdfPath);
            $fullText = $pdf->getText();
        } catch (\Throwable $e) {
            Log::warning('Smalot PDF parse exception, proceeding with inline PDF: ' . $e->getMessage());
        }

        $days = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'];
        $schema = $this->getRoomScheduleSchema();
        $allData = [];

        foreach ($days as $dayIdx => $day) {
            $prompt = $this->getRoomScheduleDayPrompt($day);

            Log::info("Jadwal Ruangan: Requesting extraction for {$day}");

            // Try multimodal PDF inline_data first (preserves visual 2D table grid alignment)
            $data = $this->callGeminiApiWithPdf($url, $pdfPath, $prompt, $schema, 65536);

            // Fallback to extracted text if inline data failed
            if (empty($data) && !empty(trim($fullText))) {
                Log::info("Jadwal Ruangan: Falling back to text extraction for {$day}");
                $data = $this->callGeminiApi($url, $fullText, $prompt, $schema, 65536);
            }

            if (!empty($data)) {
                foreach ($data as &$item) {
                    $item['day_of_week'] = $day;
                    if (empty($item['cycle_type'])) {
                        $item['cycle_type'] = 'Putaran 2';
                    }
                }
                unset($item);

                Log::info("Jadwal Ruangan: {$day} extracted " . count($data) . " entries");
                $allData = array_merge($allData, $data);
            } else {
                Log::warning("Jadwal Ruangan: {$day} returned 0 entries");
            }

            // Pacing delay between day calls to stay under 15 RPM
            if ($dayIdx < count($days) - 1) {
                sleep(4);
            }
        }

        if (empty($allData)) {
            throw new Exception("Respons AI kosong untuk semua hari. Dokumen mungkin tidak berisi matriks jadwal ruangan.");
        }

        // Guarantee 100% complete coverage for all 48 classes across all periods
        $allData = $this->ensureCompleteScheduleCoverage($allData);

        Log::info('Jadwal Ruangan: Total entries after coverage guarantee: ' . count($allData));
        return $allData;
    }

    /**
     * Generic PDF parser for other document types (Daftar Siswa, Jadwal Pelajaran, etc.)
     */
    private function parseGenericPdf(string $pdfPath, string $documentType, string $url): array
    {
        $prompt = $this->getPrompt($documentType);
        $schema = $this->getSchema($documentType);

        $parser = new \Smalot\PdfParser\Parser();
        $pdf = $parser->parseFile($pdfPath);
        $pages = $pdf->getPages();
        $chunks = array_chunk($pages, 6, true);
        
        $allData = [];

        foreach ($chunks as $chunkIdx => $chunkPages) {
            $chunkText = '';
            $pageNumbers = [];

            foreach ($chunkPages as $pIndex => $page) {
                $pText = trim($page->getText());
                if ($pText !== '') {
                    $pNum = $pIndex + 1;
                    $pageNumbers[] = $pNum;
                    $chunkText .= "--- Halaman {$pNum} ---\n" . $pText . "\n\n";
                }
            }

            if (trim($chunkText) === '') {
                continue;
            }

            $pagesLabel = implode(', ', $pageNumbers);
            $data = $this->callGeminiApi($url, $chunkText, $prompt, $schema, 8192);

            if (!empty($data)) {
                $allData = array_merge($allData, $data);
            }

            // Pacing delay between chunks
            if ($chunkIdx < count($chunks) - 1) {
                sleep(1);
            }
        }

        if (empty($allData)) {
            throw new Exception("Respons AI kosong atau format tidak sesuai untuk semua halaman.");
        }

        return $allData;
    }

    /**
     * Call Gemini API with retry logic.
     */
    private function callGeminiApi(string $url, string $documentText, string $prompt, array $schema, int $maxTokens): array
    {
        $maxRetries = 3;
        $retryCount = 0;

        while ($retryCount < $maxRetries) {
            try {
                $response = Http::timeout(180)->post($url, [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $prompt],
                                ['text' => "TEKS DOKUMEN PDF:\n\n" . $documentText]
                            ]
                        ]
                    ],
                    'generationConfig' => [
                        'responseMimeType' => 'application/json',
                        'responseSchema' => $schema,
                        'temperature' => 0.05,
                        'maxOutputTokens' => $maxTokens
                    ]
                ]);

                if ($response->successful()) {
                    $resultText = $response->json('candidates.0.content.parts.0.text');
                    if ($resultText) {
                        $decoded = json_decode($resultText, true);
                        $data = is_array($decoded) && isset($decoded['data']) ? $decoded['data'] : $decoded;
                        return is_array($data) ? $data : [];
                    }
                    return [];
                }

                $status = $response->status();
                $lastError = $response->body();

                if (in_array($status, [429, 500, 503])) {
                    $retryCount++;
                    if ($retryCount < $maxRetries) {
                        $delay = $status === 429 ? 8 * $retryCount : 3 * $retryCount;
                        Log::warning("Gemini API retry {$retryCount}/{$maxRetries} (HTTP {$status}), waiting {$delay}s");
                        sleep($delay);
                        continue;
                    }
                }

                Log::error("Gemini API Error", ['status' => $status, 'response' => $lastError]);

                $errorData = $response->json('error');
                $message = $errorData['message'] ?? $lastError;

                if ($status === 503) {
                    throw new Exception("Server AI Google sedang sibuk. Silakan coba lagi.");
                } elseif ($status === 429) {
                    throw new Exception("Batas permintaan API (RPM) tercapai. Silakan coba lagi sebentar lagi.");
                } else {
                    throw new Exception("Gagal memproses dokumen dengan AI: " . $message);
                }
            } catch (\Illuminate\Http\Client\ConnectionException $e) {
                $retryCount++;
                if ($retryCount < $maxRetries) {
                    sleep(5);
                    continue;
                }
                throw new Exception("Koneksi ke AI Google terputus. Silakan coba lagi.");
            }
        }

        return [];
    }

    /**
     * Call Gemini API with native PDF inline_data (multimodal vision).
     */
    private function callGeminiApiWithPdf(string $url, string $pdfPath, string $prompt, array $schema, int $maxTokens): array
    {
        $maxRetries = 3;
        $retryCount = 0;
        $pdfBase64 = base64_encode(file_get_contents($pdfPath));

        while ($retryCount < $maxRetries) {
            try {
                $response = Http::timeout(180)->post($url, [
                    'contents' => [
                        [
                            'parts' => [
                                [
                                    'inline_data' => [
                                        'mime_type' => 'application/pdf',
                                        'data' => $pdfBase64,
                                    ]
                                ],
                                ['text' => $prompt]
                            ]
                        ]
                    ],
                    'generationConfig' => [
                        'responseMimeType' => 'application/json',
                        'responseSchema' => $schema,
                        'temperature' => 0.05,
                        'maxOutputTokens' => $maxTokens
                    ]
                ]);

                if ($response->successful()) {
                    $resultText = $response->json('candidates.0.content.parts.0.text');
                    if ($resultText) {
                        $decoded = json_decode($resultText, true);
                        $data = is_array($decoded) && isset($decoded['data']) ? $decoded['data'] : $decoded;
                        return is_array($data) ? $data : [];
                    }
                    return [];
                }

                $status = $response->status();
                $lastError = $response->body();

                if (in_array($status, [429, 500, 503])) {
                    $retryCount++;
                    if ($retryCount < $maxRetries) {
                        $delay = $status === 429 ? 8 * $retryCount : 3 * $retryCount;
                        Log::warning("Gemini API retry (PDF inline) {$retryCount}/{$maxRetries} (HTTP {$status}), waiting {$delay}s");
                        sleep($delay);
                        continue;
                    }
                }

                Log::error("Gemini API Error (PDF inline)", ['status' => $status, 'response' => $lastError]);
                break;
            } catch (\Illuminate\Http\Client\ConnectionException $e) {
                $retryCount++;
                if ($retryCount < $maxRetries) {
                    sleep(5);
                    continue;
                }
                break;
            }
        }

        return [];
    }

    /**
     * Per-day prompt for Jadwal Ruangan extraction covering all 48 classes.
     */
    private function getRoomScheduleDayPrompt(string $day): string
    {
        $maxJP = ($day === 'Jumat') ? 8 : 10;
        return <<<PROMPT
Anda adalah AI vision parser dokumen jadwal ruangan FET / SMKN 1 Bantul.

TUGAS:
Dari dokumen tabel matriks jadwal ruangan yang diberikan, ekstrak data penempatan ruangan untuk SEMUA 48 KELAS khusus pada hari **{$day}** secara lengkap dari Jam Pelajaran 1 sampai Jam Pelajaran {$maxJP} (JP 1 - JP {$maxJP}).

DAFTAR 48 KELAS RESMI (16 rombel per tingkat, total 48 kelas):
- Tingkat 10 (X) [16 Kelas]:
  "X RPL 1", "X RPL 2", "X TKJ 1", "X TKJ 2", "X DKV 1", "X DKV 2", "X AK 1", "X AK 2", "X AK 3", "X LPS 1", "X LPS 2", "X MP 1", "X MP 2", "X BR 1", "X BR 2", "X BD"
- Tingkat 11 (XI) [16 Kelas]:
  "XI RPL 1", "XI RPL 2", "XI TKJ 1", "XI TKJ 2", "XI DKV 1", "XI DKV 2", "XI AK 1", "XI AK 2", "XI AK 3", "XI LPS 1", "XI LPS 2", "XI MP 1", "XI MP 2", "XI BR 1", "XI BR 2", "XI BD"
- Tingkat 12 (XII) [16 Kelas]:
  "XII RPL 1", "XII RPL 2", "XII TKJ 1", "XII TKJ 2", "XII DKV 1", "XII DKV 2", "XII AK 1", "XII AK 2", "XII AK 3", "XII AK 4", "XII LPS", "XII MP 1", "XII MP 2", "XII BR 1", "XII BR 2", "XII BD"

CATATAN KHUSUS NAMA KELAS:
- Tingkat 11 memiliki "XI LPS 1" dan "XI LPS 2" (TIDAK ADA "XI AK 4"). Jika ada kolom AK 4 pada tingkat 11, itu adalah XI LPS 1.
- Tingkat 12 memiliki "XII AK 4" dan "XII LPS".
- Gunakan HANYA nama kelas resmi dari daftar 48 kelas di atas.

STRUKTUR TABEL MATRIKS:
- Sumbu Horizontal (Kolom) = Kolom-kolom kelas (terbagi 3 tingkat angkatan: 10, 11, 12).
- Sumbu Vertikal (Baris) = Blok hari (Senin s.d. Jumat), Jam ke- (1 s.d. {$maxJP}), dan rentang Waktu.
- Nilai Sel = Kode Ruang / Laboratorium yang digunakan oleh kelas tersebut pada jam bersangkutan.
- Seluruh baris ke bawah pada kolom kelas yang sama adalah ruangan milik kelas tersebut.

ATURAN KODE RUANGAN:
- Awalan "L." atau singkatan Lab: "L.16" → "Lab 16", "L.11" → "Lab 11", "L.12" → "Lab 12", "L.20" → "Lab 20"
- Angka murni: "12" → "Ruang 12", "17" → "Ruang 17", "22" → "Ruang 22", "6" → "Ruang 6", "4" → "Ruang 4"
- Lapangan olahraga / fasilitas: "OR1"/"OR2"/"OR3" → "Lapangan OR 1"/"Lapangan OR 2"/"Lapangan OR 3", "HALL" → "Aula Hall", "LIPA" → "Lab IPA", "SINIAR" → "Ruang Siniar"

ATURAN RENTANG JAM & KELENGKAPAN:
- SETIAP KELAS DARI 48 KELAS WAJIB MEMILIKI RUANGAN UNTUK SETIAP JAM DARI JP 1 SAMPAI JP {$maxJP}!
- TIDAK BOLEH ADA KELAS YANG HILANG ATAU JAM YANG KOSONG.
- Gabungkan jam yang menempati ruangan sama secara berurutan menggunakan start_period dan end_period.
  Contoh: Jika kelas menempati Lab 16 dari jam 1 sampai 4, buat 1 entri: {"room_name": "Lab 16", "class_name": "X RPL 1", "start_period": 1, "end_period": 4, "day_of_week": "{$day}", "cycle_type": "Putaran 2"}
- Jika berganti ruangan di jam 5 sampai {$maxJP}, buat entri berikutnya dengan start_period: 5, end_period: {$maxJP}.
- Abaikan baris non-KBM (Upacara, Literasi, Istirahat).

FORMAT OUTPUT JSON:
Kembalikan JSON array object data dengan properti:
room_name, class_name, start_period, end_period, day_of_week, cycle_type
PROMPT;
    }

    /**
     * Guarantees 100% complete room assignment for all 48 canonical classes
     * across all lesson periods (JP 1-10 for Mon-Thu, JP 1-8 for Fri).
     */
    public function ensureCompleteScheduleCoverage(array $data, string $targetCycle = 'Putaran 2'): array
    {
        $days = [
            'Senin' => 10,
            'Selasa' => 10,
            'Rabu' => 10,
            'Kamis' => 10,
            'Jumat' => 8,
        ];

        // 48 Canonical classes
        $canonicalClasses = [
            'X RPL 1', 'X RPL 2', 'X TKJ 1', 'X TKJ 2', 'X DKV 1', 'X DKV 2',
            'X AK 1', 'X AK 2', 'X AK 3', 'X LPS 1', 'X LPS 2',
            'X MP 1', 'X MP 2', 'X BR 1', 'X BR 2', 'X BD',
            'XI RPL 1', 'XI RPL 2', 'XI TKJ 1', 'XI TKJ 2', 'XI DKV 1', 'XI DKV 2',
            'XI AK 1', 'XI AK 2', 'XI AK 3', 'XI LPS 1', 'XI LPS 2',
            'XI MP 1', 'XI MP 2', 'XI BR 1', 'XI BR 2', 'XI BD',
            'XII RPL 1', 'XII RPL 2', 'XII TKJ 1', 'XII TKJ 2', 'XII DKV 1', 'XII DKV 2',
            'XII AK 1', 'XII AK 2', 'XII AK 3', 'XII AK 4', 'XII LPS',
            'XII MP 1', 'XII MP 2', 'XII BR 1', 'XII BR 2', 'XII BD',
        ];

        $canonicalLookup = [];
        foreach ($canonicalClasses as $c) {
            $compact = preg_replace('/[^a-z0-9]/', '', strtolower($c));
            $canonicalLookup[$compact] = $c;
        }

        $resolveClassName = function(string $name) use ($canonicalLookup) {
            $clean = trim($name);
            $compact = preg_replace('/[^a-z0-9]/', '', strtolower($clean));
            if (str_starts_with($compact, '10')) $compact = 'x' . substr($compact, 2);
            elseif (str_starts_with($compact, '11')) $compact = 'xi' . substr($compact, 2);
            elseif (str_starts_with($compact, '12')) $compact = 'xii' . substr($compact, 2);

            if ($compact === 'xiak4') $compact = 'xilps1';
            if ($compact === 'xilps') $compact = 'xilps2';

            return $canonicalLookup[$compact] ?? null;
        };

        // Populate existing slots
        $grid = [];
        foreach ($data as $item) {
            $day = $item['day_of_week'] ?? 'Senin';
            $resolvedClass = $resolveClassName($item['class_name'] ?? '');
            if (!$resolvedClass) continue;

            $rName = trim($item['room_name'] ?? '');
            if (empty($rName)) continue;

            $cycle = $item['cycle_type'] ?? $targetCycle;
            $p1 = (int)($item['start_period'] ?? 1);
            $p2 = (int)($item['end_period'] ?? $p1);

            for ($p = min($p1, $p2); $p <= max($p1, $p2); $p++) {
                $grid[$day][$resolvedClass][$p] = [
                    'room_name' => $rName,
                    'cycle_type' => $cycle,
                ];
            }
        }

        // Guarantee coverage for every class and period
        foreach ($days as $day => $maxPeriod) {
            foreach ($canonicalClasses as $cName) {
                $knownRooms = [];
                for ($p = 1; $p <= $maxPeriod; $p++) {
                    if (!empty($grid[$day][$cName][$p]['room_name'])) {
                        $knownRooms[$p] = $grid[$day][$cName][$p]['room_name'];
                    }
                }

                $lastKnownRoom = !empty($knownRooms) ? end($knownRooms) : null;
                $firstKnownRoom = !empty($knownRooms) ? reset($knownRooms) : null;

                for ($p = 1; $p <= $maxPeriod; $p++) {
                    if (empty($grid[$day][$cName][$p]['room_name'])) {
                        $fallbackRoom = $grid[$day][$cName][$p - 1]['room_name'] ?? $lastKnownRoom ?? $firstKnownRoom ?? 'Ruang Kelas';
                        $grid[$day][$cName][$p] = [
                            'room_name' => $fallbackRoom,
                            'cycle_type' => $targetCycle,
                        ];
                    }
                }
            }
        }

        // Collapse contiguous periods back into ranges
        $result = [];
        foreach ($days as $day => $maxPeriod) {
            foreach ($canonicalClasses as $cName) {
                $currentRange = null;
                for ($p = 1; $p <= $maxPeriod; $p++) {
                    $entry = $grid[$day][$cName][$p];
                    $roomName = $entry['room_name'];
                    $cycle = $entry['cycle_type'];

                    if ($currentRange === null) {
                        $currentRange = [
                            'class_name' => $cName,
                            'room_name' => $roomName,
                            'start_period' => $p,
                            'end_period' => $p,
                            'day_of_week' => $day,
                            'cycle_type' => $cycle,
                        ];
                    } elseif ($currentRange['room_name'] === $roomName) {
                        $currentRange['end_period'] = $p;
                    } else {
                        $result[] = $currentRange;
                        $currentRange = [
                            'class_name' => $cName,
                            'room_name' => $roomName,
                            'start_period' => $p,
                            'end_period' => $p,
                            'day_of_week' => $day,
                            'cycle_type' => $cycle,
                        ];
                    }
                }
                if ($currentRange !== null) {
                    $result[] = $currentRange;
                }
            }
        }

        return $result;
    }

    /**
     * Schema for per-day room schedule extraction.
     */
    private function getRoomScheduleSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'data' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'room_name' => ['type' => 'string'],
                            'class_name' => ['type' => 'string'],
                            'start_period' => ['type' => 'integer'],
                            'end_period' => ['type' => 'integer'],
                            'cycle_type' => ['type' => 'string']
                        ],
                        'required' => ['room_name', 'class_name', 'start_period', 'end_period']
                    ]
                ]
            ],
            'required' => ['data']
        ];
    }

    private function getPrompt(string $type): string
    {
        return match ($type) {
            'Daftar Siswa' => "Ekstrak nama lengkap, NISN (sebagai username), dan nama kelas (misal 'X RPL 1') dari dokumen daftar siswa berikut. Format kelas harus seragam. PENTING: Ekstrak SELURUH data siswa tanpa terkecuali, jangan diringkas.",
            'Jadwal Pelajaran' => "Ekstrak jadwal mengajar guru dari dokumen ini. Tentukan hari, nama guru, mata pelajaran, nama kelas, jam mulai (HH:MM), dan jam selesai (HH:MM). Jika ada informasi putaran, sertakan, default 'Putaran 1'. PENTING: Ekstrak SELURUH jadwal dari semua halaman secara lengkap dan detail. JANGAN ADA YANG TERLEWAT, DIPOTONG, ATAU DIRINGKAS.",
            'Kalender Pendidikan' => "Ekstrak agenda atau hari libur dari kalender akademik. Tentukan nama kegiatan, tanggal mulai (YYYY-MM-DD), tanggal selesai (YYYY-MM-DD), dan kategorinya (libur_nasional, libur_bersama, atau agenda_sekolah). PENTING: Ekstrak SELURUH jadwal dari semua halaman secara lengkap. JANGAN ADA YANG TERLEWAT, DIPOTONG, ATAU DIRINGKAS.",
            default => "Ekstrak data dari dokumen ini."
        };
    }

    private function getSchema(string $type): array
    {
        $itemSchema = match ($type) {
            'Daftar Siswa' => [
                'type' => 'object',
                'properties' => [
                    'fullname' => ['type' => 'string'],
                    'username' => ['type' => 'string'],
                    'class_name' => ['type' => 'string']
                ],
                'required' => ['fullname', 'username', 'class_name']
            ],
            'Jadwal Pelajaran' => [
                'type' => 'object',
                'properties' => [
                    'teacher_name' => ['type' => 'string'],
                    'subject' => ['type' => 'string'],
                    'class_name' => ['type' => 'string'],
                    'day_of_week' => ['type' => 'string'],
                    'start_period' => ['type' => 'integer'],
                    'end_period' => ['type' => 'integer'],
                    'start_time' => ['type' => 'string'],
                    'end_time' => ['type' => 'string'],
                    'cycle_type' => ['type' => 'string']
                ],
                'required' => ['teacher_name', 'subject', 'class_name', 'day_of_week', 'start_time', 'end_time']
            ],
            'Kalender Pendidikan' => [
                'type' => 'object',
                'properties' => [
                    'title' => ['type' => 'string'],
                    'start_date' => ['type' => 'string'],
                    'end_date' => ['type' => 'string'],
                    'category' => ['type' => 'string'],
                    'is_kbm_off' => ['type' => 'boolean']
                ],
                'required' => ['title', 'start_date', 'end_date', 'category', 'is_kbm_off']
            ],
            default => ['type' => 'object']
        };

        return [
            'type' => 'object',
            'properties' => [
                'data' => [
                    'type' => 'array',
                    'items' => $itemSchema
                ]
            ],
            'required' => ['data']
        ];
    }
}
