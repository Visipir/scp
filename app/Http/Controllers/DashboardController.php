<?php

namespace App\Http\Controllers;

use App\Models\SystemConfiguration;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\Schedule;
use App\Services\AcademicCalendarService;
use App\Services\DashboardService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request, DashboardService $dashboards): View
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
                $alerts = $dashboards->unfilledJournalAlerts($user->id, $config);
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
                $unfilledSchedules = $dashboards->wakaUnfilledSchedules($config);
                $attendanceStats = $dashboards->attendanceTodayCounts();
            }

            return view('dashboard', compact('user', 'config', 'kbmSuspendedEvent', 'unfilledSchedules', 'attendanceStats'));
        }

        return view('dashboard', compact('user', 'config', 'kbmSuspendedEvent'));
    }
}
