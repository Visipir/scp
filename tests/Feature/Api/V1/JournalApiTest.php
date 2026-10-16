<?php

namespace Tests\Feature\Api\V1;

use App\Models\Attendance;
use App\Models\Journal;
use App\Models\LeaveRequest;
use App\Models\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Support\ScpTestHelpers;
use Tests\TestCase;

class JournalApiTest extends TestCase
{
    use RefreshDatabase;
    use ScpTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
    }

    private function makeJournal(Schedule $schedule, string $date = null, array $attrs = []): Journal
    {
        return Journal::create(array_merge([
            'schedule_id'       => $schedule->id,
            'date'              => $date ?? now()->toDateString(),
            'topic_description' => 'Materi',
        ], $attrs));
    }

    public function test_index_requires_auth_and_role(): void
    {
        $this->getJson('/api/v1/journals')->assertStatus(401);
        $this->apiAs($this->makeUser(4))->getJson('/api/v1/journals')->assertStatus(403);
    }

    public function test_guru_sees_only_own_journals_with_counts(): void
    {
        $guruA = $this->makeUser(2);
        $guruB = $this->makeUser(2);
        $class = $this->makeClass('XII RPL 1');
        $student = $this->makeUser(4, ['class_id' => $class->id]);

        $own = $this->makeJournal($this->makeSchedule(['teacher_id' => $guruA->id, 'class_id' => $class->id]));
        $this->makeJournal($this->makeSchedule(['teacher_id' => $guruB->id, 'class_id' => $class->id]));
        Attendance::create(['journal_id' => $own->id, 'student_id' => $student->id, 'status' => 'Hadir', 'date' => now()->toDateString()]);

        $response = $this->apiAs($guruA)->getJson('/api/v1/journals');
        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.id', $own->id);
        $response->assertJsonPath('data.0.attendance_summary.hadir', 1);
        $response->assertJsonStructure(['data', 'meta' => ['current_page', 'per_page', 'total', 'last_page']]);
    }

    public function test_tim_kesiswaan_is_scoped_by_class(): void
    {
        $classA = $this->makeClass('Kelas A');
        $classB = $this->makeClass('Kelas B');
        $this->makeJournal($this->makeSchedule(['class_id' => $classA->id]));
        $this->makeJournal($this->makeSchedule(['class_id' => $classB->id]));

        $waliA = $this->makeUser(3, ['class_id' => $classA->id]);
        $this->apiAs($waliA)->getJson('/api/v1/journals')->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_prepare_returns_roster_with_leave_flags(): void
    {
        $guru = $this->makeUser(2);
        $class = $this->makeClass('XII RPL 1');
        $present = $this->makeUser(4, ['class_id' => $class->id]);
        $onLeave = $this->makeUser(4, ['class_id' => $class->id]);
        $schedule = $this->makeSchedule(['teacher_id' => $guru->id, 'class_id' => $class->id]);
        $date = now()->toDateString();

        LeaveRequest::create([
            'student_id' => $onLeave->id, 'applicant_id' => $onLeave->id,
            'start_date' => $date, 'end_date' => $date, 'reason' => 'Izin', 'status_approval' => 'Approved',
        ]);

        $response = $this->apiAs($guru)->getJson('/api/v1/journals/prepare?schedule_id='.$schedule->id.'&date='.$date);
        $response->assertOk();
        $response->assertJsonCount(2, 'data.students');

        $students = collect($response->json('data.students'))->keyBy('student_id');
        $this->assertTrue($students[$onLeave->id]['on_approved_leave']);
        $this->assertFalse($students[$present->id]['on_approved_leave']);
    }

    public function test_prepare_forbidden_for_other_teacher(): void
    {
        $schedule = $this->makeSchedule(['teacher_id' => $this->makeUser(2)->id]);

        $this->apiAs($this->makeUser(2))
            ->getJson('/api/v1/journals/prepare?schedule_id='.$schedule->id.'&date='.now()->toDateString())
            ->assertStatus(403);
    }

    public function test_store_creates_journal_and_syncs_attendance(): void
    {
        Storage::fake('local');

        $guru = $this->makeUser(2);
        $class = $this->makeClass('XII RPL 1');
        $present = $this->makeUser(4, ['class_id' => $class->id]);
        $onLeave = $this->makeUser(4, ['class_id' => $class->id]);
        $schedule = $this->makeSchedule(['teacher_id' => $guru->id, 'class_id' => $class->id]);
        $date = now()->toDateString();

        LeaveRequest::create([
            'student_id' => $onLeave->id, 'applicant_id' => $onLeave->id,
            'start_date' => $date, 'end_date' => $date, 'reason' => 'Izin', 'status_approval' => 'Approved',
        ]);

        $response = $this->apiAs($guru)->post('/api/v1/journals', [
            'schedule_id'       => $schedule->id,
            'date'              => $date,
            'topic_description' => 'Materi hari ini',
            'photo'             => UploadedFile::fake()->image('foto.jpg'),
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure(['data' => ['id', 'date', 'photo_url', 'attendance_summary']]);
        $this->assertDatabaseHas('attendances', ['student_id' => $present->id, 'status' => 'Hadir']);
        $this->assertDatabaseHas('attendances', ['student_id' => $onLeave->id, 'status' => 'Izin']);
    }

    public function test_store_rejects_duplicate_journal(): void
    {
        Storage::fake('local');

        $guru = $this->makeUser(2);
        $schedule = $this->makeSchedule(['teacher_id' => $guru->id]);
        $date = now()->toDateString();
        $this->makeJournal($schedule, $date);

        $this->apiAs($guru)->post('/api/v1/journals', [
            'schedule_id'       => $schedule->id,
            'date'              => $date,
            'topic_description' => 'Duplikat',
            'photo'             => UploadedFile::fake()->image('foto.jpg'),
        ])->assertStatus(422)->assertJsonStructure(['errors' => ['date']]);
    }

    public function test_store_rejects_bad_extension_and_oversize(): void
    {
        Storage::fake('local');
        $guru = $this->makeUser(2);
        $schedule = $this->makeSchedule(['teacher_id' => $guru->id]);

        $this->apiAs($guru)->post('/api/v1/journals', [
            'schedule_id'       => $schedule->id,
            'date'              => now()->toDateString(),
            'topic_description' => 'x',
            'photo'             => UploadedFile::fake()->create('dokumen.txt', 10, 'text/plain'),
        ])->assertStatus(422)->assertJsonStructure(['errors' => ['photo']]);

        $this->apiAs($guru)->post('/api/v1/journals', [
            'schedule_id'       => $schedule->id,
            'date'              => now()->toDateString(),
            'topic_description' => 'x',
            'photo'             => UploadedFile::fake()->image('besar.jpg')->size(6000),
        ])->assertStatus(422)->assertJsonStructure(['errors' => ['photo']]);
    }

    public function test_show_returns_detail_with_attendances(): void
    {
        $guru = $this->makeUser(2);
        $class = $this->makeClass('XII RPL 1');
        $student = $this->makeUser(4, ['class_id' => $class->id]);
        $journal = $this->makeJournal($this->makeSchedule(['teacher_id' => $guru->id, 'class_id' => $class->id]));
        Attendance::create(['journal_id' => $journal->id, 'student_id' => $student->id, 'status' => 'Alpa', 'date' => now()->toDateString(), 'notes' => 'Tanpa keterangan']);

        $response = $this->apiAs($guru)->getJson('/api/v1/journals/'.$journal->id);
        $response->assertOk();
        $response->assertJsonPath('data.attendances.0.student_id', $student->id);
        $response->assertJsonPath('data.attendances.0.status', 'Alpa');
        $response->assertJsonPath('data.attendance_summary.alpa', 1);
    }

    public function test_show_forbidden_for_other_teacher(): void
    {
        $journal = $this->makeJournal($this->makeSchedule(['teacher_id' => $this->makeUser(2)->id]));

        $this->apiAs($this->makeUser(2))->getJson('/api/v1/journals/'.$journal->id)->assertStatus(403);
    }

    public function test_photo_access(): void
    {
        Storage::fake('local');
        $guru = $this->makeUser(2);
        $journal = $this->makeJournal($this->makeSchedule(['teacher_id' => $guru->id]), null, ['photo_path' => 'journals/foto.jpg']);
        Storage::disk('local')->put('journals/foto.jpg', 'FOTO-KBM');

        $this->apiAs($guru)->get('/api/v1/journals/'.$journal->id.'/photo')->assertOk();
        $this->apiAs($this->makeUser(2))->get('/api/v1/journals/'.$journal->id.'/photo')->assertStatus(403);
        $this->apiAs($this->makeUser(1))->get('/api/v1/journals/'.$journal->id.'/photo')->assertOk();
    }

    public function test_update_attendances_by_student(): void
    {
        $guru = $this->makeUser(2);
        $class = $this->makeClass('XII RPL 1');
        $student = $this->makeUser(4, ['class_id' => $class->id]);
        $journal = $this->makeJournal($this->makeSchedule(['teacher_id' => $guru->id, 'class_id' => $class->id]));
        Attendance::create(['journal_id' => $journal->id, 'student_id' => $student->id, 'status' => 'Hadir', 'date' => now()->toDateString()]);

        $response = $this->apiAs($guru)->putJson('/api/v1/journals/'.$journal->id.'/attendances', [
            'attendances' => [
                ['student_id' => $student->id, 'status' => 'Terlambat', 'notes' => 'Datang 07:20'],
            ],
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.attendances.0.status', 'Terlambat');
        $this->assertDatabaseHas('attendances', ['student_id' => $student->id, 'status' => 'Terlambat', 'notes' => 'Datang 07:20']);
    }

    public function test_update_attendances_validates_status(): void
    {
        $guru = $this->makeUser(2);
        $class = $this->makeClass('XII RPL 1');
        $student = $this->makeUser(4, ['class_id' => $class->id]);
        $journal = $this->makeJournal($this->makeSchedule(['teacher_id' => $guru->id, 'class_id' => $class->id]));
        Attendance::create(['journal_id' => $journal->id, 'student_id' => $student->id, 'status' => 'Hadir', 'date' => now()->toDateString()]);

        $this->apiAs($guru)->putJson('/api/v1/journals/'.$journal->id.'/attendances', [
            'attendances' => [['student_id' => $student->id, 'status' => 'Bolos']],
        ])->assertStatus(422)->assertJsonStructure(['errors' => ['attendances.0.status']]);
    }
}
