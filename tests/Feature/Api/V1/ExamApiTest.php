<?php

namespace Tests\Feature\Api\V1;

use App\Models\Exam;
use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Support\ScpTestHelpers;
use Tests\TestCase;

class ExamApiTest extends TestCase
{
    use RefreshDatabase;
    use ScpTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
        Storage::fake('public');
    }

    public function test_exam_endpoints_require_authentication(): void
    {
        $this->getJson('/api/v1/exams')->assertStatus(401);
        $this->postJson('/api/v1/exams', [])->assertStatus(401);
        $this->getJson('/api/v1/exams/1')->assertStatus(401);
        $this->putJson('/api/v1/exams/1', [])->assertStatus(401);
        $this->deleteJson('/api/v1/exams/1')->assertStatus(401);
        $this->getJson('/api/v1/exams/1/download')->assertStatus(401);
    }

    public function test_student_and_bk_cannot_mutate_exams(): void
    {
        $class = $this->makeClass('X RPL 1');
        $teacher = $this->makeUser(2);
        $exam = Exam::create([
            'teacher_id' => $teacher->id,
            'class_id'   => $class->id,
            'subject'    => 'Basis Data',
            'category'   => 'materi',
            'title'      => 'Pengenalan SQL',
            'exam_date'  => '2026-10-20',
            'status'     => 'published',
        ]);

        $bk = $this->makeUser(3);
        $student = $this->makeUser(4, ['class_id' => $class->id]);

        foreach ([$bk, $student] as $user) {
            $this->apiAs($user)->postJson('/api/v1/exams', [
                'category'   => 'materi',
                'title'      => 'Modul Ilegal',
                'subject'    => 'Basis Data',
                'class_ids'  => [$class->id],
                'exam_date'  => '2026-10-20',
            ])->assertStatus(403);

            $this->apiAs($user)->putJson('/api/v1/exams/' . $exam->id, [
                'category'   => 'materi',
                'title'      => 'Modul Diubah',
                'subject'    => 'Basis Data',
                'class_id'   => $class->id,
                'exam_date'  => '2026-10-20',
            ])->assertStatus(403);

            $this->apiAs($user)->deleteJson('/api/v1/exams/' . $exam->id)->assertStatus(403);
        }
    }

    public function test_teacher_cannot_update_or_delete_other_teacher_exam_but_admin_can(): void
    {
        $class = $this->makeClass('X RPL 1');
        $teacherA = $this->makeUser(2);
        $teacherB = $this->makeUser(2);
        $admin = $this->makeUser(1);

        $exam = Exam::create([
            'teacher_id' => $teacherA->id,
            'class_id'   => $class->id,
            'subject'    => 'Basis Data',
            'category'   => 'materi',
            'title'      => 'Materi Guru A',
            'exam_date'  => '2026-10-20',
            'status'     => 'published',
        ]);

        // Teacher B tries to edit/delete Teacher A's exam -> 403
        $this->apiAs($teacherB)->putJson('/api/v1/exams/' . $exam->id, [
            'category'   => 'materi',
            'title'      => 'Materi Dibajak',
            'subject'    => 'Basis Data',
            'class_id'   => $class->id,
            'exam_date'  => '2026-10-20',
        ])->assertStatus(403);

        $this->apiAs($teacherB)->deleteJson('/api/v1/exams/' . $exam->id)->assertStatus(403);

        // Admin can update Teacher A's exam
        $this->apiAs($admin)->putJson('/api/v1/exams/' . $exam->id, [
            'category'   => 'materi',
            'title'      => 'Materi Diedit Admin',
            'subject'    => 'Basis Data',
            'class_id'   => $class->id,
            'exam_date'  => '2026-10-20',
        ])->assertOk()->assertJsonPath('data.title', 'Materi Diedit Admin');

        // Admin can delete
        $this->apiAs($admin)->deleteJson('/api/v1/exams/' . $exam->id)->assertOk();
        $this->assertDatabaseMissing('exams', ['id' => $exam->id]);
    }

    public function test_student_scoping_and_visibility(): void
    {
        $classA = $this->makeClass('X RPL 1');
        $classB = $this->makeClass('X RPL 2');
        $teacher = $this->makeUser(2);

        $examA = Exam::create([
            'teacher_id' => $teacher->id,
            'class_id'   => $classA->id,
            'subject'    => 'PBO',
            'category'   => 'materi',
            'title'      => 'OOP Dasar',
            'exam_date'  => '2026-10-20',
            'status'     => 'published',
        ]);

        $examB = Exam::create([
            'teacher_id' => $teacher->id,
            'class_id'   => $classB->id,
            'subject'    => 'PBO',
            'category'   => 'ujian',
            'title'      => 'UTS PBO Kelas B',
            'exam_date'  => '2026-10-21',
            'status'     => 'published',
        ]);

        $studentA = $this->makeUser(4, ['class_id' => $classA->id]);

        // Student A only sees examA
        $list = $this->apiAs($studentA)->getJson('/api/v1/exams')->assertOk()->json('data');
        $this->assertCount(1, $list);
        $this->assertSame($examA->id, $list[0]['id']);

        // Student A can view examA
        $this->apiAs($studentA)->getJson('/api/v1/exams/' . $examA->id)
            ->assertOk()
            ->assertJsonPath('data.title', 'OOP Dasar');

        // Student A cannot view examB
        $this->apiAs($studentA)->getJson('/api/v1/exams/' . $examB->id)->assertStatus(403);
    }

    public function test_exam_validation_rules(): void
    {
        $teacher = $this->makeUser(2);

        $this->apiAs($teacher)->postJson('/api/v1/exams', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['category', 'title', 'subject', 'class_ids', 'exam_date']);

        $class = $this->makeClass('X RPL 1');
        $fakeLargeFile = UploadedFile::fake()->create('huge.pdf', 52000, 'application/pdf'); // > 51200 KB

        $this->apiAs($teacher)->postJson('/api/v1/exams', [
            'category'      => 'materi',
            'title'         => 'Modul Besar',
            'subject'       => 'PBO',
            'class_ids'     => [$class->id],
            'exam_date'     => '2026-10-20',
            'material_file' => $fakeLargeFile,
        ])->assertStatus(422)->assertJsonValidationErrors(['material_file']);
    }

    public function test_teacher_can_create_broadcast_exam_with_file(): void
    {
        $teacher = $this->makeUser(2);
        $c1 = $this->makeClass('X RPL 1');
        $c2 = $this->makeClass('X RPL 2');

        $file = UploadedFile::fake()->create('modul-belajar.pdf', 1024, 'application/pdf');

        $response = $this->apiAs($teacher)->postJson('/api/v1/exams', [
            'category'      => 'materi',
            'title'         => 'Modul Pemrograman Java',
            'subject'       => 'PBO',
            'class_ids'     => [$c1->id, $c2->id],
            'exam_date'     => '2026-10-22',
            'start_time'    => '08:00',
            'end_time'      => '10:00',
            'description'   => 'Pelajari Bab 1 sampai 3',
            'material_file' => $file,
        ])->assertStatus(201);

        $data = $response->json('data');
        $this->assertCount(2, $data);
        $this->assertSame('Modul Pemrograman Java', $data[0]['title']);
        $this->assertTrue($data[0]['has_file']);
        $this->assertSame('modul-belajar.pdf', $data[0]['file_name']);

        $this->assertDatabaseCount('exams', 2);
    }

    public function test_download_attached_material_file(): void
    {
        $teacher = $this->makeUser(2);
        $class = $this->makeClass('X RPL 1');
        $student = $this->makeUser(4, ['class_id' => $class->id]);

        $file = UploadedFile::fake()->create('arsip-tugas.zip', 500, 'application/zip');
        $storedPath = $file->store('exam_materials', 'public');

        $exam = Exam::create([
            'teacher_id'         => $teacher->id,
            'class_id'           => $class->id,
            'subject'            => 'PBO',
            'category'           => 'bank_soal',
            'title'              => 'Tugas 1',
            'exam_date'          => '2026-10-22',
            'status'             => 'published',
            'material_file_path' => $storedPath,
            'file_name'          => 'arsip-tugas.zip',
            'file_size'          => 512000,
        ]);

        // Student can download
        $download = $this->apiAs($student)->get('/api/v1/exams/' . $exam->id . '/download');
        $download->assertOk();
        $this->assertStringContainsString('attachment; filename=arsip-tugas.zip', $download->headers->get('content-disposition'));

        // Student of another class cannot download
        $otherClass = $this->makeClass('X RPL 2');
        $otherStudent = $this->makeUser(4, ['class_id' => $otherClass->id]);
        $this->apiAs($otherStudent)->get('/api/v1/exams/' . $exam->id . '/download')->assertStatus(403);
    }

    public function test_update_and_delete_exam_with_file_cleanup(): void
    {
        $teacher = $this->makeUser(2);
        $class = $this->makeClass('X RPL 1');

        $file1 = UploadedFile::fake()->create('awal.pdf', 200, 'application/pdf');
        $path1 = $file1->store('exam_materials', 'public');

        $exam = Exam::create([
            'teacher_id'         => $teacher->id,
            'class_id'           => $class->id,
            'subject'            => 'Web',
            'category'           => 'materi',
            'title'              => 'HTML Dasar',
            'exam_date'          => '2026-10-20',
            'status'             => 'published',
            'material_file_path' => $path1,
            'file_name'          => 'awal.pdf',
            'file_size'          => 204800,
        ]);

        Storage::disk('public')->assertExists($path1);

        // Replace file
        $file2 = UploadedFile::fake()->create('revisi.pdf', 300, 'application/pdf');
        $this->apiAs($teacher)->putJson('/api/v1/exams/' . $exam->id, [
            'category'      => 'materi',
            'title'         => 'HTML Revisi',
            'subject'       => 'Web',
            'class_id'      => $class->id,
            'exam_date'     => '2026-10-20',
            'material_file' => $file2,
        ])->assertOk()->assertJsonPath('data.file_name', 'revisi.pdf');

        Storage::disk('public')->assertMissing($path1);

        // Delete exam removes file2 as well
        $newPath = $exam->fresh()->material_file_path;
        Storage::disk('public')->assertExists($newPath);

        $this->apiAs($teacher)->deleteJson('/api/v1/exams/' . $exam->id)->assertOk();
        Storage::disk('public')->assertMissing($newPath);
    }
}
