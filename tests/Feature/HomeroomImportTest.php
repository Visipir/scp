<?php

namespace Tests\Feature;

use App\Models\CounselorAssignment;
use App\Models\HomeroomAssignment;
use App\Models\Role;
use App\Models\SchoolClass;
use App\Models\User;
use App\Services\HomeroomImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\Support\ScpTestHelpers;
use Tests\TestCase;

class HomeroomImportTest extends TestCase
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
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Wali_BK');

        $sheet->fromArray([
            ['NO', 'KELAS', 'NAMA WALI', 'NAMA GURU BK'],
            [1, 'X AK 2', 'Budi Santoso, S.Pd.', 'Dra. Siti Aminah'],
            [2, 'XI TKJ 1', 'Ahmad Fauzi, S.Kom.', 'Dra. Siti Aminah'],
            [3, 'XII RPL 2', 'Guru Tidak Terdaftar', 'Guru BK Luput'],
        ]);

        $tempPath = tempnam(sys_get_temp_dir(), 'test_wali_') . '.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempPath);

        return $tempPath;
    }

    public function test_only_waka_can_access_homeroom_import_pages()
    {
        $waka = $this->makeUser(1);
        $guru = $this->makeUser(2);

        $this->actingAs($guru)
            ->get(route('waka.import-wali-bk.index'))
            ->assertForbidden();

        $this->actingAs($waka)
            ->get(route('waka.import-wali-bk.index'))
            ->assertOk()
            ->assertSee('Import Penugasan Wali Kelas');
    }

    public function test_preview_matches_classes_and_teachers_without_mutating_database()
    {
        $waka = $this->makeUser(1);

        $class1 = SchoolClass::create(['class_name' => 'X AK 2', 'academic_year' => '2026/2027']);
        $class2 = SchoolClass::create(['class_name' => 'XI TKJ 1', 'academic_year' => '2026/2027']);

        $wali1 = $this->makeUser(2, ['fullname' => 'Budi Santoso']);
        $wali2 = $this->makeUser(2, ['fullname' => 'Ahmad Fauzi']);
        $bk = $this->makeUser(2, ['fullname' => 'Siti Aminah']);

        $filePath = $this->createSampleSpreadsheet();
        $uploadedFile = new UploadedFile($filePath, 'WALI_KELAS_2026_2027.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);

        $response = $this->actingAs($waka)->post(route('waka.import-wali-bk.preview'), [
            'file' => $uploadedFile,
        ]);

        $response->assertOk()
            ->assertViewIs('waka.import-wali-bk.preview')
            ->assertSee('X AK 2')
            ->assertSee('XI TKJ 1')
            ->assertSee('Budi Santoso')
            ->assertSee('Siti Aminah');

        // Check DB was not mutated
        $this->assertSame(0, HomeroomAssignment::count());
        $this->assertSame(0, CounselorAssignment::count());

        @unlink($filePath);
    }

    public function test_commit_assigns_homeroom_and_counselor_properly()
    {
        $waka = $this->makeUser(1);

        $class1 = SchoolClass::create(['class_name' => 'X AK 2', 'academic_year' => '2026/2027']);
        $class2 = SchoolClass::create(['class_name' => 'XI TKJ 1', 'academic_year' => '2026/2027']);

        $wali1 = $this->makeUser(2, ['fullname' => 'Budi Santoso']);
        $wali2 = $this->makeUser(2, ['fullname' => 'Ahmad Fauzi']);
        $bk = $this->makeUser(2, ['fullname' => 'Siti Aminah']);

        $filePath = $this->createSampleSpreadsheet();
        $uploadedFile = new UploadedFile($filePath, 'WALI_KELAS_2026_2027.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);

        $service = app(HomeroomImportService::class);
        $preview = $service->generatePreview($uploadedFile);

        $response = $this->actingAs($waka)->post(route('waka.import-wali-bk.commit'), [
            'token' => $preview['token'],
        ]);

        $response->assertRedirect(route('classes.index'));

        // Verify Homeroom Assignments
        $assign1 = HomeroomAssignment::where('class_id', $class1->id)->first();
        $this->assertNotNull($assign1);
        $this->assertSame($wali1->id, $assign1->user_id);

        $assign2 = HomeroomAssignment::where('class_id', $class2->id)->first();
        $this->assertNotNull($assign2);
        $this->assertSame($wali2->id, $assign2->user_id);

        // Verify Counselor Assignments (BK assigned to both classes)
        $counselorAssignments = CounselorAssignment::where('user_id', $bk->id)->get();
        $this->assertCount(2, $counselorAssignments);
        $this->assertTrue($counselorAssignments->contains('class_id', $class1->id));
        $this->assertTrue($counselorAssignments->contains('class_id', $class2->id));

        @unlink($filePath);
    }

    public function test_waka_can_manually_update_class_assignments()
    {
        $waka = $this->makeUser(1);
        $guru = $this->makeUser(2);

        $class = SchoolClass::create(['class_name' => 'X PPLG 1', 'academic_year' => '2026/2027']);
        $teacher1 = $this->makeUser(2, ['fullname' => 'Guru Wali']);
        $teacher2 = $this->makeUser(2, ['fullname' => 'Guru BK']);

        // Non-waka cannot update
        $this->actingAs($guru)
            ->put(route('classes.assignments.update', $class), [
                'homeroom_user_id'   => $teacher1->id,
                'counselor_user_ids' => [$teacher2->id],
            ])
            ->assertForbidden();

        // Waka updates assignments
        $response = $this->actingAs($waka)
            ->put(route('classes.assignments.update', $class), [
                'homeroom_user_id'   => $teacher1->id,
                'counselor_user_ids' => [$teacher2->id],
            ]);

        $response->assertRedirect();

        $this->assertSame($teacher1->id, $class->homeroomAssignment()->first()?->user_id);
        $this->assertSame($teacher2->id, $class->counselors()->first()?->id);
    }
}
