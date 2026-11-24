<?php

namespace Tests\Feature;

use App\Models\AcademicCalendar;
use App\Models\Attendance;
use App\Models\Journal;
use App\Models\Room;
use App\Models\RoomSchedule;
use App\Models\Schedule;
use App\Models\SchoolClass;
use App\Models\SystemConfiguration;
use App\Models\User;
use App\Services\FetScheduleParserService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\Support\ScpTestHelpers;
use Tests\TestCase;

class UkkInternalRevisionsTest extends TestCase
{
    use RefreshDatabase, ScpTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
        $this->makeConfig([
            'active_cycle' => 'Putaran 1',
            'is_schedule_locked' => false,
            'is_journal_time_restricted' => false,
        ]);
    }

    public function test_attendance_recap_page_is_accessible_by_waka_and_superadmin(): void
    {
        $waka = $this->makeUser(1);
        $superadmin = $this->makeUser(5);

        $responseWaka = $this->actingAs($waka)->get(route('attendance.recap'));
        $responseWaka->assertStatus(200);
        $responseWaka->assertSee('Rekapitulasi Presensi Bulanan');

        $responseSuperadmin = $this->actingAs($superadmin)->get(route('attendance.recap'));
        $responseSuperadmin->assertStatus(200);
    }

    public function test_room_management_crud(): void
    {
        $waka = $this->makeUser(1);

        // 1. Index
        $response = $this->actingAs($waka)->get(route('rooms.index'));
        $response->assertStatus(200);
        $response->assertSee('Daftar Ruangan & Laboratorium');

        // 2. Create
        $uniqueRoomName = 'Lab Robotik ' . uniqid();
        $createRes = $this->actingAs($waka)->post(route('rooms.store'), [
            'room_name' => $uniqueRoomName,
        ]);
        $createRes->assertRedirect();
        $this->assertDatabaseHas('rooms', ['room_name' => $uniqueRoomName]);

        $room = Room::where('room_name', $uniqueRoomName)->first();

        // 3. Update
        $updatedRoomName = $uniqueRoomName . ' Rev';
        $updateRes = $this->actingAs($waka)->put(route('rooms.update', $room->id), [
            'room_name' => $updatedRoomName,
        ]);
        $updateRes->assertRedirect();
        $this->assertDatabaseHas('rooms', ['id' => $room->id, 'room_name' => $updatedRoomName]);

        // 4. Delete
        $deleteRes = $this->actingAs($waka)->delete(route('rooms.destroy', $room->id));
        $deleteRes->assertRedirect();
        $this->assertDatabaseMissing('rooms', ['id' => $room->id]);
    }

    public function test_room_schedule_update_and_conflict_check(): void
    {
        $waka = $this->makeUser(1);
        $classA = SchoolClass::firstOrCreate(['class_name' => 'X TEST A'], ['academic_year' => '2026/2027']);
        $classB = SchoolClass::firstOrCreate(['class_name' => 'X TEST B'], ['academic_year' => '2026/2027']);
        
        $room1 = Room::firstOrCreate(['room_name' => 'Ruang Test 1']);
        $room2 = Room::firstOrCreate(['room_name' => 'Ruang Test 2']);

        $schedA = RoomSchedule::updateOrCreate(
            [
                'class_id'   => $classA->id,
                'day_of_week'=> 'Senin',
                'period'     => 1,
                'cycle_type' => 'Putaran 1',
            ],
            [
                'room_id'    => $room1->id,
                'start_time' => '07:15:00',
                'end_time'   => '08:00:00',
            ]
        );

        // Occupy room 2 with Class B at period 1
        $schedB = RoomSchedule::updateOrCreate(
            [
                'class_id'   => $classB->id,
                'day_of_week'=> 'Senin',
                'period'     => 1,
                'cycle_type' => 'Putaran 1',
            ],
            [
                'room_id'    => $room2->id,
                'start_time' => '07:15:00',
                'end_time'   => '08:00:00',
            ]
        );

        // Attempt to move schedA to room 2 should trigger conflict warning/error
        $conflictRes = $this->actingAs($waka)->put(route('room-schedules.update', $schedA->id), [
            'room_id' => $room2->id,
        ]);
        $conflictRes->assertSessionHas('error');

        // Move to available room (e.g. create room 3)
        $room3 = Room::firstOrCreate(['room_name' => 'Ruang Test 3']);
        $successRes = $this->actingAs($waka)->put(route('room-schedules.update', $schedA->id), [
            'room_id' => $room3->id,
        ]);
        $successRes->assertSessionHas('success');
        $this->assertEquals($room3->id, $schedA->fresh()->room_id);
    }

    public function test_academic_calendar_access_for_all_roles_and_direct_update(): void
    {
        $teacher = $this->makeUser(2);
        $student = $this->makeUser(4);
        $waka = $this->makeUser(1);

        // Guru can view calendar
        $resTeacher = $this->actingAs($teacher)->get(route('academic-calendar.index'));
        $resTeacher->assertStatus(200);

        // Siswa can view calendar
        $resStudent = $this->actingAs($student)->get(route('academic-calendar.index'));
        $resStudent->assertStatus(200);

        // Create an agenda item to test direct update
        $agenda = AcademicCalendar::create([
            'title'       => 'Agenda Awal',
            'start_date'  => '2026-12-01',
            'end_date'    => '2026-12-02',
            'category'    => 'agenda_sekolah',
            'is_kbm_off'  => false,
            'description' => 'Awal Keterangan',
        ]);

        // Direct update via PUT
        $updateRes = $this->actingAs($waka)->put(route('academic-calendar.update', $agenda->id), [
            'title'       => 'Agenda Diperbarui Langsung',
            'start_date'  => '2026-12-01',
            'end_date'    => '2026-12-03',
            'category'    => 'libur_nasional',
            'is_kbm_off'  => '1',
            'description' => 'Langsung diedit dari modal',
        ]);

        $updateRes->assertSessionHas('success');
        $this->assertDatabaseHas('academic_calendars', [
            'id'         => $agenda->id,
            'title'      => 'Agenda Diperbarui Langsung',
            'category'   => 'libur_nasional',
            'is_kbm_off' => 1,
        ]);
    }

    public function test_class_promotion_simulation_and_execution(): void
    {
        $waka = $this->makeUser(1);

        $classX = SchoolClass::firstOrCreate(['class_name' => 'X PROMO 1'], ['academic_year' => '2026/2027']);
        $classXI = SchoolClass::firstOrCreate(['class_name' => 'XI PROMO 1'], ['academic_year' => '2026/2027']);

        $student1 = $this->makeUser(4, [
            'fullname' => 'Siswa Promo 1',
            'class_id' => $classX->id,
        ]);

        $student2 = $this->makeUser(4, [
            'fullname' => 'Siswa Promo 2',
            'class_id' => $classX->id,
        ]);

        // 1. Access promotion index
        $indexRes = $this->actingAs($waka)->get(route('classes.promotion', [
            'source_class_id' => $classX->id,
            'target_class_id' => $classXI->id,
            'action'          => 'promote',
        ]));
        $indexRes->assertStatus(200);
        $indexRes->assertSee('Simulasi Kenaikan Kelas');
        $indexRes->assertSee('Siswa Promo 1');

        // 2. Simulate via POST
        $simRes = $this->actingAs($waka)->post(route('classes.promotion.simulate'), [
            'source_class_id' => $classX->id,
            'target_class_id' => $classXI->id,
            'action'          => 'promote',
            'student_ids'     => [$student1->id, $student2->id],
        ]);
        $simRes->assertRedirect();
        $simRes->assertSessionHas('success');

        // 3. Execute Promotion
        $execRes = $this->actingAs($waka)->post(route('classes.promotion.execute'), [
            'source_class_id' => $classX->id,
            'target_class_id' => $classXI->id,
            'action'          => 'promote',
            'student_ids'     => [$student1->id, $student2->id],
        ]);
        $execRes->assertRedirect(route('classes.promotion'));
        $execRes->assertSessionHas('success');

        $this->assertEquals($classXI->id, $student1->fresh()->class_id);
        $this->assertEquals($classXI->id, $student2->fresh()->class_id);

        // 4. Test Graduation
        $gradRes = $this->actingAs($waka)->post(route('classes.promotion.execute'), [
            'source_class_id' => $classXI->id,
            'action'          => 'graduate',
            'student_ids'     => [$student1->id],
        ]);
        $gradRes->assertSessionHas('success');
        $this->assertNull($student1->fresh()->class_id);
    }

    public function test_bulk_homeroom_and_counselor_assignment(): void
    {
        $waka = $this->makeUser(1);
        $class = SchoolClass::firstOrCreate(['class_name' => 'XII ASSIGN 1'], ['academic_year' => '2026/2027']);
        $teacherWali = $this->makeUser(2, ['fullname' => 'Guru Wali']);
        $teacherBK = $this->makeUser(3, ['fullname' => 'Guru BK']);

        $assignRes = $this->actingAs($waka)->post(route('classes.promotion.assignments'), [
            'assignments' => [
                $class->id => [
                    'homeroom_user_id'   => $teacherWali->id,
                    'counselor_user_ids' => [$teacherBK->id],
                ],
            ],
            'mode' => 'commit',
        ]);

        $assignRes->assertSessionHas('success');
        $this->assertEquals($teacherWali->id, $class->fresh()->homeroomTeacher?->id);
        $this->assertTrue($class->fresh()->counselors->contains('id', $teacherBK->id));
    }

    public function test_fet_schedule_class_pdf_import(): void
    {
        $samplePath = base_path('storage/app/import-samples/JADWAL KELAS PUT 1.pdf');
        if (!file_exists($samplePath)) {
            $this->markTestSkipped('Sample PDF JADWAL KELAS PUT 1.pdf not found.');
        }

        // Seed teachers from code map so slots are mapped
        foreach (\App\Http\Controllers\ScheduleImportController::TEACHER_CODE_MAP as $code => $name) {
            $this->makeUser(2, ['fullname' => $name]);
        }

        $service = app(FetScheduleParserService::class);
        $slots = $service->parsePdf($samplePath);

        $this->assertNotEmpty($slots);
        $this->assertGreaterThan(500, count($slots));

        $file = new UploadedFile($samplePath, 'JADWAL KELAS PUT 1.pdf', 'application/pdf', null, true);
        $preview = $service->generatePreview($file, 'Putaran 1');

        $this->assertGreaterThan(500, $preview['summary']['valid_count']);
        $this->assertArrayHasKey('token', $preview);
    }

    public function test_attendance_recap_renders_class_breakdown_and_superadmin_curriculum_access(): void
    {
        $superadmin = $this->makeUser(5);
        $teacher = $this->makeUser(2, ['fullname' => 'Guru Uji Recap']);
        $class = SchoolClass::firstOrCreate(['class_name' => 'XI REKAP TEST'], ['academic_year' => '2026/2027']);
        $student = $this->makeUser(4, ['fullname' => 'Bima Absen Hari Ini', 'class_id' => $class->id]);

        $schedule = Schedule::create([
            'teacher_id' => $teacher->id,
            'class_id' => $class->id,
            'subject' => 'Informatika',
            'day_of_week' => 'Senin',
            'start_period' => 1,
            'end_period' => 2,
            'start_time' => '07:00',
            'end_time' => '08:20',
            'cycle_type' => 'Putaran 1',
        ]);

        $journal = Journal::create([
            'schedule_id' => $schedule->id,
            'date' => today(),
            'topic_description' => 'Testing Recap Breakdown',
        ]);

        Attendance::create([
            'journal_id' => $journal->id,
            'student_id' => $student->id,
            'date' => today(),
            'status' => 'Sakit',
            'notes' => 'Demam',
        ]);

        // Recap renders both class breakdown and today's absentees
        $recap = $this->actingAs($superadmin)->get(route('attendance.recap', ['month' => today()->format('Y-m')]));
        $recap->assertOk();
        $recap->assertSee('XI REKAP TEST');
        $recap->assertSee('Bima Absen Hari Ini');

        // Superadmin has dashboard summary + full curriculum/swap/users access
        $this->actingAs($superadmin)->get('/dashboard')->assertOk()->assertSee('Statistik Presensi Hari Ini')->assertSee('Menu Utama')->assertSee('Manajemen Pengguna');
        $this->actingAs($superadmin)->get('/schedules')->assertOk()->assertSee('Laporan Tukar Jadwal');
        $this->actingAs($superadmin)->get('/schedule-swaps')->assertOk();
        $this->actingAs($superadmin)->get('/schedule-swaps/print')->assertOk()->assertSee('SMK NEGERI 1 BANTUL');
        $this->actingAs($superadmin)->get('/users')->assertOk()->assertSee('Reset PW Massal Guru');
    }
}
