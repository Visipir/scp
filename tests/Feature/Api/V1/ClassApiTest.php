<?php

namespace Tests\Feature\Api\V1;

use App\Models\Attendance;
use App\Models\Journal;
use App\Models\Schedule;
use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\ScpTestHelpers;
use Tests\TestCase;

class ClassApiTest extends TestCase
{
    use RefreshDatabase;
    use ScpTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
    }

    public function test_class_management_endpoints_require_authentication(): void
    {
        $this->postJson('/api/v1/classes', [])->assertStatus(401);
        $this->getJson('/api/v1/classes/1')->assertStatus(401);
        $this->putJson('/api/v1/classes/1', [])->assertStatus(401);
        $this->deleteJson('/api/v1/classes/1')->assertStatus(401);
        $this->getJson('/api/v1/classes/1/attendance?month=2026-10')->assertStatus(401);
        $this->getJson('/api/v1/classes/1/export?start_date=2026-10-01&end_date=2026-10-31')->assertStatus(401);
    }

    public function test_guru_and_siswa_cannot_access_class_management(): void
    {
        $class = $this->makeClass('X RPL 1');
        $guru = $this->makeUser(2);
        $siswa = $this->makeUser(4);

        foreach ([$guru, $siswa] as $user) {
            $this->apiAs($user)->postJson('/api/v1/classes', [
                'class_name'    => 'X RPL 2',
                'academic_year' => '2026/2027',
            ])->assertStatus(403);

            $this->apiAs($user)->getJson('/api/v1/classes/' . $class->id)->assertStatus(403);
            $this->apiAs($user)->putJson('/api/v1/classes/' . $class->id, [
                'class_name'    => 'X RPL 1 Renamed',
                'academic_year' => '2026/2027',
            ])->assertStatus(403);
            $this->apiAs($user)->deleteJson('/api/v1/classes/' . $class->id)->assertStatus(403);
            $this->apiAs($user)->getJson('/api/v1/classes/' . $class->id . '/attendance?month=2026-10')->assertStatus(403);
            $this->apiAs($user)->getJson('/api/v1/classes/' . $class->id . '/export?start_date=2026-10-01&end_date=2026-10-31')->assertStatus(403);
        }

        // But lookup GET /api/v1/classes is allowed for guru & siswa
        $this->apiAs($guru)->getJson('/api/v1/classes')->assertOk();
        $this->apiAs($siswa)->getJson('/api/v1/classes')->assertOk();
    }

    public function test_classes_index_supports_pagination_and_filtering(): void
    {
        $admin = $this->makeUser(1);
        $c1 = $this->makeClass('X PPLG 1', '2026/2027');
        $c1->update(['grade_level' => 'X', 'major' => 'PPLG']);
        $c2 = $this->makeClass('XI PPLG 1', '2026/2027');
        $c2->update(['grade_level' => 'XI', 'major' => 'PPLG']);
        $c3 = $this->makeClass('XII AKL 1', '2025/2026');
        $c3->update(['grade_level' => 'XII', 'major' => 'AKL']);

        // Plain list
        $plain = $this->apiAs($admin)->getJson('/api/v1/classes')->assertOk()->json('data');
        $this->assertCount(3, $plain);

        // Paginated
        $paginated = $this->apiAs($admin)->getJson('/api/v1/classes?per_page=2&page=1')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [['id', 'class_name', 'academic_year']],
                'meta' => ['current_page', 'per_page', 'total', 'last_page'],
            ]);
        $this->assertCount(2, $paginated->json('data'));
        $this->assertSame(3, $paginated->json('meta.total'));

        // Filter by grade_level
        $filtered = $this->apiAs($admin)->getJson('/api/v1/classes?grade_level=XII')->assertOk()->json('data');
        $this->assertCount(1, $filtered);
        $this->assertSame('XII AKL 1', $filtered[0]['class_name']);

        // Filter by search
        $searched = $this->apiAs($admin)->getJson('/api/v1/classes?search=PPLG')->assertOk()->json('data');
        $this->assertCount(2, $searched);
    }

    public function test_admin_can_create_update_and_show_class(): void
    {
        $admin = $this->makeUser(1);

        // Create
        $response = $this->apiAs($admin)->postJson('/api/v1/classes', [
            'class_name'    => 'X PPLG 2',
            'academic_year' => '2026/2027',
        ])->assertStatus(201);

        $createdId = $response->json('data.id');
        $this->assertNotNull($createdId);
        $this->assertSame('X PPLG 2', $response->json('data.class_name'));
        $this->assertSame('X', $response->json('data.grade_level'));
        $this->assertSame('PPLG', $response->json('data.major'));

        // Show
        $show = $this->apiAs($admin)->getJson('/api/v1/classes/' . $createdId)
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'class_name',
                    'academic_year',
                    'grade_level',
                    'major',
                    'students_count',
                    'summary' => [
                        'total_hadir',
                        'total_sakit',
                        'total_izin',
                        'total_alpa',
                        'total_terlambat',
                        'total_records',
                        'attendance_rate',
                    ],
                    'students',
                    'recent_absences',
                ],
            ]);
        $this->assertSame(0, $show->json('data.students_count'));

        // Update
        $this->apiAs($admin)->putJson('/api/v1/classes/' . $createdId, [
            'class_name'    => 'XI PPLG 2',
            'academic_year' => '2027/2028',
        ])->assertOk()
          ->assertJson([
              'data' => [
                  'id'            => $createdId,
                  'class_name'    => 'XI PPLG 2',
                  'academic_year' => '2027/2028',
                  'grade_level'   => 'XI',
              ],
          ]);
    }

    public function test_class_validation_errors(): void
    {
        $admin = $this->makeUser(1);
        $class = $this->makeClass('X DKV 1');

        // Store validation
        $this->apiAs($admin)->postJson('/api/v1/classes', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['class_name', 'academic_year']);

        // Update validation
        $this->apiAs($admin)->putJson('/api/v1/classes/' . $class->id, [
            'class_name' => '',
        ])->assertStatus(422)
          ->assertJsonValidationErrors(['class_name', 'academic_year']);

        // Attendance validation
        $this->apiAs($admin)->getJson('/api/v1/classes/' . $class->id . '/attendance')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['month']);

        $this->apiAs($admin)->getJson('/api/v1/classes/' . $class->id . '/attendance?month=invalid-date')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['month']);

        // Export validation
        $this->apiAs($admin)->getJson('/api/v1/classes/' . $class->id . '/export')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['start_date', 'end_date']);

        $this->apiAs($admin)->getJson('/api/v1/classes/' . $class->id . '/export?start_date=2026-10-31&end_date=2026-10-01')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['end_date']);
    }

    public function test_wali_kelas_scoping_on_classes(): void
    {
        $classA = $this->makeClass('X RPL 1');
        $classB = $this->makeClass('X RPL 2');

        $waliA = $this->makeUser(3, ['class_id' => $classA->id]);

        // Wali A only sees classA in index
        $index = $this->apiAs($waliA)->getJson('/api/v1/classes')->assertOk()->json('data');
        $this->assertCount(1, $index);
        $this->assertSame($classA->id, $index[0]['id']);

        // Wali A cannot access classB
        $this->apiAs($waliA)->getJson('/api/v1/classes/' . $classB->id)->assertStatus(403);
        $this->apiAs($waliA)->putJson('/api/v1/classes/' . $classB->id, [
            'class_name'    => 'X RPL 2 Hack',
            'academic_year' => '2026/2027',
        ])->assertStatus(403);
        $this->apiAs($waliA)->deleteJson('/api/v1/classes/' . $classB->id)->assertStatus(403);
        $this->apiAs($waliA)->getJson('/api/v1/classes/' . $classB->id . '/attendance?month=2026-10')->assertStatus(403);
        $this->apiAs($waliA)->getJson('/api/v1/classes/' . $classB->id . '/export?start_date=2026-10-01&end_date=2026-10-31')->assertStatus(403);

        // Wali A can access classA
        $this->apiAs($waliA)->getJson('/api/v1/classes/' . $classA->id)->assertOk();
        $this->apiAs($waliA)->getJson('/api/v1/classes/' . $classA->id . '/attendance?month=2026-10')->assertOk();
        $this->apiAs($waliA)->getJson('/api/v1/classes/' . $classA->id . '/export?start_date=2026-10-01&end_date=2026-10-31')->assertOk();
    }

    public function test_monthly_attendance_recap_json(): void
    {
        $admin = $this->makeUser(1);
        $class = $this->makeClass('X SIJA 1');
        $student = $this->makeUser(4, ['class_id' => $class->id, 'attendance_number' => 1]);
        $schedule = $this->makeSchedule(['class_id' => $class->id]);
        $journal = Journal::create(['schedule_id' => $schedule->id, 'date' => '2026-10-15', 'topic_description' => 'Topik A']);

        Attendance::create([
            'journal_id' => $journal->id,
            'student_id' => $student->id,
            'date'       => '2026-10-15',
            'status'     => 'Hadir',
        ]);
        Attendance::create([
            'journal_id' => $journal->id,
            'student_id' => $student->id,
            'date'       => '2026-10-16',
            'status'     => 'Sakit',
            'notes'      => 'Demam',
        ]);

        $response = $this->apiAs($admin)->getJson('/api/v1/classes/' . $class->id . '/attendance?month=2026-10')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'class' => ['id', 'class_name', 'academic_year'],
                    'month',
                    'summary' => [
                        'total_hadir',
                        'total_sakit',
                        'total_izin',
                        'total_alpa',
                        'total_terlambat',
                        'total_records',
                        'attendance_rate',
                    ],
                    'students' => [
                        [
                            'id',
                            'attendance_number',
                            'fullname',
                            'username',
                            'gender',
                            'hadir',
                            'sakit',
                            'izin',
                            'alpa',
                            'terlambat',
                            'total',
                            'attendance_rate',
                        ],
                    ],
                ],
            ]);

        $this->assertSame(1, $response->json('data.summary.total_hadir'));
        $this->assertSame(1, $response->json('data.summary.total_sakit'));
        $this->assertSame(2, $response->json('data.summary.total_records'));
        $this->assertSame(50.0, (float) $response->json('data.summary.attendance_rate'));
    }

    public function test_export_attendance_csv_stream(): void
    {
        $admin = $this->makeUser(1);
        $class = $this->makeClass('X TO 1');
        $student = $this->makeUser(4, ['class_id' => $class->id, 'fullname' => 'Budi Santoso']);
        $schedule = $this->makeSchedule(['class_id' => $class->id]);
        $journal = Journal::create(['schedule_id' => $schedule->id, 'date' => '2026-10-10', 'topic_description' => 'Topik B']);

        Attendance::create([
            'journal_id' => $journal->id,
            'student_id' => $student->id,
            'date'       => '2026-10-10',
            'status'     => 'Hadir',
        ]);

        $response = $this->apiAs($admin)->get('/api/v1/classes/' . $class->id . '/export?start_date=2026-10-01&end_date=2026-10-31');

        $response->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('content-type'));
        $this->assertStringContainsString('Rekap_Presensi_X TO 1', $response->headers->get('content-disposition'));

        $content = $response->streamedContent();
        $this->assertStringContainsString('No,"Nama Siswa",Hadir,Izin,Sakit,Alpa,Terlambat', $content);
        $this->assertStringContainsString('Budi Santoso', $content);
    }

    public function test_cannot_delete_class_with_students_or_schedules(): void
    {
        $admin = $this->makeUser(1);
        $classWithStudent = $this->makeClass('Class With Student');
        $this->makeUser(4, ['class_id' => $classWithStudent->id]);

        $this->apiAs($admin)->deleteJson('/api/v1/classes/' . $classWithStudent->id)
            ->assertStatus(422)
            ->assertJsonFragment(['message' => "Tidak dapat menghapus kelas {$classWithStudent->class_name} karena masih memiliki data siswa terdaftar."]);

        $classWithSchedule = $this->makeClass('Class With Schedule');
        $this->makeSchedule(['class_id' => $classWithSchedule->id]);

        $this->apiAs($admin)->deleteJson('/api/v1/classes/' . $classWithSchedule->id)
            ->assertStatus(422)
            ->assertJsonFragment(['message' => "Tidak dapat menghapus kelas {$classWithSchedule->class_name} karena terdapat jadwal mengajar yang tertaut."]);

        $this->apiAs($admin)->deleteJson('/api/v1/classes/' . $classWithSchedule->id)
            ->assertStatus(422)
            ->assertJsonFragment(['message' => "Tidak dapat menghapus kelas {$classWithSchedule->class_name} karena terdapat jadwal mengajar yang tertaut."]);

        // Empty class can be deleted
        $emptyClass = $this->makeClass('Empty Class');
        $this->apiAs($admin)->deleteJson('/api/v1/classes/' . $emptyClass->id)
            ->assertOk()
            ->assertJson(['message' => "Kelas {$emptyClass->class_name} berhasil dihapus."]);

        $this->assertDatabaseMissing('classes', ['id' => $emptyClass->id]);
    }
}
