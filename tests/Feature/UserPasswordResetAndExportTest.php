<?php

namespace Tests\Feature;

use App\Models\CounselorAssignment;
use App\Models\HomeroomAssignment;
use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\Support\ScpTestHelpers;
use Tests\TestCase;

class UserPasswordResetAndExportTest extends TestCase
{
    use RefreshDatabase;
    use ScpTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
    }

    public function test_waka_single_teacher_reset_generates_random_password_revokes_tokens_and_exports_csv(): void
    {
        Storage::fake('local');

        $waka = $this->makeUser(1);
        $teacher = $this->makeUser(2, [
            'username'             => 'guru-contoh-satu',
            'fullname'             => 'GURU CONTOH SATU, S.Pd',
            'nip'                  => '198001012005011001',
            'password'             => Hash::make('OldPass123!'),
            'must_change_password' => false,
        ]);

        // Issue a Sanctum token that must be revoked upon reset
        $teacher->createToken('ios-device');
        $this->assertSame(1, $teacher->tokens()->count());

        $response = $this->actingAs($waka)
            ->from(route('users.index'))
            ->patch(route('users.reset-password', $teacher));

        $response->assertRedirect(route('users.index'));
        $response->assertSessionHas('reset_credentials');
        $response->assertSessionHas('reset_csv_path');

        $creds = session('reset_credentials');
        $csvPath = session('reset_csv_path');

        $this->assertCount(1, $creds);
        $tempPassword = $creds[0]['temporary_password'];
        $this->assertNotEmpty($tempPassword);
        $this->assertNotSame('guru-contoh-satu', $tempPassword);
        $this->assertNotSame('OldPass123!', $tempPassword);

        $teacher->refresh();
        $this->assertTrue((bool) $teacher->must_change_password);
        $this->assertTrue(Hash::check($tempPassword, $teacher->password));
        $this->assertSame(0, $teacher->tokens()->count());

        Storage::disk('local')->assertExists($csvPath);
        $csvContent = Storage::disk('local')->get($csvPath);
        $this->assertStringContainsString('guru-contoh-satu', $csvContent);
        $this->assertStringContainsString($tempPassword, $csvContent);
    }

    public function test_waka_bulk_teacher_reset_randomizes_all_teachers_revokes_tokens_and_exports_csv(): void
    {
        Storage::fake('local');

        $waka = $this->makeUser(1);
        $t1 = $this->makeUser(2, ['username' => 'guru_satu', 'fullname' => 'GURU SATU', 'must_change_password' => false]);
        $t2 = $this->makeUser(2, ['username' => 'guru_dua', 'fullname' => 'GURU DUA', 'must_change_password' => false]);
        $t1->createToken('token-1');
        $t2->createToken('token-2');

        // Non-waka cannot run bulk reset
        $this->actingAs($t1)
            ->post(route('users.bulk-reset-passwords'))
            ->assertStatus(403);

        $response = $this->actingAs($waka)
            ->from(route('users.index'))
            ->post(route('users.bulk-reset-passwords'));

        $response->assertRedirect(route('users.index'));
        $creds = session('reset_credentials');
        $csvPath = session('reset_csv_path');

        $this->assertCount(2, $creds);
        $this->assertNotSame($creds[0]['temporary_password'], $creds[1]['temporary_password']);

        $this->assertSame(0, $t1->fresh()->tokens()->count());
        $this->assertSame(0, $t2->fresh()->tokens()->count());
        $this->assertTrue((bool) $t1->fresh()->must_change_password);
        $this->assertTrue((bool) $t2->fresh()->must_change_password);

        Storage::disk('local')->assertExists($csvPath);
    }

    public function test_students_reset_imported_passwords_command_dry_run_and_apply(): void
    {
        Storage::fake('local');

        $student = $this->makeUser(4, [
            'username'             => '99001',
            'fullname'             => 'SISWA UJI',
            'password'             => Hash::make('99001'),
            'must_change_password' => true,
        ]);
        $student->createToken('student-token');

        // Dry run does not modify password or revoke tokens
        $this->artisan('students:reset-imported-passwords')
            ->expectsOutputToContain('DRY-RUN completed')
            ->assertSuccessful();

        $this->assertTrue(Hash::check('99001', $student->fresh()->password));
        $this->assertSame(1, $student->fresh()->tokens()->count());

        // Apply randomizes password, revokes token, and exports CSV
        $this->artisan('students:reset-imported-passwords --apply')
            ->expectsOutputToContain('Successfully randomized passwords for 1 students')
            ->assertSuccessful();

        $this->assertFalse(Hash::check('99001', $student->fresh()->password));
        $this->assertTrue((bool) $student->fresh()->must_change_password);
        $this->assertSame(0, $student->fresh()->tokens()->count());

        $files = Storage::disk('local')->files('credentials');
        $this->assertNotEmpty($files);
    }

    public function test_setup_demo_accounts_command_from_env(): void
    {
        $class = SchoolClass::create(['class_name' => 'X RPL 1', 'academic_year' => '2026/2027']);
        $waka = $this->makeUser(1, ['username' => 'waka_demo']);
        $guru = $this->makeUser(2, ['username' => 'guru-contoh-satu']);
        $wali = $this->makeUser(2, ['username' => 'wali-demo']);
        HomeroomAssignment::create(['user_id' => $wali->id, 'class_id' => $class->id, 'academic_year' => '2026/2027']);
        $bk = $this->makeUser(3, ['username' => 'sri-wahyuningsih']);
        CounselorAssignment::create(['user_id' => $bk->id, 'class_id' => $class->id, 'academic_year' => '2026/2027']);
        $siswa = $this->makeUser(4, ['username' => '99002', 'class_id' => $class->id]);

        putenv('DEMO_DEFAULT_PASSWORD=EnvSuppliedPass#2026');
        putenv('DEMO_WAKA_USERNAME=waka_demo');
        putenv('DEMO_WALI_USERNAME=wali-demo');
        putenv('DEMO_SISWA_USERNAME=99002');

        $this->artisan('accounts:setup-demo --apply')
            ->expectsOutputToContain('Successfully configured 5 demo tester accounts')
            ->assertSuccessful();

        $this->assertTrue(Hash::check('EnvSuppliedPass#2026', $waka->fresh()->password));
        $this->assertTrue(Hash::check('EnvSuppliedPass#2026', $guru->fresh()->password));
        $this->assertTrue(Hash::check('EnvSuppliedPass#2026', $wali->fresh()->password));
        $this->assertTrue(Hash::check('EnvSuppliedPass#2026', $bk->fresh()->password));
        $this->assertTrue(Hash::check('EnvSuppliedPass#2026', $siswa->fresh()->password));

        putenv('DEMO_DEFAULT_PASSWORD');
        putenv('DEMO_WAKA_USERNAME');
        putenv('DEMO_WALI_USERNAME');
        putenv('DEMO_SISWA_USERNAME');
    }
}
