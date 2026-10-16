<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\SystemCycleRequest;
use App\Http\Requests\Api\V1\SystemLockRequest;
use App\Http\Resources\Api\V1\ScheduleResource;
use App\Models\Schedule;
use App\Models\SystemConfiguration;
use App\Services\DashboardService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SystemController extends Controller
{
    public function cycle(SystemCycleRequest $request): JsonResponse
    {
        $config = SystemConfiguration::firstOrFail();

        $config->update([
            'active_cycle' => $request->input('active_cycle'),
            'updated_by'   => $request->user()->id,
        ]);

        return ApiResponse::data(['active_cycle' => $config->active_cycle]);
    }

    public function lock(SystemLockRequest $request): JsonResponse
    {
        $config = SystemConfiguration::firstOrFail();

        $config->update([
            'is_schedule_locked' => $request->boolean('is_schedule_locked'),
            'updated_by'         => $request->user()->id,
        ]);

        return ApiResponse::data(['is_schedule_locked' => (bool) $config->is_schedule_locked]);
    }

    public function unfilledJournals(Request $request, DashboardService $dashboards): JsonResponse
    {
        $config = SystemConfiguration::first();

        $teachers = collect($dashboards->unfilledJournalsForTeachers($config))
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

        return ApiResponse::data(['teachers' => $teachers]);
    }
}
