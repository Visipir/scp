<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreScheduleSwapRequest;
use App\Http\Resources\Api\V1\ScheduleSwapLogResource;
use App\Http\Resources\Api\V1\ScheduleSwapResource;
use App\Models\ScheduleSwap;
use App\Services\ScheduleSwapService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ScheduleSwapController extends Controller
{
    public function __construct(
        private readonly ScheduleSwapService $swapService
    ) {}

    public function wakaReport(Request $request): JsonResponse
    {
        if ((int) $request->user()->role_id !== 1) {
            abort(403, 'Akses khusus Waka Kurikulum.');
        }

        $filters = $request->only(['start_date', 'end_date', 'status', 'cycle', 'teacher_id', 'class_id', 'search']);
        $report = $this->swapService->getWakaSwapReport($filters, (int) $request->input('per_page', 25));

        return ApiResponse::data([
            'summary'    => $report['summary'],
            'filters'    => $report['filters'],
            'swaps'      => ScheduleSwapResource::collection($report['swaps'])->resolve(),
            'pagination' => [
                'current_page' => $report['swaps']->currentPage(),
                'last_page'    => $report['swaps']->lastPage(),
                'per_page'     => $report['swaps']->perPage(),
                'total'        => $report['swaps']->total(),
            ],
        ]);
    }

    public function wakaShow(Request $request, int $id): JsonResponse
    {
        if ((int) $request->user()->role_id !== 1) {
            abort(403, 'Akses khusus Waka Kurikulum.');
        }

        $swap = $this->swapService->getWakaSwapDetail($id);

        return ApiResponse::item(new ScheduleSwapResource($swap));
    }

    public function wakaLogs(Request $request): JsonResponse
    {
        if ((int) $request->user()->role_id !== 1) {
            abort(403, 'Akses khusus Waka Kurikulum.');
        }

        $filters = $request->only(['event', 'swap_id', 'search']);
        $logs = $this->swapService->getWakaSwapLogs($filters, (int) $request->input('per_page', 25));

        return ApiResponse::data([
            'logs'       => ScheduleSwapLogResource::collection($logs)->resolve(),
            'pagination' => [
                'current_page' => $logs->currentPage(),
                'last_page'    => $logs->lastPage(),
                'per_page'     => $logs->perPage(),
                'total'        => $logs->total(),
            ],
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if ((int) $user->role_id === 1) {
            $all = $this->swapService->listAllSwapsForWaka();
            return ApiResponse::data([
                'swaps' => ScheduleSwapResource::collection($all)->resolve(),
            ]);
        }

        $list = $this->swapService->listSwapsForUser($user);

        return ApiResponse::data([
            'inbox'         => ScheduleSwapResource::collection($list['inbox'])->resolve(),
            'outbox'        => ScheduleSwapResource::collection($list['outbox'])->resolve(),
            'pending_count' => $list['pending_count'],
        ]);
    }

    public function candidates(Request $request): JsonResponse
    {
        $request->validate([
            'date'        => ['required', 'date'],
            'schedule_id' => ['required', 'integer'],
        ], [
            'date.required'        => 'Tanggal pertukaran wajib diisi.',
            'schedule_id.required' => 'Jadwal yang ingin ditukar wajib dipilih.',
        ]);

        $candidates = $this->swapService->getCandidates(
            $request->user(),
            (int) $request->input('schedule_id'),
            (string) $request->input('date')
        );

        return ApiResponse::data([
            'candidates' => $candidates,
        ]);
    }

    public function store(StoreScheduleSwapRequest $request): JsonResponse
    {
        $swap = $this->swapService->createSwapRequest($request->user(), $request->validated());

        return ApiResponse::item(new ScheduleSwapResource($swap), 201);
    }

    public function approve(Request $request, ScheduleSwap $scheduleSwap): JsonResponse
    {
        $swap = $this->swapService->approveSwap($request->user(), $scheduleSwap);

        return ApiResponse::item(new ScheduleSwapResource($swap), 200);
    }

    public function reject(Request $request, ScheduleSwap $scheduleSwap): JsonResponse
    {
        $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $swap = $this->swapService->rejectSwap(
            $request->user(),
            $scheduleSwap,
            $request->input('reason')
        );

        return ApiResponse::item(new ScheduleSwapResource($swap), 200);
    }

    public function cancel(Request $request, ScheduleSwap $scheduleSwap): JsonResponse
    {
        $swap = $this->swapService->cancelSwap($request->user(), $scheduleSwap);

        return ApiResponse::item(new ScheduleSwapResource($swap), 200);
    }
}
