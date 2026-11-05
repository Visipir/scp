<?php

namespace App\Console\Commands;

use App\Services\MailConfigService;
use App\Models\SystemConfiguration;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Throwable;

class MailDiagnoseCommand extends Command
{
    protected $signature = 'scp:mail-diagnose {to : Alamat email penerima untuk pengujian}';

    protected $description = 'Diagnosa konfigurasi SMTP, cek konektivitas soket TCP, dan kirim email tes secara sinkron';

    public function handle(MailConfigService $mailService): int
    {
        $to = $this->argument('to');

        $this->info("========================================");
        $this->info("     SCP SMTP DIAGNOSTICS & TEST        ");
        $this->info("========================================");

        // 1. Ambil & tampilkan konfigurasi aktif
        $config = SystemConfiguration::first();
        $mailService->applyRuntimeConfig(force: true);

        $host = Config::get('mail.mailers.smtp.host');
        $port = (int) Config::get('mail.mailers.smtp.port');
        $scheme = Config::get('mail.mailers.smtp.scheme') ?? 'smtp';
        $encryption = Config::get('mail.mailers.smtp.encryption') ?? 'none';
        $username = Config::get('mail.mailers.smtp.username') ?? '(tidak diatur)';
        $from = Config::get('mail.from.address') ?? '(tidak diatur)';
        $fromName = Config::get('mail.from.name') ?? 'SCP';
        $verifyPeer = Config::get('mail.mailers.smtp.verify_peer') !== false;
        $hasPassword = ! empty($config?->mail_password);

        $this->table(['Parameter', 'Nilai'], [
            ['Host', $host ?? '(kosong)'],
            ['Port', $port],
            ['Scheme', $scheme],
            ['Encryption', $encryption],
            ['Username', $username],
            ['Password Tersimpan', $hasPassword ? 'Ya (Terenkripsi)' : 'Tidak'],
            ['From Address', $from],
            ['From Name', $fromName],
            ['Verifikasi Sertifikat (SSL)', $verifyPeer ? 'Aktif (Strict)' : 'Diabaikan (Self-Signed Diizinkan)'],
            ['Target Penerima', $to],
        ]);

        if (empty($host)) {
            $this->error("Gagal: Host SMTP belum diisi pada Pengaturan Email atau .env.");
            return 1;
        }

        // 2. Cek konektivitas soket TCP (fsockopen dengan timeout 5 detik)
        $this->newLine();
        $this->info("Menguji koneksi soket TCP ke {$host}:{$port} (timeout 5s)...");

        $socketStart = microtime(true);
        $errno = 0;
        $errstr = '';
        $socket = @fsockopen($host, $port, $errno, $errstr, 5.0);
        $socketDuration = round((microtime(true) - $socketStart) * 1000, 2);

        if (! $socket) {
            $this->error("GAGAL: Tidak dapat terhubung ke {$host}:{$port} ({$errstr} [{$errno}]) setelah {$socketDuration}ms.");
            $this->newLine();
            $this->warn("Saran Pemecahan Masalah:");
            $this->line("1. Pastikan nama host ({$host}) dan port ({$port}) sudah benar.");
            $this->line("2. Periksa firewall server / security group: apakah outbound port {$port} diizinkan?");
            $this->line("3. Jika port 465 diblokir hosting/provider, coba gunakan port 587 dengan enkripsi TLS.");
            $this->line("4. Periksa apakah DNS server dapat me-resolve nama host {$host}.");
            return 1;
        }

        fclose($socket);
        $this->info("SUKSES: Koneksi soket TCP ke {$host}:{$port} berhasil dibuka dalam {$socketDuration}ms.");

        // 3. Kirim email tes secara sinkron
        $this->newLine();
        $this->info("Mengirim email tes secara sinkron ke {$to}...");

        $mailStart = microtime(true);
        try {
            $mailService->sendTestEmail($to, 'Administrator SCP');
            $mailDuration = round((microtime(true) - $mailStart) * 1000, 2);

            $this->info("SUKSES: Email tes berhasil dikirimkan ke {$to} dalam {$mailDuration}ms!");
            $this->info("Silakan periksa kotak masuk (inbox / spam) penerima.");
            return 0;
        } catch (Throwable $e) {
            $mailDuration = round((microtime(true) - $mailStart) * 1000, 2);
            $this->error("GAGAL mengirim email tes setelah {$mailDuration}ms:");
            $this->line("Detail Error: " . $e->getMessage());
            $this->newLine();

            $this->warn("Saran Pemecahan Masalah Berdasarkan Error:");
            $msg = strtolower($e->getMessage());
            if (str_contains($msg, 'auth') || str_contains($msg, '535') || str_contains($msg, 'credential')) {
                $this->line("- Otentikasi SMTP ditolak. Periksa kembali username dan password.");
                $this->line("- Untuk Gmail, pastikan 2FA aktif dan gunakan App Password 16 karakter, bukan password akun Google biasa.");
            } elseif (str_contains($msg, 'certificate') || str_contains($msg, 'ssl') || str_contains($msg, 'tls')) {
                $this->line("- Terjadi masalah handshake SSL/TLS.");
                $this->line("- Jika menggunakan sertifikat self-signed, aktifkan opsi 'Abaikan verifikasi sertifikat (self-signed)'.");
                $this->line("- Periksa kesesuaian port: port 465 wajib menggunakan SSL (scheme smtps), port 587 menggunakan TLS (STARTTLS).");
            } elseif (str_contains($msg, 'timeout') || str_contains($msg, 'timed out')) {
                $this->line("- Koneksi SMTP mengalami timeout (melebihi batas 10 detik).");
                $this->line("- Periksa latensi jaringan ke server SMTP atau coba provider SMTP alternatif.");
            } elseif (str_contains($msg, 'password')) {
                $this->line("- " . $e->getMessage());
            } else {
                $this->line("- Periksa log aplikasi di storage/logs/laravel.log untuk trace lengkap.");
                $this->line("- Pastikan parameter 'From Address' cocok dengan domain pengirim yang diizinkan oleh server SMTP.");
            }

            return 1;
        }
    }
}
