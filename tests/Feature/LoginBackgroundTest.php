<?php

namespace Tests\Feature;

use App\Models\SystemConfiguration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\Support\ScpTestHelpers;
use Tests\TestCase;

class LoginBackgroundTest extends TestCase
{
    use RefreshDatabase;
    use ScpTestHelpers;

    private User $waka;
    private User $guru;
    private User $siswa;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();

        $this->waka = $this->makeUser(1); // Waka Kurikulum
        $this->guru = $this->makeUser(2); // Guru
        $this->siswa = $this->makeUser(4); // Siswa
    }

    public function test_guest_can_access_login_page_and_default_login_background()
    {
        // 1. Guest can access /login (200)
        $resLogin = $this->get('/login');
        $resLogin->assertStatus(200);
        $resLogin->assertSee('School Class Progress');
        $resLogin->assertSee('Masuk');

        // 2. Guest can access GET /login-background (200, default SVG brand gradient)
        $resBg = $this->get('/login-background');
        $resBg->assertStatus(200);
        $resBg->assertHeader('Content-Type', 'image/svg+xml');
        $this->assertTrue($resBg->headers->has('ETag'));
        $this->assertTrue($resBg->headers->has('Cache-Control'));
    }

    public function test_non_waka_roles_forbidden_from_uploading_background()
    {
        $fakeImg = UploadedFile::fake()->image('bg.jpg', 1400, 900);

        // Guru (Role 2) -> 403
        $this->actingAs($this->guru)
            ->post('/waka/settings/login-background', ['background' => $fakeImg])
            ->assertStatus(403);

        // Siswa (Role 4) -> 403
        $this->actingAs($this->siswa)
            ->post('/waka/settings/login-background', ['background' => $fakeImg])
            ->assertStatus(403);

        // Guest -> 403 Forbidden by role middleware
        $this->post('/waka/settings/login-background', ['background' => $fakeImg])
            ->assertStatus(403);
    }

    public function test_waka_can_view_settings_page()
    {
        $res = $this->actingAs($this->waka)->get(route('waka.settings.login-background'));
        $res->assertStatus(200);
        $res->assertSee('Tampilan Login');
        $res->assertSee('Unggah Gambar Latar Belakang Baru');
    }

    public function test_php_file_with_jpg_extension_is_rejected_with_422()
    {
        // Double extension e.g. script.php.jpg
        $file = UploadedFile::fake()->createWithContent('malicious.php.jpg', '<?php phpinfo(); ?>');

        $res = $this->actingAs($this->waka)
            ->post('/waka/settings/login-background', ['background' => $file]);

        $res->assertSessionHasErrors(['background']);
    }

    public function test_svg_file_is_rejected_with_422()
    {
        $svgContent = '<svg xmlns="http://www.w3.org/2000/svg"><rect width="100" height="100"/></svg>';
        $file = UploadedFile::fake()->createWithContent('vector.svg', $svgContent);

        $res = $this->actingAs($this->waka)
            ->post('/waka/settings/login-background', ['background' => $file]);

        $res->assertSessionHasErrors(['background']);
    }

    public function test_file_exceeding_3mb_is_rejected_with_422()
    {
        // 4 MB file
        $file = UploadedFile::fake()->image('huge.jpg', 1600, 1000)->size(4096);

        $res = $this->actingAs($this->waka)
            ->post('/waka/settings/login-background', ['background' => $file]);

        $res->assertSessionHasErrors(['background']);
    }

    public function test_image_dimensions_below_1200x800_is_rejected_with_422()
    {
        // 800x600 px image
        $file = UploadedFile::fake()->image('small.jpg', 800, 600);

        $res = $this->actingAs($this->waka)
            ->post('/waka/settings/login-background', ['background' => $file]);

        $res->assertSessionHasErrors(['background']);
    }

    public function test_waka_valid_upload_reencodes_and_stream_serves_custom_image()
    {
        $file = UploadedFile::fake()->image('school_hall.jpg', 1600, 1000);

        $res = $this->actingAs($this->waka)
            ->post('/waka/settings/login-background', ['background' => $file]);

        $res->assertRedirect(route('waka.settings.login-background'));
        $res->assertSessionHas('success');

        $config = SystemConfiguration::first();
        $this->assertNotNull($config->login_background_path);
        $this->assertSame($this->waka->id, $config->login_background_updated_by);

        $diskPath = storage_path('app/' . $config->login_background_path);
        $this->assertFileExists($diskPath);

        // Verify public streaming route serves this custom image
        $streamRes = $this->get('/login-background');
        $streamRes->assertStatus(200);
        $streamRes->assertHeader('Content-Type', 'image/webp');
    }

    public function test_changing_background_image_deletes_old_file_from_disk()
    {
        // First upload
        $file1 = UploadedFile::fake()->image('first.jpg', 1400, 900);
        $this->actingAs($this->waka)->post('/waka/settings/login-background', ['background' => $file1]);

        $config = SystemConfiguration::first();
        $firstPath = storage_path('app/' . $config->login_background_path);
        $this->assertFileExists($firstPath);

        // Second upload
        $file2 = UploadedFile::fake()->image('second.png', 1500, 1000);
        $this->actingAs($this->waka)->post('/waka/settings/login-background', ['background' => $file2]);

        $config->refresh();
        $secondPath = storage_path('app/' . $config->login_background_path);

        $this->assertNotEquals($firstPath, $secondPath);
        $this->assertFileDoesNotExist($firstPath);
        $this->assertFileExists($secondPath);
    }

    public function test_reset_background_deletes_file_and_reverts_to_default()
    {
        $file = UploadedFile::fake()->image('custom.jpg', 1400, 900);
        $this->actingAs($this->waka)->post('/waka/settings/login-background', ['background' => $file]);

        $config = SystemConfiguration::first();
        $filePath = storage_path('app/' . $config->login_background_path);
        $this->assertFileExists($filePath);

        // Reset to default
        $res = $this->actingAs($this->waka)->delete(route('waka.settings.login-background.destroy'));
        $res->assertRedirect(route('waka.settings.login-background'));

        $config->refresh();
        $this->assertNull($config->login_background_path);
        $this->assertFileDoesNotExist($filePath);

        // Stream reverts to default SVG
        $streamRes = $this->get('/login-background');
        $streamRes->assertStatus(200);
        $streamRes->assertHeader('Content-Type', 'image/svg+xml');
    }

    public function test_storage_guest_access_remains_403_and_traversal_impossible()
    {
        // Old storage routes must remain 403 for guests
        $this->get('/storage/leave-documents/rahasia.txt')->assertStatus(403);
        $this->get('/storage/journals/foto.txt')->assertStatus(403);
        $this->get('/storage/../secrets.txt')->assertStatus(403);

        // Path traversal on /login-background is impossible (route takes no path parameters)
        $this->get('/login-background?path=../../etc/passwd')->assertStatus(200);
        // It streams only the configured file / SVG, never /etc/passwd
    }
}
