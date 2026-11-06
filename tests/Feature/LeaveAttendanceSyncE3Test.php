<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\CounselorAssignment;
use App\Models\HomeroomAssignment;
use App\Models\LeaveRequest;
use App\Models\LeaveRequestDecision;
use App\Models\Schedule;
use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Support\ScpTestHelpers;
use Tests\TestCase;

class LeaveAttendanceSyncE3Test extends TestCase
{
    use RefreshDatabase;
    use ScpTestHelpers;

    private SchoolClass $classA;
    private User $waliA;
    private User $bkA;
    private User $guruPengajar;
    private User $siswaIzin;
    private User $siswaSakit;
    private User $siswaHadir;
    private Schedule $schedule;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Storage::fake('public');
        $this->seedRoles();
        $this->makeConfig(['active_cycle' => 'Putaran 2']);

        $this->classA = SchoolClass::create([
            'class_name'    => 'X RPL 1',
            'academic_year' => '2026/2027',
            'grade_level'   => 'X',
            'major'         => 'RPL',
        ]);

        $this->waliA = $this->makeUser(2, ['username' => 'wali_a', 'fullname' => 'HARIS PURWANTO, S.Kom']);
        HomeroomAssignment::create([
            'user_id'       => $this->waliA->id,
            'class_id'      => $this->classA->id,
            'academic_year' => '2026/2027',
        ]);

        $this->bkA = $this->makeUser(3, ['username' => 'bk_a', 'fullname' => 'DRA. ENDANG SULISTYOWATI']);
        CounselorAssignment::create([
            'user_id'       => $this->bkA->id,
            'class_id'      => $this->classA->id,
            'academic_year' => '2026/2027',
        ]);

        $this->guruPengajar = $this->makeUser(2, ['username' => 'guru_ajar', 'fullname' => 'Guru Mapel']);
        $this->schedule = Schedule::create([
            'teacher_id'   => $this->guruPengajar->id,
            'class_id'     => $this->classA->id,
            'subject'      => 'Pemrograman Dasar',
            'day_of_week'  => 'Senin',
            'start_time'   => '07:15:00',
            'end_time'     => '08:45:00',
            'start_period' => 1,
            'end_period'   => 2,
            'cycle_type'   => 'Putaran 2',
        ]);

