<?php

namespace Tests\Feature;

use App\Console\Commands\PurgeDummyAccountsCommand;
use App\Models\Role;
use App\Models\SchoolClass;
use App\Models\User;
use App\Services\EmployeeImportService;
use App\Services\StudentImportService;
use App\Services\TeacherEmailImportService;
use App\Services\UserService;
use Database\Seeders\RealAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class ProtectedAccountsF1Test extends TestCase
{
    use RefreshDatabase;

    private SchoolClass $classRpl1;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');

        Role::firstOrCreate(['id' => 1], ['role_name' => 'Waka Kurikulum']);
        Role::firstOrCreate(['id' => 2], ['role_name' => 'Guru']);
        Role::firstOrCreate(['id' => 3], ['role_name' => 'Tim Kesiswaan']);
        Role::firstOrCreate(['id' => 4], ['role_name' => 'Siswa']);

        $this->classRpl1 = SchoolClass::create([
            'id'            => 3,
            'class_name'    => 'XII RPL 1',
            'academic_year' => '2026/2027',
            'grade_level'   => 'XII',
            'major'         => 'RPL',
        ]);

        // Seed the 5 protected accounts with unique runtime-generated random password hashes (never hardcoded)
        User::create([
            'id'                   => 1,
            'username'             => 'waka_kurikulum',
            'fullname'             => 'Pak Budi Kurikulum, M.Pd',
            'role_id'              => 1,
            'password'             => Hash::make(Str::random(20), ['rounds' => 4]),
            'must_change_password' => false,
        ]);

        User::create([
            'id'                   => 4,
            'username'             => 'tim_kesiswaan',
            'fullname'             => 'Pak Rudi Kesiswaan',
            'role_id'              => 3,
            'password'             => Hash::make(Str::random(20), ['rounds' => 4]),
            'must_change_password' => false,
        ]);

        User::create([
            'id'                   => 1164,
            'username'             => '17766',
            'fullname'             => '17766',
            'role_id'              => 4,
            'class_id'             => $this->classRpl1->id,
            'password'             => Hash::make(Str::random(20), ['rounds' => 4]),
            'must_change_password' => false,
        ]);

        User::create([
            'id'                   => 1721,
            'username'             => 'ryawidyaningtyas',
            'fullname'             => 'RYAWIDYANINGTYAS, S.Pd',
            'role_id'              => 2,
            'password'             => Hash::make(Str::random(20), ['rounds' => 4]),
            'must_change_password' => false,
        ]);

        User::create([
            'id'                   => 1723,
            'username'             => 'haris-budiawan',
            'fullname'             => 'HARIS BUDIAWAN, S.Pd',
            'role_id'              => 2,
            'password'             => Hash::make(Str::random(20), ['rounds' => 4]),
            'must_change_password' => false,
        ]);
    }

    /**
     * Capture fingerprint snapshot of all 5 protected accounts.
     */
    private function snapshotProtectedAccounts(): array
    {
        return User::whereIn('username', User::protectedUsernames())
            ->orderBy('username')
            ->get()
            ->mapWithKeys(fn (User $u) => [
                $u->username => [
                    'id'                   => (int) $u->id,
                    'username'             => $u->username,
                    'fullname'             => $u->fullname,
                    'role_id'              => (int) $u->role_id,
                    'password_sha256'      => hash('sha256', (string) $u->password),
                    'must_change_password' => (bool) $u->must_change_password,
                ],
            ])
            ->all();
    }

    public function test_config_and_is_protected_account_identify_all_five_accounts(): void
    {
        $expected = ['waka_kurikulum', 'haris-budiawan', 'ryawidyaningtyas', 'tim_kesiswaan', '17766'];
        $this->assertSame($expected, User::protectedUsernames());

        foreach ($expected as $username) {
            $user = User::where('username', $username)->firstOrFail();
            $this->assertTrue($user->isProtectedAccount());
            $this->assertTrue(User::isProtectedUsername($username));
        }

        $nonProtected = User::create([
            'username'             => 'guru-biasa',
            'fullname'             => 'GURU BIASA, S.Pd',
            'role_id'              => 2,
            'password'             => Hash::make(Str::random(16), ['rounds' => 4]),
            'must_change_password' => true,
        ]);
        $this->assertFalse($nonProtected->isProtectedAccount());
    }

    public function test_reset_imported_teacher_passwords_command_skips_protected_teachers(): void
    {
        $regularTeacher = User::create([
            'username'             => 'guru-reguler',
            'fullname'             => 'GURU REGULER, S.Pd',
            'role_id'              => 2,
            'password'             => Hash::make(Str::random(16), ['rounds' => 4]),
            'must_change_password' => false,
        ]);
        $beforeRegularHash = $regularTeacher->password;
        $beforeProtected = $this->snapshotProtectedAccounts();

        putenv('SEED_DEFAULT_PASSWORD=' . Str::random(16));
        $this->artisan('teachers:reset-imported-passwords', ['--apply' => true])
            ->expectsOutputToContain('Protected Skipped: 2')
            ->assertExitCode(0);

        $this->assertSame($beforeProtected, $this->snapshotProtectedAccounts());
        $this->assertNotSame($beforeRegularHash, $regularTeacher->fresh()->password);
        $this->assertTrue((bool) $regularTeacher->fresh()->must_change_password);
    }

    public function test_bulk_reset_and_single_reset_temporary_password_protect_accounts(): void
    {
        $waka = User::where('username', 'waka_kurikulum')->firstOrFail();
        $haris = User::where('username', 'haris-budiawan')->firstOrFail();
        $regularTeacher = User::create([
            'username'             => 'guru-reguler-2',
            'fullname'             => 'GURU REGULER 2, S.Pd',
            'role_id'              => 2,
            'password'             => Hash::make(Str::random(16), ['rounds' => 4]),
            'must_change_password' => false,
        ]);

        $beforeProtected = $this->snapshotProtectedAccounts();
        $userService = app(UserService::class);

        // 1. Bulk reset skips haris-budiawan & ryawidyaningtyas
        $bulkResult = $userService->bulkResetTeacherPasswords($waka);
        $this->assertSame(1, $bulkResult['count']);
        $this->assertEqualsCanonicalizing(['haris-budiawan', 'ryawidyaningtyas'], $bulkResult['skipped_protected']);
        $this->assertSame($beforeProtected, $this->snapshotProtectedAccounts());

        // 2. Single resetTemporaryPassword / resetPasswordWithTempCredential / resetPassword aborts with 422
        foreach (User::whereIn('username', User::protectedUsernames())->get() as $protectedUser) {
            try {
                $userService->resetTemporaryPassword($waka, $protectedUser);
                $this->fail("Expected HttpException when resetting protected user {$protectedUser->username}");
            } catch (HttpException $e) {
                $this->assertSame(422, $e->getStatusCode());
                $this->assertStringContainsString('akun terlindung', $e->getMessage());
            }

            try {
                $userService->resetPassword($waka, $protectedUser);
                $this->fail("Expected HttpException when resetting protected user {$protectedUser->username}");
            } catch (HttpException $e) {
                $this->assertSame(422, $e->getStatusCode());
            }
        }

        $this->assertSame($beforeProtected, $this->snapshotProtectedAccounts());
    }

    public function test_reset_imported_student_passwords_and_student_import_skip_protected_17766(): void
    {
        $regularStudent = User::create([
            'username'             => '99767',
            'fullname'             => 'Siswa Reguler',
            'role_id'              => 4,
            'class_id'             => $this->classRpl1->id,
            'password'             => Hash::make(Str::random(16), ['rounds' => 4]),
            'must_change_password' => true,
        ]);

        // Even if someone erroneously flipped must_change_password=true in memory/DB before command runs,
        // ResetImportedStudentPasswordsCommand must still skip 17766 because isProtectedAccount() is true.
        $beforeProtected = $this->snapshotProtectedAccounts();

        $this->artisan('students:reset-imported-passwords', ['--apply' => true])
            ->expectsOutputToContain('reason=protected_account')
            ->assertExitCode(0);

        $this->assertSame($beforeProtected, $this->snapshotProtectedAccounts());

        // StudentImportService::provisionStudents must NOT overwrite 17766's fullname, password, role_id, or class_id
        $importService = app(StudentImportService::class);
        $result = $importService->provisionStudents([
            [
                'nis'               => '17766',
                'fullname'          => 'NAMA BERBEDA DARI EXCEL',
                'gender'            => 'L',
                'class_name'        => 'X RPL 1',
                'attendance_number' => 99,
                'academic_year'     => '2026/2027',
            ],
            [
                'nis'               => '99768',
                'fullname'          => 'Siswa Baru Import',
                'gender'            => 'P',
                'class_name'        => 'XII RPL 1',
                'attendance_number' => 12,
                'academic_year'     => '2026/2027',
            ],
        ]);

        $this->assertSame(['17766'], $result['stats']['skipped_protected']);
        $this->assertSame(1, $result['stats']['created']);
        $this->assertSame($beforeProtected, $this->snapshotProtectedAccounts());
    }

    public function test_normalize_usernames_purge_dummy_real_accounts_seeder_and_setup_demo_skip_protected(): void
    {
        $beforeProtected = $this->snapshotProtectedAccounts();

        // 1. NormalizeTeacherUsernamesCommand
        $this->artisan('teachers:normalize-usernames', ['--apply' => true])
            ->expectsOutputToContain('Protected Skipped: 2')
            ->assertExitCode(0);
        $this->assertSame($beforeProtected, $this->snapshotProtectedAccounts());

        // 2. PurgeDummyAccountsCommand
        $this->artisan('accounts:purge-dummy', ['--apply' => true])
            ->assertExitCode(0);
        $this->assertSame($beforeProtected, $this->snapshotProtectedAccounts());

        // 3. RealAccountsSeeder
        $randPass = Str::random(16);
        putenv('SEED_DEFAULT_PASSWORD=' . $randPass);
        $_ENV['SEED_DEFAULT_PASSWORD'] = $randPass;
        $_SERVER['SEED_DEFAULT_PASSWORD'] = $randPass;
        $seedDir = storage_path('app/seed');
        if (!File::exists($seedDir)) {
            File::makeDirectory($seedDir, 0755, true);
        }
        $csvPath = $seedDir . '/accounts.csv';
        File::put($csvPath, "username,name,role,email\nwaka_kurikulum,NAMA BARU WAKA,1,waka@smk.id\ntim_kesiswaan,NAMA BARU TIM,3,tim@smk.id\n");

        try {
            app(RealAccountsSeeder::class)->run();
        } finally {
            File::delete($csvPath);
        }
        $this->assertSame($beforeProtected, $this->snapshotProtectedAccounts());

        // 4. SetupDemoAccountsCommand
        putenv('DEMO_DEFAULT_PASSWORD=' . Str::random(16));
        putenv('DEMO_GURU_USERNAME=haris-budiawan');
        putenv('DEMO_BK_USERNAME=ryawidyaningtyas');
        $this->artisan('accounts:setup-demo', ['--apply' => true])
            ->expectsOutputToContain('Protected accounts skipped (5)')
            ->assertExitCode(0);
        $this->assertSame($beforeProtected, $this->snapshotProtectedAccounts());
    }

    public function test_employee_and_teacher_email_imports_preserve_protected_credentials_and_identity(): void
    {
        $beforeProtected = $this->snapshotProtectedAccounts();
        $haris = User::where('username', 'haris-budiawan')->firstOrFail();

        // 1. EmployeeImportService commit on matched/fuzzy protected teacher
        $empToken = 'emp_import_test_' . Str::random(16);
        Cache::put($empToken, [
            'token' => $empToken,
            'rows'  => [
                [
                    'status'       => 'matched',
                    'name'         => 'HARIS BUDIAWAN, S.Pd., M.Kom',
                    'nip'          => '198501012010011001',
                    'email'        => 'haris@smkn.sch.id',
                    'matched_user' => [
                        'id'       => $haris->id,
                        'username' => $haris->username,
                        'fullname' => $haris->fullname,
                        'nip'      => null,
                    ],
                ],
            ],
        ], now()->addHour());

        $empResult = app(EmployeeImportService::class)->commit($empToken);
        $this->assertSame(['haris-budiawan'], $empResult['protected_preserved']);
        $this->assertSame($beforeProtected, $this->snapshotProtectedAccounts());
        $this->assertSame('198501012010011001', $haris->fresh()->nip);
        $this->assertSame('haris@smkn.sch.id', $haris->fresh()->email);

        // 2. TeacherEmailImportService commit: does NOT overwrite existing email on protected account,
        //    and only populates email when currently null (e.g. ryawidyaningtyas)
        $rya = User::where('username', 'ryawidyaningtyas')->firstOrFail();
        $emailToken = 'email_import_test_' . Str::random(16);
        Cache::put($emailToken, [
            'token'   => $emailToken,
            'matched' => [
                [
                    'teacher_id'       => $haris->id,
                    'teacher_username' => $haris->username,
                    'teacher_fullname' => $haris->fullname,
                    'new_email'        => 'overwrite-attempt@smkn.sch.id',
                ],
                [
                    'teacher_id'       => $rya->id,
                    'teacher_username' => $rya->username,
                    'teacher_fullname' => $rya->fullname,
                    'new_email'        => 'rya@smkn.sch.id',
                ],
            ],
        ], now()->addHour());

        $emailResult = app(TeacherEmailImportService::class)->commit($emailToken);
        $this->assertSame(1, $emailResult['updated']);
        $this->assertSame(['haris-budiawan'], $emailResult['skipped_protected']);
        $this->assertSame('haris@smkn.sch.id', $haris->fresh()->email);
        $this->assertSame('rya@smkn.sch.id', $rya->fresh()->email);
        $this->assertSame($beforeProtected, $this->snapshotProtectedAccounts());
    }
}
