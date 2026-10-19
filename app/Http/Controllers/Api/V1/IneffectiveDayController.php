<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreIneffectiveDayRequest;
use App\Http\Resources\Api\V1\IneffectiveDayResource;
use App\Models\IneffectiveDay;
use App\Services\IneffectiveDayService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IneffectiveDayController extends Controller
{
    public function __construct(
        private readonly IneffectiveDayService $ineffectiveDayService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $days = $this->ineffectiveDayService->listIneffectiveDays($request->query('academic_year'));

        return ApiResponse::list($days, IneffectiveDayResource::class);
    }

    public function store(StoreIneffectiveDayRequest $request): JsonResponse
    {
        $created = $this->ineffectiveDayService->createIneffectiveDays(
            $request->validated(),
            $request->user()?->id
        );

        if ($created->count() === 1) {
            return ApiResponse::item(new IneffectiveDayResource($created->first()), 201);
        }

        return response()->json([
            'data' => IneffectiveDayResource::collection($created),
        ], 201);
    }

    public function destroy(Request $request, IneffectiveDay $ineffectiveDay): JsonResponse
    {
        $this->ineffectiveDayService->deleteIneffectiveDay($ineffectiveDay);

        return response()->json([
            'message' => 'Hari tidak efektif berhasil dihapus.',
        ]);
    }
}
