<?php

namespace Tests\Feature;

use App\Models\CounselorAssignment;
use App\Models\HomeroomAssignment;
use App\Models\Schedule;
use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\Support\ScpTestHelpers;
use Tests\TestCase;

class StudentLoginCardsAndPasswordResetE4Test extends TestCase
{
    use RefreshDatabase;
    use ScpTestHelpers;

    private SchoolClass $classA;
    private SchoolClass $classB;
    private User $waka;
    private User $waliA;
    private User $waliB;
    private User $bkA;
    private User $guruPengajarA;
    private User $siswaAUnchanged;
    private User $siswaAChanged;
    private User $siswaB;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->seedRoles();

        $this->classA = SchoolClass::create(['class_name' => 'X RPL 1', 'academic_year' => '2026/2027', 'grade_level' => 'X', 'major' => 'RPL']);
        $this->classB = SchoolClass::create(['class_name' => 'X TKJ 1', 'academic_year' => '2026/2027', 'grade_level' => 'X', 'major' => 'TKJ']);

        $this->waka = $this->makeUser(1, ['username' => 'waka', 'fullname' => 'Waka Kurikulum']);

        $this->waliA = $this->makeUser(2, ['username' => 'wali_a', 'fullname' => 'Wali Kelas A']);
        HomeroomAssignment::create(['user_id' => $this->waliA->id, 'class_id' => $this->classA->id, 'academic_year' => '2026/2027']);

        $this->waliB = $this->makeUser(2, ['username' => 'wali_b', 'fullname' => 'Wali Kelas B']);
        HomeroomAssignment::create(['user_id' => $this->waliB->id, 'class_id' => $this->classB->id, 'academic_year' => '2026/2027']);

        $this->bkA = $this->makeUser(3, ['username' => 'bk_a', 'fullname' => 'Guru BK A']);
        CounselorAssignment::create(['user_id' => $this->bkA->id, 'class_id' => $this->classA->id, 'academic_year' => '2026/2027']);

        $this->guruPengajarA = $this->makeUser(2, ['username' => 'guru_a', 'fullname' => 'Guru Pengajar A']);
        Schedule::create([
            'teacher_id'   => $this->guruPengajarA->id,
            'class_id'     => $this->classA->id,
            'subject'      => 'Basis Data',
            'day_of_week'  => 'Senin',
            'start_time'   => '07:15:00',
            'end_time'     => '08:45:00',
            'start_period' => 1,
            'end_period'   => 2,
            'cycle_type'   => 'Putaran 2',
        ]);

        $this->siswaAUnchanged = $this->makeUser(4, [
            'username'             => '99001',
            'fullname'             => 'Siswa A Belum Ganti',
            'class_id'             => $this->classA->id,
            'must_change_password' => true,
            'password'             => Hash::make('99001', ['rounds' => 4]),
        ]);

        $this->siswaAChanged = $this->makeUser(4, [
            'username'             => '17766',
            'fullname'             => 'Siswa A Sudah Ganti',
            'class_id'             => $this->classA->id,
            'must_change_password' => false,
            'password'             => Hash::make('CustomSecret123!', ['rounds' => 4]),
        ]);

        $this->siswaB = $this->makeUser(4, [
            'username'             => '99002',
            'fullname'             => 'Siswa B Belum Ganti',
            'class_id'             => $this->classB->id,
            'must_change_password' => true,
            'password'             => Hash::make('99002', ['rounds' => 4]),
        ]);
    }

    public function test_reset_imported_student_passwords_skips_already_changed_and_wali_a_cannot_download_class_b_login_cards(): void
    {
        $oldHashChanged = $this->siswaAChanged->password;
        $oldHashUnchanged = $this->siswaAUnchanged->password;

        // 1. Dry-run does not modify passwords
        $this->artisan('students:reset-imported-passwords --dry-run')->assertExitCode(0);
        $this->assertSame($oldHashUnchanged, $this->siswaAUnchanged->fresh()->password);

        // 2. Apply resets only must_change_password=true (2 targets, 1 skipped)
        $this->artisan('students:reset-imported-passwords --apply')->assertExitCode(0);
        $this->assertNotSame($oldHashUnchanged, $this->siswaAUnchanged->fresh()->password);
        $this->assertSame($oldHashChanged, $this->siswaAChanged->fresh()->password);
        $this->assertFalse((bool) $this->siswaAChanged->fresh()->must_change_password);
        Storage::disk('local')->assertExists('credentials/student_credentials_latest.csv');

        // 3. Wali Kelas A can download login cards for Class A (reusing exported credentials)
        $resWaliA = $this->actingAs($this->waliA)
            ->get(route('classes.login-cards', $this->classA))
            ->assertOk();
        $csvContent = $resWaliA->streamedContent();
        $this->assertStringContainsString('99001,"Siswa A Belum Ganti","X RPL 1"', $csvContent);
        $this->assertStringContainsString('17766,"Siswa A Sudah Ganti","X RPL 1","(Sudah Diganti)"', $csvContent);

        // 4. Wali Kelas A CANNOT download login cards for Class B (403 on Web and API)
        $this->actingAs($this->waliA)->get(route('classes.login-cards', $this->classB))->assertStatus(403);
        $this->apiAs($this->waliA)->getJson("/api/v1/classes/{$this->classB->id}/login-cards")->assertStatus(403);

        // 5. BK, regular teaching teacher, and student get 403 on login cards
        $this->actingAs($this->bkA)->get(route('classes.login-cards', $this->classA))->assertStatus(403);
        $this->apiAs($this->bkA)->getJson("/api/v1/classes/{$this->classA->id}/login-cards")->assertStatus(403);
        $this->actingAs($this->guruPengajarA)->get(route('classes.login-cards', $this->classA))->assertStatus(403);
        $this->apiAs($this->guruPengajarA)->getJson("/api/v1/classes/{$this->classA->id}/login-cards")->assertStatus(403);
        $this->actingAs($this->siswaAChanged)->get(route('classes.login-cards', $this->classA))->assertStatus(403);
        $this->apiAs($this->siswaAChanged)->getJson("/api/v1/classes/{$this->classA->id}/login-cards")->assertStatus(403);

        // 6. Waka can download login cards for both Class A and Class B
        $this->actingAs($this->waka)->get(route('classes.login-cards', $this->classA))->assertOk();
        $this->apiAs($this->waka)->get("/api/v1/classes/{$this->classB->id}/login-cards")->assertOk();
    }
}
