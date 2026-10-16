<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreJournalRequest;
use App\Models\IneffectiveDay;
use App\Models\Journal;
use App\Models\Schedule;
use App\Models\User;
use App\Services\JournalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class JournalController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $query = Journal::with(['schedule.schoolClass', 'schedule.room', 'schedule.teacher', 'attendances']);

        // Guru (role 2) only sees their own journals. Waka (role 1) and BK (role 3) supervise all journals
        if ($user->role_id === 2) {
            $query->whereHas('schedule', fn ($q) => $q->where('teacher_id', $user->id));
        }

        $journals = $query->latest('date')->paginate(15);

        return view('journals.index', compact('journals'));
    }

    public function create(Request $request): View
    {
        $user = $request->user();
        $schedules = Schedule::with(['schoolClass', 'room'])
            ->where('teacher_id', $user->id)
            ->get();

        $kbmSuspendedEvent = \App\Services\AcademicCalendarService::isKbmSuspendedToday(\Carbon\Carbon::today()->toDateString());

        $now = \Carbon\Carbon::now();
        $dayMap = [
            'Monday' => 'Senin', 'Tuesday' => 'Selasa', 'Wednesday' => 'Rabu',
            'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu', 'Sunday' => 'Minggu',
        ];
        $todayName = $dayMap[$now->format('l')] ?? 'Senin';
        $currentTime = $now->format('H:i:s');

        // Today's schedules for this teacher
        $todaySchedules = $schedules->where('day_of_week', $todayName);

        // Schedule ongoing right now (start_time <= now <= end_time)
        $currentSchedule = $todaySchedules->first(function ($sched) use ($currentTime) {
            return $sched->start_time <= $currentTime && $sched->end_time >= $currentTime;
        });

        // Default schedule: query parameter -> current ongoing -> next upcoming -> first today -> first overall
        $targetScheduleId = $request->query('schedule_id');
        $defaultSchedule = ($targetScheduleId ? $schedules->firstWhere('id', $targetScheduleId) : null)
            ?? $currentSchedule 
            ?? $todaySchedules->first(fn ($sched) => $sched->start_time >= $currentTime)
            ?? $todaySchedules->first()
            ?? $schedules->first();

        $defaultDate = $request->query('date', $now->format('Y-m-d'));

        $classIds = $schedules->pluck('class_id')->unique()->filter();
        $studentsByClass = User::whereIn('class_id', $classIds)
            ->where('role_id', 4) // Siswa
            ->orderBy('fullname')
            ->get(['id', 'fullname', 'username', 'gender', 'class_id'])
            ->groupBy('class_id');

        return view('journals.create', compact('schedules', 'kbmSuspendedEvent', 'defaultSchedule', 'defaultDate', 'studentsByClass'));
    }

    public function store(StoreJournalRequest $request, JournalService $journals): RedirectResponse
    {
        $validated = $request->validated();

        // Verify teacher owns this schedule
        $schedule = Schedule::findOrFail($validated['schedule_id']);
        if ($schedule->teacher_id !== $request->user()->id) {
            abort(403, 'Anda tidak memiliki akses ke jadwal ini.');
        }

        $journals->create($schedule, $validated, $request->file('photo'));

        return redirect()->route('journals.index')
            ->with('success', 'Jurnal berhasil dibuat dan presensi otomatis telah diisi.');
    }

    public function show(Request $request, Journal $journal): View
    {
        $journal->load(['schedule.schoolClass', 'schedule.room', 'attendances.student']);
        $user = $request->user();

        // Guru (role 2) only sees their own journals. Waka (role 1) and BK (role 3) can monitor all
        if ($user->role_id === 2 && $journal->schedule->teacher_id !== $user->id) {
            abort(403, 'Anda tidak memiliki akses ke jurnal ini.');
        }

        return view('journals.show', compact('journal'));
    }

    /**
     * Stream the private journal documentation photo to an authorized user (inline).
     */
    public function photo(Request $request, Journal $journal)
    {
        $journal->loadMissing('schedule');
        $user = $request->user();

        $allowed = (int) $user->role_id === 1
            || (int) $user->role_id === 3
            || ((int) $user->role_id === 2 && (int) $journal->schedule?->teacher_id === (int) $user->id);

        abort_unless($allowed, 403, 'Akses ditolak.');

        abort_if(blank($journal->photo_path), 404);

        $disk = Storage::disk('local');
        abort_unless($disk->exists($journal->photo_path), 404);

        return $disk->response($journal->photo_path);
    }

}
