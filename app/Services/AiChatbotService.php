<?php

namespace App\Services;

use App\Models\SystemConfiguration;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class AiChatbotService
{
    /**
     * Send user message to Google Gemini Chatbot (optimized for High Daily Quota / Low Latency).
     *
     * @param string $message User prompt
     * @param array $history Previous message history [['role' => 'user'|'model', 'parts' => [['text' => '...']]]]
     * @return string AI assistant reply
     * @throws RuntimeException
     */
    public function sendMessage(string $message, array $history = []): string
    {
        $config = SystemConfiguration::first();
        $apiKey = $config?->effective_gemini_api_key;

        if (empty($apiKey)) {
            throw new RuntimeException('Google Gemini API Key belum dikonfigurasi. Hubungi Waka Kurikulum untuk mengatur API Key.');
        }

        // Default to gemini-3.5-flash-lite for high 500 RPD quota and fast response
        $model = $config?->effective_gemini_chat_model ?: 'gemini-3.5-flash-lite';

        $endpoint = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent";

        $systemInstruction = $this->buildSystemPrompt();

        $contents = [];

        // Append historical turns if provided
        foreach ($history as $turn) {
            if (isset($turn['role'], $turn['content'])) {
                $contents[] = [
                    'role' => $turn['role'] === 'user' ? 'user' : 'model',
                    'parts' => [
                        ['text' => $turn['content']],
                    ],
                ];
            }
        }

        // Current message
        $contents[] = [
            'role' => 'user',
            'parts' => [
                ['text' => $message],
            ],
        ];

        $payload = [
            'contents' => $contents,
            'systemInstruction' => [
                'parts' => [
                    ['text' => $systemInstruction],
                ],
            ],
            'generationConfig' => [
                'temperature' => 0.7,
                'maxOutputTokens' => 1024,
            ],
        ];

        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'Content-Type'   => 'application/json',
                    'x-goog-api-key' => $apiKey,
                ])
                ->post($endpoint, $payload);
        } catch (\Throwable $e) {
            Log::error('AI Chatbot request failed due to connection error or timeout.');
            throw new RuntimeException('Koneksi ke layanan AI Assistant gagal atau waktu tunggu habis.');
        }

        if (!$response->successful()) {
            $errorData = $response->json('error');
            $errorMessage = $errorData['message'] ?? 'Status HTTP ' . $response->status();
            Log::error("AI Chatbot Request Failed with HTTP status: {$response->status()}");

            if ($response->status() === 429) {
                throw new RuntimeException('Batas kuota harian chat tercapai (Rate Limit). Silakan coba lagi sebentar lagi.');
            }

            throw new RuntimeException("Gagal berkomunikasi dengan AI: {$errorMessage}");
        }

        $candidates = $response->json('candidates');
        if (empty($candidates) || empty($candidates[0]['content']['parts'][0]['text'])) {
            throw new RuntimeException('AI Assistant tidak memberikan balasan.');
        }

        return trim($candidates[0]['content']['parts'][0]['text']);
    }

    /**
     * Teacher guidance system prompt.
     */
    private function buildSystemPrompt(): string
    {
        return <<<PROMPT
Anda adalah 'SCP Assistant', asisten cerdas resmi pada aplikasi School Class Progress (SCP) untuk sekolah SMK/SMA.
Tugas utama Anda adalah membantu Guru Pengajar, Waka Kurikulum, dan Tim Kesiswaan/BK dalam pengoperasian KBM harian.

PANDUAN UTAMA SISTEM SCP YANG HARUS ANDA PAHAMI DAN JELASKAN KE PENGGUNA:
1. Pengisian Jurnal Harian KBM (/journals/create):
   - Guru wajib mengisi jurnal setiap selesai mengajar.
   - Data jurnal: materi pembelajaran, catatan kegiatan, dan unggah foto dokumentasi KBM (maks 2MB).
   - Presensi siswa terisi otomatis secara default "Hadir" dan otomatis sinkron jika ada siswa yang memiliki surat izin sah dari BK.

2. Aturan Peringatan Keterlambatan (Late Alert 30 Menit):
   - Terdapat toleransi 30 menit setelah jam mengajar berakhir untuk mengisi jurnal.
   - Jika melewati 30 menit sejak jam KBM usai dan jurnal belum diisi, sistem otomatis menampilkan peringatan darurat kuning/merah di Dashboard Guru.

3. Siklus Putaran & Jadwal Blok Jam (Putaran 1 & Putaran 2):
   - Sekolah menerapkan siklus 2-mingguan: 'Putaran 1' dan 'Putaran 2'.
   - Aturan Jam KBM Putaran 2:
     * SENIN (10 JP): 07.00–08.00 Upacara Bendera; Jam 1–3 (08.00–09.45); Istirahat I (09.45–10.00); Jam 4–6 (10.00–11.45); Ishoma (11.45–12.45); Jam 7 (12.45–13.25); Jam 8–10 (13.25–15.10).
     * SELASA (10 JP): 07.00–07.30 Literasi; Jam 1–3 (07.30–09.30); Istirahat I (09.30–09.45); Jam 4–6 (09.45–11.45); Ishoma (11.45–12.45); Jam 7–10 (12.45–15.10).
     * RABU & KAMIS (10 JP): Jam 1–3 (07.15–09.15); Istirahat I (09.15–09.30); Jam 4–6 (09.30–11.30); Ishoma (11.30–12.30); Jam 7–10 (12.30–15.10).
     * JUMAT (8 JP): Jam 1–3 (07.15–09.15); Istirahat I (09.15–09.30); Jam 4–5 (09.30–10.50); Jam 6 (10.50–11.25); Sholat Jumat (11.25–12.40); Jam 7–8 (12.40–13.50).

4. Hak Akses & Peran:
   - Waka Kurikulum (Role 1): Mengatur putaran, kunci jadwal, input AI key, dan manajemen kelas.
   - Guru (Role 2): Mengisi jurnal KBM, mengedit presensi kelas, dan melihat jadwal.
   - BK (Role 3): Verifikasi permohonan izin/sakit siswa dan monitoring presensi.
   - Siswa (Role 4): Mengajukan surat permohonan izin/sakit beserta lampiran.

GAYA KOMUNIKASI:
- Gunakan bahasa Indonesia yang sopan, ramah, ringkas, dan solutif.
- Berikan langkah-langkah praktis dan nama menu yang tepat di aplikasi SCP.
PROMPT;
    }
}
