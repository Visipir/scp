<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Services\EmployeeImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\Support\ScpTestHelpers;
use Tests\TestCase;

class EmployeeImportTest extends TestCase
{
    use RefreshDatabase;
    use ScpTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
    }

    private function createSampleSpreadsheet(): string
    {
        $spreadsheet = new Spreadsheet();

        // 1. Non-Guru sheet (should be ignored)
        $sheetNonGuru = $spreadsheet->getActiveSheet();
        $sheetNonGuru->setTitle('TU');
        $sheetNonGuru->fromArray([
            ['NO', 'NIP', 'NAMA', 'JK', 'JABATAN'],
            [1, '198001010001', 'Staf Tata Usaha', 'L', 'Staff'],
        ]);

        // 2. Sheet Guru
        $sheetGuru = $spreadsheet->createSheet();
        $sheetGuru->setTitle('Guru');
        $sheetGuru->fromArray([
            // Headers
            ['NO', 'NIP', 'NAMA', 'JK', 'GOLONGAN', 'JABATAN', 'PENDIDIKAN', 'MAPEL'],
            // Row 1: Exact NIP & Name match
            [1, '197505052000031001', 'Drs. Budi Santoso, M.Pd.', 'L', 'IV/a', 'Guru Madya', 'S2', 'Matematika'],
            // Row 2: Fuzzy match with existing teacher
            [2, '198101012005012002', 'Siti Aminah, S.Pd', 'P', 'III/c', 'Guru Muda', 'S1', 'Bahasa Indonesia'],
            // Row 3: New teacher with email
            [3, '199002022020121003', 'Ahmad Fauzi, S.Kom.', 'L', 'III/a', 'Guru Pertama', 'S1', 'Informatika'],
            // Row 4: New teacher without email & without NIP
            [4, '', 'Dewi Lestari, S.Pd.', 'P', 'GTT', 'Guru Honor', 'S1', 'Seni Budaya'],
        ]);

        $tempPath = tempnam(sys_get_temp_dir(), 'test_emp_') . '.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempPath);

        return $tempPath;
    }

    public function test_only_waka_can_access_employee_import_pages()
    {
        $waka = $this->makeUser(1);
        $guru = $this->makeUser(2);

        $this->actingAs($guru)
            ->get(route('waka.import-pegawai.index'))
            ->assertForbidden();

        $this->actingAs($waka)
            ->get(route('waka.import-pegawai.index'))
            ->assertOk()
            ->assertSee('Import Data Pegawai (Guru)');
    }

    public function test_preview_does_not_mutate_database_and_categorizes_correctly()
    {
        $waka = $this->makeUser(1);

        // Create an existing teacher with NIP & matching normalized name
        $existingBudi = $this->makeUser(2, [
            'fullname' => 'Budi Santoso',
            'username' => 'budi.santoso',
            'nip'      => '197505052000031001',
            'email'    => 'budi@example.test',
        ]);

        // Create an existing teacher with slight name variance for fuzzy match
        $existingSiti = $this->makeUser(2, [
            'fullname' => 'Siti Aminah Putri',
            'username' => 'siti.aminah',
            'nip'      => null,
            'email'    => null,
        ]);

        $filePath = $this->createSampleSpreadsheet();
        $uploadedFile = new UploadedFile($filePath, 'Data_Pegawai.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);

        $initialUserCount = User::count();

        $response = $this->actingAs($waka)->post(route('waka.import-pegawai.preview'), [
            'file' => $uploadedFile,
        ]);

        $response->assertOk()
            ->assertViewIs('waka.import-pegawai.preview')
            ->assertSee('Drs. Budi Santoso, M.Pd.')
            ->assertSee('Ahmad Fauzi, S.Kom.')
            ->assertSee('tanpa email');

        // Database must remain untouched during preview
        $this->assertSame($initialUserCount, User::count());

        @unlink($filePath);
    }

    public function test_commit_updates_matched_and_creates_new_teachers_with_random_passwords()
    {
        $waka = $this->makeUser(1);

        $existingBudi = $this->makeUser(2, [
            'fullname' => 'Budi Santoso',
            'username' => 'budi.santoso',
            'nip'      => null, // Will be updated by import
            'email'    => null,
        ]);

        $filePath = $this->createSampleSpreadsheet();
        $uploadedFile = new UploadedFile($filePath, 'Data_Pegawai.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);

        $service = app(EmployeeImportService::class);
        $preview = $service->generatePreview($uploadedFile);

        $this->assertNotEmpty($preview['token']);
        $this->assertGreaterThan(0, $preview['summary']['total_rows']);

        $response = $this->actingAs($waka)->post(route('waka.import-pegawai.commit'), [
            'token' => $preview['token'],
        ]);

        $response->assertRedirect(route('waka.import-pegawai.index'));

        // Check Budi's NIP was updated
        $existingBudi->refresh();
        $this->assertSame('197505052000031001', $existingBudi->nip);

        // Check new teacher Ahmad Fauzi was created
        $newTeacher = User::where('nip', '199002022020121003')->first();
        $this->assertNotNull($newTeacher);
        $this->assertSame(2, $newTeacher->role_id);
        $this->assertTrue((bool) $newTeacher->must_change_password);
        $this->assertNotEmpty($newTeacher->password);
        // Password must NOT match default predictable password
        $this->assertFalse(Hash::check('password', $newTeacher->password));
        $this->assertFalse(Hash::check('admin123', $newTeacher->password));

        // Check teacher without NIP was also created
        $dewi = User::where('fullname', 'Dewi Lestari, S.Pd.')->first();
        $this->assertNotNull($dewi);
        $this->assertNull($dewi->nip);
        $this->assertNull($dewi->email);
        $this->assertTrue((bool) $dewi->must_change_password);

        @unlink($filePath);
    }
}
