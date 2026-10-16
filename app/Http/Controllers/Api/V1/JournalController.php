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
        $this->authorizeOwner($request, $schedule);

        $date = (string) $request->input('date');

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
        $this->authorizeOwner($request, $schedule);

        $journal = $journals->create($schedule, $validated, $request->file('photo'));
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

        if ((int) $journal->schedule?->teacher_id !== (int) $request->user()->id) {
            abort(403, 'Akses ditolak.');
        }

        $attendance->updateForJournalByStudent($journal, $request->validated()['attendances']);

        $journal->load('attendances.student')->loadCount(self::countSelects());

        return ApiResponse::item(new JournalDetailResource($journal));
    }

    private function authorizeOwner(Request $request, Schedule $schedule): void
    {
        if ((int) $schedule->teacher_id !== (int) $request->user()->id) {
            abort(403, 'Anda tidak memiliki akses ke jadwal ini.');
        }
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
