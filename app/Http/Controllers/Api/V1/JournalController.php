<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\PrepareJournalRequest;
use App\Http\Requests\Api\V1\UpdateAttendancesRequest;
use App\Http\Requests\StoreJournalRequest;
use App\Http\Resources\Api\V1\JournalDetailResource;
use App\Http\Resources\Api\V1\JournalResource;
use App\Http\Resources\Api\V1\ScheduleResource;
use App\Models\Journal;
use App\Models\Schedule;
use App\Services\AttendanceService;
use App\Services\JournalService;
use App\Services\ScheduleSwapService;
use App\Support\ApiResponse;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class JournalController extends Controller
{
    public function availableSchedules(Request $request): JsonResponse
    {
        $dateStr = trim((string) $request->input('date'));
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateStr)) {
            return ApiResponse::message('Format tanggal tidak valid (harus YYYY-MM-DD).', 422);
        }

        $today = Carbon::today('Asia/Jakarta');
        try {
            $date = Carbon::createFromFormat('Y-m-d', $dateStr, 'Asia/Jakarta')->startOfDay();
        } catch (\Throwable $e) {
            return ApiResponse::message('Format tanggal tidak valid (harus YYYY-MM-DD).', 422);
        }

        if ($date->greaterThan($today)) {
            return ApiResponse::message('Jurnal KBM tidak dapat dibuat untuk tanggal masa depan.', 422);
        }

        if ($date->lessThan($today->copy()->subDays(90))) {
            return ApiResponse::message('Tanggal di luar batas periode yang diizinkan (maksimal 90 hari ke belakang).', 422);
        }

        $user = $request->user();
        if ((int) $user->role_id !== 2) {
            return ApiResponse::message('Hanya guru yang dapat mengakses jadwal KBM.', 403);
        }

        $swapService = app(ScheduleSwapService::class);
        $schedules = $swapService->getEffectiveSchedulesForTeacher($user->id, $date);

        $filledScheduleIds = Journal::whereDate('date', $dateStr)
            ->whereIn('schedule_id', $schedules->pluck('id'))
            ->pluck('schedule_id')
            ->toArray();

        $available = $schedules->reject(fn ($s) => in_array($s->id, $filledScheduleIds))->values();

        $data = $available->map(function ($s) {
            $className = $s->schoolClass?->class_name ?? '';
            $roomName = $s->room?->room_name ?? null;
            $isSubstitute = (bool) ($s->is_substitute ?? false);
            $startTime = substr($s->start_time, 0, 5);
            $endTime = substr($s->end_time, 0, 5);
            $label = "{$s->subject} • {$className} • {$startTime}-{$endTime}" . ($isSubstitute ? ' (Pengganti)' : '');

            return [
                'schedule_id'   => (int) $s->id,
                'subject'       => (string) $s->subject,
                'class'         => (string) $className,
                'room'          => $roomName,
                'start_time'    => $startTime,
                'end_time'      => $endTime,
                'is_substitute' => $isSubstitute,
                'label'         => $label,
            ];
        })->all();

        return response()->json([
            'data' => $data,
            'meta' => [
                'total_schedules' => $schedules->count(),
                'filled'          => count($filledScheduleIds),
            ],
        ]);
    }
    public function index(Request $request, JournalService $journals): JsonResponse
    {
        $perPage = min(max((int) $request->input('per_page', 15), 1), 50);

        $query = Journal::with(['schedule.schoolClass', 'schedule.teacher'])
            ->withCount(self::countSelects());

        $journals->scopeForUser($request->user(), $query);

        $paginator = $query->latest('date')->latest('id')->paginate($perPage);

        return ApiResponse::paginated($paginator, JournalResource::class);
    }

    public function prepare(PrepareJournalRequest $request, JournalService $journals): JsonResponse
    {
        $schedule = Schedule::findOrFail($request->input('schedule_id'));
        $date = (string) $request->input('date');
        $this->authorizeOwner($request, $schedule, $date);

        return ApiResponse::data([
            'schedule' => (new ScheduleResource($schedule->load(['schoolClass', 'room'])))->resolve(),
            'date'     => $date,
            'students' => $journals->preparation($schedule, $date),
        ]);
    }

    public function store(StoreJournalRequest $request, JournalService $journals): JsonResponse
    {
        $validated = $request->validated();

        $schedule = Schedule::findOrFail($validated['schedule_id']);
        $date = (string) $validated['date'];
        $swapId = $this->authorizeOwner($request, $schedule, $date);

        $journal = $journals->create($schedule, $validated, $request->file('photo'), $request->user()->id, $swapId);
        $journal->load(['schedule.schoolClass'])->loadCount(self::countSelects());

        return ApiResponse::item(new JournalResource($journal), 201);
    }

    public function show(Request $request, Journal $journal, JournalService $journals): JsonResponse
    {
        $journal->load(['schedule.schoolClass', 'schedule.teacher', 'attendances.student']);

        abort_unless($journals->userCanView($request->user(), $journal), 403, 'Akses ditolak.');

        $journal->loadCount(self::countSelects());

        return ApiResponse::item(new JournalDetailResource($journal));
    }

    public function photo(Request $request, Journal $journal, JournalService $journals)
    {
        $journal->loadMissing('schedule');

        abort_unless($journals->canViewPhoto($request->user(), $journal), 403, 'Akses ditolak.');
        abort_if(blank($journal->photo_path), 404);

        $disk = Storage::disk('local');
        abort_unless($disk->exists($journal->photo_path), 404);

        return $disk->response($journal->photo_path);
    }

    public function updateAttendances(UpdateAttendancesRequest $request, Journal $journal, AttendanceService $attendance): JsonResponse
    {
        $journal->loadMissing('schedule');

        $userId = (int) $request->user()->id;
        if ((int) $journal->schedule?->teacher_id !== $userId && (int) $journal->teacher_id !== $userId) {
            abort(403, 'Akses ditolak.');
        }

        $attendance->updateForJournalByStudent($journal, $request->validated()['attendances']);

        $journal->load('attendances.student')->loadCount(self::countSelects());

        return ApiResponse::item(new JournalDetailResource($journal));
    }

    private function authorizeOwner(Request $request, Schedule $schedule, ?string $date = null): ?int
    {
        $checkDate = $date ?? now()->toDateString();
        $swapService = app(\App\Services\ScheduleSwapService::class);
        $check = $swapService->canTeacherFillJournal($request->user()->id, $schedule->id, $checkDate);

        if (! $check['allowed']) {
            abort(403, 'Anda bukan guru pengajar efektif untuk jadwal ini pada tanggal tersebut.');
        }

        return $check['swap_id'];
    }

    /**
     * @return array<mixed>
     */
    private static function countSelects(): array
    {
        return [
            'attendances',
            'attendances as hadir_count'     => fn ($q) => $q->where('status', 'Hadir'),
            'attendances as sakit_count'     => fn ($q) => $q->where('status', 'Sakit'),
            'attendances as izin_count'      => fn ($q) => $q->where('status', 'Izin'),
            'attendances as alpa_count'      => fn ($q) => $q->where('status', 'Alpa'),
            'attendances as terlambat_count' => fn ($q) => $q->where('status', 'Terlambat'),
        ];
    }
}
