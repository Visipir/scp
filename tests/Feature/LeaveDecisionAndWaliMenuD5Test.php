<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\CounselorAssignment;
use App\Models\HomeroomAssignment;
use App\Models\Journal;
use App\Models\LeaveRequest;
use App\Models\Role;
use App\Models\Room;
use App\Models\Schedule;
use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LeaveDecisionAndWaliMenuD5Test extends TestCase
{
    use RefreshDatabase;

    private SchoolClass $classA;
    private User $bkA;
    private User $waliA;
    private User $studentA;
    private User $parentA;

    protected function setUp(): void
    {
        parent::setUp();

        Role::insert([
            ['id' => 1, 'role_name' => 'Waka Kurikulum'],
            ['id' => 2, 'role_name' => 'Guru'],
            ['id' => 3, 'role_name' => 'Guru BK'],
            ['id' => 4, 'role_name' => 'Siswa'],
        ]);

        $this->classA = SchoolClass::create([
            'class_name'    => 'XI RPL 1',
            'academic_year' => '2026/2027',
            'grade_level'   => 11,
            'major'         => 'RPL',
        ]);

        $this->bkA = User::create([
            'fullname' => 'DRA. ENDANG SULISTYOWATI',
            'username' => 'bk_endang',
            'password' => bcrypt('password'),
            'role_id'  => 2,
        ]);
        CounselorAssignment::create([
            'user_id'       => $this->bkA->id,
            'class_id'      => $this->classA->id,
            'academic_year' => '2026/2027',
        ]);

        $this->waliA = User::create([
            'fullname' => 'HARIS PURWANTO, S.Kom',
            'username' => 'wali_haris',
            'password' => bcrypt('password'),
            'role_id'  => 2,
        ]);
        HomeroomAssignment::create([
            'user_id'       => $this->waliA->id,
            'class_id'      => $this->classA->id,
            'academic_year' => '2026/2027',
        ]);

        $this->studentA = User::create([
            'fullname' => 'SISWA RPL SATU',
            'username' => '99001',
            'password' => bcrypt(\Illuminate\Support\Str::random(16)),
            'role_id'  => 4,
            'class_id' => $this->classA->id,
        ]);

        $this->parentA = User::create([
            'fullname' => 'ORTU SISWA RPL SATU',
            'username' => 'ortu_99001',
            'password' => bcrypt(\Illuminate\Support\Str::random(16)),
            'role_id'  => 4,
        ]);
        $this->studentA->update(['parent_id' => $this->parentA->id]);
    }

    public function test_bk_and_wali_both_can_decide_last_decision_is_recorded_and_attendance_syncs(): void
    {
        $room = Room::create(['room_name' => 'LAB RPL 1']);
        $schedule = Schedule::create([
            'class_id'    => $this->classA->id,
            'teacher_id'  => $this->waliA->id,
            'room_id'     => $room->id,
            'subject'     => 'Pemrograman Web',
            'day_of_week' => 'Senin',
            'start_time'  => '07:00:00',
            'end_time'    => '08:30:00',
            'cycle_type'  => 'Putaran 1',
        ]);

        $date = now()->toDateString();
        $journal = Journal::create([
            'schedule_id'       => $schedule->id,
            'teacher_id'        => $this->waliA->id,
            'date'              => $date,
            'topic_description' => 'Autentikasi Laravel',
            'photo_path'        => 'journals/sample.jpg',
        ]);

        $attendance = Attendance::create([
            'journal_id' => $journal->id,
            'student_id' => $this->studentA->id,
            'date'       => $date,
            'status'     => 'Alpa',
        ]);

        $leave = LeaveRequest::create([
            'student_id'      => $this->studentA->id,
            'applicant_id'    => $this->studentA->id,
            'start_date'      => $date,
            'end_date'        => $date,
            'reason'          => 'Sakit demam tinggi',
            'status_approval' => 'Pending',
        ]);

        // Initial state: Menunggu
        $this->assertSame('Menunggu', $leave->decisionLabel());

        // 1. BK approves via Web
        $this->actingAs($this->bkA)
            ->patch(route('leave-requests.update-status', $leave), [
                'status_approval' => 'Approved',
            ])
            ->assertRedirect(route('leave-requests.show', $leave));

        $leave->refresh();
        $attendance->refresh();
        $this->assertSame('Approved', $leave->status_approval);
        $this->assertSame($this->bkA->id, (int) $leave->decided_by_user_id);
        $this->assertSame('BK', $leave->decided_by_role);
        $this->assertNotNull($leave->decided_at);
        $this->assertSame('Disetujui oleh BK: DRA. ENDANG SULISTYOWATI', $leave->decisionLabel());
        $this->assertSame('Sakit', $attendance->status);

        // Visible on BK, Wali, Student, Parent pages
        foreach ([$this->bkA, $this->waliA, $this->studentA, $this->parentA] as $viewer) {
            $this->actingAs($viewer)
                ->get(route('leave-requests.show', $leave))
                ->assertOk()
                ->assertSee('Disetujui oleh BK: DRA. ENDANG SULISTYOWATI');

            $this->actingAs($viewer)
                ->get(route('leave-requests.index'))
                ->assertOk()
                ->assertSee('Disetujui oleh BK: DRA. ENDANG SULISTYOWATI');
        }

        // 2. Wali overrides to Rejected via Web -> last decision recorded & attendance re-synced to Alpa
        $this->actingAs($this->waliA)
            ->patch(route('leave-requests.update-status', $leave), [
                'status_approval' => 'Rejected',
            ])
            ->assertRedirect(route('leave-requests.show', $leave));

        $leave->refresh();
        $attendance->refresh();
        $this->assertSame('Rejected', $leave->status_approval);
        $this->assertSame($this->waliA->id, (int) $leave->decided_by_user_id);
        $this->assertSame('Wali', $leave->decided_by_role);
        $this->assertNotNull($leave->decided_at);
        $this->assertSame('Ditolak oleh Wali: HARIS PURWANTO, S.Kom', $leave->decisionLabel());
        $this->assertSame('Alpa', $attendance->status);

        foreach ([$this->bkA, $this->waliA, $this->studentA, $this->parentA] as $viewer) {
            $this->actingAs($viewer)
                ->get(route('leave-requests.show', $leave))
                ->assertOk()
                ->assertSee('Ditolak oleh Wali: HARIS PURWANTO, S.Kom');
        }

        // 3. Wali approves via API -> last decision recorded & attendance re-synced to Sakit
        Sanctum::actingAs($this->waliA);
        $this->patchJson("/api/v1/leave-requests/{$leave->id}/status", [
            'status_approval' => 'Approved',
        ])
            ->assertOk()
            ->assertJsonPath('data.status_approval', 'Approved')
            ->assertJsonPath('data.decided_by_user_id', $this->waliA->id)
            ->assertJsonPath('data.decided_by_role', 'Wali')
            ->assertJsonPath('data.decision_label', 'Disetujui oleh Wali: HARIS PURWANTO, S.Kom');

        $attendance->refresh();
        $this->assertSame('Sakit', $attendance->status);

        // 4. Reset to Pending clears decision metadata
        Sanctum::actingAs($this->bkA);
        $this->patchJson("/api/v1/leave-requests/{$leave->id}/status", [
            'status_approval' => 'Pending',
        ])
            ->assertOk()
            ->assertJsonPath('data.status_approval', 'Pending')
            ->assertJsonPath('data.decided_by_user_id', null)
            ->assertJsonPath('data.decided_by_role', null)
            ->assertJsonPath('data.decided_at', null)
            ->assertJsonPath('data.decision_label', 'Menunggu');

        $attendance->refresh();
        $this->assertSame('Alpa', $attendance->status);
    }

    public function test_wali_sees_all_leave_statuses_with_filter_and_dashboard_menus(): void
    {
        $date = now()->toDateString();

        $pending = LeaveRequest::create([
            'student_id'      => $this->studentA->id,
            'applicant_id'    => $this->studentA->id,
            'start_date'      => $date,
            'end_date'        => $date,
            'reason'          => 'Izin urusan keluarga',
            'status_approval' => 'Pending',
        ]);

        $approved = LeaveRequest::create([
            'student_id'         => $this->studentA->id,
            'applicant_id'       => $this->studentA->id,
            'start_date'         => $date,
            'end_date'           => $date,
            'reason'             => 'Sakit demam',
            'status_approval'    => 'Approved',
            'verified_by'        => $this->bkA->id,
            'decided_by_user_id' => $this->bkA->id,
            'decided_by_role'    => 'BK',
            'decided_at'         => now(),
        ]);

        $rejected = LeaveRequest::create([
            'student_id'         => $this->studentA->id,
            'applicant_id'       => $this->studentA->id,
            'start_date'         => $date,
            'end_date'           => $date,
            'reason'             => 'Izin liburan',
            'status_approval'    => 'Rejected',
            'verified_by'        => $this->waliA->id,
            'decided_by_user_id' => $this->waliA->id,
            'decided_by_role'    => 'Wali',
            'decided_at'         => now(),
        ]);

        // Unfiltered index shows all 3 statuses to Wali
        $this->actingAs($this->waliA)
            ->get(route('leave-requests.index'))
            ->assertOk()
            ->assertSee('Izin urusan keluarga')
            ->assertSee('Sakit demam')
            ->assertSee('Izin liburan')
            ->assertSee('Menunggu')
            ->assertSee('Disetujui oleh BK: DRA. ENDANG SULISTYOWATI')
            ->assertSee('Ditolak oleh Wali: HARIS PURWANTO, S.Kom');

        // Filter by Approved
        $this->actingAs($this->waliA)
            ->get(route('leave-requests.index', ['status' => 'Approved']))
            ->assertOk()
            ->assertSee('Sakit demam')
            ->assertDontSee('Izin urusan keluarga')
            ->assertDontSee('Izin liburan');

        // Filter by Rejected
        $this->actingAs($this->waliA)
            ->get(route('leave-requests.index', ['status' => 'Rejected']))
            ->assertOk()
            ->assertSee('Izin liburan')
            ->assertDontSee('Izin urusan keluarga')
            ->assertDontSee('Sakit demam');

        // Filter by Pending
        $this->actingAs($this->waliA)
            ->get(route('leave-requests.index', ['status' => 'Pending']))
            ->assertOk()
            ->assertSee('Izin urusan keluarga')
            ->assertDontSee('Sakit demam')
            ->assertDontSee('Izin liburan');

        // Wali sees verification buttons on leave-requests.show
        $this->actingAs($this->waliA)
            ->get(route('leave-requests.show', $pending))
            ->assertOk()
            ->assertSee('Keputusan Verifikasi Kesiswaan')
            ->assertSee('Setujui')
            ->assertSee('Tolak');

        // Wali sees Wali Kelas section on Teacher Dashboard
        $this->actingAs($this->waliA)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Kelas Perwalian: XI RPL 1')
            ->assertSee('Cek List Siswa')
            ->assertSee('Presensi Siswa')
            ->assertSee('Catatan Siswa')
            ->assertSee('Izin Siswa Perwalian');
    }
}
