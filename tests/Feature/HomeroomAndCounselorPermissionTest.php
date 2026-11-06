<?php

namespace Tests\Feature;

use App\Models\CounselorAssignment;
use App\Models\HomeroomAssignment;
use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\ScpTestHelpers;
use Tests\TestCase;

class HomeroomAndCounselorPermissionTest extends TestCase
{
    use RefreshDatabase;
    use ScpTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
    }

    private function makeLeave(User $student): LeaveRequest
    {
        return LeaveRequest::create([
            'student_id'      => $student->id,
            'applicant_id'    => $student->id,
            'start_date'      => now()->addDay()->toDateString(),
            'end_date'        => now()->addDays(2)->toDateString(),
            'reason'          => 'Izin keperluan keluarga',
            'status_approval' => 'Pending',
        ]);
    }

    public function test_wali_x_rpl_1_only_sees_their_class_on_web_and_api(): void
    {
        $classA = $this->makeClass('X RPL 1');
        $classB = $this->makeClass('X TKJ 1');

        $studentA = $this->makeUser(4, ['class_id' => $classA->id]);
        $studentB = $this->makeUser(4, ['class_id' => $classB->id]);

        $leaveA = $this->makeLeave($studentA);
        $leaveB = $this->makeLeave($studentB);

        // Teacher (role 2) assigned as Homeroom Teacher (Wali Kelas) of Class A
        $waliA = $this->makeUser(2, ['fullname' => 'Pak Budi Guru Wali']);
        HomeroomAssignment::create([
            'user_id'  => $waliA->id,
            'class_id' => $classA->id,
        ]);

        // === WEB VERIFICATION ===
        $this->actingAs($waliA)->get(route('leave-requests.index'))
            ->assertOk()
            ->assertSee($studentA->fullname)
            ->assertDontSee($studentB->fullname);

        $this->actingAs($waliA)->get(route('leave-requests.show', $leaveA))->assertOk();
        $this->actingAs($waliA)->get(route('leave-requests.show', $leaveB))->assertForbidden();

        $this->actingAs($waliA)->patch(route('leave-requests.update-status', $leaveB), [
            'status_approval' => 'Approved',
        ])->assertForbidden();

        // === API VERIFICATION ===
        $this->apiAs($waliA)->getJson('/api/v1/leave-requests')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $leaveA->id);

        $this->apiAs($waliA)->getJson('/api/v1/leave-requests/' . $leaveA->id)->assertOk();
        $this->apiAs($waliA)->getJson('/api/v1/leave-requests/' . $leaveB->id)->assertForbidden();

        $this->apiAs($waliA)->patchJson('/api/v1/leave-requests/' . $leaveB->id . '/status', [
            'status_approval' => 'Approved',
        ])->assertForbidden();
    }

    public function test_counselor_sees_all_assigned_classes_and_no_others_on_web_and_api(): void
    {
        $classA = $this->makeClass('X RPL 1');
        $classB = $this->makeClass('X RPL 2');
        $classC = $this->makeClass('X TKJ 1');

        $studentA = $this->makeUser(4, ['class_id' => $classA->id]);
        $studentB = $this->makeUser(4, ['class_id' => $classB->id]);
        $studentC = $this->makeUser(4, ['class_id' => $classC->id]);

        $leaveA = $this->makeLeave($studentA);
        $leaveB = $this->makeLeave($studentB);
        $leaveC = $this->makeLeave($studentC);

        // Teacher (role 2) assigned as Counselor (BK) for Class A and Class B
        $counselor = $this->makeUser(2, ['fullname' => 'Bu Guru BK']);
        CounselorAssignment::create(['user_id' => $counselor->id, 'class_id' => $classA->id]);
        CounselorAssignment::create(['user_id' => $counselor->id, 'class_id' => $classB->id]);

        // === WEB VERIFICATION ===
        $this->actingAs($counselor)->get(route('leave-requests.index'))
            ->assertOk()
            ->assertSee($studentA->fullname)
            ->assertSee($studentB->fullname)
            ->assertDontSee($studentC->fullname);

        $this->actingAs($counselor)->get(route('leave-requests.show', $leaveA))->assertOk();
        $this->actingAs($counselor)->get(route('leave-requests.show', $leaveB))->assertOk();
        $this->actingAs($counselor)->get(route('leave-requests.show', $leaveC))->assertForbidden();

        // === API VERIFICATION ===
        $this->apiAs($counselor)->getJson('/api/v1/leave-requests')
            ->assertOk()
            ->assertJsonCount(2, 'data');

        $this->apiAs($counselor)->getJson('/api/v1/leave-requests/' . $leaveC->id)->assertForbidden();
        $this->apiAs($counselor)->patchJson('/api/v1/leave-requests/' . $leaveC->id . '/status', [
            'status_approval' => 'Approved',
        ])->assertForbidden();
    }

    public function test_regular_teacher_without_assignments_cannot_access_leave_management(): void
    {
        $regularTeacher = $this->makeUser(2);

        $this->actingAs($regularTeacher)->get(route('leave-requests.index'))->assertForbidden();
        $this->apiAs($regularTeacher)->getJson('/api/v1/leave-requests')->assertForbidden();
    }

    public function test_teacher_with_homeroom_has_dual_access_guru_and_kesiswaan(): void
    {
        $class = $this->makeClass('X RPL 1');
        $teacher = $this->makeUser(2);
        HomeroomAssignment::create(['user_id' => $teacher->id, 'class_id' => $class->id]);

        // Guru KBM access
        $this->actingAs($teacher)->get(route('schedules.index'))->assertOk();
        $this->apiAs($teacher)->getJson('/api/v1/journals/available-schedules?date=' . now()->toDateString())->assertOk();

        // Kesiswaan leave access
        $this->actingAs($teacher)->get(route('leave-requests.index'))->assertOk();
        $this->apiAs($teacher)->getJson('/api/v1/leave-requests')->assertOk();
    }
}
