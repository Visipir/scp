<?php

namespace Tests\Feature;

use App\Models\SystemConfiguration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\ScpTestHelpers;
use Tests\TestCase;

class UiConsistencyTest extends TestCase
{
    use RefreshDatabase, ScpTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
        $this->makeConfig([
            'active_cycle' => 'Putaran 2',
            'is_schedule_locked' => true,
        ]);
    }

    public function test_waka_can_access_all_12_required_pages_with_uniform_titles(): void
    {
        $waka = $this->makeUser(1, ['email' => 'waka@example.test', 'email_verified_at' => now()]);

        $pages = [
            '/dashboard' => 'Dashboard',
            '/schedules' => 'Jadwal Pelajaran',
            '/room-schedules' => 'Jadwal Ruangan',
            '/journals' => 'Jurnal &amp; Presensi',
            '/schedule-swaps' => 'Laporan Tukar Jadwal',
            '/leave-requests' => 'Izin Siswa',
            '/academic-calendar' => 'Kalender Akademik',
            '/exams' => 'Materi &amp; Ujian',
            '/classes' => 'Data Kelas',
            '/users' => 'Manajemen Pengguna',
            '/waka/settings' => 'Pengaturan Kurikulum',
            '/system-configuration/email' => 'Pengaturan Email',
            '/profile' => 'Profil',
        ];

        foreach ($pages as $uri => $expectedTitle) {
            $response = $this->actingAs($waka)->get($uri);

            $response->assertStatus(200, "Page {$uri} failed to render 200 for Waka.");
            $response->assertSee($expectedTitle, false);
            // Verify topbar active cycle chip shows sans Putaran 2
            $response->assertSee('Putaran 2', false);
            $response->assertSee('Jadwal Terkunci', false);
        }
    }

    public function test_teacher_dashboard_and_pages_render_cleanly_with_uniform_titles(): void
    {
        $teacher = $this->makeUser(2);

        $response = $this->actingAs($teacher)->get('/dashboard');
        $response->assertStatus(200);
        $response->assertSee('Dashboard');
        $response->assertDontSee('Dashboard Guru'); // unified to Dashboard
        $response->assertSee('Riwayat Jurnal');

        $response = $this->actingAs($teacher)->get('/schedules');
        $response->assertStatus(200);
        $response->assertSee('Jadwal Pelajaran');

        $response = $this->actingAs($teacher)->get('/journals');
        $response->assertStatus(200);
        $response->assertSee('Jurnal &amp; Presensi', false);

        $response = $this->actingAs($teacher)->get('/exams');
        $response->assertStatus(200);
        $response->assertSee('Materi &amp; Ujian', false);
    }

    public function test_kesiswaan_pages_render_cleanly_with_uniform_titles(): void
    {
        $kesiswaan = $this->makeUser(3);

        $response = $this->actingAs($kesiswaan)->get('/leave-requests');
        $response->assertStatus(200);
        $response->assertSee('Izin Siswa');
        $response->assertDontSee('Permohonan Izin &amp; Sakit');

        $response = $this->actingAs($kesiswaan)->get('/classes');
        $response->assertStatus(200);
        $response->assertSee('Data Kelas');
    }

    public function test_student_pages_render_cleanly_with_uniform_titles(): void
    {
        $class = $this->makeClass('X RPL 1');
        $student = $this->makeUser(4, ['class_id' => $class->id]);

        $response = $this->actingAs($student)->get('/dashboard');
        $response->assertStatus(200);
        $response->assertSee('Dashboard');

        $response = $this->actingAs($student)->get('/leave-requests');
        $response->assertStatus(200);
        $response->assertSee('Izin Siswa');
        $response->assertSee('Ajukan Izin');
    }

    public function test_waka_settings_displays_lock_helper_notice(): void
    {
        $waka = $this->makeUser(1);

        $response = $this->actingAs($waka)->get('/waka/settings');
        $response->assertStatus(200);
        $response->assertSee('Tukar jadwal guru tetap bisa dilakukan saat jadwal terkunci.');
        $response->assertSee('Pengaturan Kurikulum');
        $response->assertDontSee('Waka Kurikulum</span>', false); // floating role badge removed
    }

    public function test_guest_auth_pages_render_cleanly(): void
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
        $response->assertSee('Masuk ke Akun');
        $response->assertSee('School Class Progress');

        $response = $this->get('/forgot-password');
        $response->assertStatus(200);
        $response->assertSee('Lupa Kata Sandi?');
    }
}
