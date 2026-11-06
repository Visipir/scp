<?php

namespace Tests\Feature;

use App\Models\HomeroomAssignment;
use App\Models\LeaveRequest;
use App\Models\Schedule;
use App\Models\SchoolClass;
use App\Models\SystemConfiguration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Support\ScpTestHelpers;
use Tests\TestCase;

class TeacherClassAccessE2Test extends TestCase
{
    use RefreshDatabase;
    use ScpTestHelpers;

    private SchoolClass $classA;
    private SchoolClass $classB;
    private SchoolClass $classC;

    private User $guruPengajarA;
    private User $guruWaliAMengajarB;

    private User $siswaA;
    private User $siswaB;
    private User $siswaC;

    private Schedule $scheduleA;
    private Schedule $scheduleB;

    private LeaveRequest $leaveA;
    private LeaveRequest $leaveB;
    private LeaveRequest $leaveC;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Storage::fake('public');
        $this->seedRoles();

        $this->makeConfig(['active_cycle' => 'Putaran 2']);

        $this->classA = SchoolClass::create(['class_name' => 'X RPL 1', 'academic_year' => '2026/2027', 'grade_level' => 'X', 'major' => 'RPL']);
        $this->classB = SchoolClass::create(['class_name' => 'X TKJ 1', 'academic_year' => '2026/2027', 'grade_level' => 'X', 'major' => 'TKJ']);
        $this->classC = SchoolClass::create(['class_name' => 'XI DKV 1', 'academic_year' => '2026/2027', 'grade_level' => 'XI', 'major' => 'DKV']);

        $this->guruPengajarA = $this->makeUser(2, ['username' => 'guru_pengajar_a', 'fullname' => 'Guru Biasa Pengajar A']);
        $this->scheduleA = Schedule::create([
            'teacher_id'   => $this->guruPengajarA->id,
            'class_id'     => $this->classA->id,
            'subject'      => 'Basis Data',
            'day_of_week'  => 'Senin',
            'start_time'   => '07:15:00',
            'end_time'     => '08:45:00',
            'start_period' => 1,
            'end_period'   => 2,
            'cycle_type'   => 'Putaran 2',
        ]);

        $this->guruWaliAMengajarB = $this->makeUser(2, ['username' => 'wali_a_ajar_b', 'fullname' => 'Guru Wali A Mengajar B']);
        HomeroomAssignment::create([
            'user_id'       => $this->guruWaliAMengajarB->id,
            'class_id'      => $this->classA->id,
            'academic_year' => '2026/2027',
        ]);
        $this->scheduleB = Schedule::create([
            'teacher_id'   => $this->guruWaliAMengajarB->id,
            'class_id'     => $this->classB->id,
            'subject'      => 'Jaringan Komputer',
            'day_of_week'  => 'Senin',
            'start_time'   => '08:45:00',
            'end_time'     => '10:15:00',
            'start_period' => 3,
            'end_period'   => 4,
            'cycle_type'   => 'Putaran 2',
        ]);

        $this->siswaA = $this->makeUser(4, ['username' => '20001', 'fullname' => 'Siswa Kelas A', 'class_id' => $this->classA->id, 'attendance_number' => 1]);
        $this->siswaB = $this->makeUser(4, ['username' => '20002', 'fullname' => 'Siswa Kelas B', 'class_id' => $this->classB->id, 'attendance_number' => 1]);
        $this->siswaC = $this->makeUser(4, ['username' => '20003', 'fullname' => 'Siswa Kelas C', 'class_id' => $this->classC->id, 'attendance_number' => 1]);

        $this->leaveA = LeaveRequest::create([
            'student_id'      => $this->siswaA->id,
            'applicant_id'    => $this->siswaA->id,
            'start_date'      => '2026-10-12',
            'end_date'        => '2026-10-12',
            'reason'          => 'Izin Keluarga A',
            'status_approval' => 'Pending',
        ]);

        $this->leaveB = LeaveRequest::create([
            'student_id'      => $this->siswaB->id,
            'applicant_id'    => $this->siswaB->id,
            'start_date'      => '2026-10-12',
            'end_date'        => '2026-10-12',
            'reason'          => 'Izin Keluarga B',
            'status_approval' => 'Pending',
        ]);

