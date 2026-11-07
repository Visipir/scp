<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class BasicAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_with_username_and_password()
    {
        $this->artisan('db:seed');
        $plainPass = (string) env('TEST_FIXTURE_PASSWORD', \Illuminate\Support\Str::random(16));
        User::where('username', 'guru_andi')->update(['password' => \Illuminate\Support\Facades\Hash::make($plainPass)]);

        $response = $this->post('/login', [
            'username' => 'guru_andi',
            'password' => $plainPass,
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect('/dashboard');

        $dashboardResponse = $this->get('/dashboard');
        $dashboardResponse->assertOk();
    }

    public function test_user_can_update_fullname_but_cannot_change_username()
    {
        $this->artisan('db:seed');

        $user = User::where('username', 'guru_andi')->first();
        $this->actingAs($user);

        // Update fullname only (as sent by form where username is disabled)
        $response = $this->patch('/profile', [
            'fullname' => 'Pak Andi Update',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect('/profile');

        $user->refresh();
        $this->assertSame('Pak Andi Update', $user->fullname);
        $this->assertSame('guru_andi', $user->username);

        // Attempting to inject username in payload must not change username
        $response = $this->patch('/profile', [
            'fullname' => 'Pak Andi Final',
            'username' => 'guru_hacked',
        ]);

        $response->assertSessionHasNoErrors();
        $user->refresh();
        $this->assertSame('Pak Andi Final', $user->fullname);
        $this->assertSame('guru_andi', $user->username);
    }

    public function test_profile_page_displays_locked_username_field()
    {
        $this->artisan('db:seed');

        $user = User::where('username', 'guru_andi')->first();
        $response = $this->actingAs($user)->get('/profile');

        $response->assertOk();
        $response->assertSee('Terkunci');
        $response->assertSee('tidak dapat diubah secara mandiri');
    }
}
