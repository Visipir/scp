<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Journal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\ScpTestHelpers;
use Tests\TestCase;

class DashboardStatsTest extends TestCase
{
    use RefreshDatabase;
    use ScpTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
    }

    public function test_dashboard_attendance_stats_are_grouped_and_correct(): void
    {
        $waka = $this->makeUser(1);
        $class = $this->makeClass('XII RPL 1');
        $student = $this->makeUser(4, ['class_id' => $class->id]);
        $date = now()->toDateString();

        foreach (['Hadir', 'Hadir', 'Alpa'] as $status) {
            $this->makeAttendance($student, $date, $status, $class->id);
        }

        $response = $this->actingAs($waka)->get('/dashboard');
        $response->assertOk();
        $response->assertViewHas('attendanceStats', function (array $stats) {
            return $stats === ['Hadir' => 2, 'Sakit' => 0, 'Izin' => 0, 'Alpa' => 1, 'Terlambat' => 0];
        });
    }

    private function makeAttendance(User $student, string $date, string $status, int $classId): Attendance
    {
        $schedule = $this->makeSchedule(['class_id' => $classId]);
        $journal = Journal::create([
            'schedule_id'       => $schedule->id,
            'date'              => $date,
            'topic_description' => 'Materi',
        ]);

        return Attendance::create([
            'journal_id' => $journal->id,
            'student_id' => $student->id,
            'status'     => $status,
            'date'       => $date,
        ]);
    }
}
