<?php

namespace Tests\Feature;

use App\Mail\EmailVerificationCodeMail;
use App\Models\EmailVerificationCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\Support\ScpTestHelpers;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;
    use ScpTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
    }

    public function test_user_can_request_email_verification_code(): void
    {
        Mail::fake();

        $user = $this->makeUser(2);

        $response = $this->apiAs($user)->postJson('/api/v1/profile/email', [
            'email' => 'my.new.email@example.com',
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
            ]);

        // Code generated in DB
        $this->assertDatabaseHas('email_verification_codes', [
            'user_id' => $user->id,
            'email'   => 'my.new.email@example.com',
        ]);

        // Email unverified on user until confirmed
        $this->assertNull($user->fresh()->email_verified_at);

        Mail::assertSent(EmailVerificationCodeMail::class, function ($mail) {
            return $mail->hasTo('my.new.email@example.com');
        });
    }

    public function test_request_email_fails_if_email_already_taken(): void
    {
        $existing = $this->makeUser(2, ['email' => 'taken@example.com']);
        $user = $this->makeUser(3);

        $response = $this->apiAs($user)->postJson('/api/v1/profile/email', [
            'email' => 'taken@example.com',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_request_email_throttles_within_one_minute(): void
    {
        Mail::fake();

        $user = $this->makeUser(2);

        $this->apiAs($user)->postJson('/api/v1/profile/email', [
            'email' => 'fast@example.com',
        ])->assertOk();

        // Immediate second attempt within 60 seconds
        $second = $this->apiAs($user)->postJson('/api/v1/profile/email', [
            'email' => 'fast@example.com',
        ]);

        $second->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_user_can_verify_email_with_correct_code(): void
    {
        $user = $this->makeUser(2, [
            'email'             => 'verify.me@example.com',
            'email_verified_at' => null,
        ]);

        EmailVerificationCode::create([
            'user_id'    => $user->id,
            'email'      => 'verify.me@example.com',
            'code_hash'  => Hash::make('123456'),
            'attempts'   => 0,
            'expires_at' => now()->addMinutes(15),
        ]);

        $response = $this->apiAs($user)->postJson('/api/v1/profile/email/verify', [
            'code' => '123456',
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'user'    => [
                    'email'             => 'verify.me@example.com',
                    'is_email_verified' => true,
                ],
            ]);

        $fresh = $user->fresh();
        $this->assertNotNull($fresh->email_verified_at);
        $this->assertSame('verify.me@example.com', $fresh->email);

        // Code deleted after success
        $this->assertDatabaseMissing('email_verification_codes', [
            'user_id' => $user->id,
        ]);
    }

    public function test_verify_code_fails_with_wrong_code_and_tracks_attempts(): void
    {
        $user = $this->makeUser(2);

        EmailVerificationCode::create([
            'user_id'    => $user->id,
            'email'      => 'wrong.code@example.com',
            'code_hash'  => Hash::make('654321'),
            'attempts'   => 0,
            'expires_at' => now()->addMinutes(15),
        ]);

        $response = $this->apiAs($user)->postJson('/api/v1/profile/email/verify', [
            'code' => '000000',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['code']);

        $record = EmailVerificationCode::where('user_id', $user->id)->first();
        $this->assertSame(1, $record->attempts);
    }

    public function test_verify_code_locks_after_5_failed_attempts(): void
    {
        $user = $this->makeUser(2);

        EmailVerificationCode::create([
            'user_id'    => $user->id,
            'email'      => 'locked.code@example.com',
            'code_hash'  => Hash::make('654321'),
            'attempts'   => 5,
            'expires_at' => now()->addMinutes(15),
        ]);

        $response = $this->apiAs($user)->postJson('/api/v1/profile/email/verify', [
            'code' => '654321', // Even correct code must be rejected when locked
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['code']);
    }

    public function test_user_can_remove_email_with_correct_current_password(): void
    {
        $user = $this->makeUser(2, [
            'email'             => 'removable@example.com',
            'email_verified_at' => now(),
            'password'          => Hash::make('validPassword123'),
        ]);

        $response = $this->apiAs($user)->deleteJson('/api/v1/profile/email', [
            'current_password' => 'validPassword123',
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
            ]);

        $fresh = $user->fresh();
        $this->assertNull($fresh->email);
        $this->assertNull($fresh->email_verified_at);
    }

    public function test_remove_email_fails_with_incorrect_password(): void
    {
        $user = $this->makeUser(2, [
            'email'             => 'safe@example.com',
            'email_verified_at' => now(),
            'password'          => Hash::make('correctPassword'),
        ]);

        $response = $this->apiAs($user)->deleteJson('/api/v1/profile/email', [
            'current_password' => 'wrongPassword',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['current_password']);

        $this->assertSame('safe@example.com', $user->fresh()->email);
    }

    public function test_waka_editing_user_email_resets_verification(): void
    {
        $waka = $this->makeUser(1);
        $teacher = $this->makeUser(2, [
            'email'             => 'old.verified@example.com',
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($waka)->put('/users/' . $teacher->id, [
            'fullname' => $teacher->fullname,
            'username' => $teacher->username,
            'email'    => 'new.unverified@example.com',
            'role_id'  => 2,
        ]);

        $response->assertRedirect('/users');

        $fresh = $teacher->fresh();
        $this->assertSame('new.unverified@example.com', $fresh->email);
        $this->assertNull($fresh->email_verified_at);
    }
}
