<?php
namespace Tests\Feature;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
class SchedulePageTest extends TestCase {
    use RefreshDatabase;
    public function test_can_view_schedule_index() {
        $this->artisan('db:seed');
        $user = User::where('username', 'waka_kurikulum')->first();
        $response = $this->actingAs($user)->get('/schedules');
        $response->assertOk();
    }
}
