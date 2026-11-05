<?php
namespace Tests\Feature;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
class DashboardTest extends TestCase {
    use RefreshDatabase;
    public function test_can_view_dashboard() {
        $this->artisan('db:seed');
        $user = User::where('username', 'waka_kurikulum')->first();
        $response = $this->actingAs($user)->get('/dashboard');
        $response->assertOk();
    }

    public function test_waka_dashboard_alert_30_min_before_class_end_and_after_end() {
        $this->artisan('db:seed');
        $waka = User::where('username', 'waka_kurikulum')->first();
        $guru = User::where('username', 'guru_andi')->first();
        $class = \App\Models\SchoolClass::first();

        $config = \App\Models\SystemConfiguration::first();
        $config->update(['active_cycle' => 'Putaran 1']);

        // A Monday in Asia/Jakarta
        $monday = \Carbon\Carbon::parse('2026-11-09 00:00:00', 'Asia/Jakarta');

        $schedule = \App\Models\Schedule::create([
            'teacher_id'  => $guru->id,
            'class_id'    => $class->id,
            'subject'     => 'Fisika Terapan',
            'day_of_week' => 'Senin',
            'start_time'  => '08:00',
            'end_time'    => '10:00',
            'cycle_type'  => 'Putaran 1',
        ]);

        $dashboards = app(\App\Services\DashboardService::class);

        // 1. At 09:15 (45 min before end) -> NOT triggered
        \Carbon\Carbon::setTestNow($monday->copy()->setTime(9, 15, 0));
        $unfilled1 = $dashboards->wakaUnfilledSchedules($config);
        $this->assertFalse($unfilled1->contains('id', $schedule->id));

        // 2. At 09:35 (25 min before end) -> Triggered, flagged "segera_berakhir"
        \Carbon\Carbon::setTestNow($monday->copy()->setTime(9, 35, 0));
        $unfilled2 = $dashboards->wakaUnfilledSchedules($config);
        $foundNearEnd = $unfilled2->firstWhere('id', $schedule->id);
        $this->assertNotNull($foundNearEnd);
        $this->assertSame('segera_berakhir', $foundNearEnd->alert_status);
        $this->assertSame('Segera Berakhir', $foundNearEnd->alert_badge);

        // Web view renders "Segera Berakhir"
        $resWebNearEnd = $this->actingAs($waka)->get('/dashboard');
        $resWebNearEnd->assertOk();
        $resWebNearEnd->assertSee('Segera Berakhir');

        // 3. At 10:05 (after end time) -> Triggered, flagged "terlambat"
        \Carbon\Carbon::setTestNow($monday->copy()->setTime(10, 5, 0));
        $unfilled3 = $dashboards->wakaUnfilledSchedules($config);
        $foundLate = $unfilled3->firstWhere('id', $schedule->id);
        $this->assertNotNull($foundLate);
        $this->assertSame('terlambat', $foundLate->alert_status);
        $this->assertSame('Terlambat', $foundLate->alert_badge);

        // Web view renders "Terlambat"
        $resWebLate = $this->actingAs($waka)->get('/dashboard');
        $resWebLate->assertOk();
        $resWebLate->assertSee('Terlambat');

        // 4. Once journal created -> removed from alert
        \App\Models\Journal::create([
            'schedule_id'       => $schedule->id,
            'teacher_id'        => $guru->id,
            'date'              => $monday->toDateString(),
            'topic_description' => 'Materi Selesai',
        ]);
        $unfilled4 = $dashboards->wakaUnfilledSchedules($config);
        $this->assertFalse($unfilled4->contains('id', $schedule->id));

        \Carbon\Carbon::setTestNow(); // reset
    }
}
