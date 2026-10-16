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
}
