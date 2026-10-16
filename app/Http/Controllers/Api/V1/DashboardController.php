<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ScheduleResource;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\Journal;
use App\Models\LeaveRequest;
use App\Models\Schedule;
use App\Models\SystemConfiguration;
use App\Models\User;
use App\Services\AttendanceSummaryService;
use App\Services\DashboardService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request, DashboardService $dashboards, AttendanceSummaryService $attendance): JsonResponse
    {
        $user = $request->user();
        $config = SystemConfiguration::first();

        return match ((int) $user->role_id) {
            1       => $this->waka($config, $dashboards),
            2       => $this->guru($user, $config, $dashboards),
            3       => $this->kesiswaan($user),
            default => $this->studentOrParent($user, $attendance),
        };
    }

    private function guru(User $user, ?SystemConfiguration $config, DashboardService $dashboards): JsonResponse
    {
        $today = now();
        $day = $dashboards->indonesianDay($today);
        $activeCycle = $config?->active_cycle ?? 'Putaran 1';

        $schedules = collect();
        if ($day !== null) {
            $schedules = Schedule::with(['schoolClass', 'room'])
                ->where('teacher_id', $user->id)
                ->where('day_of_week', $day)
                ->where('cycle_type', $activeCycle)
                ->orderBy('start_time')
                ->get();
        }

        $filled = Journal::whereDate('date', $today->toDateString())
            ->whereIn('schedule_id', $schedules->pluck('id'))
            ->pluck('schedule_id')
            ->flip();

        $todaySchedules = $schedules->map(function (Schedule $schedule) use ($filled) {
            return (new ScheduleResource($schedule))->resolve() + ['journal_filled' => $filled->has($schedule->id)];
        })->values()->all();

        $alerts = collect($dashboards->unfilledJournalAlerts($user->id, $config))
            ->map(fn (Schedule $schedule) => (new ScheduleResource($schedule))->resolve())
            ->values()->all();

        return ApiResponse::data([
            'today_schedules'     => $todaySchedules,
            'late_journal_alerts' => $alerts,
        ]);
    }

    private function waka(?SystemConfiguration $config, DashboardService $dashboards): JsonResponse
    {
        $unfilled = collect($dashboards->unfilledJournalsForTeachers($config))
            ->map(function (array $entry) {
                $schedules = $entry['schedules'];

                return [
                    'teacher_id' => $entry['teacher_id'],
                    'teacher'    => $schedules[0]->teacher?->fullname,
                    'schedules'  => collect($schedules)
                        ->map(fn (Schedule $schedule) => (new ScheduleResource($schedule))->resolve())
                        ->all(),
                ];
            })
            ->values()->all();

        return ApiResponse::data([
            'attendance_today'  => $dashboards->attendanceTodayCounts(),
            'unfilled_journals' => $unfilled,
        ]);
    }

    private function kesiswaan(User $user): JsonResponse
    {
        $count = LeaveRequest::visibleTo($user)
            ->where('status_approval', 'Pending')
            ->count();

        return ApiResponse::data(['pending_leave_count' => $count]);
    }

    private function studentOrParent(User $user, AttendanceSummaryService $attendance): JsonResponse
    {
        $target = $attendance->resolveTargetStudent($user, null);
        $month = now()->format('Y-m');

        ['summary' => $summary] = $attendance->forStudent($target['student_id'], $month);

        return ApiResponse::data([
            'student' => (new UserResource($target['student']))->resolve(),
            'month'   => $month,
            'summary' => $summary,
        ]);
    }
}
