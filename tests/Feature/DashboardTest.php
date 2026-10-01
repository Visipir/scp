<?php
namespace Tests\Feature;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
class DashboardTest extends TestCase {
    use RefreshDatabase;
    public function test_can_view_dashboard() {
        $this->artisan('db:seed');
        $user = User::where('username', 'waka')->first();
        $response = $this->actingAs($user)->get('/dashboard');
        $response->assertOk();
    }
}
