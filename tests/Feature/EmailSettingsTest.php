<?php

namespace Tests\Feature;

use App\Models\SystemConfiguration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;
use Tests\Support\ScpTestHelpers;
use Tests\TestCase;

class EmailSettingsTest extends TestCase
{
    use RefreshDatabase;
    use ScpTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
    }

    public function test_non_waka_cannot_access_email_settings(): void
    {
        $teacher = $this->makeUser(2); // Guru
        $student = $this->makeUser(4); // Siswa

        // Web routes
        $this->actingAs($teacher)->get('/system-configuration/email')->assertForbidden();
        $this->actingAs($student)->get('/system-configuration/email')->assertForbidden();

        // API routes
        $this->apiAs($teacher)->getJson('/api/v1/system/email-settings')->assertForbidden();
        $this->apiAs($student)->getJson('/api/v1/system/email-settings')->assertForbidden();
    }

    public function test_waka_can_view_email_settings_and_password_is_masked(): void
    {
        $waka = $this->makeUser(1, ['email' => 'waka@example.com']);
        $this->makeConfig([
            'mail_host'         => 'smtp.mailtrap.io',
            'mail_port'         => 2525,
            'mail_encryption'   => 'tls',
            'mail_username'     => 'testuser',
            'mail_password'     => Crypt::encryptString('supersecretpassword'),
            'mail_from_address' => 'noreply@sekolah.sch.id',
            'mail_from_name'    => 'SMK Negeri 1',
        ]);

        // Web view
        $response = $this->actingAs($waka)->get('/system-configuration/email');
        $response->assertOk();
        $response->assertSee('smtp.mailtrap.io');
        $response->assertSee('testuser');
        $response->assertDontSee('supersecretpassword');

        // API view
        $apiResponse = $this->apiAs($waka)->getJson('/api/v1/system/email-settings');
        $apiResponse->assertOk();
        $apiResponse->assertJson([
            'data' => [
                'mail_host'     => 'smtp.mailtrap.io',
                'mail_port'     => 2525,
                'mail_username' => 'testuser',
                'mail_password' => '••••••',
            ],
        ]);
        $this->assertStringNotContainsString('supersecretpassword', $apiResponse->getContent());
    }

    public function test_waka_can_update_email_settings_with_encrypted_password(): void
    {
        $waka = $this->makeUser(1);
        $this->makeConfig();

        $payload = [
            'mail_host'         => 'smtp.office365.com',
            'mail_port'         => 587,
            'mail_encryption'   => 'tls',
            'mail_username'     => 'admin@school.sch.id',
            'mail_password'     => 'newMailPassword123',
            'mail_from_address' => 'admin@school.sch.id',
            'mail_from_name'    => 'Official School Mailer',
        ];

        $response = $this->apiAs($waka)->postJson('/api/v1/system/email-settings', $payload);
        $response->assertOk();

        $config = SystemConfiguration::first();
        $this->assertSame('smtp.office365.com', $config->mail_host);
        $this->assertSame(587, $config->mail_port);
        $this->assertSame('admin@school.sch.id', $config->mail_username);

        // Password stored in DB must NOT be plain text
        $this->assertNotSame('newMailPassword123', $config->mail_password);
        $this->assertSame('newMailPassword123', Crypt::decryptString($config->mail_password));
    }

    public function test_updating_without_password_keeps_existing_password(): void
    {
        $waka = $this->makeUser(1);
        $this->makeConfig([
            'mail_host'     => 'smtp.gmail.com',
            'mail_password' => Crypt::encryptString('existingSecret'),
        ]);

        $response = $this->apiAs($waka)->postJson('/api/v1/system/email-settings', [
            'mail_host'         => 'smtp.updated.com',
            'mail_port'         => 465,
            'mail_encryption'   => 'ssl',
            'mail_username'     => 'updated@gmail.com',
            'mail_password'     => '', // Left empty so existing is retained
            'mail_from_address' => 'updated@gmail.com',
            'mail_from_name'    => 'Updated Name',
        ]);

        $response->assertOk();

        $config = SystemConfiguration::first();
        $this->assertSame('smtp.updated.com', $config->mail_host);
        $this->assertSame('existingSecret', Crypt::decryptString($config->mail_password));
    }

    public function test_waka_can_send_test_email(): void
    {
        Mail::fake();

        $waka = $this->makeUser(1, [
            'email'             => 'waka.test@sekolah.sch.id',
            'email_verified_at' => now(),
        ]);
        $this->makeConfig([
            'mail_host'         => 'smtp.test.com',
            'mail_port'         => 587,
            'mail_encryption'   => 'tls',
            'mail_username'     => 'mailer',
            'mail_password'     => Crypt::encryptString('pass'),
            'mail_from_address' => 'noreply@sekolah.sch.id',
            'mail_from_name'    => 'SMK Test',
        ]);

        $response = $this->apiAs($waka)->postJson('/api/v1/system/email-settings/test');
        $response->assertOk()
            ->assertJson([
                'message' => 'Email tes berhasil dikirimkan ke: waka.test@sekolah.sch.id',
            ]);

        Mail::assertSent(\App\Mail\TestMail::class, function ($mail) {
            return $mail->hasTo('waka.test@sekolah.sch.id');
        });
    }

    public function test_waka_without_verified_email_cannot_send_test_email(): void
    {
        Mail::fake();

        $wakaUnverified = $this->makeUser(1, [
            'email'             => 'waka.unverified@sekolah.sch.id',
            'email_verified_at' => null,
        ]);
        $this->makeConfig([
            'mail_host' => 'smtp.test.com',
        ]);

        $response = $this->apiAs($wakaUnverified)->postJson('/api/v1/system/email-settings/test');
        $response->assertStatus(422);

        Mail::assertNothingSent();
    }

    public function test_smtp_scheme_and_verify_peer_configuration(): void
    {
        $service = app(\App\Services\MailConfigService::class);

        // 1. SSL on port 465 -> smtps scheme
        $this->makeConfig([
            'mail_host'        => 'smtp.gmail.com',
            'mail_port'        => 465,
            'mail_encryption'  => 'ssl',
            'mail_verify_peer' => false,
        ]);

        $service->applyRuntimeConfig(force: true);
        $this->assertSame('smtps', \Illuminate\Support\Facades\Config::get('mail.mailers.smtp.scheme'));
        $this->assertFalse(\Illuminate\Support\Facades\Config::get('mail.mailers.smtp.verify_peer'));
        $this->assertFalse(\Illuminate\Support\Facades\Config::get('mail.mailers.smtp.stream.ssl.verify_peer'));

        // 2. TLS on port 587 -> smtp scheme
        SystemConfiguration::first()->update([
            'mail_port'        => 587,
            'mail_encryption'  => 'tls',
            'mail_verify_peer' => true,
        ]);

        $service->applyRuntimeConfig(force: true);
        $this->assertSame('smtp', \Illuminate\Support\Facades\Config::get('mail.mailers.smtp.scheme'));
        $this->assertTrue(\Illuminate\Support\Facades\Config::get('mail.mailers.smtp.verify_peer'));
    }

    public function test_corrupted_password_decrypt_returns_readable_error(): void
    {
        $this->makeConfig([
            'mail_host'     => 'smtp.test.com',
            'mail_password' => 'invalid_ciphertext_not_decryptable',
        ]);

        $service = app(\App\Services\MailConfigService::class);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Gagal membaca password email. Silakan simpan ulang password email di Pengaturan Email.');

        $service->sendTestEmail('user@test.com', 'User Test');
    }

    public function test_mail_send_logs_recorded_with_masked_email(): void
    {
        Mail::fake();

        $waka = $this->makeUser(1, [
            'email'             => 'fikricavan13@gmail.com',
            'email_verified_at' => now(),
        ]);
        $this->makeConfig([
            'mail_host'     => 'smtp.test.com',
            'mail_password' => Crypt::encryptString('secret'),
        ]);

        $service = app(\App\Services\MailConfigService::class);
        $service->sendTestEmail($waka->email, $waka->fullname);

        $this->assertDatabaseHas('mail_send_logs', [
            'mail_type' => 'test',
            'recipient' => 'f***3@gmail.com',
            'status'    => 'success',
        ]);
    }
}
