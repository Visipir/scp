<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreJournalRequest;
use App\Models\IneffectiveDay;
use App\Models\Journal;
use App\Models\LeaveRequest;
use App\Models\Schedule;
use App\Models\User;
use App\Services\JournalService;
use Illuminate\Http\JsonResponse;
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
        $defaultDate = $request->query('date', \Carbon\Carbon::today('Asia/Jakarta')->format('Y-m-d'));
        $swapService = app(\App\Services\ScheduleSwapService::class);
        $schedules = $swapService->getEffectiveSchedulesForTeacher($user->id, $defaultDate);

        if ($schedules->isEmpty()) {
            $schedules = Schedule::with(['schoolClass', 'room'])
                ->where('teacher_id', $user->id)
                ->get();
        }

        // Exclude schedules that already have a journal on defaultDate
        $filledScheduleIds = Journal::whereDate('date', $defaultDate)
            ->whereIn('schedule_id', $schedules->pluck('id'))
            ->pluck('schedule_id')
            ->toArray();

        $schedules = $schedules->reject(fn ($s) => in_array($s->id, $filledScheduleIds))->values();

        $kbmSuspendedEvent = \App\Services\AcademicCalendarService::isKbmSuspendedToday(\Carbon\Carbon::today('Asia/Jakarta')->toDateString());

        $now = \Carbon\Carbon::now('Asia/Jakarta');
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

        // Default schedule: query parameter -> current ongoing -> next upcoming -> first today (null if none today)
        $targetScheduleId = $request->query('schedule_id');
        $defaultSchedule = ($targetScheduleId ? $schedules->firstWhere('id', (int) $targetScheduleId) : null)
            ?? $currentSchedule 
            ?? $todaySchedules->first(fn ($sched) => $sched->start_time >= $currentTime)
            ?? $todaySchedules->first();

        $defaultDate = $request->query('date', $now->format('Y-m-d'));

        $classIds = $schedules->pluck('class_id')->unique()->filter();
        $studentsByClass = User::whereIn('class_id', $classIds)
            ->where('role_id', 4) // Siswa
            ->orderBy('fullname')
            ->get(['id', 'fullname', 'username', 'gender', 'class_id'])
            ->groupBy('class_id');

        $allStudentIds = $studentsByClass->flatten(1)->pluck('id')->all();
        $approvedLeaves = LeaveRequest::with(['decidedBy', 'verifier'])
            ->where('status_approval', 'Approved')
            ->whereIn('student_id', $allStudentIds)
            ->whereDate('start_date', '<=', $defaultDate)
            ->whereDate('end_date', '>=', $defaultDate)
            ->get()
            ->keyBy('student_id');

        $initialAbsentStudents = old('absent_students');
        if ($initialAbsentStudents === null && $defaultSchedule) {
            $classStudents = $studentsByClass->get($defaultSchedule->class_id, collect());
            $initialAbsentStudents = [];
            foreach ($classStudents as $std) {
                if ($leave = $approvedLeaves->get($std->id)) {
                    $initialAbsentStudents[] = [
                        'student_id'        => (string) $std->id,
                        'status'            => $leave->attendance_status,
                        'notes'             => 'Izin resmi (Pengajuan surat izin disetujui)',
                        'is_approved_leave' => true,
                        'leave_id'          => $leave->id,
                        'leave_reason'      => $leave->reason,
                        'decision_label'    => $leave->decisionLabel(),
                    ];
                }
            }
        }

        return view('journals.create', compact('schedules', 'kbmSuspendedEvent', 'defaultSchedule', 'defaultDate', 'studentsByClass', 'todayName', 'todaySchedules', 'initialAbsentStudents'));
    }

    /**
     * Fetch approved leaves for a schedule and date (JSON for dynamic journal form sync).
     */
    public function leaves(Request $request): JsonResponse
    {
        $scheduleId = $request->query('schedule_id');
        $date = $request->query('date', now('Asia/Jakarta')->toDateString());

        if (! $scheduleId) {
            return response()->json(['leaves' => []]);
        }

        $schedule = Schedule::find($scheduleId);
        if (! $schedule) {
            return response()->json(['leaves' => []]);
        }

        $studentIds = User::where('class_id', $schedule->class_id)
            ->where('role_id', 4)
            ->pluck('id');

        $leaves = LeaveRequest::with(['decidedBy', 'verifier'])
            ->where('status_approval', 'Approved')
            ->whereIn('student_id', $studentIds)
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)
            ->get();

        $data = $leaves->map(function ($leave) {
            return [
                'student_id'        => (string) $leave->student_id,
                'status'            => $leave->attendance_status,
                'notes'             => 'Izin resmi (Pengajuan surat izin disetujui)',
                'is_approved_leave' => true,
                'leave_id'          => (int) $leave->id,
                'leave_reason'      => $leave->reason,
                'decision_label'    => $leave->decisionLabel(),
            ];
        });

        return response()->json(['leaves' => $data]);
    }

    public function store(StoreJournalRequest $request, JournalService $journals): RedirectResponse
    {
        $validated = $request->validated();
        $schedule = Schedule::findOrFail($validated['schedule_id']);
        $date = $validated['date'];

        $swapService = app(\App\Services\ScheduleSwapService::class);
        $check = $swapService->canTeacherFillJournal($request->user()->id, $schedule->id, $date);
        if (! $check['allowed']) {
            abort(403, 'Anda bukan guru pengajar efektif untuk jadwal ini pada tanggal tersebut.');
        }

        $journals->create($schedule, $validated, $request->file('photo'), $request->user()->id, $check['swap_id']);

        return redirect()->route('journals.index')
            ->with('success', 'Jurnal berhasil dibuat dan presensi otomatis telah diisi.');
    }

    public function update(Request $request, Journal $journal, JournalService $journals): RedirectResponse
    {
        $userId = (int) $request->user()->id;
        if ((int) $journal->schedule->teacher_id !== $userId && (int) $journal->teacher_id !== $userId) {
            abort(403, 'Akses ditolak.');
        }

        if (! \Carbon\Carbon::parse($journal->date)->isSameDay(now('Asia/Jakarta'))) {
            abort(422, 'Jurnal hanya dapat diubah pada hari yang sama.');
        }

        $validated = $request->validate([
            'topic_description' => ['sometimes', 'required', 'string', 'max:5000'],
            'photo'             => ['nullable', 'image', 'mimes:jpeg,png,jpg', 'max:5120'],
        ]);

        if (isset($validated['topic_description'])) {
            $journal->topic_description = $validated['topic_description'];
        }
        if ($request->hasFile('photo')) {
            $journal->photo_path = $journals->compressAndStorePhoto($request->file('photo'));
        }
        $journal->save();

        return redirect()->route('journals.show', $journal)
            ->with('success', 'Jurnal berhasil diperbarui.');
    }

    public function show(Request $request, Journal $journal): View
    {
        $journal->load(['schedule.schoolClass', 'schedule.room', 'attendances.student']);
        $user = $request->user();

        // Guru (role 2) only sees their own journals (master teacher or effective substitute teacher)
        if ($user->role_id === 2 
            && (int) $journal->schedule->teacher_id !== (int) $user->id 
            && (int) $journal->teacher_id !== (int) $user->id) {
            abort(403, 'Anda tidak memiliki akses ke jurnal ini.');
        }

        return view('journals.show', compact('journal'));
    }

    /**
     * Stream the private journal documentation photo to an authorized user (inline).
     */
    public function photo(Request $request, Journal $journal, JournalService $journals)
    {
        $journal->loadMissing('schedule');
        $user = $request->user();

        abort_unless($journals->canViewPhoto($user, $journal), 403, 'Akses ditolak.');

        abort_if(blank($journal->photo_path), 404);

        $disk = Storage::disk('local');
        abort_unless($disk->exists($journal->photo_path), 404);

        return $disk->response($journal->photo_path);
    }

}
