<?php

namespace Tests\Feature;

use App\Models\Journal;
use App\Models\Notification;
use App\Models\Schedule;
use App\Models\ScheduleSwap;
use App\Models\SchoolClass;
use App\Models\SystemConfiguration;
use App\Models\User;
use App\Services\ScheduleSwapService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Support\ScpTestHelpers;
use Tests\TestCase;

class ScheduleSwapTest extends TestCase
{
    use RefreshDatabase;
    use ScpTestHelpers;

    protected User $guru1;
    protected User $guru2;
    protected User $waka;
    protected SchoolClass $class1;
    protected SchoolClass $class2;
    protected Schedule $sched1;
    protected Schedule $sched2;
    protected string $swapDate;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();

        $this->waka = $this->makeUser(1, ['username' => 'waka_kurikulum', 'fullname' => 'Waka Kurikulum']);
        $this->guru1 = $this->makeUser(2, ['username' => 'guru_budi', 'fullname' => 'Budi Santoso']);
        $this->guru2 = $this->makeUser(2, ['username' => 'guru_siti', 'fullname' => 'Siti Rahma']);

        $this->class1 = $this->makeClass('X PPLG 1');
        $this->class2 = $this->makeClass('XI PPLG 2');

        $this->makeConfig([
            'active_cycle'       => 'Putaran 1',
            'is_schedule_locked' => false,
        ]);

        // Find next Monday date
        $nextMonday = Carbon::now()->next(Carbon::MONDAY);
        $this->swapDate = $nextMonday->toDateString();

        // Guru 1 teaches Monday 07:00 - 08:30 in class 1
        $this->sched1 = Schedule::create([
            'teacher_id'   => $this->guru1->id,
            'class_id'     => $this->class1->id,
            'subject'      => 'Pemrograman Web',
            'day_of_week'  => 'Senin',
            'start_time'   => '07:00:00',
            'end_time'     => '08:30:00',
            'cycle_type'   => 'Putaran 1',
        ]);

