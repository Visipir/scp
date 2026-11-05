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
            3       => $this->kesiswaan($user, $config),
            default => $this->studentOrParent($user, $config, $attendance),
        };
    }

    private function guru(User $user, ?SystemConfiguration $config, DashboardService $dashboards): JsonResponse
    {
        $today = now();
        $day = $dashboards->indonesianDay($today);
        $activeCycle = $config?->active_cycle ?? 'Putaran 1';

        $swapService = app(\App\Services\ScheduleSwapService::class);
        $swapService->autoExpirePendingSwaps();
        $schedules = collect();
        if ($day !== null) {
            $schedules = $swapService->getEffectiveSchedulesForTeacher($user->id, $today, $activeCycle);
        }

        $filled = Journal::whereDate('date', $today->toDateString())
            ->whereIn('schedule_id', $schedules->pluck('id'))
            ->pluck('schedule_id')
            ->flip();

        $todaySchedules = $schedules->map(function (Schedule $schedule) use ($filled) {
            $item = (new ScheduleResource($schedule))->resolve() + ['journal_filled' => $filled->has($schedule->id)];
            if (!empty($schedule->is_swapped)) {
                $item['is_swapped'] = true;
                $item['partner_teacher'] = $schedule->partner_teacher?->fullname;
                $item['swap_id'] = $schedule->swap_id;
            }
            return $item;
        })->values()->all();

        $alerts = collect($dashboards->unfilledJournalAlerts($user->id, $config))
            ->map(fn (Schedule $schedule) => (new ScheduleResource($schedule))->resolve())
            ->values()->all();

        $pendingSwaps = \App\Models\ScheduleSwap::with(['requester', 'requesterSchedule.schoolClass', 'targetSchedule.schoolClass'])
            ->where('target_id', $user->id)
            ->where('status', 'pending')
            ->whereDate('swap_date', '>=', \Carbon\Carbon::today())
            ->get();

        $unreadNotifications = \App\Models\Notification::where('user_id', $user->id)
            ->whereNull('read_at')
            ->count();

        return ApiResponse::data([
            'today_schedules'        => $todaySchedules,
            'late_journal_alerts'    => $alerts,
            'pending_swap_requests'  => $pendingSwaps,
            'unread_notifications'   => $unreadNotifications,
            'active_cycle'           => $activeCycle,
            'is_schedule_locked'     => (bool) ($config?->is_schedule_locked ?? false),
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

        $todaySwaps = app(\App\Services\ScheduleSwapService::class)->getTodayApprovedSwapsForDashboard();

        return ApiResponse::data([
            'attendance_today'   => $dashboards->attendanceTodayCounts(),
            'unfilled_journals'  => $unfilled,
            'today_swaps'        => [
                'today_count'   => $todaySwaps['today_count'],
                'pending_count' => $todaySwaps['pending_count'],
                'approved'      => \App\Http\Resources\Api\V1\ScheduleSwapResource::collection($todaySwaps['approved'])->resolve(),
            ],
            'active_cycle'       => $config?->active_cycle ?? 'Putaran 1',
            'is_schedule_locked' => (bool) ($config?->is_schedule_locked ?? false),
        ]);
    }

    private function kesiswaan(User $user, ?SystemConfiguration $config): JsonResponse
    {
        $count = LeaveRequest::visibleTo($user)
            ->where('status_approval', 'Pending')
            ->count();

        return ApiResponse::data([
            'pending_leave_count' => $count,
            'active_cycle'        => $config?->active_cycle ?? 'Putaran 1',
            'is_schedule_locked'  => (bool) ($config?->is_schedule_locked ?? false),
        ]);
    }

    private function studentOrParent(User $user, ?SystemConfiguration $config, AttendanceSummaryService $attendance): JsonResponse
    {
        $target = $attendance->resolveTargetStudent($user, null);
        $month = now()->format('Y-m');

        ['summary' => $summary] = $attendance->forStudent($target['student_id'], $month);

        return ApiResponse::data([
            'student'            => (new UserResource($target['student']))->resolve(),
            'month'              => $month,
            'summary'            => $summary,
            'active_cycle'       => $config?->active_cycle ?? 'Putaran 1',
            'is_schedule_locked' => (bool) ($config?->is_schedule_locked ?? false),
        ]);
    }
}
