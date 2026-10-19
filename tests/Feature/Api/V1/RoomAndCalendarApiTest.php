<?php

namespace Tests\Feature\Api\V1;

use App\Models\AcademicCalendar;
use App\Models\IneffectiveDay;
use App\Models\Room;
use App\Models\RoomSchedule;
use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\ScpTestHelpers;
use Tests\TestCase;

class RoomAndCalendarApiTest extends TestCase
{
    use RefreshDatabase;
    use ScpTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
    }

    public function test_room_schedules_require_proper_roles(): void
    {
        $this->getJson('/api/v1/room-schedules')->assertStatus(401);
        $this->postJson('/api/v1/room-schedules', [])->assertStatus(401);

        $student = $this->makeUser(4);
        $this->apiAs($student)->getJson('/api/v1/room-schedules')->assertStatus(403);
        $this->apiAs($student)->postJson('/api/v1/room-schedules', [])->assertStatus(403);
    }

    public function test_room_schedules_crud_and_conflict_validation(): void
    {
        $teacher = $this->makeUser(2);
        $room = $this->makeRoom('Lab RPL 1');
        $class = $this->makeClass('X RPL 1');

        // Validation error
        $this->apiAs($teacher)->postJson('/api/v1/room-schedules', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['room_id', 'class_id', 'day_of_week', 'period', 'cycle_type']);

        // Create
        $response = $this->apiAs($teacher)->postJson('/api/v1/room-schedules', [
            'room_id'     => $room->id,
            'class_id'    => $class->id,
            'teacher_id'  => $teacher->id,
            'subject'     => 'Pemrograman Web',
            'day_of_week' => 'Senin',
            'period'      => 1,
            'cycle_type'  => 'Putaran 1',
        ])->assertStatus(201);

        $createdId = $response->json('data.id');
        $this->assertNotNull($createdId);
        $this->assertNotNull($response->json('data.start_time'));
        $this->assertNotNull($response->json('data.end_time'));

        // Conflict check
        $this->apiAs($teacher)->postJson('/api/v1/room-schedules', [
            'room_id'     => $room->id,
            'class_id'    => $class->id,
            'day_of_week' => 'Senin',
            'period'      => 1,
            'cycle_type'  => 'Putaran 1',
        ])->assertStatus(422);

        // List with filter
        $list = $this->apiAs($teacher)->getJson('/api/v1/room-schedules?day_of_week=Senin&cycle_type=Putaran%201')
            ->assertOk()
            ->json('data');
        $this->assertCount(1, $list);
        $this->assertSame($createdId, $list[0]['id']);

        // Delete
        $this->apiAs($teacher)->deleteJson('/api/v1/room-schedules/' . $createdId)->assertOk();
        $this->assertDatabaseMissing('room_schedules', ['id' => $createdId]);
    }

    public function test_academic_calendar_endpoints(): void
    {
        $this->getJson('/api/v1/academic-calendar')->assertStatus(401);
        $this->postJson('/api/v1/academic-calendar', [])->assertStatus(401);

        $guru = $this->makeUser(2);
        $siswa = $this->makeUser(4);
        $this->apiAs($guru)->getJson('/api/v1/academic-calendar')->assertStatus(403);
        $this->apiAs($siswa)->getJson('/api/v1/academic-calendar')->assertStatus(403);

        $admin = $this->makeUser(1);

        // Validation
        $this->apiAs($admin)->postJson('/api/v1/academic-calendar', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['title', 'start_date', 'end_date', 'category']);

        // Create
        $response = $this->apiAs($admin)->postJson('/api/v1/academic-calendar', [
            'title'       => 'Libur Idul Fitri',
            'start_date'  => '2026-03-20',
            'end_date'    => '2026-03-25',
            'category'    => 'libur_nasional',
            'is_kbm_off'  => true,
            'description' => 'Hari Raya Idul Fitri',
        ])->assertStatus(201);

        $calId = $response->json('data.id');
        $this->assertNotNull($calId);

        // Calendar overview by month/year
        $overview = $this->apiAs($admin)->getJson('/api/v1/academic-calendar?year=2026&month=3')
            ->assertOk()
            ->json('data');

        $this->assertSame(2026, $overview['year']);
        $this->assertSame(3, $overview['month']);
        $this->assertArrayHasKey('stats', $overview);
        $this->assertCount(1, $overview['events']);

        // Delete
        $this->apiAs($admin)->deleteJson('/api/v1/academic-calendar/' . $calId)->assertOk();
        $this->assertDatabaseMissing('academic_calendars', ['id' => $calId]);
    }

    public function test_ineffective_days_endpoints(): void
    {
        $this->getJson('/api/v1/ineffective-days')->assertStatus(401);
        $this->postJson('/api/v1/ineffective-days', [])->assertStatus(401);

        $guru = $this->makeUser(2);
        $this->apiAs($guru)->getJson('/api/v1/ineffective-days')->assertStatus(403);

        $bk = $this->makeUser(3);

        // Validation
        $this->apiAs($bk)->postJson('/api/v1/ineffective-days', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['start_date', 'description']);

        // Create range
        $response = $this->apiAs($bk)->postJson('/api/v1/ineffective-days', [
            'start_date'  => '2026-11-10',
            'end_date'    => '2026-11-12',
            'description' => 'Class Meeting Semester Gasal',
        ])->assertStatus(201);

        $data = $response->json('data');
        $this->assertCount(3, $data);

        // List
        $list = $this->apiAs($bk)->getJson('/api/v1/ineffective-days')
            ->assertOk()
            ->json('data');
        $this->assertCount(3, $list);

        // Delete
        $firstId = $data[0]['id'];
        $this->apiAs($bk)->deleteJson('/api/v1/ineffective-days/' . $firstId)->assertOk();
        $this->assertDatabaseMissing('ineffective_days', ['id' => $firstId]);
    }
}
