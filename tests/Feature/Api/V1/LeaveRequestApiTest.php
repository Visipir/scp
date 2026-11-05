<?php

namespace Tests\Feature\Api\V1;

use App\Models\Attendance;
use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Support\ScpTestHelpers;
use Tests\TestCase;

class LeaveRequestApiTest extends TestCase
{
    use RefreshDatabase;
    use ScpTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
    }

    private function makeLeave(User $student, array $attrs = []): LeaveRequest
    {
        return LeaveRequest::create(array_merge([
            'student_id'      => $student->id,
            'applicant_id'    => $student->id,
            'start_date'      => now()->addDay()->toDateString(),
            'end_date'        => now()->addDays(2)->toDateString(),
            'reason'          => 'Keperluan keluarga',
            'status_approval' => 'Pending',
        ], $attrs));
    }

    public function test_index_requires_auth_and_role(): void
    {
        $this->getJson('/api/v1/leave-requests')->assertStatus(401);
        $this->apiAs($this->makeUser(2))->getJson('/api/v1/leave-requests')->assertStatus(403);
    }

    public function test_pagination_meta_shape(): void
    {
        $student = $this->makeUser(4, ['class_id' => $this->makeClass()->id]);
        $this->makeLeave($student);

        $this->apiAs($student)->getJson('/api/v1/leave-requests')
            ->assertOk()
            ->assertJsonStructure(['data', 'meta' => ['current_page', 'per_page', 'total', 'last_page']]);
    }

    public function test_wali_kelas_scope_is_applied_to_index(): void
    {
        $classA = $this->makeClass('Kelas A');
        $classB = $this->makeClass('Kelas B');
        $this->makeLeave($this->makeUser(4, ['class_id' => $classA->id]));
        $this->makeLeave($this->makeUser(4, ['class_id' => $classB->id]));

        $waliA = $this->makeUser(3, ['class_id' => $classA->id]);
        $this->apiAs($waliA)->getJson('/api/v1/leave-requests')->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_status_filter(): void
    {
        $student = $this->makeUser(4, ['class_id' => $this->makeClass()->id]);
        $this->makeLeave($student, ['status_approval' => 'Pending']);
        $this->makeLeave($student, ['status_approval' => 'Approved']);

        $this->apiAs($student)->getJson('/api/v1/leave-requests?status_approval=Approved')
            ->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_leave_filters_by_status_class_date_and_student_with_scope(): void
    {
        $classA = $this->makeClass('Kelas A');
        $classB = $this->makeClass('Kelas B');
        $studentA = $this->makeUser(4, ['fullname' => 'Budi Santoso', 'username' => 'budi_1001', 'class_id' => $classA->id]);
        $studentB = $this->makeUser(4, ['fullname' => 'Siti Nurhaliza', 'username' => 'siti_1002', 'class_id' => $classB->id]);

        $this->makeLeave($studentA, [
            'status_approval' => 'Approved',
            'start_date' => '2026-11-10',
            'end_date' => '2026-11-12',
        ]);
        $this->makeLeave($studentB, [
            'status_approval' => 'Pending',
            'start_date' => '2026-11-15',
            'end_date' => '2026-11-16',
        ]);

        $waka = $this->makeUser(1);
        // Filter by class_id
        $this->apiAs($waka)->getJson('/api/v1/leave-requests?class_id=' . $classA->id)
            ->assertOk()->assertJsonCount(1, 'data');

        // Filter by student name
        $this->apiAs($waka)->getJson('/api/v1/leave-requests?student=Budi')
            ->assertOk()->assertJsonCount(1, 'data');

        // Filter by date
        $this->apiAs($waka)->getJson('/api/v1/leave-requests?date=2026-11-11')
            ->assertOk()->assertJsonCount(1, 'data');

        // Wali Kelas cannot see classB even if querying class_id=classB
        $waliA = $this->makeUser(3, ['class_id' => $classA->id]);
        $this->apiAs($waliA)->getJson('/api/v1/leave-requests?class_id=' . $classB->id)
            ->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_student_can_submit_own_leave_with_document(): void
    {
        Storage::fake('local');
        $student = $this->makeUser(4, ['class_id' => $this->makeClass()->id]);

        $response = $this->apiAs($student)->post('/api/v1/leave-requests', [
            'student_id' => $student->id,
            'start_date' => now()->addDay()->toDateString(),
            'end_date'   => now()->addDays(2)->toDateString(),
            'reason'     => 'Sakit demam',
            'document'   => UploadedFile::fake()->create('surat.pdf', 20, 'application/pdf'),
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('data.status_approval', 'Pending');
        $response->assertJsonStructure(['data' => ['id', 'document_url']]);
        $this->assertCount(1, Storage::disk('local')->allFiles('leave-documents'));
    }

    public function test_parent_cannot_submit_for_other_student(): void
    {
        $parent = $this->makeUser(4);
        $stranger = $this->makeUser(4, ['class_id' => $this->makeClass()->id]);

        $this->apiAs($parent)->post('/api/v1/leave-requests', [
            'student_id' => $stranger->id,
            'start_date' => now()->addDay()->toDateString(),
            'end_date'   => now()->addDays(2)->toDateString(),
            'reason'     => 'Coba',
        ])->assertStatus(403);
    }

    public function test_show_scoped_to_owner(): void
    {
        $student = $this->makeUser(4, ['class_id' => $this->makeClass()->id]);
        $leave = $this->makeLeave($student);

        $this->apiAs($student)->getJson('/api/v1/leave-requests/'.$leave->id)->assertOk();

        $other = $this->makeUser(4, ['class_id' => $this->makeClass('XII RPL 9')->id]);
        $this->apiAs($other)->getJson('/api/v1/leave-requests/'.$leave->id)->assertStatus(403);
    }

    public function test_document_stream_scoped(): void
    {
        Storage::fake('local');
        $student = $this->makeUser(4, ['class_id' => $this->makeClass()->id]);
        $leave = $this->makeLeave($student, ['document_path' => 'leave-documents/rahasia.txt']);
        Storage::disk('local')->put('leave-documents/rahasia.txt', 'RAHASIA');

        $this->apiAs($student)->get('/api/v1/leave-requests/'.$leave->id.'/document')->assertOk();

        $other = $this->makeUser(4, ['class_id' => $this->makeClass('XII RPL 9')->id]);
        $this->apiAs($other)->get('/api/v1/leave-requests/'.$leave->id.'/document')->assertStatus(403);
    }

    public function test_document_missing_returns_404(): void
    {
        Storage::fake('local');
        $student = $this->makeUser(4, ['class_id' => $this->makeClass()->id]);
        $leave = $this->makeLeave($student, ['document_path' => null]);

        $this->apiAs($student)->get('/api/v1/leave-requests/'.$leave->id.'/document')->assertStatus(404);
    }

    public function test_update_status_scoped_and_does_not_write_attendance(): void
    {
        $classA = $this->makeClass('Kelas A');
        $classB = $this->makeClass('Kelas B');
        $studentB = $this->makeUser(4, ['class_id' => $classB->id]);
        $leaveB = $this->makeLeave($studentB);

        $waliA = $this->makeUser(3, ['class_id' => $classA->id]);
        $this->apiAs($waliA)->patchJson('/api/v1/leave-requests/'.$leaveB->id.'/status', ['status_approval' => 'Approved'])
            ->assertStatus(403);
        $this->assertSame('Pending', $leaveB->fresh()->status_approval);

        // BK (no class) may approve any
        $bk = $this->makeUser(3);
        $this->apiAs($bk)->patchJson('/api/v1/leave-requests/'.$leaveB->id.'/status', ['status_approval' => 'Approved'])
            ->assertOk()
            ->assertJsonPath('data.status_approval', 'Approved');

        $this->assertDatabaseHas('leave_requests', ['id' => $leaveB->id, 'status_approval' => 'Approved', 'verified_by' => $bk->id]);
        $this->assertSame(0, Attendance::count());
    }

    public function test_update_status_validation(): void
    {
        $class = $this->makeClass('Kelas A');
        $leave = $this->makeLeave($this->makeUser(4, ['class_id' => $class->id]));
        $wali = $this->makeUser(3, ['class_id' => $class->id]);

        $this->apiAs($wali)->patchJson('/api/v1/leave-requests/'.$leave->id.'/status', ['status_approval' => 'Maybe'])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['status_approval']]);
    }

    public function test_leave_status_change_re_syncs_existing_attendance(): void
    {
        Storage::fake('local');
        $class = $this->makeClass('Kelas XI');
        $student = $this->makeUser(4, ['class_id' => $class->id]);
        $guru = $this->makeUser(2);
        $wali = $this->makeUser(3, ['class_id' => $class->id]);

        $schedule = $this->makeSchedule(['teacher_id' => $guru->id, 'class_id' => $class->id]);
        $today = now('Asia/Jakarta')->toDateString();

        $leave = $this->makeLeave($student, [
            'start_date' => $today,
            'end_date'   => $today,
            'reason'     => 'Izin urusan keluarga',
        ]);

        // 1. Approve leave
        $this->apiAs($wali)->patchJson('/api/v1/leave-requests/'.$leave->id.'/status', ['status_approval' => 'Approved'])
            ->assertOk();
        $this->assertSame('Approved', $leave->fresh()->status_approval);
        $this->assertSame(0, Attendance::count()); // No attendance created yet (A.d)

        // 2. Guru creates journal -> student attendance auto-filled as Izin
        $resJournal = $this->apiAs($guru)->post('/api/v1/journals', [
            'schedule_id'       => $schedule->id,
            'date'              => $today,
            'topic_description' => 'Materi KBM Hari Ini',
            'photo'             => UploadedFile::fake()->image('foto.jpg'),
        ]);
        $resJournal->assertStatus(201);
        $this->assertDatabaseHas('attendances', [
            'student_id' => $student->id,
            'status'     => 'Izin',
        ]);

        // 3. Wali rejects leave -> existing attendance reverts to Alpa
        $this->apiAs($wali)->patchJson('/api/v1/leave-requests/'.$leave->id.'/status', ['status_approval' => 'Rejected'])
            ->assertOk();
        $this->assertSame('Rejected', $leave->fresh()->status_approval);
        $this->assertDatabaseHas('attendances', [
            'student_id' => $student->id,
            'status'     => 'Alpa',
        ]);

        // 4. Wali re-approves leave -> existing attendance re-syncs to Izin
        $this->apiAs($wali)->patchJson('/api/v1/leave-requests/'.$leave->id.'/status', ['status_approval' => 'Approved'])
            ->assertOk();
        $this->assertSame('Approved', $leave->fresh()->status_approval);
        $this->assertDatabaseHas('attendances', [
            'student_id' => $student->id,
            'status'     => 'Izin',
        ]);

        // 5. Wali sets back to Pending -> existing attendance reverts to Alpa
        $this->apiAs($wali)->patchJson('/api/v1/leave-requests/'.$leave->id.'/status', ['status_approval' => 'Pending'])
            ->assertOk();
        $this->assertSame('Pending', $leave->fresh()->status_approval);
        $this->assertDatabaseHas('attendances', [
            'student_id' => $student->id,
            'status'     => 'Alpa',
        ]);
    }
}
