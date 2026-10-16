<?php

namespace Tests\Feature;

use App\Models\Journal;
use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Support\ScpTestHelpers;
use Tests\TestCase;

class PrivateFileAccessTest extends TestCase
{
    use RefreshDatabase;
    use ScpTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
    }

    private function makeLeave(User $student, ?string $documentPath): LeaveRequest
    {
        return LeaveRequest::create([
            'student_id'      => $student->id,
            'applicant_id'    => $student->id,
            'start_date'      => now()->addDay()->toDateString(),
            'end_date'        => now()->addDays(2)->toDateString(),
            'reason'          => 'Sakit demam',
            'document_path'   => $documentPath,
            'status_approval' => 'Pending',
        ]);
    }

    public function test_guest_is_redirected_from_leave_document(): void
    {
        $student = $this->makeUser(4, ['class_id' => $this->makeClass()->id]);
        $leave = $this->makeLeave($student, 'leave-documents/x.pdf');

        $this->get(route('leave-requests.document', $leave))->assertRedirect('/login');
    }

    public function test_unauthorized_roles_cannot_access_leave_document(): void
    {
        Storage::fake('local');
        $student = $this->makeUser(4, ['class_id' => $this->makeClass()->id]);
        $leave = $this->makeLeave($student, 'leave-documents/x.pdf');
        Storage::disk('local')->put('leave-documents/x.pdf', 'RAHASIA');

        // Teacher (role 2) is not allowed
        $this->actingAs($this->makeUser(2))->get(route('leave-requests.document', $leave))->assertForbidden();

        // Another student is not allowed
        $stranger = $this->makeUser(4, ['class_id' => $this->makeClass('XII RPL 9')->id]);
        $this->actingAs($stranger)->get(route('leave-requests.document', $leave))->assertForbidden();
    }

    public function test_owner_streams_leave_document(): void
    {
        Storage::fake('local');
        $student = $this->makeUser(4, ['class_id' => $this->makeClass()->id]);
        $leave = $this->makeLeave($student, 'leave-documents/rahasia.txt');
        Storage::disk('local')->put('leave-documents/rahasia.txt', 'RAHASIA-IZIN');

        $response = $this->actingAs($student)->get(route('leave-requests.document', $leave));

        $response->assertOk();
        $this->assertSame('RAHASIA-IZIN', $response->streamedContent());
    }

    public function test_missing_leave_document_returns_404(): void
    {
        Storage::fake('local');
        $student = $this->makeUser(4, ['class_id' => $this->makeClass()->id]);
        $leave = $this->makeLeave($student, null);

        $this->actingAs($student)->get(route('leave-requests.document', $leave))->assertNotFound();
    }

    public function test_leave_document_upload_lands_on_private_disk(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        $student = $this->makeUser(4, ['class_id' => $this->makeClass()->id]);

        $this->actingAs($student)->post('/leave-requests', [
            'student_id' => $student->id,
            'start_date' => now()->addDay()->toDateString(),
            'end_date'   => now()->addDays(2)->toDateString(),
            'reason'     => 'Sakit demam',
            'document'   => UploadedFile::fake()->create('surat.pdf', 20, 'application/pdf'),
        ])->assertRedirect(route('leave-requests.index'));

        $this->assertCount(1, Storage::disk('local')->allFiles('leave-documents'));
        $this->assertCount(0, Storage::disk('public')->allFiles('leave-documents'));

        $leave = LeaveRequest::first();
        $this->assertStringStartsWith('leave-documents/', $leave->document_path);
    }

    public function test_private_files_are_not_publicly_reachable(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('leave-documents/rahasia.txt', 'RAHASIA');
        Storage::disk('local')->put('journals/foto.txt', 'FOTO');

        $this->get('/storage/leave-documents/rahasia.txt')->assertStatus(403);
        $this->get('/storage/journals/foto.txt')->assertStatus(403);
    }

    public function test_journal_photo_access_is_scoped(): void
    {
        Storage::fake('local');

        $teacher = $this->makeUser(2);
        $schedule = $this->makeSchedule(['teacher_id' => $teacher->id]);
        $journal = Journal::create([
            'schedule_id'       => $schedule->id,
            'date'              => now()->toDateString(),
            'topic_description' => 'Materi',
            'photo_path'        => 'journals/foto.jpg',
        ]);
        Storage::disk('local')->put('journals/foto.jpg', 'FOTO-KBM');

        $owner = $this->actingAs($teacher)->get(route('journals.photo', $journal));
        $owner->assertOk();
        $this->assertSame('FOTO-KBM', $owner->streamedContent());

        $this->actingAs($this->makeUser(2))->get(route('journals.photo', $journal))->assertForbidden();
        $this->actingAs($this->makeUser(1))->get(route('journals.photo', $journal))->assertOk();
        $this->actingAs($this->makeUser(3))->get(route('journals.photo', $journal))->assertOk();
    }
}
