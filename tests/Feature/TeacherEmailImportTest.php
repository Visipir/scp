<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\TeacherEmailImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\Support\ScpTestHelpers;
use Tests\TestCase;

class TeacherEmailImportTest extends TestCase
{
    use RefreshDatabase;
    use ScpTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
    }

    public function test_requesting_reset_code_without_registered_email_returns_clear_error_web_and_api(): void
    {
        $teacher = $this->makeUser(2, [
            'username' => 'guru.budi',
            'fullname' => 'Budi Santoso',
            'nip'      => '198001012005011001',
            'email'    => null, // No email registered
        ]);

        // 1. API: POST /api/v1/auth/forgot-password
        $responseApi = $this->postJson('/api/v1/auth/forgot-password', [
            'identifier' => 'guru.budi',
        ]);

        $responseApi->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
        $this->assertSame(
            'Email belum terdaftar. Hubungi Waka Kurikulum.',
            $responseApi->json('errors.email.0')
        );

        // API check by NIP
        $responseApiNip = $this->postJson('/api/v1/auth/forgot-password', [
            'identifier' => '198001012005011001',
        ]);
        $responseApiNip->assertStatus(422)
            ->assertJsonValidationErrors(['email']);

        // 2. Web: POST /forgot-password
        $responseWeb = $this->post('/forgot-password', [
            'identifier' => 'guru.budi',
        ]);

        $responseWeb->assertSessionHasErrors(['email']);
    }

    public function test_email_batch_import_service_matches_and_updates_emails(): void
    {
        $teacher1 = $this->makeUser(2, [
            'fullname' => 'Budi Santoso',
            'nip'      => '198001012005011001',
            'email'    => null,
        ]);

        $teacher2 = $this->makeUser(2, [
            'fullname' => 'Siti Aminah, S.Pd.',
            'nip'      => '198502022008022002',
            'email'    => null,
        ]);

        $csvContent = <<<CSV
NIP,NAMA,EMAIL
198001012005011001,Budi Santoso,budi.santoso@smk.sch.id
198502022008022002,Siti Aminah,siti.aminah@smk.sch.id
CSV;

        $tempPath = tempnam(sys_get_temp_dir(), 'email_test_') . '.csv';
        file_put_contents($tempPath, $csvContent);

        $uploaded = new UploadedFile($tempPath, 'guru_emails.csv', 'text/csv', null, true);

        $service = app(TeacherEmailImportService::class);
        $preview = $service->generatePreview($uploaded);

        $this->assertSame(2, $preview['summary']['total_rows']);
        $this->assertSame(2, $preview['summary']['matched']);

        $service->commit($preview['token']);

        $teacher1->refresh();
        $teacher2->refresh();

        $this->assertSame('budi.santoso@smk.sch.id', $teacher1->email);
        $this->assertSame('siti.aminah@smk.sch.id', $teacher2->email);

        @unlink($tempPath);
    }
}
