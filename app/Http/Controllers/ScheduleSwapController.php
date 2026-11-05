<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreScheduleSwapRequest;
use App\Http\Resources\Api\V1\ScheduleSwapResource;
use App\Models\Schedule;
use App\Models\ScheduleSwap;
use App\Models\SchoolClass;
use App\Models\SystemConfiguration;
use App\Models\User;
use App\Services\ScheduleSwapService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

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

        // Role 1 (Waka): Comprehensive Report View
        if ((int) $user->role_id === 1) {
            $filters = $request->only(['start_date', 'end_date', 'status', 'cycle', 'teacher_id', 'class_id', 'search']);
            $activeTab = $request->input('tab', 'swaps'); // 'swaps', 'teachers', 'logs'

            $report = $this->swapService->getWakaSwapReport($filters);
            $summary = $report['summary'];
            $swaps = $report['swaps']->appends($request->query());
            $activeFilters = $report['filters'];

            $teacherRecap = $this->swapService->getWakaTeacherRecap($filters);
            $activityLogs = $this->swapService->getWakaSwapLogs($filters)->appends($request->query());

            $teachers = User::where('role_id', 2)->orderBy('fullname')->get();
            $classes = SchoolClass::orderBy('class_name')->get();

            return view('schedules.swaps.report', compact(
                'summary',
                'swaps',
                'teacherRecap',
                'activityLogs',
                'activeFilters',
                'activeTab',
                'teachers',
                'classes',
                'isScheduleLocked'
            ));
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

    public function show(ScheduleSwap $scheduleSwap): JsonResponse
    {
        $swap = $this->swapService->getWakaSwapDetail($scheduleSwap->id);

        return response()->json([
            'swap' => (new ScheduleSwapResource($swap))->resolve(),
        ]);
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $user = $request->user();
        if ((int) $user->role_id !== 1) {
            abort(403, 'Akses khusus Waka Kurikulum.');
        }

        $filters = $request->only(['start_date', 'end_date', 'status', 'cycle', 'teacher_id', 'class_id', 'search']);
        $report = $this->swapService->getWakaSwapReport($filters, 10000);
        $swaps = $report['swaps'];

        $filename = 'laporan-tukar-jadwal-' . Carbon::now('Asia/Jakarta')->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($swaps, $report) {
            $handle = fopen('php://output', 'w');
            // UTF-8 BOM
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($handle, ['LAPORAN PERTUKARAN JADWAL GURU - SMA PGRI 1 KUDUS']);
            fputcsv($handle, ['Waktu Cetak / Unduh:', Carbon::now('Asia/Jakarta')->translatedFormat('d F Y H:i:s') . ' WIB']);
            fputcsv($handle, ['Periode Filter:', $report['filters']['start_date'] . ' s/d ' . $report['filters']['end_date']]);
            fputcsv($handle, []);
            fputcsv($handle, [
                'ID',
                'Tanggal Pelaksanaan',
                'Putaran',
                'Guru Pemohon',
                'Mapel Pemohon',
                'Kelas Pemohon',
                'Jam Mengajar Pemohon',
                'Guru Target',
                'Mapel Target',
                'Kelas Target',
                'Jam Mengajar Target',
                'Status',
                'Jurnal Terisi',
                'Waktu Diajukan',
                'Waktu Diputuskan',
                'Catatan / Alasan',
            ]);

            foreach ($swaps as $s) {
                fputcsv($handle, [
                    $s->id,
                    $s->swap_date?->format('d/m/Y'),
                    $s->cycle,
                    $s->requester?->fullname,
                    $s->requesterSchedule?->subject,
                    $s->requesterSchedule?->schoolClass?->class_name,
                    $s->requesterSchedule ? substr($s->requesterSchedule->start_time, 0, 5) . '-' . substr($s->requesterSchedule->end_time, 0, 5) : '-',
                    $s->target?->fullname,
                    $s->targetSchedule?->subject,
                    $s->targetSchedule?->schoolClass?->class_name,
                    $s->targetSchedule ? substr($s->targetSchedule->start_time, 0, 5) . '-' . substr($s->targetSchedule->end_time, 0, 5) : '-',
                    match($s->status) {
                        'approved'  => 'Disetujui',
                        'pending'   => 'Menunggu',
                        'rejected'  => 'Ditolak',
                        'cancelled' => 'Dibatalkan',
                        'expired'   => 'Kedaluwarsa',
                        default     => $s->status
                    },
                    $s->isJournalFilled() ? 'Sudah Diisi' : 'Belum Diisi',
                    $s->created_at?->timezone('Asia/Jakarta')->format('d/m/Y H:i'),
                    $s->responded_at?->timezone('Asia/Jakarta')->format('d/m/Y H:i') ?? '-',
                    $s->note ?? '-',
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function printReport(Request $request): View
    {
        $user = $request->user();
        if ((int) $user->role_id !== 1) {
            abort(403, 'Akses khusus Waka Kurikulum.');
        }

        $filters = $request->only(['start_date', 'end_date', 'status', 'cycle', 'teacher_id', 'class_id', 'search']);
        $report = $this->swapService->getWakaSwapReport($filters, 1000);
        $summary = $report['summary'];
        $swaps = $report['swaps'];
        $activeFilters = $report['filters'];

        return view('schedules.swaps.print', compact('summary', 'swaps', 'activeFilters'));
    }

    public function candidates(Request $request): JsonResponse
    {
        $request->validate([
            'date'        => ['required', 'date'],
            'schedule_id' => ['required', 'integer'],
        ]);

        try {
            $candidates = $this->swapService->getCandidates(
                $request->user(),
                (int) $request->input('schedule_id'),
                (string) $request->input('date')
            );

            return response()->json([
                'candidates' => $candidates,
            ]);
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            return response()->json([
                'message'    => $e->getMessage(),
                'candidates' => [],
            ], $e->getStatusCode());
        }
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
