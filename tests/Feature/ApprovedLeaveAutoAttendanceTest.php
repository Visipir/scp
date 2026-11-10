<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\CounselorAssignment;
use App\Models\LeaveRequest;
use App\Models\Schedule;
use App\Models\SchoolClass;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Support\ScpTestHelpers;
use Tests\TestCase;

class ApprovedLeaveAutoAttendanceTest extends TestCase
{
    use RefreshDatabase;
    use ScpTestHelpers;

    private SchoolClass $class;
    private User $teacher;
    private User $bk;
    private User $studentSick;
    private User $studentPermit;
    private User $studentPresent;
    private Schedule $schedule;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Storage::fake('public');
        $this->seedRoles();
        $this->makeConfig(['active_cycle' => 'Putaran 2']);

        $this->class = SchoolClass::create([
            'class_name'    => 'XII RPL 1',
            'academic_year' => '2026/2027',
            'grade_level'   => 'XII',
            'major'         => 'RPL',
        ]);

        $this->teacher = $this->makeUser(2, ['username' => 'guru_rpl', 'fullname' => 'Budi Guru RPL']);
        $this->schedule = Schedule::create([
            'teacher_id'   => $this->teacher->id,
            'class_id'     => $this->class->id,
            'subject'      => 'Pemrograman Web',
            'day_of_week'  => 'Selasa',
            'start_time'   => '07:15:00',
            'end_time'     => '08:45:00',
            'start_period' => 1,
            'end_period'   => 2,
            'cycle_type'   => 'Putaran 2',
        ]);

        $this->bk = $this->makeUser(3, ['username' => 'bk_ibu', 'fullname' => 'Dra. Endang Sulistyowati']);
        CounselorAssignment::create([
            'user_id'       => $this->bk->id,
            'class_id'      => $this->class->id,
            'academic_year' => '2026/2027',
        ]);

