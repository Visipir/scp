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
                'message' => 'Jika email terdaftar, kode verifikasi 6 digit telah dikirimkan ke email Anda.',
            ]);

        Mail::assertNothingSent();
        Mail::assertNothingQueued();
    }

    public function test_forgot_password_sends_email_and_stores_hash_for_registered_user(): void
    {
        Mail::fake();

        $user = $this->makeUser(2, [
            'email' => 'guru.test@example.com',
        ]);

        $response = $this->postJson('/api/forgot-password', [
            'email' => 'guru.test@example.com',
        ]);

        $response->assertOk()
            ->assertJson([
                'message' => 'Jika email terdaftar, kode verifikasi 6 digit telah dikirimkan ke email Anda.',
            ]);

        $this->assertDatabaseHas('password_reset_codes', [
            'email' => 'guru.test@example.com',
        ]);

        $record = PasswordResetCode::where('email', 'guru.test@example.com')->first();
        $this->assertNotNull($record);
        $this->assertSame(0, $record->attempts);
        $this->assertTrue($record->expires_at->isFuture());
    }

    public function test_reset_password_fails_with_invalid_code(): void
    {
        $user = $this->makeUser(2, [
            'email' => 'guru.reset@example.com',
            'password' => Hash::make('old***REMOVED***'),
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
            'password'              => 'new***REMOVED***',
            'password_confirmation' => 'new***REMOVED***',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['code']);

        $record = PasswordResetCode::where('email', 'guru.reset@example.com')->first();
        $this->assertSame(1, $record->attempts);
    }

    public function test_reset_password_locks_out_after_max_attempts(): void
    {
        $user = $this->makeUser(2, [
            'email' => 'guru.locked@example.com',
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
            'password'              => 'new***REMOVED***',
            'password_confirmation' => 'new***REMOVED***',
        ]);

        $response->assertStatus(422);

        // Next attempt must be blocked as expired / locked out
        $nextResponse = $this->postJson('/api/reset-password', [
            'email'                 => 'guru.locked@example.com',
            'code'                  => '123456',
            'password'              => 'new***REMOVED***',
            'password_confirmation' => 'new***REMOVED***',
        ]);

        $nextResponse->assertStatus(422);
    }

    public function test_reset_password_fails_if_expired(): void
    {
        $user = $this->makeUser(2, [
            'email' => 'guru.expired@example.com',
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
            'password'              => 'new***REMOVED***',
            'password_confirmation' => 'new***REMOVED***',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['code']);
    }

    public function test_reset_password_success_revokes_tokens_and_updates_password(): void
    {
        $user = $this->makeUser(2, [
            'email'    => 'guru.success@example.com',
            'password' => Hash::make('old***REMOVED***'),
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

        $response = $this->postJson('/api/reset-password', [
            'email'                 => 'guru.success@example.com',
            'code'                  => '654321',
            'password'              => '***REMOVED***',
            'password_confirmation' => '***REMOVED***',
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
        $this->assertTrue(Hash::check('***REMOVED***', $user->fresh()->password));
    }

    public function test_web_forgot_password_and_reset_flow(): void
    {
        Mail::fake();

        $user = $this->makeUser(2, [
            'email'    => 'web.user@example.com',
            'password' => Hash::make('old***REMOVED***'),
        ]);

        // Step 1: Request code
        $postResponse = $this->post('/forgot-password', [
            'email' => 'web.user@example.com',
        ]);
        $postResponse->assertRedirect('/reset-password?email=web.user%40example.com');

        // Manually retrieve code hash to test reset
        $record = PasswordResetCode::where('email', 'web.user@example.com')->first();
        $this->assertNotNull($record);

        // Update record with known plain code
        $record->update(['code_hash' => Hash::make('112233')]);

        // Step 2: Submit code & new password
        $resetResponse = $this->post('/reset-password', [
            'email'                 => 'web.user@example.com',
            'code'                  => '112233',
            'password'              => '***REMOVED***',
            'password_confirmation' => '***REMOVED***',
        ]);

        $resetResponse->assertRedirect('/login');
        $this->assertTrue(Hash::check('***REMOVED***', $user->fresh()->password));
    }
}
