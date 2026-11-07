<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Room;
use App\Models\Schedule;
use App\Models\SchoolClass;
use App\Models\SystemConfiguration;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class WeekendEmptyScheduleJ2Test extends TestCase
{
    use RefreshDatabase;

    private User $waka;
    private User $guru;
    private SchoolClass $class;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['id' => 1], ['role_name' => 'Waka Kurikulum']);
        Role::firstOrCreate(['id' => 2], ['role_name' => 'Guru']);
        Role::firstOrCreate(['id' => 3], ['role_name' => 'Tim Kesiswaan']);
        Role::firstOrCreate(['id' => 4], ['role_name' => 'Siswa']);

        SystemConfiguration::firstOrCreate(
            ['id' => 1],
            ['active_cycle' => 'Putaran 1', 'is_schedule_locked' => true]
        );

        $this->class = SchoolClass::firstOrCreate(
            ['id' => 1],
            ['class_name' => 'X RPL 1', 'academic_year' => '2026/2027', 'grade_level' => 'X', 'major' => 'RPL']
        );

        $room = Room::firstOrCreate(['id' => 1], ['room_name' => 'Lab RPL 1']);

        $this->waka = User::create([
            'username' => 'waka_test',
            'fullname' => 'waka_kurikulum',
            'role_id'  => 1,
            'password' => Hash::make('secret', ['rounds' => 4]),
        ]);

        $this->guru = User::create([
            'username' => 'guru_test',
            'fullname' => 'Haris Budiawan, S.Pd',
            'role_id'  => 2,
            'password' => Hash::make('secret', ['rounds' => 4]),
        ]);

        Schedule::create([
            'teacher_id'   => $this->guru->id,
            'class_id'     => $this->class->id,
            'room_id'      => $room->id,
            'subject'      => 'Pemrograman Dasar',
            'day_of_week'  => 'Senin',
            'start_period' => 1,
            'end_period'   => 3,
            'start_time'   => '08:00:00',
            'end_time'     => '09:45:00',
            'cycle_type'   => 'Putaran 1',
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_j2_saturday_and_sunday_never_500_and_show_clear_empty_schedule_message(): void
    {
        foreach ([
            ['2026-11-07 09:30:00', '2026-11-07', 'Sabtu'],
            ['2026-11-08 09:30:00', '2026-11-08', 'Minggu'],
        ] as [$dateTime, $dateOnly, $dayLabel]) {
            Carbon::setTestNow(Carbon::parse($dateTime, 'Asia/Jakarta'));

            // 1. Waka Dashboard (Web + API)
            $this->actingAs($this->waka)
                ->get('/dashboard')
                ->assertOk()
                ->assertSee('Tidak ada jadwal hari ini')
                ->assertSee('Lihat Jadwal Hari Lain');

            $this->actingAs($this->waka)
                ->getJson('/api/v1/dashboard')
                ->assertOk()
                ->assertJsonPath('data.unfilled_journals', []);

            // 2. Guru Dashboard (Web + API) + Alert Dashboard
            $this->actingAs($this->guru)
                ->get('/dashboard')
                ->assertOk()
                ->assertSee('Tidak ada jadwal hari ini')
                ->assertSee("({$dayLabel})")
                ->assertSee('Pilih Jadwal')
                ->assertSee('Senin | X RPL 1');

            $this->actingAs($this->guru)
                ->getJson('/api/v1/dashboard')
                ->assertOk()
                ->assertJsonPath('data.today_schedules', [])
                ->assertJsonPath('data.late_journal_alerts', []);

            // 3. Daftar Jadwal default "hari ini" (Web + API)
            $this->actingAs($this->guru)
                ->get('/schedules')
                ->assertOk()
                ->assertSee("Tidak ada jadwal hari ini ({$dayLabel})")
                ->assertSee('Semua Hari')
                ->assertSee('Senin');

            $this->actingAs($this->waka)
                ->get('/schedules')
                ->assertOk()
                ->assertSee("Tidak ada jadwal hari ini ({$dayLabel})");

            if ($dayLabel === 'Sabtu') {
                $this->actingAs($this->guru)
                    ->getJson('/api/v1/schedules?day=Sabtu')
                    ->assertOk()
                    ->assertJsonPath('data', []);
            }

            // 4. Form Jurnal default (Web + API available-schedules)
            $this->actingAs($this->guru)
                ->get('/journals/create')
                ->assertOk()
                ->assertSee('Tidak ada jadwal hari ini')
                ->assertSee("({$dayLabel})")
                ->assertSee('Pilih Jadwal')
                ->assertSee('Senin | Jam 1-3 | X RPL 1 - Pemrograman Dasar');

            $this->actingAs($this->guru)
                ->getJson("/api/v1/journals/available-schedules?date={$dateOnly}")
                ->assertOk()
                ->assertJsonPath('data', [])
                ->assertJsonPath('meta.total_schedules', 0);

            // 5. Rekap harian (Web + API)
            $this->actingAs($this->guru)
                ->get("/classes/{$this->class->id}?date={$dateOnly}")
                ->assertOk()
                ->assertSee('X RPL 1');

            $this->actingAs($this->guru)
                ->getJson("/api/v1/classes/{$this->class->id}/attendance?date={$dateOnly}")
                ->assertOk()
                ->assertJsonPath('data.summary.total_records', 0);
        }
    }
}