        $this->studentSick = $this->makeUser(4, ['username' => '17701', 'fullname' => 'Ahmad Sakit', 'class_id' => $this->class->id]);
        $this->studentPermit = $this->makeUser(4, ['username' => '17702', 'fullname' => 'Bambang Izin', 'class_id' => $this->class->id]);
        $this->studentPresent = $this->makeUser(4, ['username' => '17703', 'fullname' => 'Cahyo Hadir', 'class_id' => $this->class->id]);
    }

    public function test_determine_status_from_reason_keywords(): void
    {
        $this->assertSame('Sakit', LeaveRequest::determineStatusFromReason('Sakit demam berdarah'));
        $this->assertSame('Sakit', LeaveRequest::determineStatusFromReason('Izin tidak masuk sekolah karena demam dan flu, istirahat dokter.'));
        $this->assertSame('Sakit', LeaveRequest::determineStatusFromReason('Rawat inap di rumah sakit karena tipes'));
        $this->assertSame('Sakit', LeaveRequest::determineStatusFromReason('Pusing dan mual hebat'));
        $this->assertSame('Sakit', LeaveRequest::determineStatusFromReason('Surat dokter terlampir'));

        $this->assertSame('Izin', LeaveRequest::determineStatusFromReason('Izin acara wisuda sarjana kakak di UGM'));
        $this->assertSame('Izin', LeaveRequest::determineStatusFromReason('Mengikuti Lomba Kompetensi Siswa'));
        $this->assertSame('Izin', LeaveRequest::determineStatusFromReason('Urusan keluarga di luar kota'));
        $this->assertSame('Izin', LeaveRequest::determineStatusFromReason(null));
    }

    public function test_journal_create_view_prefills_approved_sick_and_permit_leaves(): void
    {
        $date = Carbon::today('Asia/Jakarta')->toDateString();

        $sickLeave = LeaveRequest::create([
            'student_id'         => $this->studentSick->id,
            'applicant_id'       => $this->studentSick->id,
            'start_date'         => $date,
            'end_date'           => $date,
            'reason'             => 'Sakit flu dan demam tinggi, anjuran dokter istirahat',
            'status_approval'    => 'Approved',
            'verified_by'        => $this->bk->id,
            'decided_by_user_id' => $this->bk->id,
            'decided_by_role'    => 'BK',
            'decided_at'         => now(),
        ]);

        $permitLeave = LeaveRequest::create([
            'student_id'         => $this->studentPermit->id,
            'applicant_id'       => $this->studentPermit->id,
            'start_date'         => $date,
            'end_date'           => $date,
            'reason'             => 'Izin menghadiri acara wisuda keluarga',
            'status_approval'    => 'Approved',
            'verified_by'        => $this->bk->id,
            'decided_by_user_id' => $this->bk->id,
            'decided_by_role'    => 'BK',
            'decided_at'         => now(),
        ]);

        $response = $this->actingAs($this->teacher)
            ->get("/journals/create?schedule_id={$this->schedule->id}&date={$date}");

        $response->assertOk();
        $response->assertViewHas('initialAbsentStudents');
        $initial = $response->viewData('initialAbsentStudents');

        $this->assertCount(2, $initial);

        $sickEntry = collect($initial)->firstWhere('student_id', (string) $this->studentSick->id);
        $this->assertNotNull($sickEntry);
        $this->assertSame('Sakit', $sickEntry['status']);
        $this->assertTrue($sickEntry['is_approved_leave']);

        $permitEntry = collect($initial)->firstWhere('student_id', (string) $this->studentPermit->id);
        $this->assertNotNull($permitEntry);
        $this->assertSame('Izin', $permitEntry['status']);
        $this->assertTrue($permitEntry['is_approved_leave']);
    }

    public function test_journals_leaves_json_endpoint_returns_approved_leaves_for_schedule_and_date(): void
    {
        $date = '2026-11-15';

        LeaveRequest::create([
            'student_id'         => $this->studentSick->id,
            'applicant_id'       => $this->studentSick->id,
            'start_date'         => $date,
            'end_date'           => $date,
            'reason'             => 'Demam dan flu berat ke puskesmas',
            'status_approval'    => 'Approved',
            'verified_by'        => $this->bk->id,
            'decided_by_user_id' => $this->bk->id,
            'decided_by_role'    => 'BK',
            'decided_at'         => now(),
        ]);

        $response = $this->actingAs($this->teacher)
            ->getJson("/journals/leaves?schedule_id={$this->schedule->id}&date={$date}");

        $response->assertOk();
        $response->assertJsonStructure([
            'leaves' => [
                '*' => ['student_id', 'status', 'notes', 'is_approved_leave', 'leave_id', 'leave_reason', 'decision_label'],
            ],
        ]);

        $leaves = $response->json('leaves');
        $this->assertCount(1, $leaves);
        $this->assertSame((string) $this->studentSick->id, $leaves[0]['student_id']);
        $this->assertSame('Sakit', $leaves[0]['status']);
    }

    public function test_storing_journal_records_approved_sick_leave_as_sakit_with_leave_request_id(): void
    {
        $date = '2026-11-10';

        $leave = LeaveRequest::create([
            'student_id'         => $this->studentSick->id,
            'applicant_id'       => $this->studentSick->id,
            'start_date'         => $date,
            'end_date'           => $date,
            'reason'             => 'Izin tidak masuk sekolah karena demam dan flu, istirahat dokter.',
            'status_approval'    => 'Approved',
            'verified_by'        => $this->bk->id,
            'decided_by_user_id' => $this->bk->id,
            'decided_by_role'    => 'BK',
            'decided_at'         => now(),
        ]);

        $response = $this->actingAs($this->teacher)
            ->post('/journals', [
                'schedule_id'       => $this->schedule->id,
                'date'              => $date,
                'topic_description' => 'Materi Framework Laravel',
                'photo'             => UploadedFile::fake()->image('kbm.jpg'),
                'absent_students'   => [
                    [
                        'student_id' => $this->studentSick->id,
                        'status'     => 'Sakit',
                        'notes'      => 'Izin resmi (Pengajuan surat izin disetujui)',
                    ],
                ],
            ]);

        $response->assertRedirect(route('journals.index'));

        $attendanceSick = Attendance::where('student_id', $this->studentSick->id)
            ->whereDate('date', $date)
            ->firstOrFail();

        $this->assertSame('Sakit', $attendanceSick->status);
        $this->assertSame((int) $leave->id, (int) $attendanceSick->leave_request_id);

        $attendancePresent = Attendance::where('student_id', $this->studentPresent->id)
            ->whereDate('date', $date)
            ->firstOrFail();

        $this->assertSame('Hadir', $attendancePresent->status);
        $this->assertNull($attendancePresent->leave_request_id);
    }
}
