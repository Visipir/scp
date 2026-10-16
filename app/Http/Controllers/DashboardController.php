<?php

namespace App\Http\Controllers;

use App\Models\SystemConfiguration;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\Schedule;
use App\Models\Journal;
use App\Services\AcademicCalendarService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $config = SystemConfiguration::first();
        $alerts = [];
        $journalLateAlert = false;
        $schedules = collect();
        $today = Carbon::today()->toDateString();
        $kbmSuspendedEvent = AcademicCalendarService::isKbmSuspendedToday($today);

        // Only Guru (role 2) gets the teacher dashboard with journal input form and late alerts
        if ($user->role_id === 2) {
            // If today KBM is suspended, no journal is required (suppress late alerts)
            if (!$kbmSuspendedEvent) {
                $alerts = $this->getUnfilledJournalAlerts($user->id, $config);
                $journalLateAlert = !empty($alerts);
            }

            $schedules = Schedule::with(['schoolClass', 'room'])
                ->where('teacher_id', $user->id)
                ->get();

            $now = Carbon::now();
            $dayMap = [
                'Monday' => 'Senin', 'Tuesday' => 'Selasa', 'Wednesday' => 'Rabu',
                'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu', 'Sunday' => 'Minggu',
            ];
            $todayName = $dayMap[$now->format('l')] ?? 'Senin';
            $currentTime = $now->format('H:i:s');

            $todaySchedules = $schedules->where('day_of_week', $todayName);
            $currentSchedule = $todaySchedules->first(function ($sched) use ($currentTime) {
                return $sched->start_time <= $currentTime && $sched->end_time >= $currentTime;
            });

            $targetScheduleId = $request->query('schedule_id');
            $defaultSchedule = ($targetScheduleId ? $schedules->firstWhere('id', $targetScheduleId) : null)
                ?? $currentSchedule 
                ?? $todaySchedules->first(fn ($sched) => $sched->start_time >= $currentTime)
                ?? $todaySchedules->first()
                ?? $schedules->first();

            $defaultDate = $request->query('date', $now->format('Y-m-d'));

            $classIds = $schedules->pluck('class_id')->unique()->filter();
            $studentsByClass = \App\Models\User::whereIn('class_id', $classIds)
                ->where('role_id', 4) // Siswa
                ->orderBy('fullname')
                ->get(['id', 'fullname', 'class_id'])
                ->groupBy('class_id');

            return view('teacher.dashboard', compact(
                'user', 'config', 'alerts', 'journalLateAlert', 'schedules', 'kbmSuspendedEvent',
                'defaultSchedule', 'defaultDate', 'studentsByClass'
            ));
        }

        if ($user->role_id === 1) {
            $unfilledSchedules = collect();
            $attendanceStats = ['Hadir' => 0, 'Sakit' => 0, 'Izin' => 0, 'Alpa' => 0, 'Terlambat' => 0];

            if (!$kbmSuspendedEvent) {
                $now = Carbon::now();
                $dayMap = [
                    'Monday' => 'Senin', 'Tuesday' => 'Selasa', 'Wednesday' => 'Rabu',
                    'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu', 'Sunday' => 'Minggu',
                ];
                $todayDay = $dayMap[$now->format('l')] ?? null;
                $activeCycle = $config?->active_cycle ?? 'Putaran 1';

                if ($todayDay) {
                    $allSchedules = Schedule::with(['teacher', 'schoolClass'])->where('day_of_week', $todayDay)->where('cycle_type', $activeCycle)->get();
                    $filledScheduleIds = Journal::whereDate('date', $today)->pluck('schedule_id')->toArray();
                    $threshold = $now->copy(); // For Waka, let's just use schedules that have ended, without 30m grace period, or we can keep it. I'll just check if it's past end time.

                    $unfilledSchedules = $allSchedules->filter(function($sched) use ($threshold, $filledScheduleIds) {
                        if (in_array($sched->id, $filledScheduleIds)) return false;
                        $endTimeRaw = $sched->end_time;
                        $endTime = strlen($endTimeRaw) === 5 ? Carbon::createFromFormat('H:i', $endTimeRaw) : Carbon::createFromFormat('H:i:s', $endTimeRaw);
                        return $endTime->lt($threshold);
                    });
                }

                $attendanceCounts = \App\Models\Attendance::whereDate('date', $today)
                    ->selectRaw('status, count(*) as total')
                    ->groupBy('status')
                    ->pluck('total', 'status');

                foreach (array_keys($attendanceStats) as $status) {
                    $attendanceStats[$status] = (int) ($attendanceCounts[$status] ?? 0);
                }
            }

            return view('dashboard', compact('user', 'config', 'kbmSuspendedEvent', 'unfilledSchedules', 'attendanceStats'));
        }

        return view('dashboard', compact('user', 'config', 'kbmSuspendedEvent'));
    }

    private function getUnfilledJournalAlerts(int $teacherId, ?SystemConfiguration $config): array
    {
        $today = Carbon::today();
        if (AcademicCalendarService::isKbmSuspendedToday($today->toDateString())) {
            return []; // KBM suspended — no journals required
        }

        $now = Carbon::now();

        // Map Indonesian day names
        $dayMap = [
            'Monday' => 'Senin', 'Tuesday' => 'Selasa', 'Wednesday' => 'Rabu',
            'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu',
        ];
        $todayDay = $dayMap[$today->format('l')] ?? null;

        if (!$todayDay) {
            return []; // Sunday — no schedules
        }

        $activeCycle = $config?->active_cycle ?? 'Putaran 1';

        // Get today's schedules for this teacher in the active cycle
        $schedules = Schedule::with(['schoolClass', 'room'])
            ->where('teacher_id', $teacherId)
            ->where('day_of_week', $todayDay)
            ->where('cycle_type', $activeCycle)
            ->get();

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

            $endTimeRaw = $schedule->end_time;
            $endTime = strlen($endTimeRaw) === 5
                ? Carbon::createFromFormat('H:i', $endTimeRaw)
                : Carbon::createFromFormat('H:i:s', $endTimeRaw);

            return $endTime->lte($threshold);
        })->values()->all();
    }
}
