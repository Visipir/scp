<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Tests\TestCase;

class LoginValidationJ1Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['id' => 1], ['role_name' => 'Waka Kurikulum']);
        Role::firstOrCreate(['id' => 2], ['role_name' => 'Guru']);
        Role::firstOrCreate(['id' => 3], ['role_name' => 'Tim Kesiswaan']);
        Role::firstOrCreate(['id' => 4], ['role_name' => 'Siswa']);

        SchoolClass::firstOrCreate(
            ['id' => 3],
            ['class_name' => 'XII RPL 1', 'academic_year' => '2026/2027', 'grade_level' => 'XII', 'major' => 'RPL']
        );
    }

    public function test_j1_array_and_object_inputs_return_422_on_web_and_api_login(): void
    {
        // API V1 login with array/object inputs -> 422 (never 500)
        $this->postJson('/api/v1/auth/login', [
            'username' => ['nested', 'array'],
            'password' => ['another' => 'object'],
        ])->assertStatus(422)
          ->assertJsonValidationErrors(['username', 'password']);

        // API V1 login with over-length string (>255) -> 422
        $this->postJson('/api/v1/auth/login', [
            'username' => str_repeat('a', 256),
            'password' => str_repeat('b', 256),
        ])->assertStatus(422)
          ->assertJsonValidationErrors(['username', 'password']);

        // Web login with array inputs (JSON request) -> 422
        $this->postJson('/login', [
            'username' => ['nested'],
            'password' => ['nested'],
        ])->assertStatus(422)
          ->assertJsonValidationErrors(['username', 'password']);

        // Web login with array inputs (HTML form + redirect) -> 302 with session errors, renders 200 (never 500)
        $this->from('/login')->post('/login', [
            'username' => ['nested'],
            'password' => ['nested'],
        ])->assertRedirect('/login')
          ->assertSessionHasErrors(['username', 'password']);

        $this->get('/login')->assertOk();
    }

    public function test_j1_invalid_credentials_preserve_exact_error_messages_and_rate_limiting(): void
    {
        $user = User::create([
            'username'             => 'waka_kurikulum',
            'fullname'             => 'waka_kurikulum, M.Pd',
            'role_id'              => 1,
            'password'             => Hash::make(Str::random(24), ['rounds' => 4]),
            'must_change_password' => false,
        ]);

        // Web wrong credentials -> exact trans('auth.failed')
        $this->from('/login')->post('/login', [
            'username' => $user->username,
            'password' => Str::random(16),
        ])->assertRedirect('/login')
          ->assertSessionHasErrors([
              'username' => trans('auth.failed'),
          ]);

        RateLimiter::clear('waka_kurikulum|127.0.0.1');

        // API V1 wrong credentials -> exact message 'Username atau password salah.' (401)
        for ($i = 1; $i <= 5; $i++) {
            $this->postJson('/api/v1/auth/login', [
                'username' => $user->username,
                'password' => Str::random(16),
            ])->assertStatus(401)
              ->assertJsonPath('message', 'Username atau password salah.');
        }

        // 6th attempt -> 429 Too Many Requests (throttle preserved)
        $this->postJson('/api/v1/auth/login', [
            'username' => $user->username,
            'password' => Str::random(16),
        ])->assertStatus(429);
    }

    public function test_j1_five_protected_accounts_can_login_via_web_and_api(): void
    {
        $accounts = [
            ['id' => 1,    'username' => 'waka_kurikulum',   'fullname' => 'waka_kurikulum, M.Pd', 'role_id' => 1, 'class_id' => null],
            ['id' => 1723, 'username' => 'haris-budiawan',   'fullname' => 'HARIS BUDIAWAN, S.Pd',     'role_id' => 2, 'class_id' => null],
            ['id' => 1721, 'username' => 'ryawidyaningtyas', 'fullname' => 'RYAWIDYANINGTYAS, S.Pd',   'role_id' => 2, 'class_id' => null],
            ['id' => 4,    'username' => 'tim_kesiswaan',    'fullname' => 'tim_kesiswaan',       'role_id' => 3, 'class_id' => null],
            ['id' => 1164, 'username' => '17766',            'fullname' => '17766',    'role_id' => 4, 'class_id' => 3],
        ];

        $envPath = storage_path('app/private/protected-verify.env');
        $env = [];
        if (is_file($envPath)) {
            foreach (file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
                $line = trim($line);
                if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
                    continue;
                }
                [$k, $v] = explode('=', $line, 2);
                $env[trim($k)] = trim(trim($v), "\"'");
            }
        }

        $envKeys = [
            'waka_kurikulum'   => 'WAKA_PASSWORD',
            'haris-budiawan'   => 'HARIS_PASSWORD',
            'ryawidyaningtyas' => 'RYAWIDYANINGTYAS_PASSWORD',
            'tim_kesiswaan'    => 'TIM_KESISWAAN_PASSWORD',
            '17766'            => 'SISWA_17766_PASSWORD',
        ];

        foreach ($accounts as $acct) {
            $pw = $env[$envKeys[$acct['username']]] ?? Str::random(16);
            User::create([
                'id'                   => $acct['id'],
                'username'             => $acct['username'],
                'fullname'             => $acct['fullname'],
                'role_id'              => $acct['role_id'],
                'class_id'             => $acct['class_id'],
                'password'             => Hash::make($pw, ['rounds' => 4]),
                'must_change_password' => false,
            ]);

            $this->post('/login', [
                'username' => $acct['username'],
                'password' => $pw,
            ])->assertRedirect('/dashboard');

            $this->post('/logout');

            $this->postJson('/api/v1/auth/login', [
                'username'    => $acct['username'],
                'password'    => $pw,
                'device_name' => 'j1-test',
            ])->assertOk()
              ->assertJsonPath('data.must_change_password', false);
        }
    }
}
