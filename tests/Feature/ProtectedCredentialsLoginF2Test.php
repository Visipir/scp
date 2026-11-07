<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class ProtectedCredentialsLoginF2Test extends TestCase
{
    use RefreshDatabase;

    /** @var array<string, string> */
    private array $runtimePasswords = [];

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['id' => 1], ['role_name' => 'Waka Kurikulum']);
        Role::firstOrCreate(['id' => 2], ['role_name' => 'Guru']);
        Role::firstOrCreate(['id' => 3], ['role_name' => 'Tim Kesiswaan']);
        Role::firstOrCreate(['id' => 4], ['role_name' => 'Siswa']);

        $class = SchoolClass::create([
            'id'            => 3,
            'class_name'    => 'XII RPL 1',
            'academic_year' => '2026/2027',
            'grade_level'   => 'XII',
            'major'         => 'RPL',
        ]);

        // Load from storage/app/private/protected-verify.env if provided by operator; otherwise generate runtime random secrets
        $envPasswords = $this->loadProtectedVerifyEnv();

        foreach (User::protectedUsernames() as $uname) {
            $this->runtimePasswords[$uname] = $envPasswords[$uname] ?? Str::random(20);
        }

        User::create([
            'id'                   => 1,
            'username'             => 'waka_kurikulum',
            'fullname'             => 'waka_kurikulum, M.Pd',
            'role_id'              => 1,
            'password'             => Hash::make($this->runtimePasswords['waka_kurikulum'], ['rounds' => 4]),
            'must_change_password' => false,
        ]);

        User::create([
            'id'                   => 4,
            'username'             => 'tim_kesiswaan',
            'fullname'             => 'tim_kesiswaan',
            'role_id'              => 3,
            'password'             => Hash::make($this->runtimePasswords['tim_kesiswaan'], ['rounds' => 4]),
            'must_change_password' => false,
        ]);

        User::create([
            'id'                   => 1164,
            'username'             => '17766',
            'fullname'             => '17766',
            'role_id'              => 4,
            'class_id'             => $class->id,
            'password'             => Hash::make($this->runtimePasswords['17766'], ['rounds' => 4]),
            'must_change_password' => false,
        ]);

        User::create([
            'id'                   => 1721,
            'username'             => 'ryawidyaningtyas',
            'fullname'             => 'RYAWIDYANINGTYAS, S.Pd',
            'role_id'              => 2,
            'password'             => Hash::make($this->runtimePasswords['ryawidyaningtyas'], ['rounds' => 4]),
            'must_change_password' => true, // starts true before accounts:protect-credentials --apply
        ]);

        User::create([
            'id'                   => 1723,
            'username'             => 'haris-budiawan',
            'fullname'             => 'HARIS BUDIAWAN, S.Pd',
            'role_id'              => 2,
            'password'             => Hash::make($this->runtimePasswords['haris-budiawan'], ['rounds' => 4]),
            'must_change_password' => true, // starts true before accounts:protect-credentials --apply
        ]);
    }

    /**
     * Parse storage/app/private/protected-verify.env if present without ever logging or printing values.
     *
     * @return array<string, string>
     */
    private function loadProtectedVerifyEnv(): array
    {
        $path = storage_path('app/private/protected-verify.env');
        if (!File::exists($path)) {
            return [];
        }

        $lines = preg_split('/\r\n|\r|\n/', (string) File::get($path));
        $raw = [];
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
                continue;
            }
            [$k, $v] = explode('=', $line, 2);
            $k = trim($k);
            $v = trim($v, " \t\n\r\0\x0B\"'");
            if ($v !== '') {
                $raw[$k] = $v;
            }
        }

        $keyMap = [
            'waka_kurikulum'   => ['WAKA_PASSWORD', 'PROTECTED_WAKA_PASSWORD', 'WAKA_KURIKULUM_PASSWORD', 'waka_kurikulum'],
            'haris-budiawan'   => ['HARIS_PASSWORD', 'PROTECTED_HARIS_PASSWORD', 'HARIS_BUDIAWAN_PASSWORD', 'haris-budiawan'],
            'ryawidyaningtyas' => ['RYAWIDYANINGTYAS_PASSWORD', 'PROTECTED_RYA_PASSWORD', 'ryawidyaningtyas'],
            'tim_kesiswaan'    => ['TIM_KESISWAAN_PASSWORD', 'PROTECTED_TIM_KESISWAAN_PASSWORD', 'tim_kesiswaan'],
            '17766'            => ['SISWA_17766_PASSWORD', 'PROTECTED_17766_PASSWORD', '17766'],
        ];

        $resolved = [];
        foreach ($keyMap as $username => $candidateKeys) {
            foreach ($candidateKeys as $ck) {
                if (!empty($raw[$ck])) {
                    $resolved[$username] = $raw[$ck];
                    break;
                }
            }
        }

        return $resolved;
    }

    public function test_protect_credentials_command_dry_run_and_apply_preserve_password_hashes(): void
    {
        $nonProtected = User::create([
            'username'             => 'guru-lain',
            'fullname'             => 'GURU LAIN, S.Pd',
            'role_id'              => 2,
            'password'             => Hash::make(Str::random(16), ['rounds' => 4]),
            'must_change_password' => true,
        ]);

        $hashesBefore = User::whereIn('username', User::protectedUsernames())
            ->get()
            ->mapWithKeys(fn (User $u) => [$u->username => hash('sha256', (string) $u->password)])
            ->all();

        // 1. Dry-run does not modify must_change_password
        $this->artisan('accounts:protect-credentials', ['--dry-run' => true])
            ->expectsOutputToContain('DRY-RUN completed: 2 row(s) currently have must_change_password=1')
            ->assertExitCode(0);

        $this->assertTrue((bool) User::where('username', 'haris-budiawan')->value('must_change_password'));
        $this->assertTrue((bool) User::where('username', 'ryawidyaningtyas')->value('must_change_password'));

        // 2. Apply sets must_change_password=0 on protected accounts only, keeping hashes identical
        $this->artisan('accounts:protect-credentials', ['--apply' => true])
            ->expectsOutputToContain('Completed APPLY: 2 row(s) had must_change_password updated to false (0)')
            ->assertExitCode(0);

        foreach (User::protectedUsernames() as $uname) {
            $u = User::where('username', $uname)->firstOrFail();
            $this->assertFalse((bool) $u->must_change_password, "Account {$uname} must have must_change_password=false");
            $this->assertSame($hashesBefore[$uname], hash('sha256', (string) $u->password));
        }

        // Non-protected account remains must_change_password=true
        $this->assertTrue((bool) $nonProtected->fresh()->must_change_password);
    }

    public function test_web_and_api_login_for_all_five_protected_accounts_never_redirect_or_flag_password_change(): void
    {
        // Run apply first
        $this->artisan('accounts:protect-credentials', ['--apply' => true])->assertExitCode(0);

        foreach (User::protectedUsernames() as $uname) {
            $secret = $this->runtimePasswords[$uname];

            // 1. Web login -> redirects to /dashboard, and GET /dashboard returns 200 (never redirected to first-change)
            $loginResp = $this->post('/login', [
                'username' => $uname,
                'password' => $secret,
            ]);
            $loginResp->assertRedirect('/dashboard');

            $dashResp = $this->get('/dashboard');
            $dashResp->assertOk();

            $this->post('/logout');

            // 2. API login -> returns 200 with must_change_password=false, and protected API endpoints succeed (not 403 must_change_password)
            $apiLogin = $this->postJson('/api/v1/auth/login', [
                'username'    => $uname,
                'password'    => $secret,
                'device_name' => 'phpunit-f2',
            ]);
            $apiLogin->assertOk()
                ->assertJsonPath('data.must_change_password', false)
                ->assertJsonPath('data.user.must_change_password', false);

            $token = $apiLogin->json('data.token');
            $this->assertNotEmpty($token);

            $this->withHeader('Authorization', 'Bearer ' . $token)
                ->getJson('/api/v1/dashboard')
                ->assertOk();
        }
    }
}
