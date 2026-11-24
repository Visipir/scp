<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Journal;
use App\Models\LeaveRequest;
use App\Models\Schedule;
use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Support\ScpTestHelpers;
use Tests\TestCase;

class LeaveCategoryAndJournalSyncTest extends TestCase
{
    use RefreshDatabase, ScpTestHelpers;

    public function test_student_can_choose_leave_category_and_it_auto_syncs_to_journal(): void
    {
        Storage::fake('local');
        $this->seedRoles();
        $this->makeConfig(['active_cycle' => 'Putaran 1']);

        $class = $this->makeClass('XII RPL 1');
        $studentSakit = $this->makeUser(4, ['class_id' => $class->id, 'fullname' => 'Siswa Sakit']);
        $studentDispensasi = $this->makeUser(4, ['class_id' => $class->id, 'fullname' => 'Siswa Dispensasi']);
        $studentHadir = $this->makeUser(4, ['class_id' => $class->id, 'fullname' => 'Siswa Hadir']);
        $teacher = $this->makeUser(2, ['fullname' => 'Guru Pengajar']);
        $bk = $this->makeUser(3, ['fullname' => 'Guru BK']);

        $today = now('Asia/Jakarta')->toDateString();

        $schedule = $this->makeSchedule([
            'teacher_id'  => $teacher->id,
            'class_id'    => $class->id,
            'start_time'  => '07:00:00',
            'end_time'    => '09:00:00',
            'day_of_week' => 'Senin',
        ]);

        // 1. Student 1 submits Sakit leave
        $resSakit = $this->actingAs($studentSakit)->post(route('leave-requests.store'), [
            'student_id' => $studentSakit->id,
            'category'   => 'Sakit',
            'start_date' => $today,
            'end_date'   => $today,
            'reason'     => 'Demam tinggi dan flu',
        ]);
        $resSakit->assertRedirect(route('leave-requests.index'));

        $leaveSakit = LeaveRequest::where('student_id', $studentSakit->id)->first();
        $this->assertNotNull($leaveSakit);
        $this->assertSame('Sakit', $leaveSakit->category);
        $this->assertSame('Sakit', $leaveSakit->attendance_status);

        // 2. Student 2 submits Dispensasi leave
        $resDispen = $this->actingAs($studentDispensasi)->post(route('leave-requests.store'), [
            'student_id' => $studentDispensasi->id,
            'category'   => 'Dispensasi',
            'start_date' => $today,
            'end_date'   => $today,
            'reason'     => 'Mengikuti Lomba LKS Web Tech',
        ]);
        $resDispen->assertRedirect(route('leave-requests.index'));

        $leaveDispen = LeaveRequest::where('student_id', $studentDispensasi->id)->first();
        $this->assertNotNull($leaveDispen);
        $this->assertSame('Dispensasi', $leaveDispen->category);
        $this->assertSame('Izin', $leaveDispen->attendance_status);

        // BK approves leaveSakit
        $this->actingAs($bk)->patch(route('leave-requests.update-status', $leaveSakit), [
            'status_approval' => 'Approved',
        ]);

        // 3. Teacher checks /journals/leaves endpoint
        $resLeaves = $this->actingAs($teacher)->getJson(route('journals.leaves', [
            'schedule_id' => $schedule->id,
            'date'        => $today,
        ]));
        $resLeaves->assertOk();
        $leavesData = $resLeaves->json('leaves');
        $this->assertCount(2, $leavesData);

        // 4. Teacher creates journal (without manually adding absent students -> auto-syncs)
        $this->travelTo(now('Asia/Jakarta')->setTime(8, 0, 0));
        $resJournal = $this->actingAs($teacher)->post(route('journals.store'), [
            'schedule_id'       => $schedule->id,
            'date'              => $today,
            'topic_description' => 'Materi Pemrograman Web',
            'photo'             => UploadedFile::fake()->image('kbm.jpg'),
        ]);
        $resJournal->assertRedirect(route('journals.index'));

        // Verify attendance records:
        // - studentSakit -> Sakit
        $attSakit = Attendance::where('student_id', $studentSakit->id)->first();
        $this->assertNotNull($attSakit);
        $this->assertSame('Sakit', $attSakit->status);
        $this->assertSame($leaveSakit->id, (int) $attSakit->leave_request_id);

        // - studentDispensasi -> Izin
        $attDispen = Attendance::where('student_id', $studentDispensasi->id)->first();
        $this->assertNotNull($attDispen);
        $this->assertSame('Izin', $attDispen->status);
        $this->assertSame($leaveDispen->id, (int) $attDispen->leave_request_id);

        // - studentHadir -> Hadir
        $attHadir = Attendance::where('student_id', $studentHadir->id)->first();
        $this->assertNotNull($attHadir);
        $this->assertSame('Hadir', $attHadir->status);
        $this->assertNull($attHadir->leave_request_id);
    }
}