        $this->siswaIzin = $this->makeUser(4, ['username' => '30001', 'fullname' => 'Siswa Izin', 'class_id' => $this->classA->id, 'attendance_number' => 1]);
        $this->siswaSakit = $this->makeUser(4, ['username' => '30002', 'fullname' => 'Siswa Sakit', 'class_id' => $this->classA->id, 'attendance_number' => 2]);
        $this->siswaHadir = $this->makeUser(4, ['username' => '30003', 'fullname' => 'Siswa Hadir', 'class_id' => $this->classA->id, 'attendance_number' => 3]);
    }

    public function test_approve_then_journal_then_reject_to_alpa_then_approve_to_izin_preserves_row_and_recap_counts(): void
    {
        $date = '2026-10-12';

        $leaveIzin = LeaveRequest::create([
            'student_id'      => $this->siswaIzin->id,
            'applicant_id'    => $this->siswaIzin->id,
            'start_date'      => $date,
            'end_date'        => $date,
            'reason'          => 'Izin urusan keluarga',
            'status_approval' => 'Pending',
        ]);

        $leaveSakit = LeaveRequest::create([
            'student_id'      => $this->siswaSakit->id,
            'applicant_id'    => $this->siswaSakit->id,
            'start_date'      => $date,
            'end_date'        => $date,
            'reason'          => 'Sakit demam berdarah',
            'status_approval' => 'Pending',
        ]);

        // 1. BK approves both leaves BEFORE journal is created
        $this->apiAs($this->bkA)
            ->patchJson("/api/v1/leave-requests/{$leaveIzin->id}/status", ['status_approval' => 'Approved'])
            ->assertOk();
        $this->apiAs($this->bkA)
            ->patchJson("/api/v1/leave-requests/{$leaveSakit->id}/status", ['status_approval' => 'Approved'])
            ->assertOk();

        // 2. Guru creates journal -> siswaIzin gets Izin, siswaSakit gets Sakit, siswaHadir gets Hadir
        $this->apiAs($this->guruPengajar)
            ->post('/api/v1/journals', [
                'schedule_id'       => $this->schedule->id,
                'date'              => $date,
                'topic_description' => 'Algoritma Percabangan',
                'photo'             => UploadedFile::fake()->image('kbm.jpg'),
            ], ['Accept' => 'application/json'])
            ->assertStatus(201);

        $attIzin = Attendance::where('student_id', $this->siswaIzin->id)->firstOrFail();
        $attSakit = Attendance::where('student_id', $this->siswaSakit->id)->firstOrFail();
        $attHadir = Attendance::where('student_id', $this->siswaHadir->id)->firstOrFail();

        $originalIzinRowId = $attIzin->id;
        $originalSakitRowId = $attSakit->id;

        $this->assertSame('Izin', $attIzin->status);
        $this->assertSame($leaveIzin->id, (int) $attIzin->leave_request_id);
        $this->assertSame('Sakit', $attSakit->status);
        $this->assertSame($leaveSakit->id, (int) $attSakit->leave_request_id);
        $this->assertSame('Hadir', $attHadir->status);
        $this->assertNull($attHadir->leave_request_id);

        // Verify recap counts after step 2: 1 Hadir, 1 Izin, 1 Sakit, 0 Alpa, total 3
        $recap1 = $this->apiAs($this->waliA)
            ->getJson("/api/v1/classes/{$this->classA->id}/attendance?date={$date}")
            ->assertOk()
            ->json('data.summary');
        $this->assertSame(1, $recap1['total_hadir']);
        $this->assertSame(1, $recap1['total_izin']);
        $this->assertSame(1, $recap1['total_sakit']);
        $this->assertSame(0, $recap1['total_alpa']);
        $this->assertSame(3, $recap1['total_records']);

        // 3. Wali rejects leaveIzin -> reverts to Alpa (NOT deleted, same row ID)
        $this->apiAs($this->waliA)
            ->patchJson("/api/v1/leave-requests/{$leaveIzin->id}/status", ['status_approval' => 'Rejected'])
            ->assertOk();

        $attIzin->refresh();
        $this->assertSame($originalIzinRowId, $attIzin->id);
        $this->assertSame('Alpa', $attIzin->status);
        $this->assertSame(3, Attendance::count());

        $recap2 = $this->apiAs($this->waliA)
            ->getJson("/api/v1/classes/{$this->classA->id}/attendance?date={$date}")
            ->assertOk()
            ->json('data.summary');
        $this->assertSame(1, $recap2['total_hadir']);
        $this->assertSame(0, $recap2['total_izin']);
        $this->assertSame(1, $recap2['total_sakit']);
        $this->assertSame(1, $recap2['total_alpa']);
        $this->assertSame(3, $recap2['total_records']);

        // 4. BK re-approves leaveIzin -> becomes Izin again (same row ID)
        $this->apiAs($this->bkA)
            ->patchJson("/api/v1/leave-requests/{$leaveIzin->id}/status", ['status_approval' => 'Approved'])
            ->assertOk();

        $attIzin->refresh();
        $this->assertSame($originalIzinRowId, $attIzin->id);
        $this->assertSame('Izin', $attIzin->status);
        $this->assertSame($originalSakitRowId, $attSakit->fresh()->id);
        $this->assertSame(3, Attendance::count());

        $recap3 = $this->apiAs($this->waliA)
            ->getJson("/api/v1/classes/{$this->classA->id}/attendance?date={$date}")
            ->assertOk()
            ->json('data.summary');
        $this->assertSame(1, $recap3['total_hadir']);
        $this->assertSame(1, $recap3['total_izin']);
        $this->assertSame(1, $recap3['total_sakit']);
        $this->assertSame(0, $recap3['total_alpa']);
        $this->assertSame(3, $recap3['total_records']);

        // 5. Verify Wali vs BK conflict: last decision wins on leave_requests AND all 3 decisions logged in leave_request_decisions
        $leaveIzin->refresh();
        $this->assertSame('Approved', $leaveIzin->status_approval);
        $this->assertSame($this->bkA->id, (int) $leaveIzin->decided_by_user_id);
        $this->assertSame('BK', $leaveIzin->decided_by_role);

        $decisions = LeaveRequestDecision::where('leave_request_id', $leaveIzin->id)->orderBy('id')->get();
        $this->assertCount(3, $decisions);
        $this->assertSame(['Approved', 'Rejected', 'Approved'], $decisions->pluck('status')->all());
        $this->assertSame(['BK', 'Wali', 'BK'], $decisions->pluck('role')->all());
    }

    public function test_leave_approval_does_not_overwrite_hadir_filled_by_teacher(): void
    {
        $date = '2026-10-13';

        // Teacher creates journal FIRST: siswaHadir is Hadir, siswaIzin is manually marked Sakit by teacher
        $this->apiAs($this->guruPengajar)
            ->post('/api/v1/journals', [
                'schedule_id'       => $this->schedule->id,
                'date'              => $date,
                'topic_description' => 'Perulangan For/While',
                'photo'             => UploadedFile::fake()->image('kbm2.jpg'),
                'absent_students'   => [
                    [
                        'student_id' => $this->siswaIzin->id,
                        'status'     => 'Sakit',
                        'notes'      => 'Sakit di UKS menurut guru',
                    ],
                ],
            ], ['Accept' => 'application/json'])
            ->assertStatus(201);

        // Now siswaHadir submits a leave request for the same date and BK approves it
        $leaveForHadirStudent = LeaveRequest::create([
            'student_id'      => $this->siswaHadir->id,
            'applicant_id'    => $this->siswaHadir->id,
            'start_date'      => $date,
            'end_date'        => $date,
            'reason'          => 'Izin acara keluarga',
            'status_approval' => 'Pending',
        ]);

        $this->apiAs($this->bkA)
            ->patchJson("/api/v1/leave-requests/{$leaveForHadirStudent->id}/status", ['status_approval' => 'Approved'])
            ->assertOk();

        // Teacher's Hadir MUST NOT be overwritten
        $attHadir = Attendance::where('student_id', $this->siswaHadir->id)->whereDate('date', $date)->firstOrFail();
        $this->assertSame('Hadir', $attHadir->status);
        $this->assertNull($attHadir->leave_request_id);

        // And rejecting a leave for siswaIzin must NOT overwrite teacher's manual 'Sakit di UKS menurut guru'
        $leaveForManualStudent = LeaveRequest::create([
            'student_id'      => $this->siswaIzin->id,
            'applicant_id'    => $this->siswaIzin->id,
            'start_date'      => $date,
            'end_date'        => $date,
            'reason'          => 'Sakit',
            'status_approval' => 'Pending',
        ]);

        $this->apiAs($this->bkA)
            ->patchJson("/api/v1/leave-requests/{$leaveForManualStudent->id}/status", ['status_approval' => 'Rejected'])
            ->assertOk();

        $attManual = Attendance::where('student_id', $this->siswaIzin->id)->whereDate('date', $date)->firstOrFail();
        $this->assertSame('Sakit', $attManual->status);
        $this->assertSame('Sakit di UKS menurut guru', $attManual->notes);
    }
}
