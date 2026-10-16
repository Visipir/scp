<?php

namespace Tests\Feature\Api\V1;

use App\Models\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\ScpTestHelpers;
use Tests\TestCase;

class ScheduleApiTest extends TestCase
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
            'class_id'     => $this->makeClass('XII RPL 1')->id,
            'room_id'      => null,
            'subject'      => 'Pemrograman Web',
            'day_of_week'  => 'Senin',
            'start_time'   => '08:00',
            'end_time'     => '09:00',
            'cycle_type'   => 'Putaran 1',
        ], $overrides);
    }

    public function test_index_requires_auth(): void
    {
        $this->getJson('/api/v1/schedules')->assertStatus(401);
    }

    public function test_index_forbidden_for_wrong_role(): void
    {
        $this->apiAs($this->makeUser(3))->getJson('/api/v1/schedules')->assertStatus(403);
    }

    public function test_guru_only_sees_own_schedules(): void
    {
        $this->makeConfig();
        $guruA = $this->makeUser(2);
        $guruB = $this->makeUser(2);
        $own = $this->makeSchedule(['teacher_id' => $guruA->id, 'cycle_type' => 'Putaran 1']);
        $this->makeSchedule(['teacher_id' => $guruB->id, 'cycle_type' => 'Putaran 1']);

        $response = $this->apiAs($guruA)->getJson('/api/v1/schedules');
        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.id', $own->id);
    }

    public function test_waka_sees_all_and_can_filter_by_teacher(): void
    {
        $this->makeConfig();
        $guruA = $this->makeUser(2);
        $guruB = $this->makeUser(2);
        $this->makeSchedule(['teacher_id' => $guruA->id, 'cycle_type' => 'Putaran 1']);
        $this->makeSchedule(['teacher_id' => $guruB->id, 'cycle_type' => 'Putaran 1']);

        $this->apiAs($this->makeUser(1))->getJson('/api/v1/schedules')->assertJsonCount(2, 'data');
        $this->apiAs($this->makeUser(1))->getJson('/api/v1/schedules?teacher_id='.$guruA->id)->assertJsonCount(1, 'data');
    }

    public function test_waka_can_create_schedule(): void
    {
        $this->makeConfig();
        $waka = $this->makeUser(1);

        $response = $this->apiAs($waka)->postJson('/api/v1/schedules', $this->payload());
        $response->assertStatus(201);
        $response->assertJsonStructure(['data' => ['id', 'subject', 'start_time', 'end_time', 'class', 'cycle_type']]);
        $this->assertSame(1, Schedule::count());
    }

    public function test_guru_create_ignores_teacher_id(): void
    {
        $this->makeConfig();
        $guru = $this->makeUser(2);
        $other = $this->makeUser(2);

        $this->apiAs($guru)->postJson('/api/v1/schedules', $this->payload(['teacher_id' => $other->id]))
            ->assertStatus(201);

        $this->assertSame($guru->id, Schedule::first()->teacher_id);
    }

    public function test_teacher_conflict_returns_422_with_field_key(): void
    {
        $this->makeConfig();
        $teacher = $this->makeUser(2);
        $this->makeSchedule([
            'teacher_id' => $teacher->id,
            'class_id'   => $this->makeClass('Kelas A')->id,
            'room_id'    => $this->makeRoom('R1')->id,
            'start_time' => '08:00',
            'end_time'   => '09:00',
        ]);

        $response = $this->apiAs($this->makeUser(1))->postJson('/api/v1/schedules', $this->payload([
            'teacher_id' => $teacher->id,
            'class_id'   => $this->makeClass('Kelas B')->id,
            'room_id'    => $this->makeRoom('R2')->id,
            'start_time' => '08:30',
            'end_time'   => '09:30',
        ]));

        $response->assertStatus(422);
        $response->assertJsonStructure(['message', 'errors' => ['start_time']]);
    }

    public function test_null_room_does_not_falsely_conflict(): void
    {
        $this->makeConfig();
        $this->makeSchedule([
            'teacher_id' => $this->makeUser(2)->id,
            'class_id'   => $this->makeClass('Kelas A')->id,
            'room_id'    => null,
            'start_time' => '08:00',
            'end_time'   => '09:00',
        ]);

        $this->apiAs($this->makeUser(1))->postJson('/api/v1/schedules', $this->payload([
            'teacher_id' => $this->makeUser(2)->id,
            'class_id'   => $this->makeClass('Kelas B')->id,
            'room_id'    => null,
            'start_time' => '08:30',
            'end_time'   => '09:30',
        ]))->assertStatus(201);
    }

    public function test_lock_blocks_guru_but_allows_waka(): void
    {
        $this->makeConfig(['is_schedule_locked' => true]);
        $guru = $this->makeUser(2);

        $this->apiAs($guru)->postJson('/api/v1/schedules', $this->payload())
            ->assertStatus(403)
            ->assertJson(['errors' => null]);

        $this->apiAs($this->makeUser(1))->postJson('/api/v1/schedules', $this->payload())
            ->assertStatus(201);
    }

    public function test_guru_cannot_update_others_schedule(): void
    {
        $this->makeConfig();
        $guruA = $this->makeUser(2);
        $guruB = $this->makeUser(2);
        $schedule = $this->makeSchedule(['teacher_id' => $guruB->id]);

        $this->apiAs($guruA)->putJson('/api/v1/schedules/'.$schedule->id, $this->payload())
            ->assertStatus(403);
    }

    public function test_validation_error_shape(): void
    {
        $this->makeConfig();

        $this->apiAs($this->makeUser(1))->postJson('/api/v1/schedules', [])
            ->assertStatus(422)
            ->assertJsonStructure(['message', 'errors' => ['class_id', 'subject', 'day_of_week']]);
    }
}
