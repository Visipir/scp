<?php

namespace Tests\Feature;

use App\Models\CounselorAssignment;
use App\Models\HomeroomAssignment;
use App\Models\Role;
use App\Models\Room;
use App\Models\Schedule;
use App\Models\SchoolClass;
use App\Models\SystemConfiguration;
use App\Models\User;
use App\Services\HomeroomImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class ProtectedAccountsScopeAfterImportF3Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');

        Role::firstOrCreate(['id' => 1], ['role_name' => 'Waka Kurikulum']);
        Role::firstOrCreate(['id' => 2], ['role_name' => 'Guru']);
        Role::firstOrCreate(['id' => 3], ['role_name' => 'Tim Kesiswaan']);
        Role::firstOrCreate(['id' => 4], ['role_name' => 'Siswa']);

        SystemConfiguration::create([
            'active_cycle'       => 'Putaran 2',
            'is_schedule_locked' => false,
        ]);
    }

    public function test_f3_all_five_protected_accounts_retain_exact_post_import_scopes(): void
    {
        // 1. Seed the 9 classes of ryawidyaningtyas + 1 non-Rya class (X AK 1)
        $ryaClassesData = [
            13 => ['name' => 'X LPS 2',   'grade' => 'X',   'major' => 'LPS'],
            1  => ['name' => 'X RPL 1',   'grade' => 'X',   'major' => 'RPL'],
            4  => ['name' => 'X RPL 2',   'grade' => 'X',   'major' => 'RPL'],
            2  => ['name' => 'XI RPL 1',  'grade' => 'XI',  'major' => 'RPL'],
            19 => ['name' => 'XI RPL 2',  'grade' => 'XI',  'major' => 'RPL'],
            39 => ['name' => 'XII AK 1',  'grade' => 'XII', 'major' => 'AK'],
            40 => ['name' => 'XII AK 2',  'grade' => 'XII', 'major' => 'AK'],
            3  => ['name' => 'XII RPL 1', 'grade' => 'XII', 'major' => 'RPL'],
            34 => ['name' => 'XII RPL 2', 'grade' => 'XII', 'major' => 'RPL'],
        ];

        foreach ($ryaClassesData as $id => $meta) {
            SchoolClass::create([
                'id'            => $id,
                'class_name'    => $meta['name'],
                'academic_year' => '2026/2027',
                'grade_level'   => $meta['grade'],
                'major'         => $meta['major'],
            ]);
        }

        $otherClass = SchoolClass::create([
            'id'            => 9,
            'class_name'    => 'X AK 1',
            'academic_year' => '2026/2027',
            'grade_level'   => 'X',
            'major'         => 'AK',
        ]);

        // 2. Create the 5 protected accounts + Wali Kelas XII RPL 1 + another BK (CANDRA RAHADIAN)
        $waka = User::create([
            'id'                   => 1,
            'username'             => 'waka_kurikulum',
            'fullname'             => 'Pak Budi Kurikulum, M.Pd',
            'role_id'              => 1,
            'password'             => Hash::make(Str::random(16), ['rounds' => 4]),
            'must_change_password' => false,
        ]);

        $timKesiswaan = User::create([
            'id'                   => 4,
            'username'             => 'tim_kesiswaan',
            'fullname'             => 'Pak Rudi Kesiswaan',
            'role_id'              => 3,
            'class_id'             => null,
            'password'             => Hash::make(Str::random(16), ['rounds' => 4]),
            'must_change_password' => false,
        ]);

        $siswa17766 = User::create([
            'id'                   => 1164,
            'username'             => '17766',
            'fullname'             => '17766',
            'role_id'              => 4,
            'class_id'             => 3, // XII RPL 1
            'password'             => Hash::make(Str::random(16), ['rounds' => 4]),
            'must_change_password' => false,
        ]);

        $rya = User::create([
            'id'                   => 1721,
            'username'             => 'ryawidyaningtyas',
            'fullname'             => 'RYAWIDYANINGTYAS, S.Pd',
            'role_id'              => 2,
            'password'             => Hash::make(Str::random(16), ['rounds' => 4]),
            'must_change_password' => false,
        ]);

        $haris = User::create([
            'id'                   => 1723,
            'username'             => 'haris-budiawan',
            'fullname'             => 'HARIS BUDIAWAN, S.Pd',
            'role_id'              => 2,
            'password'             => Hash::make(Str::random(16), ['rounds' => 4]),
            'must_change_password' => false,
        ]);

        $waliXiiRpl1 = User::create([
            'id'                   => 1711,
            'username'             => 'diah-utaminingsih',
            'fullname'             => 'DIAH UTAMININGSIH, S.T',
            'role_id'              => 2,
            'password'             => Hash::make(Str::random(16), ['rounds' => 4]),
            'must_change_password' => false,
        ]);

        $otherBk = User::create([
            'id'                   => 1759,
            'username'             => 'candra-rahadian',
            'fullname'             => 'CANDRA RAHADIAN, S.Pd',
            'role_id'              => 2,
            'password'             => Hash::make(Str::random(16), ['rounds' => 4]),
            'must_change_password' => false,
        ]);

        // 3. Assign Rya's 9 classes in counselor_assignments and Candra's class
        foreach (array_keys($ryaClassesData) as $classId) {
            CounselorAssignment::create([
                'user_id'       => $rya->id,
                'class_id'      => $classId,
                'academic_year' => '2026/2027',
            ]);
        }
        CounselorAssignment::create([
            'user_id'       => $otherBk->id,
            'class_id'      => $otherClass->id,
            'academic_year' => '2026/2027',
        ]);

        HomeroomAssignment::create([
            'user_id'       => $waliXiiRpl1->id,
            'class_id'      => 3, // XII RPL 1
            'academic_year' => '2026/2027',
        ]);

        // Verify Rya's 9 classes in WALI KELAS 2026.2027.xlsx match exactly
        $excelPath = storage_path('app/import-samples/WALI KELAS 2026.2027.xlsx');
        if (file_exists($excelPath)) {
            $preview = app(HomeroomImportService::class)->generatePreview(new UploadedFile(
                $excelPath,
                'WALI KELAS 2026.2027.xlsx',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                null,
                true
            ));
            $excelRyaClasses = [];
            $rplHeldByOtherBk = [];
            foreach ($preview['rows'] as $row) {
                $cName = $row['class']['class_name'] ?? $row['class_raw'];
                $bkRaw = strtoupper($row['bk_raw']);
                if (str_contains($bkRaw, 'RYAWIDYANINGTYAS')) {
                    $excelRyaClasses[] = $cName;
                } elseif (str_contains($cName, 'RPL')) {
                    $rplHeldByOtherBk[] = "{$cName} -> {$bkRaw}";
                }
            }
            $this->assertEmpty($rplHeldByOtherBk, 'No RPL class may be held by another BK');
            $this->assertCount(9, $excelRyaClasses);
            $this->assertEqualsCanonicalizing(array_column($ryaClassesData, 'name'), $excelRyaClasses);
        }

        // Verify Rya's authorizedClassIds include all 6 RPL classes + 3 other classes = 9 classes
        $ryaAuthorized = $rya->authorizedClassIds();
        $this->assertCount(9, $ryaAuthorized);
        $this->assertEqualsCanonicalizing(array_keys($ryaClassesData), $ryaAuthorized);
        foreach ([1, 4, 2, 19, 3, 34] as $rplClassId) {
            $this->assertTrue($rya->canAccessClass($rplClassId));
            $this->assertTrue($rya->canSuperviseClass($rplClassId));
        }
        $this->assertFalse($rya->canAccessClass($otherClass->id));

        // 4. Verify tim_kesiswaan has 0 counselor_assignments rows (preserving 1 BK per class)
        //    yet supervisedClassIds() === null (unrestricted access to all classes for leave requests & recaps)
        $this->assertSame(0, CounselorAssignment::where('user_id', $timKesiswaan->id)->count());
        $this->assertNull($timKesiswaan->supervisedClassIds());
        $this->assertNull($timKesiswaan->authorizedClassIds());
        $this->assertTrue($timKesiswaan->canAccessClass(3));
        $this->assertTrue($timKesiswaan->canAccessClass($otherClass->id));

        // 5. Seed Haris Budiawan's Putaran 2 schedules (X RPL 1 and XII RPL 2)
        $room = Room::create(['room_name' => 'Lab 16']);
        $schedRpl1 = Schedule::create([
            'class_id'     => 1, // X RPL 1
            'teacher_id'   => $haris->id,
            'room_id'      => $room->id,
            'subject'      => 'jaringan Komputer',
            'day_of_week'  => 'Selasa',
            'start_period' => 7,
            'end_period'   => 8,
            'start_time'   => '12:45:00',
            'end_time'     => '14:00:00',
            'cycle_type'   => 'Putaran 2',
        ]);
        Schedule::create([
            'class_id'     => 34, // XII RPL 2
            'teacher_id'   => $haris->id,
            'room_id'      => $room->id,
            'subject'      => 'Kreatifitas, Inovasi dan kewirausahaan',
            'day_of_week'  => 'Kamis',
            'start_period' => 8,
            'end_period'   => 9,
            'start_time'   => '13:10:00',
            'end_time'     => '14:30:00',
            'cycle_type'   => 'Putaran 2',
        ]);

        $this->assertSame(2, (int) $haris->role_id);
        $this->assertEqualsCanonicalizing([1, 34], $haris->taughtClassIds());
        $this->assertEqualsCanonicalizing([1, 34], $haris->authorizedClassIds());

        // Haris can open journal preparation and attendance recap for X RPL 1 & XII RPL 2, but gets 403 on XII RPL 1 (id=3)
        $this->actingAs($haris)
            ->get(route('journals.create', ['schedule_id' => $schedRpl1->id, 'date' => '2026-11-10']))
            ->assertOk();
        $this->actingAs($haris)
            ->get(route('classes.show', ['class' => 1]))
            ->assertOk();
        $this->actingAs($haris)
            ->get(route('classes.show', ['class' => 3]))
            ->assertStatus(403);

        // 6. Verify 17766 (XII RPL 1) can submit leave request, visible to ryawidyaningtyas (BK) and Wali Kelas XII RPL 1,
        //    and decision is recorded
        \Laravel\Sanctum\Sanctum::actingAs($siswa17766);
        $submitResp = $this->postJson('/api/v1/leave-requests', [
            'student_id' => $siswa17766->id,
            'start_date' => '2026-11-10',
            'end_date'   => '2026-11-10',
            'reason'     => 'Izin keperluan keluarga penguji F3',
            'document'   => UploadedFile::fake()->create('surat.pdf', 100, 'application/pdf'),
        ]);
        $submitResp->assertStatus(201);
        $leaveId = (int) $submitResp->json('data.id');
        $leave = \App\Models\LeaveRequest::findOrFail($leaveId);

        // Visible to ryawidyaningtyas (BK), Wali Kelas XII RPL 1, and tim_kesiswaan, but NOT to otherBk
        $this->actingAs($rya)->get(route('leave-requests.index'))->assertOk()->assertSee('17766');
        $this->actingAs($waliXiiRpl1)->get(route('leave-requests.index'))->assertOk()->assertSee('17766');
        $this->actingAs($timKesiswaan)->get(route('leave-requests.index'))->assertOk()->assertSee('17766');
        $this->actingAs($otherBk)->get(route('leave-requests.show', $leave))->assertStatus(403);

        // Ryawidyaningtyas approves the leave request -> decision recorded
        $this->actingAs($rya)
            ->patch(route('leave-requests.update-status', $leave), ['status_approval' => 'Approved'])
            ->assertRedirect(route('leave-requests.show', $leave));

        $this->assertDatabaseHas('leave_requests', [
            'id'                 => $leaveId,
            'status_approval'    => 'Approved',
            'decided_by_user_id' => $rya->id,
            'decided_by_role'    => 'BK',
        ]);
        $this->assertDatabaseHas('leave_request_decisions', [
            'leave_request_id' => $leaveId,
            'user_id'          => $rya->id,
            'role'             => 'BK',
            'status'           => 'Approved',
        ]);

        // 7. Verify waka_kurikulum has full access
        $this->assertNull($waka->supervisedClassIds());
        $this->assertNull($waka->authorizedClassIds());
        $this->actingAs($waka)->get(route('classes.show', ['class' => $otherClass->id]))->assertOk();
    }
}
