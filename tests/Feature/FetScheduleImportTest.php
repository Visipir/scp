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

    public function test_pdf_sample_teachers_raw_jp_and_blocks_reconciliation()
    {
        $pdfPath = storage_path('app/import-samples/JADWAL GURU PUT 2 SEM GANJIL 26_27 REVISI 2.pdf');
        if (!file_exists($pdfPath)) {
            $this->markTestSkipped('Sample PDF timetable not found.');
        }

        $parser = app(FetScheduleParserService::class);
        $rawSlots = $parser->parsePdf($pdfPath);
        $this->assertCount(1453, $rawSlots);

        $slotsByCode = [];
        foreach ($rawSlots as $s) {
            $code = $s['teacher_code'] ?? null;
            if ($code !== null) {
                $slotsByCode[$code][] = $s;
            }
        }

        // AMAT SUKARDI (Code 6) -> 24 raw JP, 15 blocks
        $amatSlots = $slotsByCode[6] ?? [];
        $this->assertCount(24, $amatSlots);
        $amatAgg = $parser->aggregateConsecutiveSlots($amatSlots, 'Putaran 2');
        $this->assertCount(15, $amatAgg);

        // SRI SUNARTINI (Code 4) -> 10 raw JP, 6 blocks
        $sriSlots = $slotsByCode[4] ?? [];
        $this->assertCount(10, $sriSlots);
        $sriAgg = $parser->aggregateConsecutiveSlots($sriSlots, 'Putaran 2');
        $this->assertCount(6, $sriAgg);

        // ARIS PURWANTINAH (Code 2) -> 8 raw JP, 5 blocks
        $arisSlots = $slotsByCode[2] ?? [];
        $this->assertCount(8, $arisSlots);
        $arisAgg = $parser->aggregateConsecutiveSlots($arisSlots, 'Putaran 2');
        $this->assertCount(5, $arisAgg);

        // WINDU MAHMUD (Code 12) -> 8 raw JP, 5 blocks
        $winduSlots = $slotsByCode[12] ?? [];
        $this->assertCount(8, $winduSlots);
        $winduAgg = $parser->aggregateConsecutiveSlots($winduSlots, 'Putaran 2');
        $this->assertCount(5, $winduAgg);

        // RUSDIYANTO (Code 57) -> 14 raw JP, 9 blocks
        $rusdiSlots = $slotsByCode[57] ?? [];
        $this->assertCount(14, $rusdiSlots);
        $rusdiAgg = $parser->aggregateConsecutiveSlots($rusdiSlots, 'Putaran 2');
        $this->assertCount(9, $rusdiAgg);

        // DINDA AYU DEWANTI (Code 81) -> 10 raw JP, 6 blocks
        $dindaSlots = $slotsByCode[81] ?? [];
        $this->assertCount(10, $dindaSlots);
        $dindaAgg = $parser->aggregateConsecutiveSlots($dindaSlots, 'Putaran 2');
        $this->assertCount(6, $dindaAgg);

        // Total school-wide aggregated blocks = 866
        $allAgg = $parser->aggregateConsecutiveSlots($rawSlots, 'Putaran 2');
        $this->assertCount(866, $allAgg);
    }

    public function test_reimport_twice_preserves_existing_ids_and_journals()
    {
        $waka = $this->makeUser(1);
        $teacherAmat = $this->makeUser(2, ['fullname' => 'Amat Sukardi, S.Pd.']);
        $teacherSunarsih = $this->makeUser(2, ['fullname' => 'Sunarsih, S.Pd.']);
        $class = SchoolClass::create(['class_name' => 'X RPL 1']);

        $filePath = $this->createSampleFetXml();
        $parser = app(FetScheduleParserService::class);

        // 1. First import
        $uploadedFile1 = new UploadedFile($filePath, 'fet_timetable.xml', 'application/xml', null, true);
        $preview1 = $parser->generatePreview($uploadedFile1, 'Putaran 2');
        $result1 = $parser->commit($preview1['token']);

        $this->assertSame(2, $result1['inserted']);
        $this->assertSame(0, $result1['unchanged']);
        $this->assertSame(0, $result1['deleted']);

        $mathSchedule = Schedule::where('subject', 'Matematika')->first();
        $this->assertNotNull($mathSchedule);
        $originalMathId = $mathSchedule->id;

        // Attach a journal to the imported schedule
        $journal = Journal::create([
            'schedule_id'       => $mathSchedule->id,
            'teacher_id'        => $teacherAmat->id,
            'date'              => now()->toDateString(),
            'topic_description' => 'Materi Aljabar',
        ]);

        // 2. Second import of the EXACT SAME file
        $uploadedFile2 = new UploadedFile($filePath, 'fet_timetable.xml', 'application/xml', null, true);
        $preview2 = $parser->generatePreview($uploadedFile2, 'Putaran 2');
        $result2 = $parser->commit($preview2['token']);

        // Assert 0 inserted, 2 unchanged, 0 deleted
        $this->assertSame(0, $result2['inserted']);
        $this->assertSame(2, $result2['unchanged']);
        $this->assertSame(0, $result2['deleted']);

        // Assert schedule ID is identical (retained, NOT replaced)
        $mathScheduleAfter = Schedule::where('subject', 'Matematika')->first();
        $this->assertSame($originalMathId, $mathScheduleAfter->id);

        // Assert journal is still attached to the exact same schedule
        $this->assertDatabaseHas('journals', [
            'id'          => $journal->id,
            'schedule_id' => $originalMathId,
        ]);

        @unlink($filePath);
    }

    public function test_synthetic_fixture_csv_import_full_flow_preview_commit_reimport_and_journal_preservation(): void
    {
        $waka = $this->makeUser(1);
        $teacher1 = $this->makeUser(2, ['fullname' => 'Guru Sintetis Satu, S.Pd.']);
        $teacher2 = $this->makeUser(2, ['fullname' => 'Guru Sintetis Dua, S.Pd.']);
        $class = SchoolClass::create(['class_name' => 'X RPL Sintetis', 'academic_year' => '2026/2027']);

        $csvPath = base_path('tests/Fixtures/synthetic_fet_timetable.csv');
        $this->assertFileExists($csvPath);

        $uploadedFile = new UploadedFile($csvPath, 'synthetic_fet_timetable.csv', 'text/csv', null, true);
        $parser = app(FetScheduleParserService::class);

        // 1. Preview
        $preview = $parser->generatePreview($uploadedFile, 'Putaran 2');
        $this->assertNotEmpty($preview['token']);
        $this->assertSame(3, $preview['summary']['total_slots_raw']);
        $this->assertSame(2, $preview['summary']['aggregated_total']);
        $this->assertSame(2, $preview['summary']['valid_count']);

        // 2. Commit
        $result = $parser->commit($preview['token']);
        $this->assertSame(2, $result['inserted']);
        $this->assertSame(0, $result['unchanged']);
        $this->assertSame(0, $result['deleted']);

        // Verify DB records
        $sched1 = Schedule::where('subject', 'Pemrograman Web Sintetis')->first();
        $this->assertNotNull($sched1);
        $this->assertSame($teacher1->id, $sched1->teacher_id);
        $this->assertSame($class->id, $sched1->class_id);
        $this->assertSame('Senin', $sched1->day_of_week);
        $this->assertSame('Putaran 2', $sched1->cycle_type);

        $sched2 = Schedule::where('subject', 'Basis Data Sintetis')->first();
        $this->assertNotNull($sched2);
        $this->assertSame($teacher2->id, $sched2->teacher_id);
        $this->assertSame('Selasa', $sched2->day_of_week);

        // 3. Attach Journal
        $journal = Journal::create([
            'schedule_id'       => $sched1->id,
            'teacher_id'        => $teacher1->id,
            'date'              => now()->toDateString(),
            'topic_description' => 'Materi Sintetis Web',
        ]);

        // 4. Idempotent re-import
        $uploadedFile2 = new UploadedFile($csvPath, 'synthetic_fet_timetable.csv', 'text/csv', null, true);
        $preview2 = $parser->generatePreview($uploadedFile2, 'Putaran 2');
        $result2 = $parser->commit($preview2['token']);

        $this->assertSame(0, $result2['inserted']);
        $this->assertSame(2, $result2['unchanged']);
        $this->assertSame(0, $result2['deleted']);
        $this->assertSame($sched1->id, Schedule::where('subject', 'Pemrograman Web Sintetis')->first()->id);
        $this->assertDatabaseHas('journals', ['id' => $journal->id, 'schedule_id' => $sched1->id]);
    }

    public function test_synthetic_fixture_putaran_1_import_does_not_mutate_putaran_2_schedules(): void
    {
        $waka = $this->makeUser(1);
        $teacher1 = $this->makeUser(2, ['fullname' => 'Guru Sintetis Satu, S.Pd.']);
        $class = SchoolClass::create(['class_name' => 'X RPL Sintetis', 'academic_year' => '2026/2027']);

        // Existing schedule in Putaran 2
        $putaran2Schedule = Schedule::create([
            'teacher_id'   => $teacher1->id,
            'class_id'     => $class->id,
            'subject'      => 'Mapel Putaran 2 Aman',
            'day_of_week'  => 'Kamis',
            'start_period' => 1,
            'end_period'   => 2,
            'start_time'   => '07:15:00',
            'end_time'     => '08:35:00',
            'cycle_type'   => 'Putaran 2',
        ]);

        $csvPath = base_path('tests/Fixtures/synthetic_fet_timetable.csv');
        $uploadedFile = new UploadedFile($csvPath, 'synthetic_fet_timetable.csv', 'text/csv', null, true);
        $parser = app(FetScheduleParserService::class);

        // Import targeting Putaran 1
        $preview = $parser->generatePreview($uploadedFile, 'Putaran 1');
        $this->assertSame('Putaran 1', $preview['cycle_type']);

        $result = $parser->commit($preview['token']);
        $this->assertSame(2, $result['inserted']);

        // Verify Putaran 1 schedules created
        $p1Count = Schedule::where('cycle_type', 'Putaran 1')->count();
        $this->assertSame(2, $p1Count);

        // Verify Putaran 2 schedule remains completely untouched
        $p2After = Schedule::where('cycle_type', 'Putaran 2')->get();
        $this->assertCount(1, $p2After);
        $this->assertSame($putaran2Schedule->id, $p2After->first()->id);
        $this->assertSame('Mapel Putaran 2 Aman', $p2After->first()->subject);
    }
}
