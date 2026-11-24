<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class SecurityHeadersAndModernWebTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_and_dashboard_include_security_headers_and_nonce_csp(): void
    {
        $this->artisan('db:seed');

        $guestResponse = $this->get('/login');
        $guestResponse->assertOk();
        $this->assertSecurityHeaders($guestResponse);
        $guestResponse->assertSee('x-data="{ shown: false }"', false);

        $waka = User::where('role_id', 1)->firstOrFail();
        $dashResponse = $this->actingAs($waka)->get('/dashboard');
        $dashResponse->assertOk();
        $this->assertSecurityHeaders($dashResponse);
    }

    public function test_logout_sets_clear_site_data_header(): void
    {
        $this->artisan('db:seed');
        $user = User::where('role_id', 2)->firstOrFail();

        $response = $this->actingAs($user)->post('/logout');

        $response->assertRedirect('/');
        $response->assertHeader('Clear-Site-Data', '"cookies", "storage", "cache"');
    }

    public function test_ai_importer_preview_requires_matching_owner_key_prefix(): void
    {
        $this->artisan('db:seed');
        $waka = User::where('role_id', 1)->firstOrFail();

        $ownKey = 'ai_import_' . $waka->id . '_testkey';
        Cache::put($ownKey, [
            'type' => 'Daftar Siswa',
            'data' => [],
            'origin_url' => url('/schedules'),
        ], now()->addMinutes(5));

        $okResponse = $this->actingAs($waka)->get(route('ai-importer.preview', ['key' => $ownKey]));
        $okResponse->assertOk();

        $foreignKey = 'ai_import_' . ($waka->id + 999) . '_testkey';
        Cache::put($foreignKey, [
            'type' => 'Daftar Siswa',
            'data' => [],
            'origin_url' => url('/schedules'),
        ], now()->addMinutes(5));

        $forbiddenResponse = $this->actingAs($waka)->get(route('ai-importer.preview', ['key' => $foreignKey]));
        $forbiddenResponse->assertForbidden();
    }

    public function test_https_reverse_proxy_generates_https_form_actions_and_login_redirects(): void
    {
        $this->artisan('db:seed');
        $plainPass = 'ProxyLoginTest#123';
        User::where('username', 'guru_andi')->update([
            'password' => \Illuminate\Support\Facades\Hash::make($plainPass),
        ]);

        $proxyHeaders = [
            'REMOTE_ADDR' => '127.0.0.1',
            'HTTP_HOST' => 'scp.fikricavan.my.id',
            'HTTP_X_FORWARDED_HOST' => 'scp.fikricavan.my.id',
            'HTTP_X_FORWARDED_PROTO' => 'https',
            'HTTP_X_FORWARDED_FOR' => '203.0.113.10',
        ];

        $loginPage = $this->withServerVariables($proxyHeaders)->get('/login');
        $loginPage->assertOk();
        $loginPage->assertSee('action="https://scp.fikricavan.my.id/login"', false);

        $loginPost = $this->withServerVariables($proxyHeaders)->post('/login', [
            'username' => 'guru_andi',
            'password' => $plainPass,
        ]);
        $loginPost->assertRedirect('https://scp.fikricavan.my.id/dashboard');

        $logoutPost = $this->withServerVariables($proxyHeaders)->post('/logout');
        $logoutPost->assertRedirect('https://scp.fikricavan.my.id');
    }

    private function assertSecurityHeaders($response): void
    {
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'DENY');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('Permissions-Policy');
        $response->assertHeader('Cross-Origin-Opener-Policy', 'same-origin-allow-popups');
        $response->assertHeader('Cross-Origin-Resource-Policy', 'same-origin');

        $csp = (string) $response->headers->get('Content-Security-Policy');
        $this->assertNotEmpty($csp);
        $this->assertStringContainsString("object-src 'none'", $csp);
        $this->assertMatchesRegularExpression("/script-src 'self' 'nonce-[A-Za-z0-9+\/=]+' 'strict-dynamic'/", $csp);
    }
}
