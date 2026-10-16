<?php

namespace Tests\Feature\Api\V1;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Support\ScpTestHelpers;
use Tests\TestCase;

class SystemApiTest extends TestCase
{
    use RefreshDatabase;
    use ScpTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
    }

    public function test_system_endpoints_are_waka_only(): void
    {
        $this->makeConfig();
        $this->apiAs($this->makeUser(2))->patchJson('/api/v1/system/cycle', ['active_cycle' => 'Putaran 2'])->assertStatus(403);
        $this->apiAs($this->makeUser(2))->patchJson('/api/v1/system/lock', ['is_schedule_locked' => true])->assertStatus(403);
        $this->apiAs($this->makeUser(2))->getJson('/api/v1/system/unfilled-journals')->assertStatus(403);
    }

    public function test_cycle_is_set_explicitly(): void
    {
        $config = $this->makeConfig(['active_cycle' => 'Putaran 1']);

        $this->apiAs($this->makeUser(1))->patchJson('/api/v1/system/cycle', ['active_cycle' => 'Putaran 2'])
            ->assertOk()->assertJsonPath('data.active_cycle', 'Putaran 2');

        $this->assertSame('Putaran 2', $config->fresh()->active_cycle);

        $this->apiAs($this->makeUser(1))->patchJson('/api/v1/system/cycle', ['active_cycle' => 'Salah'])
            ->assertStatus(422)->assertJsonStructure(['errors' => ['active_cycle']]);
    }

    public function test_lock_is_set_explicitly(): void
    {
        $config = $this->makeConfig(['is_schedule_locked' => false]);

        $this->apiAs($this->makeUser(1))->patchJson('/api/v1/system/lock', ['is_schedule_locked' => true])
            ->assertOk()->assertJsonPath('data.is_schedule_locked', true);

        $this->assertTrue((bool) $config->fresh()->is_schedule_locked);
    }

    public function test_unfilled_journals_lists_teachers(): void
    {
        $this->travelTo(Carbon::now()->startOfWeek(Carbon::MONDAY)->setTime(14, 0, 0));
        $this->makeConfig(['active_cycle' => 'Putaran 1']);

        $teacher = $this->makeUser(2, ['fullname' => 'Pak Andi']);
        $this->makeSchedule([
            'teacher_id'  => $teacher->id,
            'day_of_week' => 'Senin',
            'cycle_type'  => 'Putaran 1',
            'start_time'  => '07:00',
            'end_time'    => '08:00',
        ]);

        $response = $this->apiAs($this->makeUser(1))->getJson('/api/v1/system/unfilled-journals');
        $response->assertOk();
        $response->assertJsonPath('data.teachers.0.teacher_id', $teacher->id);
        $response->assertJsonStructure(['data' => ['teachers' => [['teacher_id', 'teacher', 'schedules']]]]);
    }
}