        // Guru 2 teaches Monday 09:00 - 10:30 in class 2
        $this->sched2 = Schedule::create([
            'teacher_id'   => $this->guru2->id,
            'class_id'     => $this->class2->id,
            'subject'      => 'Basis Data',
            'day_of_week'  => 'Senin',
            'start_time'   => '09:00:00',
            'end_time'     => '10:30:00',
            'cycle_type'   => 'Putaran 1',
        ]);
    }

    public function test_teacher_can_fetch_candidates_for_swap(): void
    {
        $response = $this->apiAs($this->guru1)->getJson("/api/v1/schedule-swaps/candidates?date={$this->swapDate}&schedule_id={$this->sched1->id}");

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'candidates',
            ],
        ]);

        $candidates = $response->json('data.candidates');
        $this->assertNotEmpty($candidates);
        $this->assertEquals($this->guru2->id, $candidates[0]['teacher_id']);
    }

    public function test_teacher_can_create_swap_request(): void
    {
        $response = $this->apiAs($this->guru1)->postJson('/api/v1/schedule-swaps', [
            'requester_schedule_id' => $this->sched1->id,
            'target_schedule_id'    => $this->sched2->id,
            'swap_date'             => $this->swapDate,
            'note'                  => 'Ada tugas dinas luar kota',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('schedule_swaps', [
            'requester_id'          => $this->guru1->id,
            'target_id'             => $this->guru2->id,
            'requester_schedule_id' => $this->sched1->id,
            'target_schedule_id'    => $this->sched2->id,
            'status'                => 'pending',
            'note'                  => 'Ada tugas dinas luar kota',
        ]);

        // Check notification created for target teacher
        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->guru2->id,
            'type'    => 'schedule_swap_request',
        ]);
    }

    public function test_cannot_create_swap_when_schedule_locked(): void
    {
        SystemConfiguration::first()->update(['is_schedule_locked' => true]);

        $response = $this->apiAs($this->guru1)->postJson('/api/v1/schedule-swaps', [
            'requester_schedule_id' => $this->sched1->id,
            'target_schedule_id'    => $this->sched2->id,
            'swap_date'             => $this->swapDate,
            'note'                  => 'Mencoba tukar saat jadwal dikunci',
        ]);

        $response->assertStatus(422);
    }

    public function test_cannot_create_duplicate_pending_swap_for_same_schedule_and_date(): void
    {
        // First swap request
        ScheduleSwap::create([
            'requester_id'          => $this->guru1->id,
            'target_id'             => $this->guru2->id,
            'requester_schedule_id' => $this->sched1->id,
            'target_schedule_id'    => $this->sched2->id,
            'cycle'                 => 'Putaran 1',
            'swap_date'             => $this->swapDate,
            'status'                => 'pending',
        ]);

        // Attempt second swap request for same schedule & date
        $response = $this->apiAs($this->guru1)->postJson('/api/v1/schedule-swaps', [
            'requester_schedule_id' => $this->sched1->id,
            'target_schedule_id'    => $this->sched2->id,
            'swap_date'             => $this->swapDate,
            'note'                  => 'Duplicate pending',
        ]);

        $response->assertStatus(422);
    }

    public function test_cannot_create_swap_if_journal_already_exists(): void
    {
        // Journal already filled for sched1 on this date
        Journal::create([
            'teacher_id'        => $this->guru1->id,
            'schedule_id'       => $this->sched1->id,
            'date'              => $this->swapDate,
            'topic_description' => 'Materi HTML Dasar',
        ]);

        $response = $this->apiAs($this->guru1)->postJson('/api/v1/schedule-swaps', [
            'requester_schedule_id' => $this->sched1->id,
            'target_schedule_id'    => $this->sched2->id,
            'swap_date'             => $this->swapDate,
        ]);

        $response->assertStatus(422);
    }

    public function test_target_teacher_can_approve_swap_and_effective_schedules_update(): void
    {
        $swap = ScheduleSwap::create([
            'requester_id'          => $this->guru1->id,
            'target_id'             => $this->guru2->id,
            'requester_schedule_id' => $this->sched1->id,
            'target_schedule_id'    => $this->sched2->id,
            'cycle'                 => 'Putaran 1',
            'swap_date'             => $this->swapDate,
            'status'                => 'pending',
        ]);

        // Non-target cannot approve
        $otherGuru = $this->makeUser(2);
        $this->apiAs($otherGuru)->postJson("/api/v1/schedule-swaps/{$swap->id}/approve")
            ->assertForbidden();

        // Target teacher approves
        $response = $this->apiAs($this->guru2)->postJson("/api/v1/schedule-swaps/{$swap->id}/approve");
        $response->assertOk();

        $swap->refresh();
        $this->assertSame('approved', $swap->status);
        $this->assertNotNull($swap->responded_at);

        // Verification of Master Schedule untouched
        $this->sched1->refresh();
        $this->sched2->refresh();
        $this->assertSame($this->guru1->id, $this->sched1->teacher_id);
        $this->assertSame($this->guru2->id, $this->sched2->teacher_id);

        // Single Source of Truth: Effective schedules for swap date
        $swapService = app(ScheduleSwapService::class);

        $effGuru1 = $swapService->getEffectiveSchedulesForTeacher($this->guru1->id, $this->swapDate);
        $effGuru2 = $swapService->getEffectiveSchedulesForTeacher($this->guru2->id, $this->swapDate);

        // Guru 1 now has sched2 (Basis Data)
        $this->assertCount(1, $effGuru1);
        $this->assertSame($this->sched2->id, $effGuru1->first()->id);
        $this->assertTrue($effGuru1->first()->is_substitute);

        // Guru 2 now has sched1 (Pemrograman Web)
        $this->assertCount(1, $effGuru2);
        $this->assertSame($this->sched1->id, $effGuru2->first()->id);
        $this->assertTrue($effGuru2->first()->is_substitute);

        // Notification created for Guru 1
        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->guru1->id,
            'type'    => 'schedule_swap_approved',
        ]);
    }

    public function test_approval_fails_when_time_overlap_conflict_occurs(): void
    {
        // Guru 1 has another schedule that overlaps with sched2 (09:00 - 10:30)
        Schedule::create([
            'teacher_id'   => $this->guru1->id,
            'class_id'     => $this->class1->id,
            'subject'      => 'Matematika Terapan',
            'day_of_week'  => 'Senin',
            'start_time'   => '09:30:00', // Overlaps with 09:00 - 10:30
            'end_time'     => '11:00:00',
            'cycle_type'   => 'Putaran 1',
        ]);

        $swap = ScheduleSwap::create([
            'requester_id'          => $this->guru1->id,
            'target_id'             => $this->guru2->id,
            'requester_schedule_id' => $this->sched1->id,
            'target_schedule_id'    => $this->sched2->id,
            'cycle'                 => 'Putaran 1',
            'swap_date'             => $this->swapDate,
            'status'                => 'pending',
        ]);

        // Attempt approve
        $response = $this->apiAs($this->guru2)->postJson("/api/v1/schedule-swaps/{$swap->id}/approve");
        $response->assertStatus(422);

        $this->assertSame('pending', $swap->fresh()->status);
    }

    public function test_substitute_teacher_can_fill_journal_and_original_teacher_cannot(): void
    {
        $swap = ScheduleSwap::create([
            'requester_id'          => $this->guru1->id,
            'target_id'             => $this->guru2->id,
            'requester_schedule_id' => $this->sched1->id,
            'target_schedule_id'    => $this->sched2->id,
            'cycle'                 => 'Putaran 1',
            'swap_date'             => $this->swapDate,
            'status'                => 'approved',
            'responded_at'          => now(),
        ]);

        Storage::fake('local');

        // Guru 2 is substitute teacher for sched1 on swapDate
        // Original teacher Guru 1 cannot fill journal for sched1 on this date
        $deniedResponse = $this->apiAs($this->guru1)->post('/api/v1/journals', [
            'schedule_id'       => $this->sched1->id,
            'date'              => $this->swapDate,
            'topic_description' => 'Original teacher attempting to fill journal',
            'photo'             => UploadedFile::fake()->image('test1.jpg'),
        ]);
        $deniedResponse->assertStatus(403);

        // Substitute teacher Guru 2 can fill journal for sched1 on this date
        $successResponse = $this->apiAs($this->guru2)->post('/api/v1/journals', [
            'schedule_id'       => $this->sched1->id,
            'date'              => $this->swapDate,
            'topic_description' => 'Substitute teacher teaching topic',
            'photo'             => UploadedFile::fake()->image('test2.jpg'),
        ]);
        $successResponse->assertStatus(201);

        $this->assertDatabaseHas('journals', [
            'schedule_id' => $this->sched1->id,
            'teacher_id'  => $this->guru2->id,
            'swap_id'     => $swap->id,
        ]);
    }

    public function test_target_teacher_can_reject_swap(): void
    {
        $swap = ScheduleSwap::create([
            'requester_id'          => $this->guru1->id,
            'target_id'             => $this->guru2->id,
            'requester_schedule_id' => $this->sched1->id,
            'target_schedule_id'    => $this->sched2->id,
            'cycle'                 => 'Putaran 1',
            'swap_date'             => $this->swapDate,
            'status'                => 'pending',
        ]);

        $response = $this->apiAs($this->guru2)->postJson("/api/v1/schedule-swaps/{$swap->id}/reject", [
            'reason' => 'Sedang ada agenda lain pada jam tersebut',
        ]);

        $response->assertOk();
        $this->assertSame('rejected', $swap->fresh()->status);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->guru1->id,
            'type'    => 'schedule_swap_rejected',
        ]);
    }

    public function test_requester_can_cancel_pending_swap(): void
    {
        $swap = ScheduleSwap::create([
            'requester_id'          => $this->guru1->id,
            'target_id'             => $this->guru2->id,
            'requester_schedule_id' => $this->sched1->id,
            'target_schedule_id'    => $this->sched2->id,
            'cycle'                 => 'Putaran 1',
            'swap_date'             => $this->swapDate,
            'status'                => 'pending',
        ]);

        $response = $this->apiAs($this->guru1)->postJson("/api/v1/schedule-swaps/{$swap->id}/cancel");
        $response->assertOk();
        $this->assertSame('cancelled', $swap->fresh()->status);
    }

    public function test_waka_can_view_all_swaps_monitoring(): void
    {
        ScheduleSwap::create([
            'requester_id'          => $this->guru1->id,
            'target_id'             => $this->guru2->id,
            'requester_schedule_id' => $this->sched1->id,
            'target_schedule_id'    => $this->sched2->id,
            'cycle'                 => 'Putaran 1',
            'swap_date'             => $this->swapDate,
            'status'                => 'approved',
        ]);

        // Waka view monitoring web
        $webResponse = $this->actingAs($this->waka)->get('/schedule-swaps');
        $webResponse->assertOk();
        $webResponse->assertSee('Monitoring Tukar Jadwal Guru');
        $webResponse->assertSee('Budi Santoso');
        $webResponse->assertSee('Siti Rahma');

        // Waka view monitoring API
        $apiResponse = $this->apiAs($this->waka)->getJson('/api/v1/schedule-swaps');
        $apiResponse->assertOk();
        $apiResponse->assertJsonStructure([
            'data' => [
                'swaps' => [
                    '*' => ['id', 'status', 'swap_date'],
                ],
            ],
        ]);
    }
}
