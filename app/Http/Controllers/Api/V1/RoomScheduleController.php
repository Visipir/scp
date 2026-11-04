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
        $validated = $request->validate([
            'cycle'       => ['nullable', 'string', 'in:Putaran 1,Putaran 2'],
            'cycle_type'  => ['nullable', 'string', 'in:Putaran 1,Putaran 2'],
            'day_of_week' => ['nullable', 'string', 'in:Senin,Selasa,Rabu,Kamis,Jumat,Sabtu'],
            'room_id'     => ['nullable', 'integer', 'exists:rooms,id'],
            'class_id'    => ['nullable', 'integer', 'exists:classes,id'],
        ], [
            'cycle.in'      => 'Putaran jadwal tidak valid. Pilihan yang tersedia: Putaran 1 atau Putaran 2.',
            'cycle_type.in' => 'Putaran jadwal tidak valid. Pilihan yang tersedia: Putaran 1 atau Putaran 2.',
        ]);

        $filters = $request->only(['day_of_week', 'room_id', 'class_id']);
        if (!empty($validated['cycle_type'])) {
            $filters['cycle_type'] = $validated['cycle_type'];
        } elseif (!empty($validated['cycle'])) {
            $filters['cycle_type'] = $validated['cycle'];
        }

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
