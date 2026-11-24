<?php

namespace Tests\Feature;

use App\Models\AcademicCalendar;
use App\Models\Attendance;
use App\Models\Journal;
use App\Models\Room;
use App\Models\Schedule;
use App\Models\ScheduleSwap;
use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\ScpTestHelpers;
use Tests\TestCase;

class PaginationThresholdTest extends TestCase
{
    use RefreshDatabase;
    use ScpTestHelpers;

    private User $waka;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
        $this->makeConfig(['active_cycle' => 'Putaran 1']);
        $this->waka = $this->makeUser(1, ['fullname' => 'AAA Waka Utama', 'username' => 'aaa_waka']);
    }

    public function test_users_index_paginates_above_15_and_hides_pagination_at_or_below_15(): void
    {
        // Create 14 students + 1 waka = 15 total -> no pagination
        for ($i = 1; $i <= 14; $i++) {
            $this->makeUser(4, ['fullname' => sprintf('Siswa %02d', $i), 'username' => "siswa_{$i}"]);
        }

        $this->actingAs($this->waka)
            ->get(route('users.index'))
            ->assertOk()
            ->assertDontSee('Navigasi Halaman');

        // Add 2 more -> 17 total (> 15) -> paginated at 15
        $this->makeUser(4, ['fullname' => 'Siswa 15', 'username' => 'siswa_15']);
        $this->makeUser(4, ['fullname' => 'ZZZ Siswa 16', 'username' => 'siswa_16']);

        $this->actingAs($this->waka)
            ->get(route('users.index'))
            ->assertOk()
            ->assertSee('Navigasi Halaman')
            ->assertDontSee('ZZZ Siswa 16');

        $this->actingAs($this->waka)
            ->get(route('users.index', ['page' => 2]))
            ->assertOk()
            ->assertSee('ZZZ Siswa 16');
    }

    public function test_academic_calendar_paginates_above_15(): void
    {
        for ($i = 1; $i <= 16; $i++) {
            AcademicCalendar::create([
                'title'      => sprintf('Agenda %02d', $i),
                'start_date' => sprintf('2026-01-%02d', $i),
                'end_date'   => sprintf('2026-01-%02d', $i),
                'category'   => 'agenda_sekolah',
                'is_kbm_off' => false,
            ]);
        }

        $this->actingAs($this->waka)
            ->get(route('academic-calendar.index'))
            ->assertOk()
            ->assertSee('Navigasi Halaman')
            ->assertSee('Total 16 agenda')
            ->assertSee('Agenda 16')
            ->assertDontSee('Agenda 01');

        $this->actingAs($this->waka)
            ->get(route('academic-calendar.index', ['page' => 2]))
            ->assertOk()
            ->assertSee('Agenda 01');
    }

    public function test_class_show_students_paginate_above_15_while_preserving_total_kpi(): void
    {
        $class = $this->makeClass('XI RPL 1');

        for ($i = 1; $i <= 16; $i++) {
            $this->makeUser(4, [
                'fullname'          => sprintf('Murid %02d', $i),
                'username'          => "murid_{$i}",
                'class_id'          => $class->id,
                'attendance_number' => $i,
            ]);
        }

        $this->actingAs($this->waka)
            ->get(route('classes.show', $class))
            ->assertOk()
            ->assertSee('Navigasi Halaman')
            ->assertSee('Daftar Siswa (16)')
            ->assertSee('Murid 15')
            ->assertDontSee('Murid 16');

        $this->actingAs($this->waka)
            ->get(route('classes.show', [$class, 'students_page' => 2]))
            ->assertOk()
            ->assertSee('Murid 16')
            ->assertDontSee('Murid 15');
    }

    public function test_student_attendance_paginates_above_15(): void
    {
        $class = $this->makeClass('XII RPL 1');
        $teacher = $this->makeUser(2);
        $student = $this->makeUser(4, ['class_id' => $class->id]);
        $schedule = $this->makeSchedule(['teacher_id' => $teacher->id, 'class_id' => $class->id, 'subject' => 'Matematika']);

        for ($i = 1; $i <= 16; $i++) {
            $date = sprintf('2026-10-%02d', $i);
            $journal = Journal::create([
                'schedule_id'       => $schedule->id,
                'teacher_id'        => $teacher->id,
                'date'              => $date,
                'topic_description' => sprintf('Pertemuan %02d', $i),
            ]);
            Attendance::create([
                'journal_id' => $journal->id,
                'student_id' => $student->id,
                'date'       => $date,
                'status'     => 'Hadir',
                'notes'      => sprintf('Catatan %02d', $i),
            ]);
        }

        $this->actingAs($student)
            ->get(route('student.attendance.index', ['month' => '2026-10']))
            ->assertOk()
            ->assertSee('Navigasi Halaman')
            ->assertSee('Catatan 16')
            ->assertDontSee('Catatan 01');

        $this->actingAs($student)
            ->get(route('student.attendance.index', ['month' => '2026-10', 'page' => 2]))
            ->assertOk()
            ->assertSee('Catatan 01');
    }

    public function test_schedule_swaps_teacher_recap_and_guru_inbox_paginate_above_15(): void
    {
        $class = $this->makeClass('X RPL 1');
        $teachers = [];
        for ($i = 1; $i <= 16; $i++) {
            $teachers[] = $this->makeUser(2, [
                'fullname' => sprintf('Guru %02d', $i),
                'username' => "guru_pg_{$i}",
            ]);
        }

        // Waka report -> teachers tab paginates > 15 teachers
        $page1 = $this->actingAs($this->waka)
            ->get(route('schedule-swaps.index', ['tab' => 'teachers']))
            ->assertOk()
            ->assertSee('16 Guru Terdaftar')
            ->assertSee('Navigasi Halaman')
            ->assertSee('teachers_page=2', false);

        $recap1 = $page1->viewData('teacherRecap');
        $this->assertCount(15, $recap1);
        $this->assertSame(16, $recap1->total());
        $this->assertFalse(collect($recap1->items())->pluck('teacher.fullname')->contains('Guru 16'));

        $page2 = $this->actingAs($this->waka)
            ->get(route('schedule-swaps.index', ['tab' => 'teachers', 'teachers_page' => 2]))
            ->assertOk();

        $recap2 = $page2->viewData('teacherRecap');
        $this->assertCount(1, $recap2);
        $this->assertTrue(collect($recap2->items())->pluck('teacher.fullname')->contains('Guru 16'));

        // Guru inbox paginates > 15 swaps
        $targetTeacher = $teachers[0];
        $requester = $teachers[1];
        $s1 = $this->makeSchedule(['teacher_id' => $requester->id, 'class_id' => $class->id, 'day_of_week' => 'Senin']);
        $s2 = $this->makeSchedule(['teacher_id' => $targetTeacher->id, 'class_id' => $class->id, 'day_of_week' => 'Selasa']);

        for ($i = 1; $i <= 16; $i++) {
            ScheduleSwap::create([
                'requester_id'          => $requester->id,
                'target_id'             => $targetTeacher->id,
                'requester_schedule_id' => $s1->id,
                'target_schedule_id'    => $s2->id,
                'swap_date'             => now()->addDays($i)->toDateString(),
                'cycle'                 => 'Putaran 1',
                'status'                => 'approved',
                'note'                  => sprintf('Swap Note %02d', $i),
            ]);
        }

        $this->actingAs($targetTeacher)
            ->get(route('schedule-swaps.index', ['tab' => 'inbox']))
            ->assertOk()
            ->assertSee('Navigasi Halaman');

        $page2 = $this->actingAs($targetTeacher)
            ->get(route('schedule-swaps.index', ['tab' => 'inbox', 'inbox_page' => 2]))
            ->assertOk();
        $page2->assertSee('Navigasi Halaman');
    }

    public function test_schedules_and_recap_render_client_pagination_above_15(): void
    {
        $class = $this->makeClass('XI TKJ 1');
        $teacher = $this->makeUser(2);
        $room = Room::create(['room_name' => 'Lab 1']);

        for ($i = 1; $i <= 16; $i++) {
            Schedule::create([
                'teacher_id'   => $teacher->id,
                'class_id'     => $class->id,
                'room_id'      => $room->id,
                'subject'      => "Mapel {$i}",
                'day_of_week'  => 'Senin',
                'start_time'   => '08:00:00',
                'end_time'     => '09:00:00',
                'start_period' => 1,
                'end_period'   => 2,
                'cycle_type'   => 'Putaran 1',
            ]);
        }

        $this->actingAs($this->waka)
            ->get(route('schedules.index'))
            ->assertOk()
            ->assertSee('pageSize: 15', false);

        $this->actingAs($this->waka)
            ->get(route('attendance.recap'))
            ->assertOk()
            ->assertSee('pageSize: 15', false)
            ->assertDontSee('max-h-[480px] overflow-y-auto', false)
            ->assertDontSee('max-h-48 overflow-y-auto', false);
    }
}
