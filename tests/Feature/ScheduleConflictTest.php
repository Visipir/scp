<?php

namespace Tests\Feature;

use App\Models\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\ScpTestHelpers;
use Tests\TestCase;

class ScheduleConflictTest extends TestCase
{
    use RefreshDatabase;
    use ScpTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'subject'     => 'Pemrograman Web',
            'day_of_week' => 'Senin',
            'start_time'  => '08:30',
            'end_time'    => '09:30',
            'cycle_type'  => 'Putaran 1',
        ], $overrides);
    }

    public function test_two_room_less_schedules_with_different_teacher_and_class_do_not_conflict(): void
    {
        $admin = $this->makeUser(1);
        $this->makeSchedule([
            'teacher_id' => $this->makeUser(2)->id,
            'class_id'   => $this->makeClass('Kelas A')->id,
            'room_id'    => null,
        ]);

        $response = $this->actingAs($admin)->post('/schedules', $this->payload([
            'teacher_id' => $this->makeUser(2)->id,
            'class_id'   => $this->makeClass('Kelas B')->id,
            'room_id'    => null,
        ]));

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('schedules.index'));
        $this->assertSame(2, Schedule::count());
    }

    public function test_same_room_overlap_reports_room_conflict(): void
    {
        $admin = $this->makeUser(1);
        $room = $this->makeRoom();
        $this->makeSchedule([
            'teacher_id' => $this->makeUser(2)->id,
            'class_id'   => $this->makeClass('Kelas A')->id,
            'room_id'    => $room->id,
        ]);

        $response = $this->actingAs($admin)->post('/schedules', $this->payload([
            'teacher_id' => $this->makeUser(2)->id,
            'class_id'   => $this->makeClass('Kelas B')->id,
            'room_id'    => $room->id,
        ]));

        $response->assertSessionHasErrors('room_id');
        $this->assertSame(1, Schedule::count());
    }

    public function test_same_class_overlap_reports_class_conflict(): void
    {
        $admin = $this->makeUser(1);
        $class = $this->makeClass('Kelas A');
        $this->makeSchedule([
            'teacher_id' => $this->makeUser(2)->id,
            'class_id'   => $class->id,
            'room_id'    => null,
        ]);

        $response = $this->actingAs($admin)->post('/schedules', $this->payload([
            'teacher_id' => $this->makeUser(2)->id,
            'class_id'   => $class->id,
            'room_id'    => $this->makeRoom()->id,
        ]));

        $response->assertSessionHasErrors('class_id');
        $this->assertSame(1, Schedule::count());
    }

    public function test_same_teacher_overlap_reports_teacher_conflict(): void
    {
        $admin = $this->makeUser(1);
        $teacher = $this->makeUser(2);
        $this->makeSchedule([
            'teacher_id' => $teacher->id,
            'class_id'   => $this->makeClass('Kelas A')->id,
            'room_id'    => null,
        ]);

        $response = $this->actingAs($admin)->post('/schedules', $this->payload([
            'teacher_id' => $teacher->id,
            'class_id'   => $this->makeClass('Kelas B')->id,
            'room_id'    => $this->makeRoom()->id,
        ]));

        $response->assertSessionHasErrors('start_time');
        $this->assertSame(1, Schedule::count());
    }

    public function test_back_to_back_schedules_do_not_conflict(): void
    {
        $admin = $this->makeUser(1);
        $teacher = $this->makeUser(2);
        $class = $this->makeClass('Kelas A');
        $room = $this->makeRoom();

        $this->makeSchedule([
            'teacher_id' => $teacher->id,
            'class_id'   => $class->id,
            'room_id'    => $room->id,
            'start_time' => '08:00',
            'end_time'   => '09:00',
        ]);

        $response = $this->actingAs($admin)->post('/schedules', $this->payload([
            'teacher_id' => $teacher->id,
            'class_id'   => $class->id,
            'room_id'    => $room->id,
            'start_time' => '09:00',
            'end_time'   => '10:00',
        ]));

        $response->assertSessionHasNoErrors();
        $this->assertSame(2, Schedule::count());
    }

    public function test_same_slot_in_different_cycle_does_not_conflict(): void
    {
        $admin = $this->makeUser(1);
        $teacher = $this->makeUser(2);
        $class = $this->makeClass('Kelas A');
        $room = $this->makeRoom();

        $this->makeSchedule([
            'teacher_id' => $teacher->id,
            'class_id'   => $class->id,
            'room_id'    => $room->id,
            'cycle_type' => 'Putaran 1',
        ]);

        $response = $this->actingAs($admin)->post('/schedules', $this->payload([
            'teacher_id' => $teacher->id,
            'class_id'   => $class->id,
            'room_id'    => $room->id,
            'start_time' => '08:30',
            'end_time'   => '09:30',
            'cycle_type' => 'Putaran 2',
        ]));

        $response->assertSessionHasNoErrors();
        $this->assertSame(2, Schedule::count());
    }

    public function test_update_does_not_conflict_with_itself(): void
    {
        $admin = $this->makeUser(1);
        $teacher = $this->makeUser(2);
        $class = $this->makeClass('Kelas A');
        $room = $this->makeRoom();

        $schedule = $this->makeSchedule([
            'teacher_id' => $teacher->id,
            'class_id'   => $class->id,
            'room_id'    => $room->id,
            'start_time' => '08:00',
            'end_time'   => '09:00',
        ]);

        $response = $this->actingAs($admin)->put('/schedules/' . $schedule->id, $this->payload([
            'teacher_id' => $teacher->id,
            'class_id'   => $class->id,
            'room_id'    => $room->id,
            'start_time' => '08:00',
            'end_time'   => '09:00',
        ]));

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('schedules.index'));
        $this->assertSame(1, Schedule::count());
    }
}
