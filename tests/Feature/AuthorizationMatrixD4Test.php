<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\CounselorAssignment;
use App\Models\HomeroomAssignment;
use App\Models\Journal;
use App\Models\LeaveRequest;
use App\Models\Schedule;
use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\Support\ScpTestHelpers;
use Tests\TestCase;

class AuthorizationMatrixD4Test extends TestCase
{
    use RefreshDatabase;
    use ScpTestHelpers;

    private SchoolClass $classA;
    private SchoolClass $classB;
    private SchoolClass $classC;
    private SchoolClass $classD;

    private User $waka;
    private User $bkA;
    private User $bkB;
    private User $waliA;
    private User $waliB;
    private User $guruBiasa;
    private User $guruWaliAMengajarC;
    private User $siswaA;
    private User $siswaB;
    private User $siswaC;

    private LeaveRequest $leaveA;
    private LeaveRequest $leaveB;
    private LeaveRequest $leaveC;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->seedRoles();

        $this->classA = SchoolClass::create(['class_name' => 'X RPL 1', 'academic_year' => '2026/2027', 'grade_level' => 'X', 'major' => 'RPL']);
        $this->classB = SchoolClass::create(['class_name' => 'X TKJ 1', 'academic_year' => '2026/2027', 'grade_level' => 'X', 'major' => 'TKJ']);
        $this->classC = SchoolClass::create(['class_name' => 'XI DKV 1', 'academic_year' => '2026/2027', 'grade_level' => 'XI', 'major' => 'DKV']);
        $this->classD = SchoolClass::create(['class_name' => 'XI RPL 2', 'academic_year' => '2026/2027', 'grade_level' => 'XI', 'major' => 'RPL']);

        $this->waka = $this->makeUser(1, ['username' => 'waka_d4', 'fullname' => 'Waka Kurikulum']);

        $this->bkA = $this->makeUser(3, ['username' => 'bk_a', 'fullname' => 'BK Kelas A']);
        CounselorAssignment::create(['user_id' => $this->bkA->id, 'class_id' => $this->classA->id, 'academic_year' => '2026/2027']);

        $this->bkB = $this->makeUser(3, ['username' => 'bk_b', 'fullname' => 'BK Kelas B']);
        CounselorAssignment::create(['user_id' => $this->bkB->id, 'class_id' => $this->classB->id, 'academic_year' => '2026/2027']);

        $this->waliA = $this->makeUser(2, ['username' => 'wali_a', 'fullname' => 'Wali Kelas A']);
        HomeroomAssignment::create(['user_id' => $this->waliA->id, 'class_id' => $this->classA->id, 'academic_year' => '2026/2027']);

        $this->waliB = $this->makeUser(2, ['username' => 'wali_b', 'fullname' => 'Wali Kelas B']);
        HomeroomAssignment::create(['user_id' => $this->waliB->id, 'class_id' => $this->classB->id, 'academic_year' => '2026/2027']);

        $this->guruBiasa = $this->makeUser(2, ['username' => 'guru_biasa', 'fullname' => 'Guru Biasa Non Wali']);

        $this->guruWaliAMengajarC = $this->makeUser(2, ['username' => 'wali_d_ajar_c', 'fullname' => 'Guru Wali D Mengajar C']);
        HomeroomAssignment::create(['user_id' => $this->guruWaliAMengajarC->id, 'class_id' => $this->classD->id, 'academic_year' => '2026/2027']);
        $schedC = Schedule::create([
            'teacher_id'   => $this->guruWaliAMengajarC->id,
            'class_id'     => $this->classC->id,
            'subject'      => 'Pemrograman Web',
            'day_of_week'  => 'Senin',
            'start_time'   => '07:15:00',
            'end_time'     => '08:45:00',
            'start_period' => 1,
            'end_period'   => 2,
            'cycle_type'   => 'Putaran 2',
        ]);

