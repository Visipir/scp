<?php

namespace App\Services;

use App\Mail\EmailVerificationCodeMail;
use App\Mail\PasswordResetCodeMail;
use App\Mail\TestMail;
use App\Models\MailSendLog;
use App\Models\SystemConfiguration;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use RuntimeException;
use Throwable;

class MailConfigService
{
    /**
     * Apply SMTP configuration stored in system_configurations table at runtime.
     * Falls back to environment (.env) configuration when settings are empty.
     */
    public function applyRuntimeConfig(bool $force = false): void
    {
        // In testing with array driver, preserve unless explicitly forced
        if (! $force && app()->environment('testing') && Config::get('mail.default') === 'array') {
            return;
        }

        // Always purge cached mailer so runtime changes take effect immediately
        Mail::purge('smtp');

        $config = SystemConfiguration::first();
        if (! $config || ! $config->hasCustomMailConfig()) {
            return; // Fallback to .env configuration
        }

        $port = (int) ($config->mail_port ?? 587);
        $encryption = strtolower((string) ($config->mail_encryption ?? 'tls'));

        // Determine scheme according to Symfony Mailer rules:
        // Port 465 or ssl -> smtps (implicit TLS/SSL)
        // Port 587 or tls -> smtp with STARTTLS
        // none / other -> smtp without TLS
        if ($port === 465 || $encryption === 'ssl') {
            $scheme = 'smtps';
            $enc = 'ssl';
        } elseif ($encryption === 'tls' || $port === 587) {
            $scheme = 'smtp';
            $enc = 'tls';
        } else {
            $scheme = 'smtp';
            $enc = null;
        }

        Config::set('mail.default', 'smtp');
        Config::set('mail.mailers.smtp.transport', 'smtp');
        Config::set('mail.mailers.smtp.scheme', $scheme);
        Config::set('mail.mailers.smtp.host', $config->mail_host);
        Config::set('mail.mailers.smtp.port', $port);
        Config::set('mail.mailers.smtp.encryption', $enc);
        Config::set('mail.mailers.smtp.username', $config->mail_username);
        Config::set('mail.mailers.smtp.timeout', 10);

        // Verify peer setting (false disables certificate verification for self-signed certs)
        $verifyPeer = (bool) ($config->mail_verify_peer ?? true);
        Config::set('mail.mailers.smtp.verify_peer', $verifyPeer);
        if (! $verifyPeer) {
            Config::set('mail.mailers.smtp.stream.ssl.verify_peer', false);
            Config::set('mail.mailers.smtp.stream.ssl.verify_peer_name', false);
        }

        if (! empty($config->mail_password)) {
            try {
                Config::set('mail.mailers.smtp.password', Crypt::decryptString($config->mail_password));
            } catch (DecryptException $e) {
                Log::error('Gagal mendekripsi password SMTP: ' . $e->getMessage());
                throw new RuntimeException('Gagal membaca password email. Silakan simpan ulang password email di Pengaturan Email.');
            } catch (Throwable $e) {
                Log::error('Gagal memuat password SMTP: ' . $e->getMessage());
                throw new RuntimeException('Gagal membaca konfigurasi email: ' . $e->getMessage());
            }
        }

        $fromAddress = $config->mail_from_address ?: $config->mail_username;
        if (! empty($fromAddress)) {
            Config::set('mail.from.address', $fromAddress);
        }
        if (! empty($config->mail_from_name)) {
            Config::set('mail.from.name', $config->mail_from_name);
        }
    }

    /**
     * Send test email to verify SMTP configuration synchronously.
     *
     * @throws RuntimeException
     */
    public function sendTestEmail(string $recipientEmail, string $userName): void
    {
        try {
            $this->applyRuntimeConfig(force: true);
            Mail::purge('smtp');
            Mail::mailer('smtp')->to($recipientEmail)->send(new TestMail($userName));
            MailSendLog::log('test', $recipientEmail, true);
        } catch (Throwable $e) {
            MailSendLog::log('test', $recipientEmail, false, $e->getMessage());
            Log::error('SMTP Test Mail Failed: ' . $e->getMessage(), [
                'recipient' => $recipientEmail,
                'host'      => Config::get('mail.mailers.smtp.host'),
                'port'      => Config::get('mail.mailers.smtp.port'),
            ]);

            throw new RuntimeException('Gagal mengirim email tes: ' . $e->getMessage());
        }
    }

