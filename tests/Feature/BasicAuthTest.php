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

        $response = $this->post('/login', [
            'username' => 'waka',
            'password' => 'password'
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect('/dashboard');

        $dashboardResponse = $this->get('/dashboard');
        $dashboardResponse->assertSee('Berhasil Login! Selamat datang Bapak Waka Kurikulum');
    }
}
