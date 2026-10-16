<?php

namespace Tests\Feature\Api\V1;

use App\Models\Attendance;
use App\Models\Journal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\ScpTestHelpers;
use Tests\TestCase;

class AttendanceApiTest extends TestCase
{
    use RefreshDatabase;
    use ScpTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
    }

    private function record(User $student, string $date, string $status): void
    {
        $schedule = $this->makeSchedule(['class_id' => $student->class_id]);
        $journal = Journal::create(['schedule_id' => $schedule->id, 'date' => $date, 'topic_description' => 'x']);
        Attendance::create(['journal_id' => $journal->id, 'student_id' => $student->id, 'status' => $status, 'date' => $date]);
    }

    public function test_requires_role_four(): void
    {
        $this->getJson('/api/v1/attendance')->assertStatus(401);
        $this->apiAs($this->makeUser(2))->getJson('/api/v1/attendance')->assertStatus(403);
    }

    public function test_student_summary_and_records(): void
    {
        $student = $this->makeUser(4, ['class_id' => $this->makeClass('XII RPL 1')->id]);
        $month = now()->format('Y-m');
        $this->record($student, now()->toDateString(), 'Hadir');
        $this->record($student, now()->startOfMonth()->toDateString(), 'Alpa');

        $response = $this->apiAs($student)->getJson('/api/v1/attendance?month='.$month);
        $response->assertOk();
        $response->assertJsonPath('data.summary.Hadir', 1);
        $response->assertJsonPath('data.summary.Alpa', 1);
        $response->assertJsonStructure(['data' => ['student' => ['id'], 'month', 'summary', 'records' => [['date', 'status']]]]);
    }

    public function test_parent_child_selection_and_forbidden_other(): void
    {
        $parent = $this->makeUser(4);
        $child = $this->makeUser(4, ['class_id' => $this->makeClass('XII RPL 1')->id, 'parent_id' => $parent->id]);
        $stranger = $this->makeUser(4, ['class_id' => $this->makeClass('XII RPL 2')->id]);
        $this->record($child, now()->toDateString(), 'Sakit');

        $this->apiAs($parent)->getJson('/api/v1/attendance?student_id='.$child->id)
            ->assertOk()->assertJsonPath('data.summary.Sakit', 1);

        $this->apiAs($parent)->getJson('/api/v1/attendance?student_id='.$stranger->id)
            ->assertStatus(403);
    }

    public function test_month_filter(): void
    {
        $student = $this->makeUser(4, ['class_id' => $this->makeClass('XII RPL 1')->id]);
        $this->record($student, now()->toDateString(), 'Hadir');

        $previous = now()->subMonthNoOverflow()->format('Y-m');
        $this->apiAs($student)->getJson('/api/v1/attendance?month='.$previous)
            ->assertOk()->assertJsonPath('data.summary.Hadir', 0);
    }
}
