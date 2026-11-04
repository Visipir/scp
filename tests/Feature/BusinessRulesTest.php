<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\LeaveRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Tests\Support\ScpTestHelpers;
use Tests\TestCase;

/**
 * Covers the 5 core business rules of SCP:
 * scheduling conflict, cycle toggle, schedule lock, leave->attendance sync, late-journal alert.
 */
class BusinessRulesTest extends TestCase
{
    use RefreshDatabase;
    use ScpTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
    }

    public function test_waka_can_toggle_the_active_cycle(): void
    {
        $config = $this->makeConfig(['active_cycle' => 'Putaran 1']);
        $waka = $this->makeUser(1);

        $this->actingAs($waka)->patch(route('system-configuration.toggle-cycle'))
            ->assertRedirect(route('waka.settings'));
        $this->assertSame('Putaran 2', $config->fresh()->active_cycle);

        $this->actingAs($waka)->patch(route('system-configuration.toggle-cycle'));
        $this->assertSame('Putaran 1', $config->fresh()->active_cycle);
    }

    public function test_schedule_lock_blocks_teacher_and_waka(): void
    {
        $this->makeConfig(['is_schedule_locked' => true]);
        $teacher = $this->makeUser(2);
        $waka = $this->makeUser(1);
        $schedule = $this->makeSchedule(['teacher_id' => $teacher->id]);

        $this->actingAs($teacher)->get(route('schedules.create'))->assertRedirect(route('schedules.index'));
        $this->actingAs($teacher)->get(route('schedules.edit', $schedule))->assertRedirect(route('schedules.index'));
        $this->actingAs($teacher)->put(route('schedules.update', $schedule), ['subject' => 'Uji'])->assertRedirect(route('schedules.index'));
        $this->actingAs($teacher)->delete(route('schedules.destroy', $schedule))->assertRedirect(route('schedules.index'));

        // Waka Kurikulum is also blocked when schedule is locked
        $this->actingAs($waka)->get(route('schedules.create'))->assertRedirect(route('schedules.index'));
        $this->actingAs($waka)->get(route('schedules.edit', $schedule))->assertRedirect(route('schedules.index'));
        $this->actingAs($waka)->put(route('schedules.update', $schedule), ['subject' => 'Uji'])->assertRedirect(route('schedules.index'));
        $this->actingAs($waka)->delete(route('schedules.destroy', $schedule))->assertRedirect(route('schedules.index'));
    }

    public function test_journal_creation_syncs_class_attendance(): void
    {
        Storage::fake('local');

        $teacher = $this->makeUser(2);
        $class = $this->makeClass('XII RPL 1');
        $studentPresent = $this->makeUser(4, ['class_id' => $class->id]);
        $studentOnLeave = $this->makeUser(4, ['class_id' => $class->id]);
        $schedule = $this->makeSchedule(['teacher_id' => $teacher->id, 'class_id' => $class->id]);
        $date = now()->toDateString();

        LeaveRequest::create([
            'student_id'      => $studentOnLeave->id,
            'applicant_id'    => $studentOnLeave->id,
            'start_date'      => $date,
            'end_date'        => $date,
            'reason'          => 'Izin keluarga',
            'status_approval' => 'Approved',
        ]);

        $this->actingAs($teacher)->post(route('journals.store'), [
            'schedule_id'       => $schedule->id,
            'date'              => $date,
            'topic_description' => 'Materi hari ini',
            'photo'             => UploadedFile::fake()->image('foto.jpg'),
        ])->assertRedirect(route('journals.index'));

        $this->assertSame(2, Attendance::count());
        $this->assertDatabaseHas('attendances', ['student_id' => $studentPresent->id, 'status' => 'Hadir']);
        $this->assertDatabaseHas('attendances', ['student_id' => $studentOnLeave->id, 'status' => 'Izin']);
    }

    public function test_teacher_gets_late_journal_alert(): void
    {
        $monday = Carbon::now()->startOfWeek(Carbon::MONDAY)->setTime(14, 0, 0);
        $this->travelTo($monday);

        $this->makeConfig(['active_cycle' => 'Putaran 1']);
        $teacher = $this->makeUser(2);
        $this->makeSchedule([
            'teacher_id'  => $teacher->id,
            'day_of_week' => 'Senin',
            'cycle_type'  => 'Putaran 1',
            'start_time'  => '07:00',
            'end_time'    => '07:40',
        ]);

        $response = $this->actingAs($teacher)->get('/dashboard');
        $response->assertOk();
        $response->assertSee('Jurnal KBM Belum Diisi!');
    }
}
