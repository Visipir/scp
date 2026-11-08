<?php

namespace Tests\Feature;

use App\Models\MailSendLog;
use App\Models\Role;
use App\Models\SystemConfiguration;
use App\Models\User;
use App\Services\MailConfigService;
use App\Services\PasswordResetService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SmtpGuardTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_safe_settings_masks_smtp_password_when_decrypt_succeeds(): void
    {
        SystemConfiguration::create([
            'mail_host'     => 'smtp.example.com',
            'mail_port'     => 587,
            'mail_username' => 'user@example.com',
            'mail_password' => Crypt::encryptString('valid-secret-smtp-pass'),
        ]);

        $service = app(MailConfigService::class);
        $settings = $service->getSafeSettings();

        $this->assertSame('••••••', $settings['mail_password']);
    }

    public function test_get_safe_settings_shows_warning_when_smtp_password_cannot_be_decrypted(): void
    {
        SystemConfiguration::create([
            'mail_host'     => 'smtp.example.com',
            'mail_port'     => 587,
            'mail_username' => 'user@example.com',
            'mail_password' => 'invalid-undecryptable-payload',
        ]);

        $service = app(MailConfigService::class);
        $settings = $service->getSafeSettings();

        $this->assertSame('Password SMTP tidak dapat dibaca, simpan ulang', $settings['mail_password']);
    }

    public function test_forgot_password_records_log_when_user_not_found_or_no_verified_email(): void
    {
        $service = app(PasswordResetService::class);
        $response = $service->requestResetCode('nonexistent_user@example.com');

        $this->assertTrue($response['success']);

        $log = MailSendLog::latest('id')->first();
        $this->assertNotNull($log);
        $this->assertSame('password_reset', $log->mail_type);
        $this->assertSame('failed', $log->status);
        $this->assertStringContainsString('Pengguna tidak ditemukan atau belum memiliki email terverifikasi', $log->error_message);
    }

    public function test_forgot_password_records_log_when_user_has_verified_email(): void
    {
        Role::insert([
            ['id' => 2, 'role_name' => 'Guru'],
        ]);

        $user = User::create([
            'fullname'          => 'Test User Verified',
            'username'          => 'test_user_verified',
            'password'          => bcrypt('test-secret-password-xyz'),
            'role_id'           => 2,
            'email'             => 'verified.user@example.com',
            'email_verified_at' => now(),
        ]);

        config(['mail.default' => 'log']);

        $service = app(PasswordResetService::class);
        $response = $service->requestResetCode($user->email);

        $this->assertTrue($response['success']);

        $log = MailSendLog::where('mail_type', 'password_reset')->latest('id')->first();
        $this->assertNotNull($log);
        $this->assertSame('success', $log->status);
    }
}
