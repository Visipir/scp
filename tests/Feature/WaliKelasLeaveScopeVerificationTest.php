<?php

namespace Tests\Feature;

use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\Support\ScpTestHelpers;
use Tests\TestCase;

class WaliKelasLeaveScopeVerificationTest extends TestCase
{
    use RefreshDatabase;
    use ScpTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
    }

    private function createLeaveWithDocument(User $student, string $docPath = 'leave-documents/surat.pdf'): LeaveRequest
    {
        Storage::disk('local')->put($docPath, 'ISI DOKUMEN IZIN ASLI');

        return LeaveRequest::create([
            'student_id'      => $student->id,
            'applicant_id'    => $student->id,
            'start_date'      => now()->addDay()->toDateString(),
            'end_date'        => now()->addDays(2)->toDateString(),
            'reason'          => 'Izin keperluan keluarga',
            'status_approval' => 'Pending',
            'document_path'   => $docPath,
        ]);
    }

    /**
     * Test 1: Wali kelas A tidak bisa lihat / approve izin kelas B (Web + API).
     */
    public function test_wali_kelas_a_cannot_view_or_approve_leave_of_class_b_on_web_and_api(): void
    {
        $classA = $this->makeClass('X RPL 1');
        $classB = $this->makeClass('X TKJ 1');

        $studentA = $this->makeUser(4, ['class_id' => $classA->id]);
        $studentB = $this->makeUser(4, ['class_id' => $classB->id]);

        $leaveA = $this->createLeaveWithDocument($studentA, 'leave-documents/a.pdf');
        $leaveB = $this->createLeaveWithDocument($studentB, 'leave-documents/b.pdf');

        $waliA = $this->makeUser(3, ['class_id' => $classA->id]);

        // === WEB VERIFICATION ===
        // Index: Wali A sees Leave A, does NOT see Leave B
        $this->actingAs($waliA)->get(route('leave-requests.index'))
            ->assertOk()
            ->assertSee($studentA->fullname)
            ->assertDontSee($studentB->fullname);

        // Show: Wali A can view Leave A, gets 403 on Leave B
        $this->actingAs($waliA)->get(route('leave-requests.show', $leaveA))->assertOk();
        $this->actingAs($waliA)->get(route('leave-requests.show', $leaveB))->assertForbidden();

        // Approve: Wali A can approve Leave A, gets 403 on Leave B
        $this->actingAs($waliA)->patch(route('leave-requests.update-status', $leaveA), [
            'status_approval' => 'Approved',
        ])->assertRedirect();
        $this->assertSame('Approved', $leaveA->fresh()->status_approval);

        $this->actingAs($waliA)->patch(route('leave-requests.update-status', $leaveB), [
            'status_approval' => 'Approved',
        ])->assertForbidden();
        $this->assertSame('Pending', $leaveB->fresh()->status_approval);

        // === API VERIFICATION ===
        // Index: data count is 1 (only leave A)
        $this->apiAs($waliA)->getJson('/api/v1/leave-requests')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $leaveA->id);

        // Show: 200 on A, 403 on B
        $this->apiAs($waliA)->getJson('/api/v1/leave-requests/' . $leaveA->id)->assertOk();
        $this->apiAs($waliA)->getJson('/api/v1/leave-requests/' . $leaveB->id)->assertForbidden();

        // Update status via API: 403 on B
        $this->apiAs($waliA)->patchJson('/api/v1/leave-requests/' . $leaveB->id . '/status', [
            'status_approval' => 'Approved',
        ])->assertForbidden();
    }

    /**
     * Test 2: User login buka dokumen izin sendiri = 200; user lain = 403; guest = 403 (or redirect to login on web / 401 on API).
     */
    public function test_leave_document_access_permissions_owner_other_and_guest(): void
    {
        Storage::fake('local');

        $classA = $this->makeClass('X RPL 1');
        $studentOwner = $this->makeUser(4, ['class_id' => $classA->id]);
        $studentOther = $this->makeUser(4, ['class_id' => $classA->id]);
        $teacher = $this->makeUser(2);

        $leave = $this->createLeaveWithDocument($studentOwner, 'leave-documents/privat.pdf');

        // === WEB VERIFICATION ===
        // Guest -> redirect to login (or 401/403)
        $this->get(route('leave-requests.document', $leave))->assertRedirect('/login');

        // Other student -> 403 Forbidden
        $this->actingAs($studentOther)->get(route('leave-requests.document', $leave))->assertForbidden();

        // Teacher -> 403 Forbidden
        $this->actingAs($teacher)->get(route('leave-requests.document', $leave))->assertForbidden();

        // Owner -> 200 OK with file content
        $res = $this->actingAs($studentOwner)->get(route('leave-requests.document', $leave));
        $res->assertOk();
        $this->assertSame('ISI DOKUMEN IZIN ASLI', $res->streamedContent());

        // === API VERIFICATION ===
        // Clear session / guard authentication to test Guest
        $this->app['auth']->forgetGuards();

        // Guest -> 401 Unauthorized
        $this->getJson('/api/v1/leave-requests/' . $leave->id . '/document')->assertUnauthorized();

        // Other student -> 403 Forbidden
        $this->apiAs($studentOther)->getJson('/api/v1/leave-requests/' . $leave->id . '/document')->assertForbidden();

        // Owner -> 200 OK
        $apiOwnerRes = $this->apiAs($studentOwner)->get('/api/v1/leave-requests/' . $leave->id . '/document');
        $apiOwnerRes->assertOk();
        $this->assertSame('ISI DOKUMEN IZIN ASLI', $apiOwnerRes->streamedContent());
    }
}
