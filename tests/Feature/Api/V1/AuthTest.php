<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\Support\ScpTestHelpers;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;
    use ScpTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
    }

    public function test_sanctum_token_expiration_is_configured(): void
    {
        $this->assertSame(43200, config('sanctum.expiration'));
    }

    public function test_login_returns_token_in_envelope(): void
    {
        $user = $this->makeUser(2, ['username' => 'guru_api', 'fullname' => 'Guru API']);

        $response = $this->postJson('/api/v1/auth/login', [
            'username'    => 'guru_api',
            'password'    => 'password',
            'device_name' => 'iPhone 15',
        ]);

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'token',
                'must_change_password',
                'user' => ['id', 'username', 'fullname', 'role_id', 'role', 'class_id'],
            ],
        ]);
        $response->assertJsonPath('data.must_change_password', false);

        $token = $response->json('data.token');
        $this->assertNotEmpty($token);

        // Token authenticates a protected endpoint
        $this->withToken($token)->getJson('/api/v1/auth/me')->assertOk()->assertJsonPath('data.id', $user->id);
    }

    public function test_login_defaults_device_name_to_ios(): void
    {
        $this->makeUser(2, ['username' => 'guru_ios']);

        $this->postJson('/api/v1/auth/login', [
            'username' => 'guru_ios',
            'password' => 'password',
        ])->assertOk();

        $this->assertDatabaseHas('personal_access_tokens', ['name' => 'ios']);
    }

    public function test_login_with_bad_credentials_returns_401_envelope(): void
    {
        $this->makeUser(2, ['username' => 'guru_bad']);

        $response = $this->postJson('/api/v1/auth/login', [
            'username' => 'guru_bad',
            'password' => 'salah',
        ]);

        $response->assertStatus(401);
        $response->assertJson(['message' => 'Username atau password salah.', 'errors' => null]);
    }

    public function test_login_is_throttled_per_username_and_ip(): void
    {
        $this->makeUser(2, ['username' => 'guru_throttle']);

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/auth/login', [
                'username' => 'guru_throttle',
                'password' => 'salah',
            ])->assertStatus(401);
        }

        $this->postJson('/api/v1/auth/login', [
            'username' => 'guru_throttle',
            'password' => 'salah',
        ])->assertStatus(429)->assertJsonStructure(['message', 'errors']);
    }

    public function test_unauthenticated_returns_401_envelope(): void
    {
        $this->getJson('/api/v1/auth/me')
            ->assertStatus(401)
            ->assertJson(['errors' => null]);
    }

    public function test_not_found_returns_404_envelope(): void
    {
        $this->getJson('/api/v1/does-not-exist')
            ->assertStatus(404)
            ->assertJsonStructure(['message', 'errors']);
    }

    public function test_validation_error_returns_422_envelope(): void
    {
        $this->postJson('/api/v1/auth/login', [])
            ->assertStatus(422)
            ->assertJsonStructure(['message', 'errors' => ['username', 'password']]);
    }

    public function test_logout_revokes_current_token(): void
    {
        $this->makeUser(2, ['username' => 'guru_logout']);

        $token = $this->postJson('/api/v1/auth/login', [
            'username' => 'guru_logout',
            'password' => 'password',
        ])->json('data.token');

        $this->withToken($token)->postJson('/api/v1/auth/logout')->assertOk();

        $this->assertDatabaseCount('personal_access_tokens', 0);
        \Illuminate\Support\Facades\Auth::forgetGuards();
        $this->withToken($token)->getJson('/api/v1/auth/me')->assertStatus(401);
    }

    public function test_must_change_password_gate_blocks_other_endpoints(): void
    {
        $user = $this->makeUser(4, [
            'username'             => 'siswa_gate',
            'must_change_password' => true,
        ]);

        $token = $this->postJson('/api/v1/auth/login', [
            'username' => 'siswa_gate',
            'password' => 'password',
        ])->json('data.token');

        // me is allowed
        $this->withToken($token)->getJson('/api/v1/auth/me')->assertOk();

        // other endpoints are blocked with the code
        $response = $this->withToken($token)->patchJson('/api/v1/auth/profile', ['fullname' => 'Baru']);
        $response->assertStatus(403);
        $response->assertJsonPath('code', 'must_change_password');
        $response->assertJsonPath('errors', null);
    }

    public function test_first_change_password_does_not_require_current_password_and_clears_flag(): void
    {
        $this->makeUser(4, [
            'username'             => 'siswa_first',
            'must_change_password' => true,
        ]);

        $token = $this->postJson('/api/v1/auth/login', [
            'username' => 'siswa_first',
            'password' => 'password',
        ])->json('data.token');

        $this->withToken($token)->postJson('/api/v1/auth/change-password', [
            'password'              => 'rahasia123',
            'password_confirmation' => 'rahasia123',
        ])->assertOk();

        $user = User::where('username', 'siswa_first')->first();
        $this->assertFalse((bool) $user->must_change_password);
        $this->assertTrue(Hash::check('rahasia123', $user->password));

        // Gate is now open
        $this->withToken($token)->patchJson('/api/v1/auth/profile', ['fullname' => 'Nama Baru'])->assertOk();
    }

    public function test_change_password_requires_current_password_when_not_forced(): void
    {
        $this->makeUser(2, ['username' => 'guru_pw']);

        $token = $this->postJson('/api/v1/auth/login', [
            'username' => 'guru_pw',
            'password' => 'password',
        ])->json('data.token');

        $this->withToken($token)->postJson('/api/v1/auth/change-password', [
            'password'              => 'rahasia123',
            'password_confirmation' => 'rahasia123',
        ])->assertStatus(422)->assertJsonStructure(['errors' => ['current_password']]);
    }

    public function test_me_includes_children_for_parent(): void
    {
        $parent = $this->makeUser(4, ['username' => 'ortu_api']);
        $child = $this->makeUser(4, [
            'username' => 'anak_api',
            'class_id' => $this->makeClass('XII RPL 1')->id,
            'parent_id' => $parent->id,
        ]);

        $token = $this->postJson('/api/v1/auth/login', [
            'username' => 'ortu_api',
            'password' => 'password',
        ])->json('data.token');

        $response = $this->withToken($token)->getJson('/api/v1/auth/me');
        $response->assertOk();
        $response->assertJsonPath('data.children.0.id', $child->id);
        $response->assertJsonPath('data.children.0.class', 'XII RPL 1');
    }
}
