<?php
namespace Tests\Feature;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
class JournalPageTest extends TestCase {
    use RefreshDatabase;
    public function test_can_view_journal_index() {
        $this->artisan('db:seed');
        $user = User::where('username', 'waka')->first();
        $response = $this->actingAs($user)->get('/journals');
        $response->assertOk();
    }
}
