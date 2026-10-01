<?php

namespace App\Services;

use App\Models\SystemConfiguration;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class GeminiScheduleParserService
{
    /**
     * Parse timetable PDF using Google Gemini Multimodal Document Understanding.
     *
     * @param UploadedFile|string $pdfFile Uploaded file object or path to PDF
     * @param string $targetCycle Target schedule cycle ('Putaran 1' or 'Putaran 2')
     * @return array List of parsed schedule entries
     * @throws RuntimeException
     */
    public function parsePdf(UploadedFile|string $pdfFile, string $targetCycle = 'Putaran 2', ?string $modelOverride = null): array
    {
        $config = SystemConfiguration::first();
        $apiKey = $config?->effective_gemini_api_key;

        if (empty($apiKey)) {
            throw new RuntimeException('Google Gemini API Key belum dikonfigurasi. Silakan masukkan API Key di Pengaturan Kurikulum (Waka).');
        }

        $model = $modelOverride ?: ($config?->effective_gemini_schedule_model ?: 'gemini-3.5-flash-lite');

        $pdfPath = is_string($pdfFile) ? $pdfFile : $pdfFile->getRealPath();
        
        $systemPrompt = $this->buildSystemPrompt($targetCycle);
        $endpoint = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";

        @ini_set('max_execution_time', 300);
        @set_time_limit(300);

        // Group pages into batches of 6 to avoid exceeding 15 RPM and 8192 token limit
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
                    $chunkText .= "--- HALAMAN {$pNum} ---\n" . $pText . "\n\n";
                }
            }

            if (trim($chunkText) === '') {
                continue;
            }

            $pagesLabel = implode(', ', $pageNumbers);

            $payload = [
                'contents' => [
                    [
                        'role' => 'user',
                        'parts' => [
                            ['text' => "Teks Dokumen (Halaman {$pagesLabel}):\n\n" . $chunkText],
                            ['text' => $systemPrompt],
                        ],
                    ],
                ],
                'generationConfig' => [
                    'response_mime_type' => 'application/json',
                    'temperature' => 0.1,
                    'maxOutputTokens' => 8192
                ],
            ];

            $maxRetries = 3;
            $retryCount = 0;
            $success = false;

            while ($retryCount < $maxRetries && !$success) {
                try {
                    $response = Http::timeout(180)
                        ->withHeaders(['Content-Type' => 'application/json'])
                        ->post($endpoint, $payload);

                    if ($response->successful()) {
                        $success = true;
                        $candidates = $response->json('candidates');
                        if (!empty($candidates) && !empty($candidates[0]['content']['parts'][0]['text'])) {
                            $jsonText = $candidates[0]['content']['parts'][0]['text'];
                            $cleanJson = preg_replace('/^```(?:json)?\s*/i', '', trim($jsonText));
                            $cleanJson = preg_replace('/\s*```$/', '', $cleanJson);
                            
                            $decoded = json_decode($cleanJson, true);
                            if (is_array($decoded)) {
                                if (isset($decoded['schedules']) && is_array($decoded['schedules'])) {
                                    $decoded = $decoded['schedules'];
                                }
                                $allData = array_merge($allData, $decoded);
                            }
                        }
                    } else {
                        $status = $response->status();
                        $lastError = $response->body();
                        
                        if (in_array($status, [429, 500, 503])) {
                            $retryCount++;
                            if ($retryCount < $maxRetries) {
                                sleep($status === 429 ? 6 * $retryCount : 2 * $retryCount);
                                continue;
                            }
                        }

                        $errorData = $response->json('error');
                        $errorMessage = $errorData['message'] ?? 'Status HTTP ' . $status;
                        Log::error("Gemini API Request Failed on pages {$pagesLabel} [{$status}]: {$errorMessage}");

                        if ($status === 503) {
                            throw new RuntimeException("Server AI Google sedang sibuk saat memproses halaman {$pagesLabel}. Silakan coba lagi.");
                        } elseif ($status === 429) {
                            throw new RuntimeException("Batas permintaan API (RPM) tercapai pada halaman {$pagesLabel}. Silakan coba lagi sebentar lagi.");
                        } else {
                            throw new RuntimeException("Google Gemini API Error pada halaman {$pagesLabel}: {$errorMessage}");
                        }
                    }
                } catch (\Illuminate\Http\Client\ConnectionException $e) {
                    $retryCount++;
                    if ($retryCount < $maxRetries) {
                        sleep(3);
                        continue;
                    }
                    Log::error('Gemini API Connection Error: ' . $e->getMessage());
                    throw new RuntimeException("Koneksi ke AI Google terputus saat memproses halaman {$pagesLabel}. Silakan coba lagi.");
                }
            }

            // Pacing delay between chunks to avoid bursting RPM
            if ($chunkIdx < count($chunks) - 1) {
                sleep(1);
            }
        }

        if (empty($allData)) {
            throw new RuntimeException('Google Gemini tidak menghasilkan respons jadwal yang dapat diproses dari semua halaman.');
        }

        return $allData;
    }

    /**
     * Construct strict prompt with timetable rules for requested cycle.
     */
    private function buildSystemPrompt(string $targetCycle = 'Putaran 2'): string
    {
        if ($targetCycle === 'Putaran 1') {
            return <<<PROMPT
Act as an expert school timetable parser and schedule digitization system.
Extract all class schedules from the uploaded PDF timetable file strictly for 'Putaran 1' (Semester Ganjil TP 2026/2027).

CRITICAL MASTER SLOT TIME RULES FOR SIKLUS PUTARAN 1:
Standard duration is 35 minutes per Jam Pelajaran (JP).
SENIN:
- 07.00–08.00 : Upacara Bendera (Non-KBM)
- Jam 1  : 08:00:00 - 08:35:00 (35m)
- Jam 2  : 08:35:00 - 09:10:00 (35m)
- Jam 3  : 09:10:00 - 09:45:00 (35m)
- 09.45–10.00 : ISTIRAHAT I (15m)
- Jam 4  : 10:00:00 - 10:35:00 (35m)
- Jam 5  : 10:35:00 - 11:10:00 (35m)
- Jam 6  : 11:10:00 - 11:45:00 (35m)
- 11.45–12.45 : ISTIRAHAT II / Ishoma (60m)
- Jam 7  : 12:45:00 - 13:20:00 (35m)
- Jam 8  : 13:20:00 - 13:55:00 (35m)
- Jam 9  : 13:55:00 - 14:30:00 (35m)
- Jam 10 : 14:30:00 - 15:05:00 (35m)
- Jam 11 : 15:05:00 - 15:40:00 (35m)
- Jam 12 : 15:40:00 - 16:15:00 (35m)

SELASA s/d JUMAT:
Same standard 35-minute slots starting from Jam 1, with Istirahat I (09.45–10.00) and Istirahat II (11.45–12.45).

CRITICAL ROOM NAMING RULES:
- Ruang teori (theory room) memiliki nomor ruangan! Ekstrak nomor ruangnya secara spesifik (contoh: 'Ruang 1', 'Ruang 2', 'Ruang 10'). JANGAN PERNAH menghasilkan nama umum 'Teori' atau 'Ruang Teori' tanpa nomor. Jika tertulis 'R.1' atau 'Teori 1', normalkan menjadi 'Ruang 1'.
- Ruang Lab Komputer: 'LAB 14', 'LAB 16', 'LAB 18', 'LAB 19', 'LAB 22', dst.

OUTPUT FORMAT:
Return ONLY a valid JSON Array with NO markdown formatting, NO backticks, and NO prose.
Schema for each element:
{
  "class_name": "XII RPL 1",
  "day_of_week": "Senin",
  "subject": "Pemrograman Web",
  "teacher_code": "01",
  "teacher_name": "Drs. Budi Santoso",
  "room_name": "LAB 16",
  "period": 1,
  "start_time": "08:00:00",
  "end_time": "08:35:00",
  "cycle_type": "Putaran 1"
}
Every entry MUST have cycle_type = "Putaran 1" and accurate start_time/end_time in "HH:MM:SS" matching the slots above.
PROMPT;
        }

        return <<<PROMPT
Act as an expert school timetable parser and schedule digitization system.
Extract all class schedules from the uploaded PDF timetable file ('JADWAL KELAS PUT 2.pdf') strictly for 'Putaran 2' (Semester Ganjil TP 2026/2027).

CRITICAL MASTER SLOT TIME RULES FOR SIKLUS PUTARAN 2:

1. HARI SENIN (Putaran 2 - Total 10 Jam Pelajaran):
   - 07.00–08.00 : Upacara Bendera (Non-KBM)
   - Jam 1  : 08:00:00 - 08:35:00 (35 menit)
   - Jam 2  : 08:35:00 - 09:10:00 (35 menit)
   - Jam 3  : 09:10:00 - 09:45:00 (35 menit)
   - 09.45–10.00 : ISTIRAHAT I (15 menit)
   - Jam 4  : 10:00:00 - 10:35:00 (35 menit)
   - Jam 5  : 10:35:00 - 11:10:00 (35 menit)
   - Jam 6  : 11:10:00 - 11:45:00 (35 menit)
   - 11.45–12.45 : ISTIRAHAT II / Ishoma (60 menit)
   - Jam 7  : 12:45:00 - 13:25:00 (40 menit)
   - Jam 8  : 13:25:00 - 14:00:00 (35 menit)
   - Jam 9  : 14:00:00 - 14:35:00 (35 menit)
   - Jam 10 : 14:35:00 - 15:10:00 (35 menit)

2. HARI SELASA (Putaran 2 - Total 10 Jam Pelajaran):
   - 07.00–07.30 : Literasi (Non-KBM)
   - Jam 1  : 07:30:00 - 08:10:00 (40 menit)
   - Jam 2  : 08:10:00 - 08:50:00 (40 menit)
   - Jam 3  : 08:50:00 - 09:30:00 (40 menit)
   - 09.30–09.45 : ISTIRAHAT I (15 menit)
   - Jam 4  : 09:45:00 - 10:25:00 (40 menit)
   - Jam 5  : 10:25:00 - 11:05:00 (40 menit)
   - Jam 6  : 11:05:00 - 11:45:00 (40 menit)
   - 11.45–12.45 : ISTIRAHAT II / Ishoma (60 menit)
   - Jam 7  : 12:45:00 - 13:25:00 (40 menit)
   - Jam 8  : 13:25:00 - 14:00:00 (35 menit)
   - Jam 9  : 14:00:00 - 14:35:00 (35 menit)
   - Jam 10 : 14:35:00 - 15:10:00 (35 menit)

3. HARI RABU & KAMIS (Putaran 2 - Total 10 Jam Pelajaran):
   - Jam 1  : 07:15:00 - 07:55:00 (40 menit)
   - Jam 2  : 07:55:00 - 08:35:00 (40 menit)
   - Jam 3  : 08:35:00 - 09:15:00 (40 menit)
   - 09.15–09.30 : ISTIRAHAT I (15 menit)
   - Jam 4  : 09:30:00 - 10:10:00 (40 menit)
   - Jam 5  : 10:10:00 - 10:50:00 (40 menit)
   - Jam 6  : 10:50:00 - 11:30:00 (40 menit)
   - 11.30–12.30 : ISTIRAHAT II / Ishoma (60 menit)
   - Jam 7  : 12:30:00 - 13:10:00 (40 menit)
   - Jam 8  : 13:10:00 - 13:50:00 (40 menit)
   - Jam 9  : 13:50:00 - 14:30:00 (40 menit)
   - Jam 10 : 14:30:00 - 15:10:00 (40 menit)

4. HARI JUMAT (Putaran 2 - Total 8 Jam Pelajaran):
   - Jam 1  : 07:15:00 - 07:55:00 (40 menit)
   - Jam 2  : 07:55:00 - 08:35:00 (40 menit)
   - Jam 3  : 08:35:00 - 09:15:00 (40 menit)
   - 09.15–09.30 : ISTIRAHAT I (15 menit)
   - Jam 4  : 09:30:00 - 10:10:00 (40 menit)
   - Jam 5  : 10:10:00 - 10:50:00 (40 menit)
   - Jam 6  : 10:50:00 - 11:25:00 (35 menit)
   - 11.25–12.40 : ISTIRAHAT II / Sholat Jumat (75 menit)
   - Jam 7  : 12:40:00 - 13:15:00 (35 menit)
   - Jam 8  : 13:15:00 - 13:50:00 (35 menit)

CRITICAL ROOM NAMING RULES:
- Ruang teori (theory room) memiliki nomor ruangan! Ekstrak nomor ruangnya secara spesifik (contoh: 'Ruang 1', 'Ruang 2', 'Ruang 10'). JANGAN PERNAH menghasilkan nama umum 'Teori' atau 'Ruang Teori' tanpa nomor. Jika tertulis 'R.1' atau 'Teori 1', normalkan menjadi 'Ruang 1'.
- Ruang Lab Komputer: 'LAB 14', 'LAB 16', 'LAB 18', 'LAB 19', 'LAB 22', dst.

OUTPUT FORMAT:
Return ONLY a valid JSON Array with NO markdown formatting, NO backticks, and NO prose.
Schema for each element:
{
  "class_name": "XII RPL 1",
  "day_of_week": "Senin",
  "subject": "Pemrograman Web",
  "teacher_code": "01",
  "teacher_name": "Drs. Budi Santoso",
  "room_name": "LAB 16",
  "period": 1,
  "start_time": "08:00:00",
  "end_time": "08:35:00",
  "cycle_type": "Putaran 2"
}
If consecutive periods have the same class, subject, and teacher, you can either return individual period items or block items.
Every entry MUST have cycle_type = "Putaran 2" and accurate start_time/end_time in "HH:MM:SS" matching the slots above.
PROMPT;
    }
}
