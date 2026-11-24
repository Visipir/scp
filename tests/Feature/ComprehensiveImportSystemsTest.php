<?php

namespace Tests\Feature;

use App\Models\AcademicCalendar;
use App\Models\Room;
use App\Models\RoomSchedule;
use App\Models\Schedule;
use App\Models\SchoolClass;
use App\Models\SystemConfiguration;
use App\Models\User;
use App\Services\GeminiScheduleParserService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ComprehensiveImportSystemsTest extends TestCase
{
    use RefreshDatabase, \Tests\Support\ScpTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();

        SystemConfiguration::firstOrCreate(
            ['id' => 1],
            [
                'active_cycle'        => 'Putaran 2',
                'is_schedule_locked'  => false,
                'gemini_api_key'      => 'test_gemini_key',
            ]
        );
    }

    public function test_academic_calendar_import_comma_and_semicolon_csv(): void
    {
        $waka = $this->makeUser(1, [
            'username' => 'waka_cal_' . uniqid(),
            'fullname' => 'Waka Kalender',
        ]);

        // 1. Test standard comma CSV
        $commaCsv = "Title,Start Date,End Date,Category,Is KBM Off,Description\n" .
                    "Ujian Tengah Semester,2026-10-10,2026-10-15,Ujian,0,UTS Semester Ganjil\n" .
                    "Libur Nasional,2026-12-25,2026-12-25,Libur,1,Hari Raya Natal\n";

        $fileComma = UploadedFile::fake()->createWithContent('kalender_comma.csv', $commaCsv);

        $responseComma = $this->actingAs($waka)->post(route('academic-calendar.import'), [
            'file' => $fileComma,
        ]);

        $responseComma->assertRedirect();
        $responseComma->assertSessionHas('success');

        $uts = AcademicCalendar::where('title', 'Ujian Tengah Semester')->first();
        $this->assertNotNull($uts);
        $this->assertEquals('2026-10-10', $uts->start_date->format('Y-m-d'));
        $this->assertFalse((bool)$uts->is_kbm_off);

        $natal = AcademicCalendar::where('title', 'Libur Nasional')->first();
        $this->assertNotNull($natal);
        $this->assertEquals('2026-12-25', $natal->start_date->format('Y-m-d'));
        $this->assertTrue((bool)$natal->is_kbm_off);

        // 2. Test semicolon CSV with DD/MM/YYYY date formats
        $semiCsv = "Title;Start Date;End Date;Category;Is KBM Off;Description\n" .
                   "Class Meeting;15/12/2026;18/12/2026;Kegiatan Siswa;1;Lomba antarkelas pasca PAS\n";

        $fileSemi = UploadedFile::fake()->createWithContent('kalender_semi.csv', $semiCsv);

        $responseSemi = $this->actingAs($waka)->post(route('academic-calendar.import'), [
            'file' => $fileSemi,
        ]);

        $responseSemi->assertRedirect();
        $responseSemi->assertSessionHas('success');

        $meeting = AcademicCalendar::where('title', 'Class Meeting')->first();
        $this->assertNotNull($meeting);
        $this->assertEquals('2026-12-15', $meeting->start_date->format('Y-m-d'));
        $this->assertEquals('2026-12-18', $meeting->end_date->format('Y-m-d'));
        $this->assertEquals('agenda_sekolah', $meeting->category);
    }

    public function test_teacher_email_import_handles_semicolon_and_comma(): void
    {
        $waka = $this->makeUser(1, [
            'username' => 'waka_email_' . uniqid(),
            'fullname' => 'Waka Email',
        ]);

        $teacher = $this->makeUser(2, [
            'username' => 'guru_budi_' . uniqid(),
            'fullname' => 'Budi Santoso, S.Pd.',
            'nip'      => '198001012005011001',
            'email'    => null,
        ]);

        $csvContent = "NIP;NAMA GURU;EMAIL\n" .
                      "198001012005011001;Budi Santoso, S.Pd.;budi.santoso@sekolah.sch.id\n";

        $file = UploadedFile::fake()->createWithContent('guru_email.csv', $csvContent);

        $response = $this->actingAs($waka)->post(route('waka.import-email'), [
            'email_file' => $file,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $teacher->refresh();
        $this->assertEquals('budi.santoso@sekolah.sch.id', $teacher->email);
    }

    public function test_schedule_import_pdf_with_normalization_and_fallback(): void
    {
        $waka = $this->makeUser(1, [
            'username' => 'waka_sched_' . uniqid(),
            'fullname' => 'Waka Jadwal',
        ]);

        $class = SchoolClass::create([
            'class_name'    => 'X PPLG 1',
            'academic_year' => '2026/2027',
        ]);

        // Mock GeminiScheduleParserService to return entries with uppercase SENIN and non-normalized values
        $this->mock(GeminiScheduleParserService::class, function ($mock) {
            $mock->shouldReceive('parsePdf')->andReturn([
                [
                    'class_name'   => 'X PPLG 1',
                    'subject'      => 'Pemrograman Web',
                    'day_of_week'  => 'SENIN', // Should normalize to 'Senin'
                    'room_name'    => 'LAB 1',  // Should normalize to 'Lab 1'
                    'teacher_name' => 'Pak Joko',
                    'teacher_code' => '50',
                    'start_time'   => '07:15',
                    'end_time'     => '08:45',
                    'period'       => 1,
                ]
            ]);
        });

        $pdf = UploadedFile::fake()->create('jadwal_test.pdf', 100, 'application/pdf');

        $response = $this->actingAs($waka)->post(route('schedules.import'), [
            'schedule_pdf' => $pdf,
            'cycle_type'   => 'Putaran 2',
        ]);

        $response->assertRedirect(route('schedules.index', ['cycle' => 'Putaran 2']));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('schedules', [
            'class_id'    => $class->id,
            'day_of_week' => 'Senin',
            'subject'     => 'Pemrograman Web',
            'cycle_type'  => 'Putaran 2',
        ]);

        // Verify Room was registered in master room list
        $this->assertDatabaseHas('rooms', [
            'room_name' => 'Lab 1',
        ]);
    }

    public function test_room_schedule_import_normalizes_day_and_cycle(): void
    {
        $waka = $this->makeUser(1, [
            'username' => 'waka_room_' . uniqid(),
            'fullname' => 'Waka Ruang',
        ]);

        $class = SchoolClass::create([
            'class_name'    => 'XI TKJ 1',
            'academic_year' => '2026/2027',
        ]);

        // Mock AiDocumentParserService to return uppercase day and non-normalized cycle
        $this->mock(\App\Services\AiDocumentParserService::class, function ($mock) {
            $mock->shouldReceive('parsePdf')->andReturn([
                [
                    'class_name'   => 'XI TKJ 1',
                    'room_name'    => 'Ruang Teori 5',
                    'day_of_week'  => 'SELASA', // Should normalize to 'Selasa'
                    'cycle_type'   => 'putaran 2', // Should normalize to 'Putaran 2'
                    'start_period' => 1,
                    'end_period'   => 2,
                ]
            ]);
        });

        $pdf = UploadedFile::fake()->create('ruangan_test.pdf', 100, 'application/pdf');

        $response = $this->actingAs($waka)->post(route('room-schedules.import'), [
            'schedule_pdf' => $pdf,
            'cycle_type'   => 'Putaran 2',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('room_schedules', [
            'class_id'    => $class->id,
            'day_of_week' => 'Selasa',
            'cycle_type'  => 'Putaran 2',
            'period'      => 1,
        ]);
        $this->assertDatabaseHas('room_schedules', [
            'class_id'    => $class->id,
            'day_of_week' => 'Selasa',
            'cycle_type'  => 'Putaran 2',
            'period'      => 2,
        ]);

        // Room should be normalized to 'Ruang 5'
        $this->assertDatabaseHas('rooms', [
            'room_name' => 'Ruang 5',
        ]);
    }
}
