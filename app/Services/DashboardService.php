<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Journal;
use App\Models\Schedule;
use App\Models\ScheduleSwap;
use App\Models\SystemConfiguration;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class DashboardService
{
    public function __construct(
        private readonly ?ScheduleSwapService $swapService = null
    ) {}

    private function getSwapService(): ScheduleSwapService
    {
        return $this->swapService ?? app(ScheduleSwapService::class);
    }
    /**
     * Schedules whose journal is still missing after end_time + 30 minutes today.
     * Shared by the web teacher dashboard and the API.
     *
     * @return array<int, Schedule>
     */
    public function unfilledJournalAlerts(int $teacherId, ?SystemConfiguration $config): array
    {
        $today = Carbon::today();
        if (AcademicCalendarService::isKbmSuspendedToday($today->toDateString())) {
            return []; // KBM suspended — no journals required
        }

        $now = Carbon::now();

        $dayMap = [
            'Monday' => 'Senin', 'Tuesday' => 'Selasa', 'Wednesday' => 'Rabu',
            'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu',
        ];
        $todayDay = $dayMap[$today->format('l')] ?? null;

        if (! $todayDay) {
            return []; // Sunday — no schedules
        }

        $activeCycle = $config?->active_cycle ?? 'Putaran 1';

        $schedules = $this->getSwapService()->getEffectiveSchedulesForTeacher($teacherId, $today, $activeCycle);

        if ($schedules->isEmpty()) {
            return [];
        }

        $filledScheduleIds = Journal::whereDate('date', $today)
            ->whereIn('schedule_id', $schedules->pluck('id'))
            ->pluck('schedule_id')
            ->toArray();

        $threshold = $now->copy()->subMinutes(30);

        return $schedules->filter(function (Schedule $schedule) use ($threshold, $filledScheduleIds) {
            if (in_array($schedule->id, $filledScheduleIds)) {
                return false;
            }

            $endTime = $this->parseScheduleTime($schedule->end_time);

            return $endTime->lte($threshold);
        })->values()->all();
    }

    /**
     * Today's school-wide schedules without a journal, from the Waka view (no grace period).
     *
     * @return Collection<int, Schedule>
     */
    public function wakaUnfilledSchedules(?SystemConfiguration $config): Collection
    {
        $today = Carbon::today('Asia/Jakarta');
        if (AcademicCalendarService::isKbmSuspendedToday($today->toDateString())) {
            return collect();
        }

        $now = Carbon::now('Asia/Jakarta');
        $todayDay = $this->indonesianDay($now);

        if (! $todayDay) {
            return collect();
        }

        $activeCycle = $config?->active_cycle ?? 'Putaran 1';

        $allSchedules = Schedule::with(['teacher', 'schoolClass'])
            ->where('day_of_week', $todayDay)
            ->where('cycle_type', $activeCycle)
            ->get();

        $todayDateString = $today->toDateString();
        $todaySwaps = ScheduleSwap::with(['requester', 'target'])
            ->whereDate('swap_date', $todayDateString)
            ->where('status', 'approved')
            ->get();

        if ($todaySwaps->isNotEmpty()) {
            foreach ($allSchedules as $sched) {
                $swap = $todaySwaps->first(function ($s) use ($sched) {
                    return (int) $s->requester_schedule_id === (int) $sched->id
                        || (int) $s->target_schedule_id === (int) $sched->id;
                });

                if ($swap) {
                    $effectiveTeacher = ((int) $swap->requester_schedule_id === (int) $sched->id)
                        ? $swap->target
                        : $swap->requester;

                    if ($effectiveTeacher) {
                        $sched->teacher_id = $effectiveTeacher->id;
                        $sched->swap_id = $swap->id;
                        $sched->setRelation('teacher', $effectiveTeacher);
                    }
                }
            }
        }

        $filledScheduleIds = Journal::whereDate('date', $today)->pluck('schedule_id')->toArray();

        return $allSchedules->filter(function (Schedule $sched) use ($now, $filledScheduleIds) {
            if (in_array($sched->id, $filledScheduleIds)) {
                return false;
            }

            $endTime = $this->parseScheduleTime($sched->end_time);
            $warnTime = $endTime->copy()->subMinutes(30);

            // Alert triggers 30 minutes before class end time
            if ($now->lt($warnTime)) {
                return false;
            }

            if ($now->gte($endTime)) {
                $sched->alert_status = 'terlambat';
                $sched->alert_badge = 'Terlambat';
            } else {
                $sched->alert_status = 'segera_berakhir';
                $sched->alert_badge = 'Segera Berakhir';
            }

            return true;
        })->values();
    }

    /**
     * Attendance status counts for today (school-wide), keyed by status.
     *
     * @return array<string, int>
     */
    public function attendanceTodayCounts(): array
    {
        $today = Carbon::today()->toDateString();

        $counts = Attendance::whereDate('date', $today)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $stats = ['Hadir' => 0, 'Sakit' => 0, 'Izin' => 0, 'Alpa' => 0, 'Terlambat' => 0];
        foreach (array_keys($stats) as $status) {
            $stats[$status] = (int) ($counts[$status] ?? 0);
        }

        return $stats;
    }

    /**
     * Comprehensive attendance statistics for Waka:
     * 1. Today's counts and detailed absentee list (who is on leave, sick, alpa).
     * 2. Monthly aggregates with class breakdown and available month list.
     *
     * @return array<string, mixed>
     */
    public function wakaAttendanceOverview(?string $month = null): array
    {
        $now = Carbon::now('Asia/Jakarta');
        $today = $now->toDateString();
        $targetMonth = $month ?: $now->format('Y-m');

        // 1. STATISTIK HARI INI
        $todayCounts = [
            'Hadir'     => 0,
            'Sakit'     => 0,
            'Izin'      => 0,
            'Alpa'      => 0,
            'Terlambat' => 0,
        ];

        $todayRaw = Attendance::whereDate('date', $today)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        foreach (array_keys($todayCounts) as $st) {
            $todayCounts[$st] = (int) ($todayRaw[$st] ?? 0);
        }

        $totalStudents = User::where('role_id', 4)->count();
        $todayTotalLogged = array_sum($todayCounts);
        $todayAttendanceRate = $todayTotalLogged > 0
            ? round(($todayCounts['Hadir'] / $todayTotalLogged) * 100, 1)
            : 0;

        // Siswa Izin / Sakit / Alpa Hari Ini (Daftar detail siapa saja yang izin dsb)
        $todayAbsentees = Attendance::with([
                'student.schoolClass',
                'journal.schedule.teacher',
                'leaveRequest',
            ])
            ->whereDate('date', $today)
            ->whereIn('status', ['Izin', 'Sakit', 'Alpa', 'Terlambat'])
            ->orderBy('status')
            ->orderBy('id', 'desc')
            ->get();

        // 2. STATISTIK BULAN TERPILIH (FILTER BULAN)
        $parts = explode('-', $targetMonth);
        $year = isset($parts[0]) ? (int) $parts[0] : (int) $now->format('Y');
        $monthNumber = isset($parts[1]) ? (int) $parts[1] : (int) $now->format('m');
        $monthCarbon = Carbon::createFromDate($year, $monthNumber, 1, 'Asia/Jakarta');

        $monthRaw = Attendance::whereYear('date', $year)
            ->whereMonth('date', $monthNumber)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $monthCounts = [
            'Hadir'     => (int) ($monthRaw['Hadir'] ?? 0),
            'Sakit'     => (int) ($monthRaw['Sakit'] ?? 0),
            'Izin'      => (int) ($monthRaw['Izin'] ?? 0),
            'Alpa'      => (int) ($monthRaw['Alpa'] ?? 0),
            'Terlambat' => (int) ($monthRaw['Terlambat'] ?? 0),
        ];

        $monthTotalLogged = array_sum($monthCounts);
        $monthAttendanceRate = $monthTotalLogged > 0
            ? round(($monthCounts['Hadir'] / $monthTotalLogged) * 100, 1)
            : 0;

        // Rekap per Rombel Kelas untuk bulan tersebut
        $classes = \App\Models\SchoolClass::with([
            'homeroomTeacher',
            'counselors',
        ])->orderBy('class_name')->get();

        $classStatsRaw = Attendance::join('users', 'attendances.student_id', '=', 'users.id')
            ->whereYear('attendances.date', $year)
            ->whereMonth('attendances.date', $monthNumber)
            ->selectRaw("
                users.class_id,
                attendances.status,
                COUNT(*) as total
            ")
            ->groupBy('users.class_id', 'attendances.status')
            ->get();

        $classStats = $classes->map(function ($cls) use ($classStatsRaw) {
            $cRaw = $classStatsRaw->where('class_id', $cls->id);
            $hadir = (int) ($cRaw->where('status', 'Hadir')->first()?->total ?? 0);
            $sakit = (int) ($cRaw->where('status', 'Sakit')->first()?->total ?? 0);
            $izin = (int) ($cRaw->where('status', 'Izin')->first()?->total ?? 0);
            $alpa = (int) ($cRaw->where('status', 'Alpa')->first()?->total ?? 0);
            $terlambat = (int) ($cRaw->where('status', 'Terlambat')->first()?->total ?? 0);
            $total = $hadir + $sakit + $izin + $alpa + $terlambat;
            $rate = $total > 0 ? round(($hadir / $total) * 100, 1) : 0;

            return [
                'class_id'        => $cls->id,
                'class_name'      => $cls->class_name,
                'grade_level'     => $cls->grade_level,
                'major'           => $cls->major,
                'homeroom'        => $cls->homeroomTeacher?->fullname ?? '-',
                'hadir'           => $hadir,
                'sakit'           => $sakit,
                'izin'            => $izin,
                'alpa'            => $alpa,
                'terlambat'       => $terlambat,
                'total'           => $total,
                'attendance_rate' => $rate,
            ];
        });

        // List of months for dropdown filter (past 12 months)
        $availableMonths = [];
        $cursor = $now->copy()->startOfMonth();
        for ($i = 0; $i < 12; $i++) {
            $key = $cursor->format('Y-m');
            $availableMonths[$key] = $cursor->translatedFormat('F Y');
            $cursor->subMonth();
        }

        return [
            'today' => [
                'date'            => $today,
                'date_formatted'  => $now->translatedFormat('l, d F Y'),
                'counts'          => $todayCounts,
                'total_students'  => $totalStudents,
                'total_logged'    => $todayTotalLogged,
                'attendance_rate' => $todayAttendanceRate,
                'absentees'       => $todayAbsentees,
            ],
            'month' => [
                'selected'        => $targetMonth,
                'name_formatted'  => $monthCarbon->translatedFormat('F Y'),
                'counts'          => $monthCounts,
                'total_logged'    => $monthTotalLogged,
                'attendance_rate' => $monthAttendanceRate,
                'class_breakdown' => $classStats,
                'available'       => $availableMonths,
            ],
        ];
    }

    /**
     * Teachers that still have unfilled journals today (end_time - 30 min rule for Waka).
     *
     * @return array<int, array<string, mixed>>
     */
    public function unfilledJournalsForTeachers(?SystemConfiguration $config): array
    {
        $schedules = $this->wakaUnfilledSchedules($config);

        $result = [];
        foreach ($schedules->groupBy('teacher_id') as $teacherId => $teacherSchedules) {
            $result[] = [
                'teacher_id' => (int) $teacherId,
                'schedules'  => $teacherSchedules->all(),
            ];
        }

        return $result;
    }

    public function indonesianDay(Carbon $date): ?string
    {
        $dayMap = [
            'Monday' => 'Senin', 'Tuesday' => 'Selasa', 'Wednesday' => 'Rabu',
            'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu',
        ];

        return $dayMap[$date->format('l')] ?? null;
    }

    private function parseScheduleTime(string $raw): Carbon
    {
        $todayStr = Carbon::today('Asia/Jakarta')->toDateString();

        return strlen($raw) === 5
            ? Carbon::createFromFormat('Y-m-d H:i', "{$todayStr} {$raw}", 'Asia/Jakarta')
            : Carbon::createFromFormat('Y-m-d H:i:s', "{$todayStr} {$raw}", 'Asia/Jakarta');
    }
}
