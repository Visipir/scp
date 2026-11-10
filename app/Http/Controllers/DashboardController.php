<?php

namespace App\Http\Controllers;

use App\Models\SystemConfiguration;
use App\Models\ScheduleSwap;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\Schedule;
use App\Services\AcademicCalendarService;
use App\Services\DashboardService;
use App\Services\ScheduleSwapService;
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
            $swapService = app(ScheduleSwapService::class);
            $swapService->autoExpirePendingSwaps();
            $pendingSwapCount = ScheduleSwap::where('target_id', $user->id)
                ->where('status', 'pending')
                ->where('swap_date', '>=', $today)
                ->count();

            // If today KBM is suspended, no journal is required (suppress late alerts)
            if (!$kbmSuspendedEvent) {
                $alerts = $dashboards->unfilledJournalAlerts($user->id, $config);
                $journalLateAlert = !empty($alerts);
            }

            // Effective schedules for today taking approved swaps into account
            $effectiveToday = $swapService->getEffectiveSchedulesForTeacher($user->id, $today, $config?->active_cycle);

            $masterSchedules = Schedule::with(['schoolClass', 'room'])
                ->where('teacher_id', $user->id)
                ->get();

            $schedules = $masterSchedules;
            foreach ($effectiveToday as $eff) {
                if (($eff->is_substitute ?? false) && !$schedules->contains('id', $eff->id)) {
                    $schedules->push($eff);
                }
            }

            $now = Carbon::now();
            $dayMap = [
                'Monday' => 'Senin', 'Tuesday' => 'Selasa', 'Wednesday' => 'Rabu',
                'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu', 'Sunday' => 'Minggu',
            ];
            $todayName = $dayMap[$now->format('l')] ?? 'Senin';
            $currentTime = $now->format('H:i:s');

            $todaySchedules = $effectiveToday->isNotEmpty() ? $effectiveToday : $schedules->where('day_of_week', $todayName);
            $currentSchedule = $todaySchedules->first(function ($sched) use ($currentTime) {
                return $sched->start_time <= $currentTime && $sched->end_time >= $currentTime;
            });

            $targetScheduleId = $request->query('schedule_id');
            $defaultSchedule = ($targetScheduleId ? $schedules->firstWhere('id', $targetScheduleId) : null)
                ?? $currentSchedule 
                ?? $todaySchedules->first(fn ($sched) => $sched->start_time >= $currentTime)
                ?? $todaySchedules->first();

            $defaultDate = $request->query('date', $now->format('Y-m-d'));

            $classIds = $schedules->pluck('class_id')->unique()->filter();
            $studentsByClass = \App\Models\User::whereIn('class_id', $classIds)
                ->where('role_id', 4) // Siswa
                ->orderBy('fullname')
                ->get(['id', 'fullname', 'class_id'])
                ->groupBy('class_id');

            $homeroomClass = null;
            $homeroomStats = null;
            if ($user->isHomeroomTeacher()) {
                $homeroomAssignment = $user->homeroomAssignment()->with('schoolClass')->first();
                if ($homeroomAssignment && $homeroomAssignment->schoolClass) {
                    $homeroomClass = $homeroomAssignment->schoolClass;
                    $studentIds = \App\Models\User::where('class_id', $homeroomClass->id)
                        ->where('role_id', 4)
                        ->pluck('id');

                    $rawLeaveStats = \App\Models\LeaveRequest::whereIn('student_id', $studentIds)
                        ->selectRaw("
                            COUNT(*) as total,
                            SUM(CASE WHEN status_approval IN ('Pending', 'Menunggu') THEN 1 ELSE 0 END) as pending,
                            SUM(CASE WHEN status_approval = 'Approved' THEN 1 ELSE 0 END) as approved,
                            SUM(CASE WHEN status_approval = 'Rejected' THEN 1 ELSE 0 END) as rejected
                        ")
                        ->first();

                    $leaveStats = [
                        'pending'  => (int) ($rawLeaveStats?->pending ?? 0),
                        'approved' => (int) ($rawLeaveStats?->approved ?? 0),
                        'rejected' => (int) ($rawLeaveStats?->rejected ?? 0),
                        'total'    => (int) ($rawLeaveStats?->total ?? 0),
                    ];

                    $todayAttendances = \App\Models\Attendance::whereIn('student_id', $studentIds)
                        ->whereDate('date', $today)
                        ->get();

                    $homeroomStats = [
                        'total_students'     => $studentIds->count(),
                        'leaves'             => $leaveStats,
                        'attendance_summary' => [
                            'hadir'     => $todayAttendances->where('status', 'Hadir')->count(),
                            'sakit'     => $todayAttendances->where('status', 'Sakit')->count(),
                            'izin'      => $todayAttendances->where('status', 'Izin')->count(),
                            'alpa'      => $todayAttendances->where('status', 'Alpa')->count(),
                            'terlambat' => $todayAttendances->where('status', 'Terlambat')->count(),
                        ],
                    ];
                }
            }

            return view('teacher.dashboard', compact(
                'user', 'config', 'alerts', 'journalLateAlert', 'schedules', 'kbmSuspendedEvent',
                'defaultSchedule', 'defaultDate', 'studentsByClass', 'pendingSwapCount',
                'homeroomClass', 'homeroomStats', 'todayName', 'todaySchedules'
            ));
        }

        if ($user->role_id === 1) {
            $unfilledSchedules = collect();
            $attendanceStats = ['Hadir' => 0, 'Sakit' => 0, 'Izin' => 0, 'Alpa' => 0, 'Terlambat' => 0];
            $todayDay = $dashboards->indonesianDay(Carbon::now('Asia/Jakarta'));
            $todayHasSchedules = $todayDay !== null && Schedule::where('day_of_week', $todayDay)
                ->where('cycle_type', $config?->active_cycle ?? 'Putaran 1')
                ->exists();

            if (!$kbmSuspendedEvent) {
                $unfilledSchedules = $dashboards->wakaUnfilledSchedules($config);
                $attendanceStats = $dashboards->attendanceTodayCounts();
            }

            $todaySwaps = app(\App\Services\ScheduleSwapService::class)->getTodayApprovedSwapsForDashboard();

            return view('dashboard', compact('user', 'config', 'kbmSuspendedEvent', 'unfilledSchedules', 'attendanceStats', 'todaySwaps', 'todayHasSchedules'));
        }

        return view('dashboard', compact('user', 'config', 'kbmSuspendedEvent'));
    }
}
