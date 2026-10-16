<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ScheduleIndexRequest;
use App\Http\Requests\StoreScheduleRequest;
use App\Http\Requests\UpdateScheduleRequest;
use App\Http\Resources\Api\V1\ScheduleResource;
use App\Models\Schedule;
use App\Models\SystemConfiguration;
use App\Services\ScheduleService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class ScheduleController extends Controller
{
    public function index(ScheduleIndexRequest $request): JsonResponse
    {
        $user = $request->user();
        $config = SystemConfiguration::first();
        $cycle = $request->input('cycle', $config?->active_cycle ?? 'Putaran 1');

        $query = Schedule::with(['teacher', 'schoolClass', 'room'])
            ->where('cycle_type', $cycle);

        if ((int) $user->role_id === 2) {
            $query->where('teacher_id', $user->id);
        } elseif ((int) $user->role_id === 1 && $request->filled('teacher_id')) {
            $query->where('teacher_id', (int) $request->input('teacher_id'));
        }

        if ((int) $user->role_id === 1 && $request->filled('class_id')) {
            $query->where('class_id', (int) $request->input('class_id'));
        }

        if ($request->filled('day')) {
            $query->where('day_of_week', $request->input('day'));
        }

        $schedules = $query
            ->orderByRaw("CASE day_of_week WHEN 'Senin' THEN 1 WHEN 'Selasa' THEN 2 WHEN 'Rabu' THEN 3 WHEN 'Kamis' THEN 4 WHEN 'Jumat' THEN 5 WHEN 'Sabtu' THEN 6 ELSE 7 END")
            ->orderBy('start_time')
            ->get();

        return ApiResponse::list($schedules, ScheduleResource::class);
    }

    public function store(StoreScheduleRequest $request, ScheduleService $schedules): JsonResponse
    {
        $schedule = $schedules->create($request->user(), $request->validated());

        return ApiResponse::item(
            new ScheduleResource($schedule->load(['teacher', 'schoolClass', 'room'])),
            201,
        );
    }

    public function update(UpdateScheduleRequest $request, Schedule $schedule, ScheduleService $schedules): JsonResponse
    {
        $schedules->update($request->user(), $schedule, $request->validated());

        return ApiResponse::item(
            new ScheduleResource($schedule->fresh()->load(['teacher', 'schoolClass', 'room'])),
        );
    }

    public function destroy(Schedule $schedule, ScheduleService $schedules): JsonResponse
    {
        $schedules->authorizeTeacher(request()->user(), $schedule);

        $schedule->delete();

        return ApiResponse::message('Jadwal berhasil dihapus.');
    }
}
