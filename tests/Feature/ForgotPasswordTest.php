<?php

namespace Tests\Feature;

use App\Models\PasswordResetCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\Support\ScpTestHelpers;
use Tests\TestCase;

class ForgotPasswordTest extends TestCase
{
    use RefreshDatabase;
    use ScpTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
    }

    public function test_forgot_password_returns_generic_message_for_nonexistent_email(): void
    {
        Mail::fake();

        $response = $this->postJson('/api/forgot-password', [
            'email' => 'unknown@example.com',
        ]);

        $response->assertOk()
            ->assertJson([
                'message' => 'Jika akun ditemukan dan memiliki email terverifikasi, kode dikirim.',
            ]);

        Mail::assertNothingSent();
        Mail::assertNothingQueued();
    }

    public function test_forgot_password_does_not_send_email_if_email_is_unverified(): void
    {
        Mail::fake();

        $user = $this->makeUser(2, [
            'email'             => 'unverified@example.com',
            'email_verified_at' => null,
        ]);

        $response = $this->postJson('/api/forgot-password', [
            'email' => 'unverified@example.com',
        ]);

        $response->assertOk()
            ->assertJson([
                'message' => 'Jika akun ditemukan dan memiliki email terverifikasi, kode dikirim.',
            ]);

        Mail::assertNothingSent();
        $this->assertDatabaseMissing('password_reset_codes', [
            'email' => 'unverified@example.com',
        ]);
    }

    public function test_forgot_password_sends_email_for_verified_user_by_email(): void
    {
        Mail::fake();

        $user = $this->makeUser(2, [
            'email'             => 'guru.test@example.com',
            'email_verified_at' => now(),
        ]);

        $response = $this->postJson('/api/forgot-password', [
            'email' => 'guru.test@example.com',
        ]);

        $response->assertOk()
            ->assertJson([
                'message' => 'Jika akun ditemukan dan memiliki email terverifikasi, kode dikirim.',
            ]);

        $this->assertDatabaseHas('password_reset_codes', [
            'email' => 'guru.test@example.com',
        ]);

        $record = PasswordResetCode::where('email', 'guru.test@example.com')->first();
        $this->assertNotNull($record);
        $this->assertSame(0, $record->attempts);
        $this->assertTrue($record->expires_at->isFuture());
    }

    public function test_forgot_password_accepts_username_or_nis_identifier(): void
    {
        Mail::fake();

        $user = $this->makeUser(4, [
            'username'          => '12345678',
            'email'             => 'siswa.nis@example.com',
            'email_verified_at' => now(),
        ]);

        $response = $this->postJson('/api/forgot-password', [
            'identifier' => '12345678',
        ]);

        $response->assertOk()
            ->assertJson([
                'message' => 'Jika akun ditemukan dan memiliki email terverifikasi, kode dikirim.',
            ]);

        $this->assertDatabaseHas('password_reset_codes', [
            'email' => 'siswa.nis@example.com',
        ]);
    }

    public function test_forgot_password_is_rate_limited_to_5_per_hour(): void
    {
        Mail::fake();

        $user = $this->makeUser(2, [
            'email'             => 'throttled@example.com',
            'email_verified_at' => now(),
        ]);

        for ($i = 0; $i < 5; $i++) {
            $res = $this->postJson('/api/forgot-password', [
                'identifier' => 'throttled@example.com',
            ]);
            $res->assertOk();
        }

        // 6th attempt should be blocked with 429
        $blocked = $this->postJson('/api/forgot-password', [
            'identifier' => 'throttled@example.com',
        ]);
        $blocked->assertStatus(429);
    }

    public function test_reset_password_fails_with_invalid_code(): void
    {
        $oldPass = \Illuminate\Support\Str::random(16);
        $newPass = \Illuminate\Support\Str::random(16);
        $user = $this->makeUser(2, [
            'email'             => 'guru.reset@example.com',
            'email_verified_at' => now(),
            'password'          => Hash::make($oldPass),
        ]);

        PasswordResetCode::create([
            'email'      => 'guru.reset@example.com',
            'code_hash'  => Hash::make('123456'),
            'attempts'   => 0,
            'expires_at' => now()->addMinutes(15),
        ]);

        $response = $this->postJson('/api/reset-password', [
            'email'                 => 'guru.reset@example.com',
            'code'                  => '999999',
            'password'              => $newPass,
            'password_confirmation' => $newPass,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['code']);

        $record = PasswordResetCode::where('email', 'guru.reset@example.com')->first();
        $this->assertSame(1, $record->attempts);
    }

    public function test_reset_password_locks_out_after_max_attempts(): void
    {
        $newPass = \Illuminate\Support\Str::random(16);
        $user = $this->makeUser(2, [
            'email'             => 'guru.locked@example.com',
            'email_verified_at' => now(),
        ]);

        $record = PasswordResetCode::create([
            'email'      => 'guru.locked@example.com',
            'code_hash'  => Hash::make('123456'),
            'attempts'   => 4,
            'expires_at' => now()->addMinutes(15),
        ]);

        // 5th failed attempt
        $response = $this->postJson('/api/reset-password', [
            'email'                 => 'guru.locked@example.com',
            'code'                  => '000000',
            'password'              => $newPass,
            'password_confirmation' => $newPass,
        ]);

        $response->assertStatus(422);

        // Next attempt must be blocked as expired / locked out
        $nextResponse = $this->postJson('/api/reset-password', [
            'email'                 => 'guru.locked@example.com',
            'code'                  => '123456',
            'password'              => $newPass,
            'password_confirmation' => $newPass,
        ]);

        $nextResponse->assertStatus(422);
    }

    public function test_reset_password_fails_if_expired(): void
    {
        $newPass = \Illuminate\Support\Str::random(16);
        $user = $this->makeUser(2, [
            'email'             => 'guru.expired@example.com',
            'email_verified_at' => now(),
        ]);

        PasswordResetCode::create([
            'email'      => 'guru.expired@example.com',
            'code_hash'  => Hash::make('123456'),
            'attempts'   => 0,
            'expires_at' => now()->subMinute(),
        ]);

        $response = $this->postJson('/api/reset-password', [
            'email'                 => 'guru.expired@example.com',
            'code'                  => '123456',
            'password'              => $newPass,
            'password_confirmation' => $newPass,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['code']);
    }

    public function test_reset_password_success_revokes_tokens_and_updates_password(): void
    {
        $oldPass = \Illuminate\Support\Str::random(16);
        $newPass = \Illuminate\Support\Str::random(16);
        $user = $this->makeUser(2, [
            'username'          => 'guru_success',
            'email'             => 'guru.success@example.com',
            'email_verified_at' => now(),
            'password'          => Hash::make($oldPass),
        ]);

        // Create Sanctum personal access token
        $token = $user->createToken('test-device')->plainTextToken;
        $this->assertCount(1, $user->tokens);

        PasswordResetCode::create([
            'email'      => 'guru.success@example.com',
            'code_hash'  => Hash::make('654321'),
            'attempts'   => 0,
            'expires_at' => now()->addMinutes(15),
        ]);

        // Can reset using username identifier instead of raw email
        $response = $this->postJson('/api/reset-password', [
            'identifier'            => 'guru_success',
            'code'                  => '654321',
            'password'              => $newPass,
            'password_confirmation' => $newPass,
        ]);

        $response->assertOk()
            ->assertJson([
                'message' => 'Kata sandi berhasil diatur ulang. Silakan masuk menggunakan kata sandi baru Anda.',
            ]);

        // Code record deleted
        $this->assertDatabaseMissing('password_reset_codes', [
            'email' => 'guru.success@example.com',
        ]);

        // Sanctum tokens revoked
        $this->assertCount(0, $user->fresh()->tokens);

        // Password updated
        $this->assertTrue(Hash::check($newPass, $user->fresh()->password));
    }

    public function test_web_forgot_password_and_reset_flow(): void
    {
        Mail::fake();
        $oldPass = \Illuminate\Support\Str::random(16);
        $newPass = \Illuminate\Support\Str::random(16);

        $user = $this->makeUser(2, [
            'username'          => 'web_teacher',
            'email'             => 'web.user@example.com',
            'email_verified_at' => now(),
            'password'          => Hash::make($oldPass),
        ]);

        // Step 1: Request code using username
        $postResponse = $this->post('/forgot-password', [
            'identifier' => 'web_teacher',
        ]);
        $postResponse->assertRedirect();
        $this->assertStringContainsString('/reset-password', $postResponse->headers->get('Location'));

        // Manually retrieve code hash to test reset
        $record = PasswordResetCode::where('email', 'web.user@example.com')->first();
        $this->assertNotNull($record);

        // Update record with known plain code
        $record->update(['code_hash' => Hash::make('112233')]);

        // Step 2: Submit code & new password
        $resetResponse = $this->post('/reset-password', [
            'identifier'            => 'web_teacher',
            'code'                  => '112233',
            'password'              => $newPass,
            'password_confirmation' => $newPass,
        ]);

        $resetResponse->assertRedirect('/login');
        $this->assertTrue(Hash::check($newPass, $user->fresh()->password));
    }

    public function test_login_page_renders_forgot_password_link(): void
    {
        $response = $this->get('/login');
        $response->assertOk()
            ->assertSee('Lupa password?')
            ->assertSee(route('password.request'));
    }
}
