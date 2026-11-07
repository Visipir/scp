<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\IOFactory;
use Smalot\PdfParser\Parser as PdfParser;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

class StudentImportService
{
    /**
     * Threshold for switching to high-performance local parser to prevent hitting
     * Gemini's 8,192 Max Output Token limit (1,711 students = ~77,000 output tokens).
     */
    protected const LARGE_DATASET_THRESHOLD = 80;

    /**
     * Parse an uploaded Excel or PDF file into structured student rows.
     * Uses Smart Routing (Solusi 3):
     * - Small rosters (<= 80 rows): Processed via Gemini AI with local fallback
     * - Large rosters (> 80 rows, e.g. 1,711 students): Processed via High-Performance Local Engine (0 tokens, < 1s)
     */
    public function parse(UploadedFile $file, ?string $overrideAcademicYear = null): array
    {
        $extension = strtolower($file->getClientOriginalExtension());

        if (in_array($extension, ['xlsx', 'xls'])) {
            return $this->parseExcel($file, $overrideAcademicYear);
        }

        if ($extension === 'pdf') {
            return $this->parsePdf($file, $overrideAcademicYear);
        }

        throw new \InvalidArgumentException('Format file tidak didukung. Gunakan .xlsx, .xls, atau .pdf');
    }

    /**
     * Parse Excel spreadsheet for student data.
     * Supports single-sheet and multi-sheet workbooks.
     */
    protected function parseExcel(UploadedFile $file, ?string $overrideAcademicYear): array
    {
        $spreadsheet = IOFactory::load($file->getRealPath());
        $allSheets = $spreadsheet->getAllSheets();

        // 1. Gather all rows across all sheets
        $allStudents = [];
        $totalRawRows = 0;

        foreach ($allSheets as $sheet) {
            $rows = $sheet->toArray(null, true, true, true);
            $totalRawRows += count($rows);

            // Extract default class name from sheet title if applicable (e.g. "X RPL 1")
            $sheetClassHint = $this->extractClassName($sheet->getTitle());

            // If large dataset (> 80 rows, e.g. 1,711 students), parse directly locally
            if ($totalRawRows > self::LARGE_DATASET_THRESHOLD || count($allSheets) > 1) {
                $sheetStudents = $this->parseSheetLocally($rows, $overrideAcademicYear, $sheetClassHint);
                $allStudents = array_merge($allStudents, $sheetStudents);
            }
        }

        // If processed via multi-sheet or large dataset parser
        if (!empty($allStudents)) {
            return $allStudents;
        }

        // For small single-sheet rosters (<= 80 rows), attempt Gemini AI with fallback
        $activeSheet = $spreadsheet->getActiveSheet();
        $rows = $activeSheet->toArray(null, true, true, true);

        $lines = [];
        foreach ($rows as $row) {
            $filtered = array_filter($row, fn($cell) => $cell !== null && trim((string)$cell) !== '');
            if (!empty($filtered)) {
                $lines[] = implode(' | ', array_map(fn($val) => trim((string)$val), $row));
            }
        }
        $tableContent = implode("\n", $lines);

        try {
            return $this->parseTextWithGemini($tableContent, $overrideAcademicYear);
        } catch (\Throwable $e) {
            Log::warning('[StudentImport] Gemini AI failed for Excel, falling back to local parser: ' . $e->getMessage());
            return $this->parseSheetLocally($rows, $overrideAcademicYear);
        }
    }