        $this->siswaA = $this->makeUser(4, ['username' => '10001', 'fullname' => 'Siswa Kelas A', 'class_id' => $this->classA->id]);
        $this->siswaB = $this->makeUser(4, ['username' => '10002', 'fullname' => 'Siswa Kelas B', 'class_id' => $this->classB->id]);
        $this->siswaC = $this->makeUser(4, ['username' => '10003', 'fullname' => 'Siswa Kelas C', 'class_id' => $this->classC->id]);

        // Seed journals & attendances (including absence notes)
        $journalC = Journal::create([
            'schedule_id'       => $schedC->id,
            'teacher_id'        => $this->guruWaliAMengajarC->id,
            'date'              => '2026-10-12',
            'topic_description' => 'Materi HTML',
        ]);
        Attendance::create(['student_id' => $this->siswaA->id, 'journal_id' => $journalC->id, 'date' => '2026-10-12', 'status' => 'Sakit', 'notes' => 'Demam']);
        Attendance::create(['student_id' => $this->siswaB->id, 'journal_id' => $journalC->id, 'date' => '2026-10-12', 'status' => 'Alpa', 'notes' => 'Tanpa keterangan']);
        Attendance::create(['student_id' => $this->siswaC->id, 'journal_id' => $journalC->id, 'date' => '2026-10-12', 'status' => 'Izin', 'notes' => 'Acara keluarga']);

        Storage::disk('local')->put('leave-documents/doc_a.pdf', 'PDF-A');
        Storage::disk('local')->put('leave-documents/doc_b.pdf', 'PDF-B');
        Storage::disk('local')->put('leave-documents/doc_c.pdf', 'PDF-C');

        $this->leaveA = LeaveRequest::create([
            'student_id'      => $this->siswaA->id,
            'applicant_id'    => $this->siswaA->id,
            'start_date'      => '2026-10-15',
            'end_date'        => '2026-10-15',
            'reason'          => 'Sakit demam A',
            'document_path'   => 'leave-documents/doc_a.pdf',
            'status_approval' => 'Pending',
        ]);

        $this->leaveB = LeaveRequest::create([
            'student_id'      => $this->siswaB->id,
            'applicant_id'    => $this->siswaB->id,
            'start_date'      => '2026-10-15',
            'end_date'        => '2026-10-15',
            'reason'          => 'Sakit demam B',
            'document_path'   => 'leave-documents/doc_b.pdf',
            'status_approval' => 'Pending',
        ]);

