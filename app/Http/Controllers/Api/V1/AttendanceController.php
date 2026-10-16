<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StudentAttendanceRequest;
use App\Http\Resources\Api\V1\AttendanceRecordResource;
use App\Http\Resources\Api\V1\UserResource;
use App\Services\AttendanceSummaryService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class AttendanceController extends Controller
{
    public function index(StudentAttendanceRequest $request, AttendanceSummaryService $service): JsonResponse
    {
        $user = $request->user();

        $target = $service->resolveTargetStudent(
            $user,
            $request->filled('student_id') ? (int) $request->input('student_id') : null,
        );

        $month = $request->input('month', now()->format('Y-m'));

        ['summary' => $summary, 'records' => $records] = $service->forStudent($target['student_id'], $month);

        return ApiResponse::data([
            'student' => (new UserResource($target['student']))->resolve(),
            'month'   => $month,
            'summary' => $summary,
            'records' => collect($records)
                ->map(fn ($record) => (new AttendanceRecordResource($record))->resolve())
                ->all(),
        ]);
    }
}