    /**
     * Parse a single sheet's rows using local column mapping & regex.
     */
    protected function parseSheetLocally(array $rows, ?string $overrideAcademicYear, ?string $fallbackClassName = null): array
    {
        if (empty($rows)) return [];

        // 1. Scan header rows
        $headerText = '';
        foreach (array_slice($rows, 0, 12) as $row) {
            $headerText .= ' ' . implode(' ', array_filter(array_map('strval', $row)));
        }
        $headerUpper = strtoupper($headerText);

        // 2. Extract academic year & class name
        $academicYear = $overrideAcademicYear ?: $this->extractAcademicYear($headerText);
        $className = $this->extractClassName($headerText) ?: $fallbackClassName;

        // 3. Find data start row and map columns
        $dataStartRow = null;
        $colMap = [];
        foreach ($rows as $rowIdx => $row) {
            $rowText = strtoupper(implode(' ', array_filter(array_map('strval', $row))));
            if (preg_match('/\b(N[IO]S|NISN|NIPD)\b/i', $rowText) && preg_match('/\b(NAMA|PESERTA\s*DIDIK|SISWA)\b/i', $rowText)) {
                foreach ($row as $colKey => $cellVal) {
                    $val = strtoupper(trim((string) $cellVal));
                    if (preg_match('/^(NO|NO\.?|NO\.?\s*URUT|NOMOR|NOMOR\s*URUT|NO\.?\s*PRESENSI)$/i', $val) && !isset($colMap['no'])) {
                        $colMap['no'] = $colKey;
                    } elseif (preg_match('/^(NIS|NISN|NO\.?\s*INDUK|NOMOR\s*INDUK|NIPD)$/i', $val) && !isset($colMap['nis'])) {
                        $colMap['nis'] = $colKey;
                    } elseif (preg_match('/^(NAMA|NAMA\s*LENGKAP|NAMA\s*PESERTA\s*DIDIK|NAMA\s*SISWA)$/i', $val) && !isset($colMap['nama'])) {
                        $colMap['nama'] = $colKey;
                    } elseif (preg_match('/(L\s*[\/\-]\s*P|JK|J\.K|JENIS\s*KELAMIN|GENDER|SEX|LAKI|PEREMPUAN)/i', $val) && !isset($colMap['gender'])) {
                        $colMap['gender'] = $colKey;
                    } elseif (preg_match('/(KELAS|ROMBEL|ROMBONGAN|KLS)/i', $val) && !isset($colMap['kelas'])) {
                        $colMap['kelas'] = $colKey;
                    }
                }
                $dataStartRow = $rowIdx + 1;
                break;
            }
        }

        // If no explicit table header found, attempt automatic heuristic detection
        if (!$dataStartRow || empty($colMap['nis'] ?? null) || empty($colMap['nama'] ?? null)) {
            foreach ($rows as $rowIdx => $row) {
                $nisCol = null;
                $namaCol = null;
                foreach ($row as $colKey => $val) {
                    $clean = trim((string) $val);
                    if (preg_match('/^\d{4,10}$/', $clean) && !$nisCol) $nisCol = $colKey;
                    elseif (preg_match('/^[A-Za-z\s\.\,\'\-]{4,}$/', $clean) && strlen($clean) > 5 && !$namaCol) $namaCol = $colKey;
                }
                if ($nisCol && $namaCol) {
                    $colMap['nis'] = $nisCol;
                    $colMap['nama'] = $namaCol;
                    $dataStartRow = $rowIdx;
                    break;
                }
            }
        }

        if (!$dataStartRow || empty($colMap['nis'] ?? null) || empty($colMap['nama'] ?? null)) {
            return [];
        }

        // Dynamic auto-detection: If kelas column was not found in header, scan student rows
        if (!isset($colMap['kelas'])) {
            foreach (array_slice($rows, $dataStartRow - 1, 30) as $sampleRow) {
                $nisTest = trim((string)($sampleRow[$colMap['nis'] ?? ''] ?? ''));
                if (!preg_match('/^\d{3,}$/', $nisTest)) continue;

                foreach ($sampleRow as $colKey => $cellVal) {
                    if ($colKey !== ($colMap['nis'] ?? '') && $colKey !== ($colMap['nama'] ?? '') && $colKey !== ($colMap['no'] ?? '') && $colKey !== ($colMap['gender'] ?? '')) {
                        $trimmed = trim((string) $cellVal);
                        if (preg_match('/^(?:XII|XI|X)[\s\.\-]+[A-Z]{2,6}(?:[\s\.\-]*\d+)?$/i', $trimmed)) {
                            $colMap['kelas'] = $colKey;
                            break 2;
                        }
                    }
                }
            }
        }

        // Dynamic auto-detection: If gender column was not found in header, scan student rows
        if (!isset($colMap['gender'])) {
            foreach (array_slice($rows, $dataStartRow - 1, 30) as $sampleRow) {
                $nisTest = trim((string)($sampleRow[$colMap['nis'] ?? ''] ?? ''));
                if (!preg_match('/^\d{3,}$/', $nisTest)) continue;

                foreach ($sampleRow as $colKey => $cellVal) {
                    if ($colKey !== ($colMap['nis'] ?? '') && $colKey !== ($colMap['nama'] ?? '') && $colKey !== ($colMap['no'] ?? '') && $colKey !== ($colMap['kelas'] ?? '')) {
                        $trimmed = strtoupper(trim((string) $cellVal));
                        if (in_array($trimmed, ['L', 'P', 'LAKI-LAKI', 'PEREMPUAN', 'PRIA', 'WANITA'])) {
                            $colMap['gender'] = $colKey;
                            break 2;
                        }
                    }
                }
            }
        }

        // 4. Parse data rows
        $students = [];
        $dataRows = array_slice($rows, $dataStartRow - 1, null, true);
        $currentClassName = $className;

        foreach ($dataRows as $row) {
            $nisCandidate = trim((string) ($row[$colMap['nis'] ?? ''] ?? ''));
            $rowCombined = implode(' ', array_filter(array_map('strval', $row)));

            // Check if this row is an inter-class divider / subheader (e.g. "KELAS: X RPL 2")
            if (!preg_match('/^\d{3,}$/', $nisCandidate)) {
                if ($newClass = $this->extractClassName($rowCombined)) {
                    $currentClassName = $newClass;
                }
                continue;
            }

            $nis = $nisCandidate;
            $nama = trim((string) ($row[$colMap['nama']] ?? ''));

            if (empty($nis) || empty($nama) || !preg_match('/\d/', $nis)) continue;
            if (preg_match('/^(NIS|NISN|NO|JUMLAH|TOTAL)/i', $nis)) continue;

            $gender = null;
            if (isset($colMap['gender'])) {
                $gRaw = strtoupper(trim((string) ($row[$colMap['gender']] ?? '')));
                if ($gRaw === 'L' || str_starts_with($gRaw, 'LAKI') || $gRaw === 'PRIA' || $gRaw === 'M' || $gRaw === 'MALE') {
                    $gender = 'L';
                } elseif ($gRaw === 'P' || str_starts_with($gRaw, 'PEREMPUAN') || $gRaw === 'WANITA' || $gRaw === 'F' || $gRaw === 'FEMALE') {
                    $gender = 'P';
                }
            }

            $rowClassName = $currentClassName;
            if (isset($colMap['kelas'])) {
                $kcVal = trim((string) ($row[$colMap['kelas']] ?? ''));
                if (!empty($kcVal)) {
                    $extracted = $this->extractClassName($kcVal);
                    $rowClassName = $extracted ?: $kcVal;
                }
            }

            $attendanceNumber = null;
            if (isset($colMap['no'])) {
                $noVal = (int) ($row[$colMap['no']] ?? 0);
                if ($noVal > 0 && $noVal < 100) $attendanceNumber = $noVal;
            }

            $cleanNis = preg_replace('/[^0-9]/', '', $nis);
            if (strlen($cleanNis) < 3) continue;

            $students[] = [
                'nis' => $cleanNis,
                'fullname' => $this->normalizeName($nama),
                'gender' => $gender,
                'class_name' => $rowClassName ?: 'Tanpa Kelas',
                'attendance_number' => $attendanceNumber,
                'academic_year' => $academicYear,
            ];
        }

        return $students;
    }

