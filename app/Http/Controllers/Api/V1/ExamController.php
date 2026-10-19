<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreExamRequest;
use App\Http\Requests\Api\V1\UpdateExamRequest;
use App\Http\Resources\Api\V1\ExamResource;
use App\Models\Exam;
use App\Services\ExamService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExamController extends Controller
{
    public function __construct(
        private readonly ExamService $examService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only([
            'category',
            'class_id',
            'subject',
            'search',
            'view_scope',
            'sort',
        ]);

        if ($request->has('page') || $request->has('per_page')) {
            $exams = $this->examService->listExams(
                $request->user(),
                $filters,
                (int) $request->input('per_page', 15)
            );

            return ApiResponse::paginated($exams, ExamResource::class);
        }

        $exams = $this->examService->listExams($request->user(), $filters, null);

        return ApiResponse::list($exams, ExamResource::class);
    }

    public function store(StoreExamRequest $request): JsonResponse
    {
        $created = $this->examService->createExams(
            $request->user(),
            $request->validated(),
            $request->file('material_file')
        );

        if ($created->count() === 1) {
            return ApiResponse::item(new ExamResource($created->first()), 201);
        }

        return response()->json([
            'data' => ExamResource::collection($created),
        ], 201);
    }

    public function show(Request $request, Exam $exam): JsonResponse
    {
        $detailed = $this->examService->getExamDetail($request->user(), $exam);

        return ApiResponse::item(new ExamResource($detailed));
    }

    public function update(UpdateExamRequest $request, Exam $exam): JsonResponse
    {
        $updated = $this->examService->updateExam(
            $request->user(),
            $exam,
            $request->validated(),
            $request->file('material_file'),
            $request->boolean('remove_file')
        );

        return ApiResponse::item(new ExamResource($updated));
    }

    public function destroy(Request $request, Exam $exam): JsonResponse
    {
        $this->examService->deleteExam($request->user(), $exam);

        return response()->json([
            'message' => 'Materi/ujian berhasil dihapus.',
        ]);
    }

    public function download(Request $request, Exam $exam): StreamedResponse
    {
        return $this->examService->downloadExam($request->user(), $exam);
    }
}
