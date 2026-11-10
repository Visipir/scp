<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\SystemConfiguration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class GuestThemeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::insert([
            ['id' => 1, 'role_name' => 'Waka Kurikulum'],
            ['id' => 2, 'role_name' => 'Guru'],
            ['id' => 3, 'role_name' => 'Tim Kesiswaan'],
            ['id' => 4, 'role_name' => 'Siswa/Ortu'],
        ]);
    }

    public function test_guest_pages_contain_theme_tokens_meta_and_init_script(): void
    {
        $guestRoutes = [
            '/login',
            '/forgot-password',
            '/register',
        ];

        foreach ($guestRoutes as $route) {
            $response = $this->get($route);
            $response->assertOk();

            // 1. Meta color-scheme
            $response->assertSee('<meta name="color-scheme" content="light dark">', false);

            // 2. Head init script with storage and matchMedia listeners
            $response->assertSee('localStorage.getItem(\'theme\')', false);
            $response->assertSee('prefers-color-scheme: dark', false);
            $response->assertSee('window.addEventListener(\'storage\'', false);

            // 3. Accessible theme toggle with aria attributes
            $response->assertSee('id="theme-toggle-btn"', false);
            $response->assertSee('aria-label="Pilih Tema Tampilan"', false);
            $response->assertSee('aria-pressed=', false);

            // 4. Token blocks (:root, html.dark, html.light)
            $response->assertSee('--auth-bg: #1a1a1e;', false);
            $response->assertSee('--auth-surface: #1D1D21;', false);
            $response->assertSee('--auth-bg: #F4F4F6;', false);
            $response->assertSee('--auth-surface: #FFFFFF;', false);
            $response->assertSee('--auth-btn-bg: #007BB5;', false);

            // 5. Shared guest layout class & card
            $response->assertSee('auth-body', false);
            $response->assertSee('auth-card', false);
        }
    }

    public function test_login_surface_has_no_unconditional_light_classes(): void
    {
        $response = $this->get('/login');
        $response->assertOk();
        $content = $response->getContent();

        // Ensure auth-card is present and does not have hardcoded bg-white on surface
        $this->assertStringContainsString('auth-card', $content);
        $this->assertStringNotContainsString('class="auth-card bg-white', $content);
        $this->assertStringNotContainsString('text-gray-600', $content);
        $this->assertStringNotContainsString('text-gray-900', $content);
    }

    public function test_login_renders_with_and_without_configured_background(): void
    {
        // 1. Without background (default SystemConfiguration null)
        $resWithout = $this->get('/login');
        $resWithout->assertOk();
        $resWithout->assertSee('login-background', false);

        // 2. With background updated timestamp
        SystemConfiguration::create([
            'login_background_updated_at' => now(),
            'active_cycle' => 'Putaran 1',
        ]);

        $resWith = $this->get('/login');
        $resWith->assertOk();
        $resWith->assertSee('login-background', false);
    }

    public function test_five_protected_accounts_can_authenticate_via_web_and_api(): void
    {
        $protectedAccounts = [
            ['username' => 'waka_kurikulum', 'role_id' => 1, 'fullname' => 'Waka Kurikulum'],
            ['username' => 'haris-budiawan', 'role_id' => 2, 'fullname' => 'Haris Budiawan'],
            ['username' => 'ryawidyaningtyas', 'role_id' => 2, 'fullname' => 'Rya Widyaningtyas'],
            ['username' => 'tim_kesiswaan', 'role_id' => 3, 'fullname' => 'Tim Kesiswaan'],
            ['username' => '17766', 'role_id' => 4, 'fullname' => 'Siswa Test'],
        ];

        $password = 'Secret123!';

        foreach ($protectedAccounts as $acc) {
            $user = User::create([
                'username'             => $acc['username'],
                'fullname'             => $acc['fullname'],
                'role_id'              => $acc['role_id'],
                'password'             => Hash::make($password),
                'must_change_password' => false,
            ]);

            // Web Login POST
            $webRes = $this->post('/login', [
                'username' => $acc['username'],
                'password' => $password,
            ]);
            $webRes->assertRedirect('/dashboard');
            $this->assertAuthenticatedAs($user);

            $this->post('/logout');
            $this->assertGuest();

            // API Login POST
            $apiRes = $this->postJson('/api/login', [
                'username' => $acc['username'],
                'password' => $password,
            ]);
            $apiRes->assertOk();
            $apiRes->assertJsonStructure(['token', 'user']);
            $this->assertSame($acc['username'], $apiRes->json('user.username'));
        }
    }
}
