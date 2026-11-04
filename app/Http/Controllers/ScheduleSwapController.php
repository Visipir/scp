<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreScheduleSwapRequest;
use App\Models\Schedule;
use App\Models\ScheduleSwap;
use App\Models\SystemConfiguration;
use App\Services\ScheduleSwapService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ScheduleSwapController extends Controller
{
    public function __construct(
        private readonly ScheduleSwapService $swapService
    ) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        $config = SystemConfiguration::first();
        $isScheduleLocked = (bool) ($config?->is_schedule_locked);

        // Role 1 (Waka): Monitoring view of all swaps
        if ((int) $user->role_id === 1) {
            $swaps = $this->swapService->listAllSwapsForWaka();
            return view('schedules.swaps.monitoring', compact('swaps', 'isScheduleLocked'));
        }

        // Role 2 (Guru): Inbox and Outbox tabs + Swap request form
        $list = $this->swapService->listSwapsForUser($user);
        $inbox = $list['inbox'];
        $outbox = $list['outbox'];
        $pendingCount = $list['pending_count'];

        // Teacher's own active cycle schedules for form selection
        $activeCycle = $config?->active_cycle ?? 'Putaran 1';
        $mySchedules = Schedule::with(['schoolClass', 'room'])
            ->where('teacher_id', $user->id)
            ->where('cycle_type', $activeCycle)
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get();

        return view('schedules.swaps.index', compact(
            'inbox',
            'outbox',
            'pendingCount',
            'mySchedules',
            'activeCycle',
            'isScheduleLocked'
        ));
    }

    public function candidates(Request $request): JsonResponse
    {
        $request->validate([
            'date'        => ['required', 'date'],
            'schedule_id' => ['required', 'integer'],
        ]);

        $candidates = $this->swapService->getCandidates(
            $request->user(),
            (int) $request->input('schedule_id'),
            (string) $request->input('date')
        );

        return response()->json([
            'candidates' => $candidates,
        ]);
    }

    public function store(StoreScheduleSwapRequest $request): RedirectResponse
    {
        try {
            $this->swapService->createSwapRequest($request->user(), $request->validated());

            return redirect()->route('schedule-swaps.index')
                ->with('success', 'Permintaan pertukaran jadwal berhasil diajukan.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }

    public function approve(Request $request, ScheduleSwap $scheduleSwap): RedirectResponse
    {
        try {
            $this->swapService->approveSwap($request->user(), $scheduleSwap);

            return redirect()->back()
                ->with('success', 'Pertukaran jadwal berhasil disetujui.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', $e->getMessage());
        }
    }

    public function reject(Request $request, ScheduleSwap $scheduleSwap): RedirectResponse
    {
        try {
            $this->swapService->rejectSwap(
                $request->user(),
                $scheduleSwap,
                $request->input('reason')
            );

            return redirect()->back()
                ->with('success', 'Pertukaran jadwal telah ditolak.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', $e->getMessage());
        }
    }

    public function cancel(Request $request, ScheduleSwap $scheduleSwap): RedirectResponse
    {
        try {
            $this->swapService->cancelSwap($request->user(), $scheduleSwap);

            return redirect()->back()
                ->with('success', 'Permintaan pertukaran jadwal berhasil dibatalkan.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', $e->getMessage());
        }
    }
}