        $this->leaveC = LeaveRequest::create([
            'student_id'      => $this->siswaC->id,
            'applicant_id'    => $this->siswaC->id,
            'start_date'      => '2026-10-15',
            'end_date'        => '2026-10-15',
            'reason'          => 'Sakit demam C',
            'document_path'   => 'leave-documents/doc_c.pdf',
            'status_approval' => 'Pending',
        ]);
    }

    public function test_class_list_and_class_detail_and_recap_and_csv_export_matrix(): void
    {
        // 1. Waka: sees all 4 classes, can view/recap/export A, B, C, D
        $wakaClasses = $this->apiAs($this->waka)->getJson('/api/v1/classes?page=1')->assertOk()->json('data');
        $this->assertCount(4, $wakaClasses);
        $this->actingAs($this->waka)->get(route('classes.show', $this->classA))->assertOk();
        $this->actingAs($this->waka)->get(route('classes.show', $this->classB))->assertOk();
        $this->apiAs($this->waka)->getJson("/api/v1/classes/{$this->classB->id}/attendance?month=2026-10")->assertOk();
        $this->actingAs($this->waka)->get(route('classes.export', ['class' => $this->classB->id, 'start_date' => '2026-10-01', 'end_date' => '2026-10-31']))->assertOk();

        // 2. BK-A: sees ONLY class A; 403 on class B & C detail, recap, and export
        $bkAClasses = $this->apiAs($this->bkA)->getJson('/api/v1/classes?page=1')->assertOk()->json('data');
        $this->assertSame([$this->classA->id], array_column($bkAClasses, 'id'));
        // Query string manipulation ?class_id=classB returns empty
        $bkAManip = $this->apiAs($this->bkA)->getJson("/api/v1/classes?page=1&class_id={$this->classB->id}")->assertOk()->json('data');
        $this->assertEmpty($bkAManip);

        $this->actingAs($this->bkA)->get(route('classes.show', $this->classA))->assertOk();
        $this->actingAs($this->bkA)->get(route('classes.show', $this->classB))->assertStatus(403);
        $this->apiAs($this->bkA)->getJson("/api/v1/classes/{$this->classA->id}")->assertOk();
        $this->apiAs($this->bkA)->getJson("/api/v1/classes/{$this->classB->id}")->assertStatus(403);
        $this->apiAs($this->bkA)->getJson("/api/v1/classes/{$this->classA->id}/attendance?month=2026-10")->assertOk();
        $this->apiAs($this->bkA)->getJson("/api/v1/classes/{$this->classB->id}/attendance?month=2026-10")->assertStatus(403);
        $this->actingAs($this->bkA)->get(route('classes.export', ['class' => $this->classA->id, 'start_date' => '2026-10-01', 'end_date' => '2026-10-31']))->assertOk();
        $this->actingAs($this->bkA)->get(route('classes.export', ['class' => $this->classB->id, 'start_date' => '2026-10-01', 'end_date' => '2026-10-31']))->assertStatus(403);
        $this->apiAs($this->bkA)->getJson("/api/v1/classes/{$this->classA->id}/export?start_date=2026-10-01&end_date=2026-10-31")->assertOk();
        $this->apiAs($this->bkA)->getJson("/api/v1/classes/{$this->classB->id}/export?start_date=2026-10-01&end_date=2026-10-31")->assertStatus(403);

        // 3. BK-B: sees ONLY class B; 403 on class A
        $bkBClasses = $this->apiAs($this->bkB)->getJson('/api/v1/classes?page=1')->assertOk()->json('data');
        $this->assertSame([$this->classB->id], array_column($bkBClasses, 'id'));
        $this->actingAs($this->bkB)->get(route('classes.show', $this->classB))->assertOk();
        $this->actingAs($this->bkB)->get(route('classes.show', $this->classA))->assertStatus(403);
        $this->apiAs($this->bkB)->getJson("/api/v1/classes/{$this->classA->id}/attendance?month=2026-10")->assertStatus(403);
        $this->apiAs($this->bkB)->getJson("/api/v1/classes/{$this->classA->id}/export?start_date=2026-10-01&end_date=2026-10-31")->assertStatus(403);

        // 4. Wali Kelas A: sees ONLY class A; 403 on class B
        $waliAClasses = $this->apiAs($this->waliA)->getJson('/api/v1/classes?page=1')->assertOk()->json('data');
        $this->assertSame([$this->classA->id], array_column($waliAClasses, 'id'));
        $this->actingAs($this->waliA)->get(route('classes.show', $this->classA))->assertOk();
        $this->actingAs($this->waliA)->get(route('classes.show', $this->classB))->assertStatus(403);
        $this->apiAs($this->waliA)->getJson("/api/v1/classes/{$this->classA->id}")->assertOk();
        $this->apiAs($this->waliA)->getJson("/api/v1/classes/{$this->classB->id}")->assertStatus(403);
        $this->apiAs($this->waliA)->getJson("/api/v1/classes/{$this->classA->id}/attendance?month=2026-10")->assertOk();
        $this->apiAs($this->waliA)->getJson("/api/v1/classes/{$this->classB->id}/attendance?month=2026-10")->assertStatus(403);
        $this->apiAs($this->waliA)->getJson("/api/v1/classes/{$this->classA->id}/export?start_date=2026-10-01&end_date=2026-10-31")->assertOk();
        $this->apiAs($this->waliA)->getJson("/api/v1/classes/{$this->classB->id}/export?start_date=2026-10-01&end_date=2026-10-31")->assertStatus(403);

        // 5. Wali Kelas B: sees ONLY class B; 403 on class A
        $this->actingAs($this->waliB)->get(route('classes.show', $this->classB))->assertOk();
        $this->actingAs($this->waliB)->get(route('classes.show', $this->classA))->assertStatus(403);
        $this->apiAs($this->waliB)->getJson("/api/v1/classes/{$this->classA->id}/export?start_date=2026-10-01&end_date=2026-10-31")->assertStatus(403);

        // 6. Guru Biasa Non-Wali: 403 on all class management / detail / recap / export endpoints
        $this->actingAs($this->guruBiasa)->get(route('classes.index'))->assertStatus(403);
        $this->apiAs($this->guruBiasa)->getJson('/api/v1/classes?page=1')->assertStatus(403);
        $this->actingAs($this->guruBiasa)->get(route('classes.show', $this->classA))->assertStatus(403);
        $this->apiAs($this->guruBiasa)->getJson("/api/v1/classes/{$this->classA->id}")->assertStatus(403);
        $this->apiAs($this->guruBiasa)->getJson("/api/v1/classes/{$this->classA->id}/attendance?month=2026-10")->assertStatus(403);
        $this->apiAs($this->guruBiasa)->getJson("/api/v1/classes/{$this->classA->id}/export?start_date=2026-10-01&end_date=2026-10-31")->assertStatus(403);

        // 7. Guru yang Wali D + mengajar Kelas C: combined access (Class D + Class C for class/recap/export; 403 on Class A & B)
        $waliAAjarCClasses = $this->apiAs($this->guruWaliAMengajarC)->getJson('/api/v1/classes?page=1')->assertOk()->json('data');
        $this->assertEqualsCanonicalizing([$this->classC->id, $this->classD->id], array_column($waliAAjarCClasses, 'id'));
        $this->actingAs($this->guruWaliAMengajarC)->get(route('classes.show', $this->classD))->assertOk();
        $this->actingAs($this->guruWaliAMengajarC)->get(route('classes.show', $this->classC))->assertOk();
        $this->apiAs($this->guruWaliAMengajarC)->getJson("/api/v1/classes/{$this->classC->id}")->assertOk();
        $this->apiAs($this->guruWaliAMengajarC)->getJson("/api/v1/classes/{$this->classC->id}/attendance?month=2026-10")->assertOk();
        $this->apiAs($this->guruWaliAMengajarC)->getJson("/api/v1/classes/{$this->classC->id}/export?start_date=2026-10-01&end_date=2026-10-31")->assertOk();
        $this->actingAs($this->guruWaliAMengajarC)->get(route('classes.show', $this->classA))->assertStatus(403);
        $this->apiAs($this->guruWaliAMengajarC)->getJson("/api/v1/classes/{$this->classA->id}")->assertStatus(403);
        $this->apiAs($this->guruWaliAMengajarC)->getJson("/api/v1/classes/{$this->classA->id}/attendance?month=2026-10")->assertStatus(403);
        $this->apiAs($this->guruWaliAMengajarC)->getJson("/api/v1/classes/{$this->classA->id}/export?start_date=2026-10-01&end_date=2026-10-31")->assertStatus(403);

        // 8 & 9. Siswa A & Siswa B: 403 on class management endpoints; own attendance only
        foreach ([$this->siswaA, $this->siswaB] as $siswa) {
            $this->actingAs($siswa)->get(route('classes.index'))->assertStatus(403);
            $this->apiAs($siswa)->getJson('/api/v1/classes?page=1')->assertStatus(403);
            $this->actingAs($siswa)->get(route('classes.show', $this->classA))->assertStatus(403);
            $this->apiAs($siswa)->getJson("/api/v1/classes/{$this->classA->id}")->assertStatus(403);
            $this->apiAs($siswa)->getJson("/api/v1/classes/{$this->classA->id}/attendance?month=2026-10")->assertStatus(403);
            $this->apiAs($siswa)->getJson("/api/v1/classes/{$this->classA->id}/export?start_date=2026-10-01&end_date=2026-10-31")->assertStatus(403);
        }

        // Siswa A manipulating ?student_id=siswaB on /attendance gets 403
        $this->actingAs($this->siswaA)->get(route('student.attendance.index', ['student_id' => $this->siswaB->id]))->assertStatus(403);
        $this->apiAs($this->siswaA)->getJson("/api/v1/attendance?student_id={$this->siswaB->id}")->assertStatus(403);
    }

    public function test_student_list_and_query_manipulation_on_users_endpoints(): void
    {
        // BK-A sees only siswaA
        $bkAUsers = $this->apiAs($this->bkA)->getJson('/api/v1/users')->assertOk()->json('data');
        $this->assertSame([$this->siswaA->id], array_column($bkAUsers, 'id'));
        // BK-A manipulating ?class_id=classB or ?student_id=siswaB gets empty
        $this->assertEmpty($this->apiAs($this->bkA)->getJson("/api/v1/users?class_id={$this->classB->id}")->assertOk()->json('data'));
        $this->assertEmpty($this->apiAs($this->bkA)->getJson("/api/v1/users?student_id={$this->siswaB->id}")->assertOk()->json('data'));
        $this->apiAs($this->bkA)->getJson("/api/v1/users/{$this->siswaA->id}")->assertOk();
        $this->apiAs($this->bkA)->getJson("/api/v1/users/{$this->siswaB->id}")->assertStatus(403);

        // Wali A sees only siswaA (even though Wali A has role_id = 2!)
        $waliAUsers = $this->apiAs($this->waliA)->getJson('/api/v1/users')->assertOk()->json('data');
        $this->assertSame([$this->siswaA->id], array_column($waliAUsers, 'id'));
        $this->assertEmpty($this->apiAs($this->waliA)->getJson("/api/v1/users?class_id={$this->classB->id}")->assertOk()->json('data'));
        $this->apiAs($this->waliA)->getJson("/api/v1/users/{$this->siswaA->id}")->assertOk();
        $this->apiAs($this->waliA)->getJson("/api/v1/users/{$this->siswaB->id}")->assertStatus(403);

        // Guru Wali A + Mengajar C cannot access siswaC via users endpoint
        $this->apiAs($this->guruWaliAMengajarC)->getJson("/api/v1/users/{$this->siswaC->id}")->assertStatus(403);
        $this->assertEmpty($this->apiAs($this->guruWaliAMengajarC)->getJson("/api/v1/users?class_id={$this->classC->id}")->assertOk()->json('data'));

        // Guru Biasa & Siswa get 403 on /users and /api/v1/users
        $this->actingAs($this->guruBiasa)->get(route('users.index'))->assertStatus(403);
        $this->apiAs($this->guruBiasa)->getJson('/api/v1/users')->assertStatus(403);
        $this->actingAs($this->siswaA)->get(route('users.index'))->assertStatus(403);
        $this->apiAs($this->siswaA)->getJson('/api/v1/users')->assertStatus(403);
    }

    public function test_leave_requests_list_detail_document_status_and_query_manipulation(): void
    {
        // 1. Waka: sees all 3 leaves, can view/download/update status on any
        $this->assertCount(3, $this->apiAs($this->waka)->getJson('/api/v1/leave-requests')->assertOk()->json('data'));
        $this->apiAs($this->waka)->getJson("/api/v1/leave-requests/{$this->leaveB->id}")->assertOk();
        $this->actingAs($this->waka)->get(route('leave-requests.document', $this->leaveB))->assertOk();

        // 2. BK-A: sees only leaveA; 403 on leaveB & leaveC detail/document/update-status; query ?class_id=classB & ?student_id=siswaB returns empty
        $bkALeaves = $this->apiAs($this->bkA)->getJson('/api/v1/leave-requests')->assertOk()->json('data');
        $this->assertSame([$this->leaveA->id], array_column($bkALeaves, 'id'));
        $this->assertEmpty($this->apiAs($this->bkA)->getJson("/api/v1/leave-requests?class_id={$this->classB->id}")->assertOk()->json('data'));
        $this->assertEmpty($this->apiAs($this->bkA)->getJson("/api/v1/leave-requests?student_id={$this->siswaB->id}")->assertOk()->json('data'));

        $this->actingAs($this->bkA)->get(route('leave-requests.show', $this->leaveA))->assertOk();
        $this->actingAs($this->bkA)->get(route('leave-requests.show', $this->leaveB))->assertStatus(403);
        $this->apiAs($this->bkA)->getJson("/api/v1/leave-requests/{$this->leaveB->id}")->assertStatus(403);
        $this->actingAs($this->bkA)->get(route('leave-requests.document', $this->leaveA))->assertOk();
        $this->actingAs($this->bkA)->get(route('leave-requests.document', $this->leaveB))->assertStatus(403);
        $this->apiAs($this->bkA)->getJson("/api/v1/leave-requests/{$this->leaveB->id}/document")->assertStatus(403);
        $this->apiAs($this->bkA)->patchJson("/api/v1/leave-requests/{$this->leaveB->id}/status", ['status_approval' => 'Approved'])->assertStatus(403);

        // 3. BK-B: sees only leaveB; 403 on leaveA
        $bkBLeaves = $this->apiAs($this->bkB)->getJson('/api/v1/leave-requests')->assertOk()->json('data');
        $this->assertSame([$this->leaveB->id], array_column($bkBLeaves, 'id'));
        $this->apiAs($this->bkB)->getJson("/api/v1/leave-requests/{$this->leaveA->id}")->assertStatus(403);
        $this->apiAs($this->bkB)->getJson("/api/v1/leave-requests/{$this->leaveA->id}/document")->assertStatus(403);
        $this->apiAs($this->bkB)->patchJson("/api/v1/leave-requests/{$this->leaveA->id}/status", ['status_approval' => 'Approved'])->assertStatus(403);

        // 4. Wali Kelas A: sees only leaveA; can approve leaveA; 403 on leaveB
        $waliALeaves = $this->apiAs($this->waliA)->getJson('/api/v1/leave-requests')->assertOk()->json('data');
        $this->assertSame([$this->leaveA->id], array_column($waliALeaves, 'id'));
        $this->apiAs($this->waliA)->getJson("/api/v1/leave-requests/{$this->leaveB->id}")->assertStatus(403);
        $this->apiAs($this->waliA)->getJson("/api/v1/leave-requests/{$this->leaveB->id}/document")->assertStatus(403);
        $this->apiAs($this->waliA)->patchJson("/api/v1/leave-requests/{$this->leaveB->id}/status", ['status_approval' => 'Approved'])->assertStatus(403);

        // 5. Guru Wali D + Mengajar C: cannot see or approve leaveC (Class C) or leaveA (Class A)
        $waliAAjarCLeaves = $this->apiAs($this->guruWaliAMengajarC)->getJson('/api/v1/leave-requests')->assertOk()->json('data');
        $this->assertEmpty($waliAAjarCLeaves);
        $this->assertEmpty($this->apiAs($this->guruWaliAMengajarC)->getJson("/api/v1/leave-requests?class_id={$this->classC->id}")->assertOk()->json('data'));
        $this->apiAs($this->guruWaliAMengajarC)->getJson("/api/v1/leave-requests/{$this->leaveC->id}")->assertStatus(403);
        $this->apiAs($this->guruWaliAMengajarC)->getJson("/api/v1/leave-requests/{$this->leaveC->id}/document")->assertStatus(403);
        $this->apiAs($this->guruWaliAMengajarC)->patchJson("/api/v1/leave-requests/{$this->leaveC->id}/status", ['status_approval' => 'Approved'])->assertStatus(403);

        // 6. Guru Biasa Non-Wali: 403 on all leave endpoints
        $this->actingAs($this->guruBiasa)->get(route('leave-requests.index'))->assertStatus(403);
        $this->apiAs($this->guruBiasa)->getJson('/api/v1/leave-requests')->assertStatus(403);
        $this->apiAs($this->guruBiasa)->getJson("/api/v1/leave-requests/{$this->leaveA->id}")->assertStatus(403);
        $this->apiAs($this->guruBiasa)->patchJson("/api/v1/leave-requests/{$this->leaveA->id}/status", ['status_approval' => 'Approved'])->assertStatus(403);

        // 7. Siswa A & Siswa B: see only own leave; 403 on other student's leave & on status update; query manipulation returns empty
        $siswaALeaves = $this->apiAs($this->siswaA)->getJson('/api/v1/leave-requests')->assertOk()->json('data');
        $this->assertSame([$this->leaveA->id], array_column($siswaALeaves, 'id'));
        $this->assertEmpty($this->apiAs($this->siswaA)->getJson("/api/v1/leave-requests?class_id={$this->classB->id}")->assertOk()->json('data'));
        $this->assertEmpty($this->apiAs($this->siswaA)->getJson("/api/v1/leave-requests?student_id={$this->siswaB->id}")->assertOk()->json('data'));
        $this->apiAs($this->siswaA)->getJson("/api/v1/leave-requests/{$this->leaveB->id}")->assertStatus(403);
        $this->apiAs($this->siswaA)->getJson("/api/v1/leave-requests/{$this->leaveB->id}/document")->assertStatus(403);
        $this->apiAs($this->siswaA)->patchJson("/api/v1/leave-requests/{$this->leaveA->id}/status", ['status_approval' => 'Approved'])->assertStatus(403);
    }

    public function test_dashboard_counters_and_badges_are_strictly_scoped(): void
    {
        // Add a second pending leave in Class B so Class A has 1 pending and Class B has 2 pending (total = 4 with Class C)
        LeaveRequest::create([
            'student_id'      => $this->siswaB->id,
            'applicant_id'    => $this->siswaB->id,
            'start_date'      => '2026-10-16',
            'end_date'        => '2026-10-16',
            'reason'          => 'Izin keluarga B2',
            'status_approval' => 'Pending',
        ]);

        // Waka sees all 4 pending leaves
        $this->assertSame(4, $this->apiAs($this->waka)->getJson('/api/v1/dashboard')->assertOk()->json('data.pending_leave_count'));

        // BK-A sees only 1 pending leave (Class A)
        $this->assertSame(1, $this->apiAs($this->bkA)->getJson('/api/v1/dashboard')->assertOk()->json('data.pending_leave_count'));

        // BK-B sees only 2 pending leaves (Class B)
        $this->assertSame(2, $this->apiAs($this->bkB)->getJson('/api/v1/dashboard')->assertOk()->json('data.pending_leave_count'));

        // Wali A sees only 1 pending leave (Class A)
        $waliADash = $this->apiAs($this->waliA)->getJson('/api/v1/dashboard')->assertOk()->json('data');
        $this->assertSame(1, $waliADash['pending_leave_count']);
        $this->assertSame(1, $waliADash['homeroom_stats']['leaves']['pending']);

        // Guru Wali D + Mengajar C sees 0 pending leaves (Class D has 0, does NOT count Class C!)
        $waliAAjarCDash = $this->apiAs($this->guruWaliAMengajarC)->getJson('/api/v1/dashboard')->assertOk()->json('data');
        $this->assertSame(0, $waliAAjarCDash['pending_leave_count']);
        $this->assertSame($this->classD->id, $waliAAjarCDash['homeroom_class']['id']);

        // Guru Biasa sees 0 pending leaves and null homeroom_class
        $guruDash = $this->apiAs($this->guruBiasa)->getJson('/api/v1/dashboard')->assertOk()->json('data');
        $this->assertSame(0, $guruDash['pending_leave_count']);
        $this->assertNull($guruDash['homeroom_class']);
    }
}
