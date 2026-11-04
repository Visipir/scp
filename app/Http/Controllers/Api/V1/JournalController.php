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
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class JournalController extends Controller
{
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
