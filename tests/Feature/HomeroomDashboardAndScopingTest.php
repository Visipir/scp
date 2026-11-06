<?php

namespace Tests\Feature;

use App\Models\CounselorAssignment;
use App\Models\HomeroomAssignment;
use App\Models\LeaveRequest;
use App\Models\SchoolClass;
use App\Models\SystemConfiguration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\ScpTestHelpers;
use Tests\TestCase;

class HomeroomDashboardAndScopingTest extends TestCase
{
    use RefreshDatabase;
    use ScpTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();

        SystemConfiguration::firstOrCreate([], [
            'active_cycle'       => 'Putaran 2',
            'is_schedule_locked' => false,
        ]);
    }

    public function test_homeroom_teacher_dashboard_and_sidebar_display_dedicated_homeroom_panel()
    {
        $classA = SchoolClass::create(['class_name' => 'X RPL 1', 'academic_year' => '2026/2027']);
        $wali = $this->makeUser(2, ['fullname' => 'Guru Wali Kelas']);
        HomeroomAssignment::create([
            'user_id'  => $wali->id,
            'class_id' => $classA->id,
        ]);

        $studentA = $this->makeUser(4, ['class_id' => $classA->id, 'fullname' => 'Siswa A']);

        // Create leave requests with various BK statuses
        LeaveRequest::create([
            'student_id'      => $studentA->id,
            'applicant_id'    => $studentA->id,
            'start_date'      => now()->toDateString(),
            'end_date'        => now()->toDateString(),
            'reason'          => 'Sakit flu',
            'status_approval' => 'Approved',
        ]);
        LeaveRequest::create([
            'student_id'      => $studentA->id,
            'applicant_id'    => $studentA->id,
            'start_date'      => now()->addDay()->toDateString(),
            'end_date'        => now()->addDay()->toDateString(),
            'reason'          => 'Izin keluarga',
            'status_approval' => 'Pending',
        ]);

        $response = $this->actingAs($wali)->get(route('dashboard'));

        $response->assertOk();
        // Verify homeroom widget exists on dashboard
        $response->assertSee('WALI KELAS');
        $response->assertSee('Kelas Perwalian: X RPL 1');
        $response->assertSee('Cek List Siswa');
        $response->assertSee('Presensi Siswa');
        $response->assertSee('Catatan Siswa');
        $response->assertSee('Izin Siswa Perwalian');
        $response->assertSee('1 Menunggu BK');

        // Verify sidebar navigation links
        $response->assertSee('WALI KELAS (X RPL 1)');
    }

    public function test_counselor_classes_list_and_detail_scoping()
    {
        $classA = SchoolClass::create(['class_name' => 'X RPL 1', 'academic_year' => '2026/2027']);
        $classB = SchoolClass::create(['class_name' => 'X RPL 2', 'academic_year' => '2026/2027']);
        $classC = SchoolClass::create(['class_name' => 'X TKJ 1', 'academic_year' => '2026/2027']);

        $counselor = $this->makeUser(2, ['fullname' => 'Guru BK']);
        CounselorAssignment::create(['user_id' => $counselor->id, 'class_id' => $classA->id]);
        CounselorAssignment::create(['user_id' => $counselor->id, 'class_id' => $classB->id]);

        // Counselor on classes.index only sees Class A and Class B, NOT Class C
        $indexResponse = $this->actingAs($counselor)->get(route('classes.index'));
        $indexResponse->assertOk();
        $indexResponse->assertSee('X RPL 1');
        $indexResponse->assertSee('X RPL 2');
        $indexResponse->assertDontSee('X TKJ 1');

        // Detail of assigned class is accessible
        $this->actingAs($counselor)->get(route('classes.show', $classA))->assertOk();
        $this->actingAs($counselor)->get(route('classes.show', $classB))->assertOk();

        // Detail of unassigned class is forbidden (403)
        $this->actingAs($counselor)->get(route('classes.show', $classC))->assertForbidden();
    }
}
