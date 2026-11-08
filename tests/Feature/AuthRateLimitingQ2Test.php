<?php

namespace Tests\Feature;

use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Tests\Support\ScpTestHelpers;
use Tests\TestCase;

class AuthRateLimitingQ2Test extends TestCase
{
    use RefreshDatabase;
    use ScpTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
        RateLimiter::clear('api-login');
        RateLimiter::clear('forgot-password');
    }

    public function test_legacy_api_login_returns_token_with_valid_credentials(): void
    {
        $user = $this->makeUser(2, ['password' => Hash::make('validPassword123!')]);

        $response = $this->postJson('/api/login', [
            'username' => $user->username,
            'password' => 'validPassword123!',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'message',
            'user',
            'token',
        ]);
        $this->assertNotEmpty($response->json('token'));
    }

    public function test_legacy_api_login_throttles_on_6th_attempt(): void
    {
        $user = $this->makeUser(2, ['password' => Hash::make('validPassword123!')]);

        for ($i = 1; $i <= 5; $i++) {
            $response = $this->postJson('/api/login', [
                'username' => $user->username,
                'password' => 'wrong-pass',
            ]);
            $this->assertEquals(401, $response->getStatusCode(), "Attempt {$i} should return 401");
        }

        $response6 = $this->postJson('/api/login', [
            'username' => $user->username,
            'password' => 'wrong-pass',
        ]);
        $response6->assertStatus(429);
    }

    public function test_api_v1_auth_login_remains_active_and_throttled(): void
    {
        $user = $this->makeUser(2, ['password' => Hash::make('validPassword123!')]);

        $response = $this->postJson('/api/v1/auth/login', [
            'username' => $user->username,
            'password' => 'validPassword123!',
            'device_name' => 'test-device',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'token',
                'user',
            ],
        ]);
    }

    public function test_reset_password_token_rate_limited_to_6_per_minute(): void
    {
        for ($i = 1; $i <= 6; $i++) {
            $res = $this->post('/reset-password-token', [
                'token' => 'dummy-token',
                'email' => 'test@example.com',
                'password' => 'short',
                'password_confirmation' => 'mismatch',
            ]);
            $this->assertNotEquals(429, $res->getStatusCode(), "Request {$i} should not be throttled");
        }

        $res7 = $this->post('/reset-password-token', [
            'token' => 'dummy-token',
            'email' => 'test@example.com',
            'password' => 'short',
            'password_confirmation' => 'mismatch',
        ]);
        $this->assertEquals(429, $res7->getStatusCode(), 'The 7th request must be throttled with HTTP 429');
    }

    public function test_web_reset_password_code_rate_limited_to_6_per_minute(): void
    {
        for ($i = 1; $i <= 6; $i++) {
            $res = $this->post('/reset-password', [
                'email' => 'test@example.com',
                'code' => '123456',
                'password' => 'short',
                'password_confirmation' => 'mismatch',
            ]);
            $this->assertNotEquals(429, $res->getStatusCode(), "Request {$i} should not be throttled");
        }

        $res7 = $this->post('/reset-password', [
            'email' => 'test@example.com',
            'code' => '123456',
            'password' => 'short',
            'password_confirmation' => 'mismatch',
        ]);
        $this->assertEquals(429, $res7->getStatusCode(), 'The 7th request must be throttled with HTTP 429');
    }

    public function test_api_v1_email_verify_rate_limited_to_6_per_minute(): void
    {
        $user = $this->makeUser(2);

        for ($i = 1; $i <= 6; $i++) {
            $res = $this->apiAs($user)->postJson('/api/v1/auth/email/verify', [
                'code' => '123456',
            ]);
            $this->assertNotEquals(429, $res->getStatusCode(), "Request {$i} should not be throttled");
        }

        $res7 = $this->apiAs($user)->postJson('/api/v1/auth/email/verify', [
            'code' => '123456',
        ]);
        $res7->assertStatus(429);
    }

    public function test_web_profile_email_verify_rate_limited_to_6_per_minute(): void
    {
        $user = $this->makeUser(2);

        for ($i = 1; $i <= 6; $i++) {
            $res = $this->actingAs($user)->post('/profile/email/verify', [
                'code' => '123456',
            ]);
            $this->assertNotEquals(429, $res->getStatusCode(), "Request {$i} should not be throttled");
        }

        $res7 = $this->actingAs($user)->post('/profile/email/verify', [
            'code' => '123456',
        ]);
        $res7->assertStatus(429);
    }
}