    /**
     * Parse PDF for student data.
     * Smart Routing:
     * 1. Uses Smalot PdfParser to extract text layers.
     * 2. If PDF contains multiple pages or > 80 students (e.g. 1,711 students across 50 pages),
     *    uses fast local text extraction to prevent exceeding Gemini's 8,192 output token limit.
     * 3. For small or scanned PDFs, delegates to Gemini Multimodal API.
     */
    protected function parsePdf(UploadedFile $file, ?string $overrideAcademicYear): array
    {
        $realPath = $file->getRealPath();

        // 1. Attempt local text extraction via smalot/pdfparser
        try {
            $pdfParser = new PdfParser();
            $pdf = $pdfParser->parseFile($realPath);
            $pages = $pdf->getPages();
            $totalPages = count($pages);

            // Extract text from all pages
            $pdfText = $pdf->getText();

            // If PDF has readable text and contains large dataset (> 2 pages or > 80 students)
            if ($totalPages > 2 && strlen($pdfText) > 1000) {
                $localStudents = $this->parsePdfTextLocally($pdfText, $overrideAcademicYear, $pages);
                if (count($localStudents) > 0) {
                    Log::info('[StudentImport] Processed PDF via local parser. Total: ' . count($localStudents) . ' students.');
                    return $localStudents;
                }
            }
        } catch (\Throwable $e) {
            Log::warning('[StudentImport] Smalot PDF text extraction failed: ' . $e->getMessage());
        }

        // 2. For single-class PDF or scanned document, delegate to Gemini AI
        return $this->parsePdfWithGemini($file, $overrideAcademicYear);
    }

