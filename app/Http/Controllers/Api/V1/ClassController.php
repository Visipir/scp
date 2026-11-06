<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ExportClassAttendanceRequest;
use App\Http\Requests\Api\V1\StoreClassRequest;
use App\Http\Requests\Api\V1\UpdateClassRequest;
use App\Http\Resources\Api\V1\ClassDetailResource;
use App\Http\Resources\Api\V1\ClassResource;
use App\Models\SchoolClass;
use App\Services\SchoolClassService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ClassController extends Controller
{
    public function __construct(
        private readonly SchoolClassService $classService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['academic_year', 'grade_level', 'major', 'search', 'class_id']);

        if ($request->has('page') || $request->has('per_page')) {
            $classes = $this->classService->listClasses(
                $request->user(),
                $filters,
                (int) $request->input('per_page', 15)
            );

            return ApiResponse::paginated($classes, ClassResource::class);
        }

        $classes = $this->classService->listClasses($request->user(), $filters, null);

        return ApiResponse::list($classes, ClassResource::class);
    }

    public function store(StoreClassRequest $request): JsonResponse
    {
        $class = $this->classService->createClass($request->user(), $request->validated());

        return ApiResponse::item(new ClassResource($class), 201);
    }

    public function show(Request $request, SchoolClass $class): JsonResponse
    {
        $detail = $this->classService->getClassDetail(
            $request->user(),
            $class,
            $request->query('start_date'),
            $request->query('end_date'),
            $request->query('search')
        );

        return ApiResponse::item(new ClassDetailResource($detail));
    }

    public function update(UpdateClassRequest $request, SchoolClass $class): JsonResponse
    {
        $updated = $this->classService->updateClass($request->user(), $class, $request->validated());

        return ApiResponse::item(new ClassResource($updated));
    }

    public function destroy(Request $request, SchoolClass $class): JsonResponse
    {
        $className = $class->class_name;
        $this->classService->deleteClass($request->user(), $class);

        return response()->json([
            'message' => "Kelas {$className} berhasil dihapus.",
        ]);
    }

    public function attendance(Request $request, SchoolClass $class): JsonResponse
    {
        $request->validate([
            'month' => ['required', 'date_format:Y-m'],
        ], [
            'month.required'    => 'Parameter bulan wajib diisi.',
            'month.date_format' => 'Format bulan harus YYYY-MM (contoh: 2026-10).',
        ]);

        $recap = $this->classService->getMonthlyRecap($request->user(), $class, $request->input('month'));

        return response()->json([
            'data' => $recap,
        ]);
    }

    public function export(ExportClassAttendanceRequest $request, SchoolClass $class): StreamedResponse
    {
        return $this->classService->exportAttendanceStream(
            $request->user(),
            $class,
            $request->input('start_date'),
            $request->input('end_date')
        );
    }
}
