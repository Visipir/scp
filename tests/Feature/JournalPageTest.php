<?php
namespace Tests\Feature;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
class JournalPageTest extends TestCase {
    use RefreshDatabase;
    public function test_can_view_journal_index() {
        $this->artisan('db:seed');
        $user = User::where('username', 'waka_kurikulum')->first();
        $response = $this->actingAs($user)->get('/journals');
        $response->assertOk();
    }

    public function test_web_edit_attendance_only_allowed_same_day_and_by_owner() {
        $this->artisan('db:seed');
        $guru = User::where('username', 'guru_andi')->first();
        $otherGuru = User::where('username', 'guru_siti')->first();
        $schedule = \App\Models\Schedule::where('teacher_id', $guru->id)->first();
        if (!$schedule) {
            $class = \App\Models\SchoolClass::first();
            $schedule = \App\Models\Schedule::create([
                'teacher_id' => $guru->id,
                'class_id' => $class->id,
                'subject' => 'Tes KBM',
                'day_of_week' => 'Senin',
                'start_time' => '07:00',
                'end_time' => '08:30',
                'cycle_type' => 'Putaran 1',
            ]);
        }

        $today = \Carbon\Carbon::today('Asia/Jakarta');
        $yesterday = $today->copy()->subDay();

        $todayJournal = \App\Models\Journal::create([
            'schedule_id' => $schedule->id,
            'teacher_id' => $guru->id,
            'date' => $today->toDateString(),
            'topic_description' => 'Materi Hari Ini',
        ]);

        $yesterdayJournal = \App\Models\Journal::create([
            'schedule_id' => $schedule->id,
            'teacher_id' => $guru->id,
            'date' => $yesterday->toDateString(),
            'topic_description' => 'Materi Kemarin',
        ]);

        // Today GET edit -> 200
        $this->actingAs($guru)->get("/attendances/{$todayJournal->id}/edit")->assertOk();

        // Yesterday GET edit -> 422
        $this->actingAs($guru)->get("/attendances/{$yesterdayJournal->id}/edit")->assertStatus(422);

        // Other teacher GET edit -> 403
        $this->actingAs($otherGuru)->get("/attendances/{$todayJournal->id}/edit")->assertStatus(403);
    }
}