        $this->leaveC = LeaveRequest::create([
            'student_id'      => $this->siswaC->id,
            'applicant_id'    => $this->siswaC->id,
            'start_date'      => '2026-10-12',
            'end_date'        => '2026-10-12',
            'reason'          => 'Izin Keluarga C',
            'status_approval' => 'Pending',
        ]);
    }

    public function test_regular_non_wali_teacher_full_flow_journal_recap_export_and_403_boundaries(): void
    {
        // 1. Open journal form (Web + API) -> students of Class A appear
        $webForm = $this->actingAs($this->guruPengajarA)
            ->get(route('journals.create', ['schedule_id' => $this->scheduleA->id, 'date' => '2026-10-12']))
            ->assertOk();
        $webForm->assertSee('Siswa Kelas A');

        $apiPrepare = $this->apiAs($this->guruPengajarA)
            ->getJson("/api/v1/journals/prepare?schedule_id={$this->scheduleA->id}&date=2026-10-12")
            ->assertOk()
            ->json('data');
        $this->assertSame([$this->siswaA->id], array_column($apiPrepare['students'], 'student_id'));

        // 2. Save journal + attendance (API)
        $photo = UploadedFile::fake()->image('kbm.jpg');
        $this->apiAs($this->guruPengajarA)
            ->post('/api/v1/journals', [
                'schedule_id'       => $this->scheduleA->id,
                'date'              => '2026-10-12',
                'topic_description' => 'Normalisasi Basis Data',
                'photo'             => $photo,
                'absent_students'   => [
                    [
                        'student_id' => $this->siswaA->id,
                        'status'     => 'Sakit',
                        'notes'      => 'Demam tinggi',
                    ],
                ],
            ], ['Accept' => 'application/json'])
            ->assertStatus(201);

        // 3. View class list -> sees ONLY Class A
        $this->actingAs($this->guruPengajarA)->get(route('classes.index'))->assertOk()->assertSee('X RPL 1');
        $apiClasses = $this->apiAs($this->guruPengajarA)->getJson('/api/v1/classes?page=1')->assertOk()->json('data');
        $this->assertSame([$this->classA->id], array_column($apiClasses, 'id'));

        // 4. View student list of taught class (200) vs non-taught class (403)
        $studentsA = $this->apiAs($this->guruPengajarA)
            ->getJson("/api/v1/classes/{$this->classA->id}/students")
            ->assertOk()
            ->json('data');
        $this->assertSame([$this->siswaA->id], array_column($studentsA, 'id'));
        $this->actingAs($this->guruPengajarA)->get(route('classes.students', $this->classA))->assertOk();
        $this->apiAs($this->guruPengajarA)->getJson("/api/v1/classes/{$this->classB->id}/students")->assertStatus(403);

        // 5. View daily attendance recap & student notes for taught Class A (200)
        $this->actingAs($this->guruPengajarA)
            ->get(route('classes.show', ['class' => $this->classA->id, 'date' => '2026-10-12']))
            ->assertOk()
            ->assertSee('Siswa Kelas A')
            ->assertSee('Demam tinggi');

        $dailyRecap = $this->apiAs($this->guruPengajarA)
            ->getJson("/api/v1/classes/{$this->classA->id}/attendance?date=2026-10-12")
            ->assertOk()
            ->json('data');
        $this->assertSame(1, $dailyRecap['summary']['total_sakit']);

        $detailA = $this->apiAs($this->guruPengajarA)
            ->getJson("/api/v1/classes/{$this->classA->id}?date=2026-10-12")
            ->assertOk()
            ->json('data');
        $this->assertSame(1, $detailA['summary']['total_sakit']);

        // 6. Export CSV recap of taught Class A (200)
        $this->actingAs($this->guruPengajarA)
            ->get(route('classes.export', ['class' => $this->classA->id, 'date' => '2026-10-12']))
            ->assertOk();
        $this->apiAs($this->guruPengajarA)
            ->get("/api/v1/classes/{$this->classA->id}/export?date=2026-10-12")
            ->assertOk();

        // 7. Non-taught class (Class B) -> 403 on detail, daily recap, monthly recap, student notes, CSV export
        $this->actingAs($this->guruPengajarA)->get(route('classes.show', $this->classB))->assertStatus(403);
        $this->apiAs($this->guruPengajarA)->getJson("/api/v1/classes/{$this->classB->id}")->assertStatus(403);
        $this->apiAs($this->guruPengajarA)->getJson("/api/v1/classes/{$this->classB->id}/attendance?date=2026-10-12")->assertStatus(403);
        $this->apiAs($this->guruPengajarA)->getJson("/api/v1/classes/{$this->classB->id}/attendance?month=2026-10")->assertStatus(403);
        $this->actingAs($this->guruPengajarA)->get(route('classes.export', ['class' => $this->classB->id, 'date' => '2026-10-12']))->assertStatus(403);
        $this->apiAs($this->guruPengajarA)->getJson("/api/v1/classes/{$this->classB->id}/export?date=2026-10-12")->assertStatus(403);

        // 8. Leave requests -> 403 (even for taught Class A!)
        $this->actingAs($this->guruPengajarA)->get(route('leave-requests.index'))->assertStatus(403);
        $this->apiAs($this->guruPengajarA)->getJson('/api/v1/leave-requests')->assertStatus(403);
        $this->apiAs($this->guruPengajarA)->getJson("/api/v1/leave-requests/{$this->leaveA->id}")->assertStatus(403);
        $this->apiAs($this->guruPengajarA)->patchJson("/api/v1/leave-requests/{$this->leaveA->id}/status", ['status_approval' => 'Approved'])->assertStatus(403);
    }

    public function test_homeroom_teacher_plus_teaching_other_class_has_combined_access(): void
    {
        // 1. Class list: union of homeroom Class A and taught Class B ([classA, classB]), NOT Class C
        $classes = $this->apiAs($this->guruWaliAMengajarB)->getJson('/api/v1/classes?page=1')->assertOk()->json('data');
        $this->assertEqualsCanonicalizing([$this->classA->id, $this->classB->id], array_column($classes, 'id'));

        // 2. Detail, students, recap, and CSV export: 200 for both Class A and Class B; 403 for Class C
        foreach ([$this->classA, $this->classB] as $allowedClass) {
            $this->actingAs($this->guruWaliAMengajarB)->get(route('classes.show', $allowedClass))->assertOk();
            $this->apiAs($this->guruWaliAMengajarB)->getJson("/api/v1/classes/{$allowedClass->id}")->assertOk();
            $this->apiAs($this->guruWaliAMengajarB)->getJson("/api/v1/classes/{$allowedClass->id}/students")->assertOk();
            $this->apiAs($this->guruWaliAMengajarB)->getJson("/api/v1/classes/{$allowedClass->id}/attendance?date=2026-10-12")->assertOk();
            $this->apiAs($this->guruWaliAMengajarB)->getJson("/api/v1/classes/{$allowedClass->id}/export?date=2026-10-12")->assertOk();
        }

        $this->actingAs($this->guruWaliAMengajarB)->get(route('classes.show', $this->classC))->assertStatus(403);
        $this->apiAs($this->guruWaliAMengajarB)->getJson("/api/v1/classes/{$this->classC->id}")->assertStatus(403);
        $this->apiAs($this->guruWaliAMengajarB)->getJson("/api/v1/classes/{$this->classC->id}/students")->assertStatus(403);
        $this->apiAs($this->guruWaliAMengajarB)->getJson("/api/v1/classes/{$this->classC->id}/attendance?date=2026-10-12")->assertStatus(403);
        $this->apiAs($this->guruWaliAMengajarB)->getJson("/api/v1/classes/{$this->classC->id}/export?date=2026-10-12")->assertStatus(403);

        // 3. Leave requests: ONLY supervised Class A (200), NOT taught Class B (403) or Class C (403)
        $leaves = $this->apiAs($this->guruWaliAMengajarB)->getJson('/api/v1/leave-requests')->assertOk()->json('data');
        $this->assertSame([$this->leaveA->id], array_column($leaves, 'id'));
        $this->apiAs($this->guruWaliAMengajarB)->getJson("/api/v1/leave-requests/{$this->leaveA->id}")->assertOk();
        $this->apiAs($this->guruWaliAMengajarB)->getJson("/api/v1/leave-requests/{$this->leaveB->id}")->assertStatus(403);
        $this->apiAs($this->guruWaliAMengajarB)->patchJson("/api/v1/leave-requests/{$this->leaveB->id}/status", ['status_approval' => 'Approved'])->assertStatus(403);
        $this->apiAs($this->guruWaliAMengajarB)->getJson("/api/v1/leave-requests/{$this->leaveC->id}")->assertStatus(403);
    }
}
