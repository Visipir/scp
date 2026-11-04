<?php

namespace App\Services;

use App\Mail\EmailVerificationCodeMail;
use App\Mail\PasswordResetCodeMail;
use App\Mail\TestMail;
use App\Models\SystemConfiguration;
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

        $config = SystemConfiguration::first();
        if (! $config || ! $config->hasCustomMailConfig()) {
            return; // Fallback to .env configuration
        }

        Config::set('mail.default', 'smtp');
        Config::set('mail.mailers.smtp.transport', 'smtp');
        Config::set('mail.mailers.smtp.host', $config->mail_host);
        Config::set('mail.mailers.smtp.port', (int) ($config->mail_port ?? 587));

        $encryption = $config->mail_encryption;
        if ($encryption === 'none' || empty($encryption)) {
            $encryption = null;
        }
        Config::set('mail.mailers.smtp.encryption', $encryption);
        Config::set('mail.mailers.smtp.username', $config->mail_username);

        if (! empty($config->mail_password)) {
            try {
                Config::set('mail.mailers.smtp.password', Crypt::decryptString($config->mail_password));
            } catch (Throwable $e) {
                Log::error('Gagal mendekripsi password SMTP: ' . $e->getMessage());
            }
        }

        if (! empty($config->mail_from_address)) {
            Config::set('mail.from.address', $config->mail_from_address);
        }
        if (! empty($config->mail_from_name)) {
            Config::set('mail.from.name', $config->mail_from_name);
        }
    }

    /**
     * Send test email to verify SMTP configuration.
     *
     * @throws RuntimeException
     */
    public function sendTestEmail(string $recipientEmail, string $userName): void
    {
        $this->applyRuntimeConfig(force: true);

        try {
            Mail::to($recipientEmail)->send(new TestMail($userName));
        } catch (Throwable $e) {
            // Log error without exposing mail_password
            Log::error('SMTP Test Mail Failed: ' . $e->getMessage(), [
                'recipient' => $recipientEmail,
                'host'      => Config::get('mail.mailers.smtp.host'),
                'port'      => Config::get('mail.mailers.smtp.port'),
            ]);

            throw new RuntimeException('Gagal mengirim email tes: ' . $e->getMessage());
        }
    }

    /**
     * Send 6-digit password reset code to user's email.
     */
    public function sendPasswordResetCode(string $email, string $code): bool
    {
        $this->applyRuntimeConfig();

        try {
            Mail::to($email)->send(new PasswordResetCodeMail($code));

            return true;
        } catch (Throwable $e) {
            // Log safely without password
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
        $this->applyRuntimeConfig();

        try {
            Mail::to($email)->send(new EmailVerificationCodeMail($code));

            return true;
        } catch (Throwable $e) {
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

        $update = [
            'mail_host'         => $data['mail_host'] ?? null,
            'mail_port'         => ! empty($data['mail_port']) ? (int) $data['mail_port'] : null,
            'mail_encryption'   => $data['mail_encryption'] ?? 'tls',
            'mail_username'     => $data['mail_username'] ?? null,
            'mail_from_address' => $data['mail_from_address'] ?? null,
            'mail_from_name'    => $data['mail_from_name'] ?? null,
            'updated_by'        => $userId,
        ];

        // Encrypt password only when provided; empty keeps existing
        if (! empty($data['mail_password'])) {
            $update['mail_password'] = Crypt::encryptString($data['mail_password']);
        }

        $config->update($update);

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

        return [
            'mail_host'         => $config?->mail_host ?? '',
            'mail_port'         => $config?->mail_port ?? 587,
            'mail_encryption'   => $config?->mail_encryption ?? 'tls',
            'mail_username'     => $config?->mail_username ?? '',
            'mail_password'     => $config?->masked_mail_password ?? '',
            'mail_from_address' => $config?->mail_from_address ?? '',
            'mail_from_name'    => $config?->mail_from_name ?? '',
        ];
    }
}
