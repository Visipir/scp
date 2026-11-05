<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Journal;
use App\Models\Schedule;
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

        $swapService = $this->getSwapService();
        foreach ($allSchedules as $sched) {
            $effective = $swapService->getEffectiveTeacherForSchedule($sched->id, $today);
            if ($effective['is_swapped']) {
                $sched->teacher_id = $effective['teacher_id'];
                $sched->swap_id = $effective['swap_id'];
                $sched->setRelation('teacher', User::find($effective['teacher_id']));
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
