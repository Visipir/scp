<?php

namespace Tests\Feature;

use App\Models\Journal;
use App\Models\Room;
use App\Models\Schedule;
use App\Models\SchoolClass;
use App\Models\SystemConfiguration;
use App\Models\User;
use App\Services\FetScheduleParserService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\Support\ScpTestHelpers;
use Tests\TestCase;

class FetScheduleImportTest extends TestCase
{
    use RefreshDatabase;
    use ScpTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();

        SystemConfiguration::firstOrCreate([], [
            'active_cycle'       => 'Putaran 2',
            'is_schedule_locked' => false,
        ]);
    }

    private function createSampleFetXml(): string
    {
        $xmlContent = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<fet version="6.0.0">
    <Activities_Timetable>
        <!-- Teacher Amat Sukardi (Code 6) -> 3 consecutive slots in X RPL 1 on Senin -> should aggregate into JP 1-3 -->
        <Activity>
            <Teacher>Amat Sukardi, S.Pd.</Teacher>
            <Subject>Matematika</Subject>
            <Students>X RPL 1</Students>
            <Room>Lab RPL 1</Room>
            <Day>Senin</Day>
            <Hour>1</Hour>
        </Activity>
        <Activity>
            <Teacher>Amat Sukardi, S.Pd.</Teacher>
            <Subject>Matematika</Subject>
            <Students>X RPL 1</Students>
            <Room>Lab RPL 1</Room>
            <Day>Senin</Day>
            <Hour>2</Hour>
        </Activity>
        <Activity>
            <Teacher>Amat Sukardi, S.Pd.</Teacher>
            <Subject>Matematika</Subject>
            <Students>X RPL 1</Students>
            <Room>Lab RPL 1</Room>
            <Day>Senin</Day>
            <Hour>3</Hour>
        </Activity>

        <!-- Teacher Sunarsih (Code 3) -> slot 4 on Senin -->
        <Activity>
            <Teacher>Sunarsih, S.Pd.</Teacher>
            <Subject>Bahasa Inggris</Subject>
            <Students>X RPL 1</Students>
            <Room>Lab RPL 1</Room>
            <Day>Senin</Day>
            <Hour>4</Hour>
        </Activity>
    </Activities_Timetable>
</fet>
XML;

        $tempPath = tempnam(sys_get_temp_dir(), 'fet_test_') . '.xml';
        file_put_contents($tempPath, $xmlContent);

        return $tempPath;
    }

    public function test_xml_parsing_aggregates_consecutive_slots()
    {
        $waka = $this->makeUser(1);
        $teacherAmat = $this->makeUser(2, ['fullname' => 'Amat Sukardi, S.Pd.']);
        $teacherSunarsih = $this->makeUser(2, ['fullname' => 'Sunarsih, S.Pd.']);
        $class = SchoolClass::create(['class_name' => 'X RPL 1']);

        $filePath = $this->createSampleFetXml();
        $uploadedFile = new UploadedFile($filePath, 'fet_timetable.xml', 'application/xml', null, true);

        $parser = app(FetScheduleParserService::class);
        $preview = $parser->generatePreview($uploadedFile, 'Putaran 2');

        $this->assertSame(4, $preview['summary']['total_slots_raw']);
        // 3 consecutive slots aggregated into 1, plus 1 single slot = 2 total schedules
        $this->assertSame(2, $preview['summary']['aggregated_total']);
        $this->assertSame(2, $preview['summary']['valid_count']);

        // Check aggregated block details for Matematika
        $mathSchedule = collect($preview['schedules'])->firstWhere('subject', 'Matematika');
        $this->assertNotNull($mathSchedule);
        $this->assertSame(1, $mathSchedule['start_period']);
        $this->assertSame(3, $mathSchedule['end_period']);
        $this->assertSame('08:00:00', $mathSchedule['start_time']);
        $this->assertSame('09:45:00', $mathSchedule['end_time']);

        @unlink($filePath);
    }

    public function test_locked_schedule_rejects_import()
    {
        $waka = $this->makeUser(1);
        $config = SystemConfiguration::first();
        $config->update(['is_schedule_locked' => true]);

        $filePath = $this->createSampleFetXml();
        $uploadedFile = new UploadedFile($filePath, 'fet_timetable.xml', 'application/xml', null, true);

        $response = $this->actingAs($waka)->post(route('waka.schedules.import.preview'), [
            'schedule_file' => $uploadedFile,
            'cycle_type'    => 'Putaran 2',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertStringContainsString('dikunci', session('error'));

        @unlink($filePath);
    }

    public function test_commit_preserves_schedules_with_journals()
    {
        $waka = $this->makeUser(1);
        $teacherAmat = $this->makeUser(2, ['fullname' => 'Amat Sukardi, S.Pd.']);
        $teacherSunarsih = $this->makeUser(2, ['fullname' => 'Sunarsih, S.Pd.']);
        $class = SchoolClass::create(['class_name' => 'X RPL 1']);

        // Create an existing schedule in Putaran 2 that HAS a journal attached
        $existingScheduleWithJournal = Schedule::create([
            'teacher_id'   => $teacherAmat->id,
            'class_id'     => $class->id,
            'subject'      => 'Jadwal Lama Penting',
            'day_of_week'  => 'Jumat',
            'start_period' => 1,
            'end_period'   => 2,
            'start_time'   => '07:15:00',
            'end_time'     => '08:35:00',
            'cycle_type'   => 'Putaran 2',
        ]);

        Journal::create([
            'schedule_id'       => $existingScheduleWithJournal->id,
            'teacher_id'        => $teacherAmat->id,
            'date'              => now()->toDateString(),
            'topic_description' => 'Materi Lama',
        ]);

        // Create an existing schedule without journal (should be deleted upon commit)
        $existingScheduleWithoutJournal = Schedule::create([
            'teacher_id'   => $teacherAmat->id,
            'class_id'     => $class->id,
            'subject'      => 'Jadwal Usang Tanpa Jurnal',
            'day_of_week'  => 'Kamis',
            'start_period' => 1,
            'end_period'   => 2,
            'start_time'   => '07:15:00',
            'end_time'     => '08:35:00',
            'cycle_type'   => 'Putaran 2',
        ]);

        $filePath = $this->createSampleFetXml();
        $uploadedFile = new UploadedFile($filePath, 'fet_timetable.xml', 'application/xml', null, true);

        $parser = app(FetScheduleParserService::class);
        $preview = $parser->generatePreview($uploadedFile, 'Putaran 2');

        $response = $this->actingAs($waka)->post(route('waka.schedules.import.commit'), [
            'token' => $preview['token'],
        ]);

        $response->assertRedirect(route('schedules.index', ['cycle' => 'Putaran 2']));

        // Assert schedule with journal was preserved
        $this->assertDatabaseHas('schedules', ['id' => $existingScheduleWithJournal->id]);

        // Assert schedule without journal was deleted
        $this->assertDatabaseMissing('schedules', ['id' => $existingScheduleWithoutJournal->id]);

        // Assert new imported schedules are present
        $this->assertDatabaseHas('schedules', [
            'subject'     => 'Matematika',
            'cycle_type'  => 'Putaran 2',
            'start_period'=> 1,
            'end_period'  => 3,
        ]);

        @unlink($filePath);
    }

    public function test_aggregate_consecutive_slots_respects_breaks_and_does_not_merge_across_them()
    {
        $parser = app(FetScheduleParserService::class);
        $teacher = $this->makeUser(2, ['fullname' => 'Guru Uji, S.Pd.']);
        $class = SchoolClass::create(['class_name' => 'X RPL 1']);

        // 1. Senin JP 6 + JP 7 (ishoma break 11:45-12:45) -> 2 separate blocks
        $seninSlots = [
            ['day_of_week' => 'Senin', 'period' => 6, 'teacher' => $teacher, 'class' => $class, 'room' => null, 'subject' => 'Fisika'],
            ['day_of_week' => 'Senin', 'period' => 7, 'teacher' => $teacher, 'class' => $class, 'room' => null, 'subject' => 'Fisika'],
        ];
        $aggSenin = $parser->aggregateConsecutiveSlots($seninSlots, 'Putaran 2');
        $this->assertCount(2, $aggSenin);
        $this->assertSame(6, $aggSenin[0]['start_period']);
        $this->assertSame(6, $aggSenin[0]['end_period']);
        $this->assertSame(7, $aggSenin[1]['start_period']);
        $this->assertSame(7, $aggSenin[1]['end_period']);

        // 2. Jumat JP 6 + JP 7 (jumatan break 11:25-12:40) -> 2 separate blocks
        $jumatSlots = [
            ['day_of_week' => 'Jumat', 'period' => 6, 'teacher' => $teacher, 'class' => $class, 'room' => null, 'subject' => 'Kimia'],
            ['day_of_week' => 'Jumat', 'period' => 7, 'teacher' => $teacher, 'class' => $class, 'room' => null, 'subject' => 'Kimia'],
        ];
        $aggJumat = $parser->aggregateConsecutiveSlots($jumatSlots, 'Putaran 2');
        $this->assertCount(2, $aggJumat);
        $this->assertSame(6, $aggJumat[0]['start_period']);
        $this->assertSame(6, $aggJumat[0]['end_period']);
        $this->assertSame(7, $aggJumat[1]['start_period']);
        $this->assertSame(7, $aggJumat[1]['end_period']);

        // 3. Rabu JP 3 + JP 4 (istirahat 1 09:15-09:30) -> 2 separate blocks
        $rabuSlots = [
            ['day_of_week' => 'Rabu', 'period' => 3, 'teacher' => $teacher, 'class' => $class, 'room' => null, 'subject' => 'Biologi'],
            ['day_of_week' => 'Rabu', 'period' => 4, 'teacher' => $teacher, 'class' => $class, 'room' => null, 'subject' => 'Biologi'],
        ];
        $aggRabu = $parser->aggregateConsecutiveSlots($rabuSlots, 'Putaran 2');
        $this->assertCount(2, $aggRabu);
        $this->assertSame(3, $aggRabu[0]['start_period']);
        $this->assertSame(3, $aggRabu[0]['end_period']);
        $this->assertSame(4, $aggRabu[1]['start_period']);
        $this->assertSame(4, $aggRabu[1]['end_period']);

        // 4. Selasa JP 1 + JP 2 (contiguous, no break) -> 1 single aggregated block
        $selasaSlots = [
            ['day_of_week' => 'Selasa', 'period' => 1, 'teacher' => $teacher, 'class' => $class, 'room' => null, 'subject' => 'Matematika'],
            ['day_of_week' => 'Selasa', 'period' => 2, 'teacher' => $teacher, 'class' => $class, 'room' => null, 'subject' => 'Matematika'],
        ];
        $aggSelasa = $parser->aggregateConsecutiveSlots($selasaSlots, 'Putaran 2');
        $this->assertCount(1, $aggSelasa);
        $this->assertSame(1, $aggSelasa[0]['start_period']);
        $this->assertSame(2, $aggSelasa[0]['end_period']);
    }
}
