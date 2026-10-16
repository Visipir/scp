<?php

namespace Tests\Feature\Api\V1;

use App\Models\Journal;
use App\Models\LeaveRequest;
use App\Models\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Support\ScpTestHelpers;
use Tests\TestCase;

class ConfigLookupDashboardTest extends TestCase
{
    use RefreshDatabase;
    use ScpTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
    }

    public function test_config_returns_expected_keys(): void
    {
        $this->makeConfig(['active_cycle' => 'Putaran 2', 'is_schedule_locked' => true]);
        $user = $this->makeUser(2);

        $response = $this->apiAs($user)->getJson('/api/v1/config');
        $response->assertOk();
        $response->assertJsonPath('data.active_cycle', 'Putaran 2');
        $response->assertJsonPath('data.is_schedule_locked', true);
        $response->assertJsonPath('data.today_kbm_suspended', false);
        $response->assertJsonStructure(['data' => ['active_cycle', 'is_schedule_locked', 'server_time', 'today_kbm_suspended']]);
    }

    public function test_lookups_return_plain_lists(): void
    {
        $this->makeClass('XII RPL 1');
        $this->makeRoom('Lab RPL 1');
        $user = $this->makeUser(2);

        $this->apiAs($user)->getJson('/api/v1/classes')
            ->assertOk()
            ->assertJsonStructure(['data' => [['id', 'class_name', 'academic_year']]]);

        $this->apiAs($user)->getJson('/api/v1/rooms')
            ->assertOk()
            ->assertJsonStructure(['data' => [['id', 'room_name']]]);
    }

    public function test_teachers_endpoint_is_waka_only(): void
    {
        $this->makeUser(2, ['fullname' => 'Guru Satu']);

        $this->apiAs($this->makeUser(2))->getJson('/api/v1/teachers')->assertStatus(403);
        $this->apiAs($this->makeUser(1))->getJson('/api/v1/teachers')
            ->assertOk()
            ->assertJsonStructure(['data' => [['id', 'fullname', 'username']]]);
    }

    public function test_unauthenticated_lookup_returns_401(): void
    {
        $this->getJson('/api/v1/classes')->assertStatus(401)->assertJson(['errors' => null]);
    }

    public function test_guru_dashboard_shape(): void
    {
        $this->travelTo(Carbon::now()->startOfWeek(Carbon::MONDAY)->setTime(10, 0, 0));
        $this->makeConfig(['active_cycle' => 'Putaran 1']);

        $teacher = $this->makeUser(2);
        $schedule = $this->makeSchedule([
            'teacher_id'  => $teacher->id,
            'day_of_week' => 'Senin',
            'cycle_type'  => 'Putaran 1',
            'start_time'  => '07:00',
            'end_time'    => '08:00',
        ]);

        $response = $this->apiAs($teacher)->getJson('/api/v1/dashboard');
        $response->assertOk();
        $response->assertJsonPath('data.today_schedules.0.id', $schedule->id);
        $response->assertJsonPath('data.today_schedules.0.journal_filled', false);
        // ended more than 30 minutes ago -> alert
        $response->assertJsonPath('data.late_journal_alerts.0.id', $schedule->id);

        // Filling the journal clears the alert and flips journal_filled
        Journal::create([
            'schedule_id'       => $schedule->id,
            'date'              => now()->toDateString(),
            'topic_description' => 'Materi',
        ]);

        $this->apiAs($teacher)->getJson('/api/v1/dashboard')
            ->assertOk()
            ->assertJsonPath('data.today_schedules.0.journal_filled', true)
            ->assertJsonCount(0, 'data.late_journal_alerts');
    }

    public function test_waka_dashboard_shape(): void
    {
        $this->makeConfig(['active_cycle' => 'Putaran 1']);
        $teacher = $this->makeUser(2);
        $class = $this->makeClass('XII RPL 1');
        $student = $this->makeUser(4, ['class_id' => $class->id]);

        $schedule = $this->makeSchedule(['teacher_id' => $teacher->id, 'class_id' => $class->id]);
        $journal = Journal::create(['schedule_id' => $schedule->id, 'date' => now()->toDateString(), 'topic_description' => 'x']);
        \App\Models\Attendance::create(['journal_id' => $journal->id, 'student_id' => $student->id, 'status' => 'Hadir', 'date' => now()->toDateString()]);

        $response = $this->apiAs($this->makeUser(1))->getJson('/api/v1/dashboard');
        $response->assertOk();
        $response->assertJsonPath('data.attendance_today.Hadir', 1);
        $response->assertJsonStructure(['data' => ['attendance_today', 'unfilled_journals']]);
    }

    public function test_kesiswaan_dashboard_shape(): void
    {
        $class = $this->makeClass('XII RPL 1');
        $student = $this->makeUser(4, ['class_id' => $class->id]);
        LeaveRequest::create([
            'student_id'      => $student->id,
            'applicant_id'    => $student->id,
            'start_date'      => now()->addDay()->toDateString(),
            'end_date'        => now()->addDays(2)->toDateString(),
            'reason'          => 'Izin',
            'status_approval' => 'Pending',
        ]);

        $wali = $this->makeUser(3, ['class_id' => $class->id]);
        $this->apiAs($wali)->getJson('/api/v1/dashboard')
            ->assertOk()
            ->assertJsonPath('data.pending_leave_count', 1);
    }

    public function test_siswa_dashboard_shape(): void
    {
        $student = $this->makeUser(4, ['class_id' => $this->makeClass('XII RPL 1')->id]);

        $response = $this->apiAs($student)->getJson('/api/v1/dashboard');
        $response->assertOk();
        $response->assertJsonStructure(['data' => ['student' => ['id'], 'month', 'summary' => ['Hadir', 'Sakit', 'Izin', 'Alpa', 'Terlambat']]]);
        $response->assertJsonPath('data.month', now()->format('Y-m'));
    }
}