    /**
     * Send 6-digit password reset code to user's email synchronously.
     */
    public function sendPasswordResetCode(string $email, string $code): bool
    {
        try {
            $this->applyRuntimeConfig();
            Mail::purge('smtp');
            Mail::to($email)->send(new PasswordResetCodeMail($code));
            MailSendLog::log('password_reset', $email, true);

            return true;
        } catch (Throwable $e) {
            MailSendLog::log('password_reset', $email, false, $e->getMessage());
            Log::error('Failed to send password reset email: ' . $e->getMessage(), [
                'email' => $email,
            ]);

            return false;
        }
    }

    /**
     * Send 6-digit verification code to confirm ownership of an email address.
     */
    public function sendEmailVerificationCode(string $email, string $code): bool
    {
        try {
            $this->applyRuntimeConfig();
            Mail::purge('smtp');
            Mail::to($email)->send(new EmailVerificationCodeMail($code));
            MailSendLog::log('email_verification', $email, true);

            return true;
        } catch (Throwable $e) {
            MailSendLog::log('email_verification', $email, false, $e->getMessage());
            Log::warning('Failed to send email verification code: ' . $e->getMessage(), [
                'email' => $email,
            ]);

            return false;
        }
    }

    /**
     * Update mail settings from form input.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateSettings(array $data, int $userId): SystemConfiguration
    {
        $config = SystemConfiguration::firstOrFail();

        $ignoreCert = ! empty($data['mail_ignore_cert']) || (isset($data['mail_verify_peer']) && ! $data['mail_verify_peer']);
        $verifyPeer = ! $ignoreCert;

        $update = [
            'mail_host'         => $data['mail_host'] ?? null,
            'mail_port'         => ! empty($data['mail_port']) ? (int) $data['mail_port'] : null,
            'mail_encryption'   => $data['mail_encryption'] ?? 'tls',
            'mail_username'     => $data['mail_username'] ?? null,
            'mail_from_address' => $data['mail_from_address'] ?? null,
            'mail_from_name'    => $data['mail_from_name'] ?? null,
            'mail_verify_peer'  => $verifyPeer,
            'updated_by'        => $userId,
        ];

        // Encrypt password only when provided; empty keeps existing
        if (! empty($data['mail_password'])) {
            $update['mail_password'] = Crypt::encryptString($data['mail_password']);
        }

        $config->update($update);

        // Purge mailer whenever settings are updated
        Mail::purge('smtp');

        return $config;
    }

    /**
     * Return safe mail settings array for client (with masked password).
     *
     * @return array<string, mixed>
     */
    public function getSafeSettings(): array
    {
        $config = SystemConfiguration::first();

        $maskedPassword = '';
        if (! empty($config?->mail_password)) {
            try {
                Crypt::decryptString($config->mail_password);
                $maskedPassword = '••••••';
            } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
                $maskedPassword = 'Password SMTP tidak dapat dibaca, simpan ulang';
            } catch (\Throwable $e) {
                $maskedPassword = 'Password SMTP tidak dapat dibaca, simpan ulang';
            }
        }

        return [
            'mail_host'         => $config?->mail_host ?? '',
            'mail_port'         => $config?->mail_port ?? 587,
            'mail_encryption'   => $config?->mail_encryption ?? 'tls',
            'mail_username'     => $config?->mail_username ?? '',
            'mail_password'     => $maskedPassword,
            'mail_from_address' => $config?->mail_from_address ?? '',
            'mail_from_name'    => $config?->mail_from_name ?? '',
            'mail_verify_peer'  => (bool) ($config?->mail_verify_peer ?? true),
            'mail_ignore_cert'  => ! (bool) ($config?->mail_verify_peer ?? true),
        ];
    }
}
