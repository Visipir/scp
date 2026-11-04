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

        $waka = $this->makeUser(1, ['email' => 'waka.test@sekolah.sch.id']);
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
}
