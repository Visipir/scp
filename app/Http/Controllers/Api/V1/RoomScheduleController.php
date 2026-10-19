<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreRoomScheduleRequest;
use App\Http\Resources\Api\V1\RoomScheduleResource;
use App\Models\RoomSchedule;
use App\Services\RoomScheduleService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RoomScheduleController extends Controller
{
    public function __construct(
        private readonly RoomScheduleService $roomScheduleService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['day_of_week', 'cycle_type', 'room_id', 'class_id']);
        $schedules = $this->roomScheduleService->listRoomSchedules($filters);

        return ApiResponse::list($schedules, RoomScheduleResource::class);
    }

    public function store(StoreRoomScheduleRequest $request): JsonResponse
    {
        $created = $this->roomScheduleService->createRoomSchedule($request->validated());

        return ApiResponse::item(new RoomScheduleResource($created), 201);
    }

    public function destroy(Request $request, RoomSchedule $roomSchedule): JsonResponse
    {
        $this->roomScheduleService->deleteRoomSchedule($roomSchedule);

        return response()->json([
            'message' => 'Jadwal ruangan berhasil dihapus.',
        ]);
    }
}