    /**
     * Local regex parser for structured PDF text across multiple pages.
     */
    protected function parsePdfTextLocally(string $fullText, ?string $overrideAcademicYear, array $pages = []): array
    {
        $academicYear = $overrideAcademicYear ?: $this->extractAcademicYear($fullText);
        $students = [];

        // Process page by page if available
        $pageTexts = !empty($pages) ? array_map(fn($p) => $p->getText(), $pages) : explode("\f", $fullText);

        foreach ($pageTexts as $pageText) {
            $pageClass = $this->extractClassName($pageText);
            $lines = explode("\n", $pageText);

            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line)) continue;

                // Match typical roster row pattern:
                // e.g.: "1  0061234567  AHMAD FAUZAN  L" or "1. 12345 RIZKY L X RPL 1"
                if (preg_match('/^(?:(\d{1,3})\s+)?(\d{4,12})\s+([A-Za-z\s\.\,\'\-]{3,40})\s+(L|P)(?:\s+([X|I|V]{1,4}\s+[A-Z]{2,5}\s*\d*))?/i', $line, $m)) {
                    $attNo = !empty($m[1]) ? (int) $m[1] : null;
                    $nis = $m[2];
                    $name = trim($m[3]);
                    $gender = strtoupper($m[4]);
                    $className = !empty($m[5]) ? strtoupper(trim($m[5])) : $pageClass;

                    $students[] = [
                        'nis' => $nis,
                        'fullname' => $this->normalizeName($name),
                        'gender' => $gender,
                        'class_name' => $className ?: 'Tanpa Kelas',
                        'attendance_number' => $attNo,
                        'academic_year' => $academicYear,
                    ];
                }
            }
        }

        return $students;
    }

    /**
     * Parse PDF via Gemini Multimodal AI (ideal for <= 80 students or scanned PDFs).
     */
    protected function parsePdfWithGemini(UploadedFile $file, ?string $overrideAcademicYear): array
    {
        $config = \App\Models\SystemConfiguration::first();
        $apiKey = $config?->effective_gemini_api_key;
        $model = $config?->effective_gemini_schedule_model ?? 'gemini-1.5-flash';

        if (!$apiKey) {
            throw new \RuntimeException('Gemini API key belum dikonfigurasi. Silakan atur di Pengaturan Sistem.');
        }

        $base64Data = base64_encode(file_get_contents($file->getRealPath()));

        $academicYearInstruction = $overrideAcademicYear
            ? "Tahun ajaran yang digunakan adalah: {$overrideAcademicYear}."
            : "Deteksi tahun ajaran dari header dokumen (biasanya ada teks 'TAHUN PELAJARAN 20XX - 20XX' atau 'TAHUN PELAJARAN 20XX/20XX'). Format sebagai 20XX/20XX.";

        $prompt = <<<PROMPT
Kamu adalah parser dokumen sekolah Indonesia. Analisis dokumen PDF berikut yang merupakan DAFTAR HADIR SISWA atau daftar siswa resmi sekolah.

INSTRUKSI VALIDASI:
1. PERTAMA, periksa apakah dokumen ini benar-benar daftar siswa/daftar hadir. Cari kata kunci seperti "DAFTAR HADIR", "DAFTAR SISWA", "NIS", "NISN", "NAMA PESERTA DIDIK", "KELAS".
2. Jika dokumen BUKAN daftar siswa, kembalikan data kosong: {"valid": false, "error": "File tidak valid. Pastikan Anda mengunggah format Daftar Hadir Siswa resmi.", "data": []}

INSTRUKSI EKSTRAKSI:
{$academicYearInstruction}

Untuk setiap siswa, ekstrak:
- nis: Nomor Induk Siswa (biasanya kolom NIS/NISN), hanya angka
- fullname: Nama lengkap siswa (bersihkan dari karakter aneh, kapitalisasi Title Case)
- gender: L (Laki-laki) atau P (Perempuan), null jika tidak ada
- class_name: Nama kelas lengkap (contoh: "X RPL 1", "XI TKJ 2", "XII DKV 1")
- attendance_number: Nomor urut presensi (kolom NO), null jika tidak ada
- academic_year: Tahun ajaran format 20XX/20XX
PROMPT;

        return $this->callGeminiApi(
            $prompt,
            $model,
            $apiKey,
            $overrideAcademicYear,
            ['mimeType' => 'application/pdf', 'data' => $base64Data]
        );
    }

    /**
     * Parse raw table text using Gemini AI.
     */
    protected function parseTextWithGemini(string $tableContent, ?string $overrideAcademicYear): array
    {
        $config = \App\Models\SystemConfiguration::first();
        $apiKey = $config?->effective_gemini_api_key;
        $model = $config?->effective_gemini_schedule_model ?? 'gemini-1.5-flash';

        if (!$apiKey) {
            throw new \RuntimeException('Gemini API key belum dikonfigurasi. Silakan atur di Pengaturan Sistem.');
        }

        $academicYearInstruction = $overrideAcademicYear
            ? "Tahun ajaran yang digunakan adalah: {$overrideAcademicYear}."
            : "Deteksi tahun ajaran dari header dokumen (biasanya ada teks 'TAHUN PELAJARAN 20XX - 20XX' atau 'TAHUN PELAJARAN 20XX/20XX'). Format sebagai 20XX/20XX.";

        $prompt = <<<PROMPT
Kamu adalah parser dokumen sekolah Indonesia. Analisis teks tabel berikut yang diekstrak dari spreadsheet Excel DAFTAR HADIR SISWA atau daftar siswa resmi sekolah.

DATA TABEL EXCEL:
---
{$tableContent}
---

INSTRUKSI VALIDASI:
1. PERTAMA, periksa apakah isi tabel ini benar-benar daftar siswa/daftar hadir sekolah. Cari kata kunci seperti "DAFTAR HADIR", "DAFTAR SISWA", "PESERTA DIDIK", "NIS", "NISN", "NAMA", "KELAS".
2. Jika dokumen BUKAN daftar siswa, kembalikan: {"valid": false, "error": "File tidak valid. Pastikan Anda mengunggah format Daftar Hadir Siswa resmi.", "data": []}

INSTRUKSI EKSTRAKSI:
{$academicYearInstruction}

Untuk setiap siswa dalam tabel, ekstrak:
- nis: Nomor Induk Siswa (biasanya kolom NIS/NISN), hanya angka
- fullname: Nama lengkap siswa (bersihkan dari nomor urut, kapitalisasi Title Case)
- gender: L (Laki-laki) atau P (Perempuan), null jika tidak tertera
- class_name: Nama kelas lengkap (contoh: "X RPL 1", "XI TKJ 2", "XII DKV 1")
- attendance_number: Nomor urut presensi (angka dari kolom NO/No. Urut), null jika tidak ada
- academic_year: Tahun ajaran format 20XX/20XX
PROMPT;

        return $this->callGeminiApi($prompt, $model, $apiKey, $overrideAcademicYear);
    }

    /**
     * Send prompt and optional inlineData to Gemini API with structured JSON output.
     */
    protected function callGeminiApi(string $prompt, string $model, string $apiKey, ?string $overrideAcademicYear, ?array $inlineData = null): array
    {
        $schema = [
            'type' => 'object',
            'properties' => [
                'valid' => ['type' => 'boolean'],
                'error' => ['type' => 'string'],
                'data' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'nis' => ['type' => 'string'],
                            'fullname' => ['type' => 'string'],
                            'gender' => ['type' => 'string'],
                            'class_name' => ['type' => 'string'],
                            'attendance_number' => ['type' => 'integer'],
                            'academic_year' => ['type' => 'string'],
                        ],
                        'required' => ['nis', 'fullname', 'class_name'],
                    ],
                ],
            ],
            'required' => ['valid', 'data'],
        ];

        $parts = [];
        if ($inlineData) {
            $parts[] = ['inlineData' => $inlineData];
        }
        $parts[] = ['text' => $prompt];

        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";

        $response = \Illuminate\Support\Facades\Http::timeout(120)->post($url, [
            'contents' => [['parts' => $parts]],
            'generationConfig' => [
                'responseMimeType' => 'application/json',
                'responseSchema' => $schema,
                'temperature' => 0.1,
            ],
        ]);

        if (!$response->successful()) {
            Log::error('Student Import AI Error', ['body' => $response->body()]);
            throw new \RuntimeException('Gagal memproses dokumen dengan AI: ' . $response->body());
        }

        $resultText = $response->json('candidates.0.content.parts.0.text');
        if (!$resultText) {
            throw new \RuntimeException('Respons AI kosong.');
        }

        $decoded = json_decode($resultText, true);

        if (!($decoded['valid'] ?? true) && !empty($decoded['error'])) {
            throw new \RuntimeException($decoded['error']);
        }

        $data = $decoded['data'] ?? [];

        if (empty($data)) {
            throw new \RuntimeException('AI tidak menemukan data siswa yang valid di dokumen ini.');
        }

        if ($overrideAcademicYear) {
            foreach ($data as &$row) {
                $row['academic_year'] = $overrideAcademicYear;
            }
        }

        return $data;
    }

    /**
     * Check if document header contains student roster keywords.
     */
    protected function isStudentRosterDocument(string $headerUpper): bool
    {
        $keywords = ['DAFTAR HADIR', 'DAFTAR SISWA', 'PESERTA DIDIK', 'NIS', 'NISN'];
        $found = 0;
        foreach ($keywords as $kw) {
            if (str_contains($headerUpper, $kw)) $found++;
        }
        return $found >= 2;
    }

    /**
     * Extract academic year from header text via regex.
     */
    protected function extractAcademicYear(string $text): string
    {
        if (preg_match('/(?:TAHUN\s*(?:PELAJARAN|AJARAN)?|TP\.?)\s*(\d{4})\s*[-\/]\s*(\d{4})/i', $text, $m)) {
            return $m[1] . '/' . $m[2];
        }
        if (preg_match('/(\d{4})\s*[-\/]\s*(\d{4})/', $text, $m)) {
            return $m[1] . '/' . $m[2];
        }
        return date('Y') . '/' . (date('Y') + 1);
    }

    /**
     * Extract class name from header text or sheet title.
     */
    protected function extractClassName(string $text): ?string
    {
        if (preg_match('/(?:KELAS|ROMBEL)\s*[:=]?\s*((?:XII|XI|X)\s+[A-Z]{2,6}(?:\s*\d+)?)\b/i', $text, $m)) {
            return strtoupper(trim(preg_replace('/\s+/', ' ', $m[1])));
        }
        if (preg_match('/\b((?:XII|XI|X)\s+[A-Z]{2,6}(?:\s*\d+)?)\b/i', $text, $m)) {
            return strtoupper(trim(preg_replace('/\s+/', ' ', $m[1])));
        }
        return null;
    }

    /**
     * Normalize student name to Title Case with cleaned whitespace.
     */
    protected function normalizeName(string $name): string
    {
        $name = preg_replace('/\s+/', ' ', trim($name));
        $name = preg_replace('/^\d+\.\s*/', '', $name);
        return mb_convert_case(mb_strtolower($name), MB_CASE_TITLE, 'UTF-8');
    }

    /**
     * Parse class_name into grade_level and major.
     */
    public static function parseClassName(string $className): array
    {
        $className = strtoupper(trim($className));

        $gradeLevel = null;
        $major = null;

        if (preg_match('/^(XII|XI|X)\s+([A-Z]{2,5})\s*\d*/', $className, $m)) {
            $gradeLevel = $m[1];
            $major = $m[2];
        } elseif (preg_match('/^(XII|XI|X)\b/', $className, $m)) {
            $gradeLevel = $m[1];
        }

        return [
            'grade_level' => $gradeLevel,
            'major' => $major,
        ];
    }

    /**
     * Provision or update student accounts from parsed rows while skipping protected accounts (F1).
     */
    public function provisionStudents(array $students): array
    {
        $allNis = array_unique(array_filter(array_column($students, 'nis')));
        $existingUsers = \App\Models\User::whereIn('username', $allNis)->get()->keyBy('username');
        $allClasses = \App\Models\SchoolClass::all()->groupBy(fn ($c) => $c->class_name . '|' . $c->academic_year);

        $stats = [
            'created'           => 0,
            'updated'           => 0,
            'classes_created'   => 0,
            'skipped_protected' => [],
        ];
        $exportedCredentials = [];
        $nowIso = now('Asia/Jakarta')->toIso8601String();

        \Illuminate\Support\Facades\DB::beginTransaction();
        try {
            $classCache = [];

            foreach ($students as $row) {
                $nis = $row['nis'] ?? '';
                $fullname = $row['fullname'] ?? '';
                $gender = $row['gender'] ?? null;
                $className = $row['class_name'] ?? '';
                $attendanceNumber = $row['attendance_number'] ?? null;
                $academicYear = $row['academic_year'] ?? date('Y') . '/' . (date('Y') + 1);

                if (empty($nis) || empty($fullname) || empty($className)) {
                    continue;
                }

                $classKey = $className . '|' . $academicYear;
                if (!isset($classCache[$classKey])) {
                    $class = $allClasses->get($classKey)?->first();

                    if (!$class) {
                        $parsed = self::parseClassName($className);
                        $class = \App\Models\SchoolClass::create([
                            'class_name'    => $className,
                            'academic_year' => $academicYear,
                            'grade_level'   => $parsed['grade_level'],
                            'major'         => $parsed['major'],
                        ]);
                        $stats['classes_created']++;
                    }

                    $classCache[$classKey] = $class;
                }

                $class = $classCache[$classKey];
                $existing = $existingUsers->get($nis);

                if ($existing) {
                    if ($existing->isProtectedAccount()) {
                        // Protected student account (e.g. 17766): never overwrite password, fullname, username, role_id, or must_change_password
                        $stats['skipped_protected'][] = $existing->username;
                        continue;
                    }

                    $userData = [
                        'fullname'          => $fullname,
                        'role_id'           => 4,
                        'class_id'          => $class->id,
                        'attendance_number' => $attendanceNumber,
                    ];

                    if ($gender) {
                        $userData['gender'] = $gender;
                    }

                    $existing->update($userData);
                    $stats['updated']++;
                } else {
                    $tempPassword = \Illuminate\Support\Str::random(12);
                    $userData = [
                        'username'              => $nis,
                        'fullname'              => $fullname,
                        'role_id'               => 4,
                        'class_id'              => $class->id,
                        'attendance_number'     => $attendanceNumber,
                        'must_change_password'  => true,
                        'password'              => \Illuminate\Support\Facades\Hash::make($tempPassword, ['rounds' => 4]),
                    ];
                    if ($gender) {
                        $userData['gender'] = $gender;
                    }

                    $newUser = \App\Models\User::create($userData);
                    $existingUsers->put($nis, $newUser);
                    $stats['created']++;

                    $exportedCredentials[] = [
                        'id'                 => $newUser->id,
                        'username'           => $newUser->username,
                        'fullname'           => $newUser->fullname,
                        'nip'                => '',
                        'role_id'            => 4,
                        'temporary_password' => $tempPassword,
                        'reset_at'           => $nowIso,
                    ];
                }
            }

            \Illuminate\Support\Facades\DB::commit();
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            throw $e;
        }

        $csvPath = null;
        if (!empty($exportedCredentials)) {
            $csvPath = app(UserService::class)->writeCredentialsCsv($exportedCredentials, 'student_import');
        }

        return [
            'stats'                => $stats,
            'exported_credentials' => $exportedCredentials,
            'csv_path'             => $csvPath,
        ];
    }
}
