<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreScheduleRequest;
use App\Http\Requests\UpdateScheduleRequest;
use App\Models\Room;
use App\Models\Schedule;
use App\Models\SchoolClass;
use App\Models\User;
use App\Models\SystemConfiguration;
use App\Services\LessonHourService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ScheduleController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user() ?? auth()->user();
        $config = SystemConfiguration::first();
        $cycleFilter = $request->query('cycle', $config?->active_cycle ?? 'Putaran 1');

        $query = Schedule::with(['teacher', 'schoolClass', 'room'])
            ->where('cycle_type', $cycleFilter);

        // Jika Guru (role 2), hanya tampilkan jadwal yang terhubung dengan guru tersebut
        if ($user?->role_id === 2) {
            $query->where('teacher_id', $user->id);
        } elseif ($user?->role_id === 1 && $request->filled('teacher_id')) {
            $query->where('teacher_id', (int) $request->query('teacher_id'));
        }

        if ($user?->role_id === 1 && $request->filled('class_id')) {
            $query->where('class_id', (int) $request->query('class_id'));
        }

        $schedules = $query
            ->orderByRaw("CASE day_of_week WHEN 'Senin' THEN 1 WHEN 'Selasa' THEN 2 WHEN 'Rabu' THEN 3 WHEN 'Kamis' THEN 4 WHEN 'Jumat' THEN 5 WHEN 'Sabtu' THEN 6 END")
            ->orderBy('start_time')
            ->get();

        $classes = SchoolClass::orderBy('class_name')->get();
        $teachers = ($user?->role_id === 1) ? User::where('role_id', 2)->orderBy('fullname')->get() : collect();
        $scheduleMatrix = LessonHourService::getAllMatrix();

        $dayMap = [
            'Monday' => 'Senin', 'Tuesday' => 'Selasa', 'Wednesday' => 'Rabu',
            'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu', 'Sunday' => 'Minggu',
        ];
        $todayName = $dayMap[now()->format('l')] ?? 'Senin';
        $defaultDay = $request->query('day', in_array($todayName, ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu']) ? $todayName : 'all');

        return view('schedules.index', compact(
            'schedules', 'config', 'cycleFilter', 'scheduleMatrix', 'classes', 'teachers', 'todayName', 'defaultDay'
        ));
    }

    public function create(): View
    {
        $classes = SchoolClass::all();
        $rooms = Room::all();
        $teachers = \App\Models\User::where('role_id', 2)->orderBy('fullname')->get();
        $config = SystemConfiguration::first();
        $scheduleMatrix = LessonHourService::getAllMatrix();

        return view('schedules.create', compact('classes', 'rooms', 'teachers', 'config', 'scheduleMatrix'));
    }

    public function store(StoreScheduleRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $user = $request->user();
        $teacherId = ($user->role_id === 1 && !empty($validated['teacher_id']))
            ? (int) $validated['teacher_id']
            : $user->id;

        $conflict = $this->findConflict($validated, $teacherId);
        if ($conflict) {
            return back()->withInput()->withErrors($conflict);
        }

        Schedule::create([...$validated, 'teacher_id' => $teacherId]);

        return redirect()->route('schedules.index')
            ->with('success', 'Jadwal blok jam pelajaran berhasil ditambahkan.');
    }

    public function edit(Schedule $schedule): View
    {
        $this->authorizeTeacher($schedule);

        $classes = SchoolClass::all();
        $rooms = Room::all();
        $teachers = \App\Models\User::where('role_id', 2)->orderBy('fullname')->get();
        $config = SystemConfiguration::first();
        $scheduleMatrix = LessonHourService::getAllMatrix();

        return view('schedules.edit', compact('schedule', 'classes', 'rooms', 'teachers', 'config', 'scheduleMatrix'));
    }

    public function update(UpdateScheduleRequest $request, Schedule $schedule): RedirectResponse
    {
        $this->authorizeTeacher($schedule);

        $validated = $request->validated();
        $user = $request->user();

        // If Waka Kurikulum (role 1) specifies a teacher_id, use it; otherwise preserve schedule's teacher
        $teacherId = ($user->role_id === 1 && !empty($validated['teacher_id']))
            ? (int) $validated['teacher_id']
            : $schedule->teacher_id;

        $conflict = $this->findConflict($validated, $teacherId, $schedule->id);
        if ($conflict) {
            return back()->withInput()->withErrors($conflict);
        }

        $data = $validated;
        if ($user->role_id === 1 && !empty($validated['teacher_id'])) {
            $data['teacher_id'] = $teacherId;
        } else {
            unset($data['teacher_id']);
        }

        $schedule->update($data);

        return redirect()->route('schedules.index')
            ->with('success', 'Jadwal berhasil diperbarui.');
    }

    public function destroy(Schedule $schedule): RedirectResponse
    {
        $this->authorizeTeacher($schedule);

        $schedule->delete();

        return redirect()->route('schedules.index')
            ->with('success', 'Jadwal berhasil dihapus.');
    }

    /**
     * Check for teacher/room/class time overlap conflicts.
     * Returns error array if conflict found, null otherwise.
     */
    private function findConflict(array $data, int $teacherId, ?int $excludeId = null): ?array
    {
        $baseQuery = fn () => Schedule::where('day_of_week', $data['day_of_week'])
            ->where('cycle_type', $data['cycle_type'])
            ->where('start_time', '<', $data['end_time'])->where('end_time', '>', $data['start_time'])
            ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId));

        // Teacher conflict
        if ($baseQuery()->where('teacher_id', $teacherId)->exists()) {
            return ['start_time' => 'Konflik jadwal: Guru sudah memiliki jadwal di waktu yang sama.'];
        }

        // Room conflict
        if ($baseQuery()->where('room_id', $data['room_id'])->exists()) {
            return ['room_id' => 'Konflik jadwal: Ruangan sudah digunakan di waktu yang sama.'];
        }

        // Class conflict
        if ($baseQuery()->where('class_id', $data['class_id'])->exists()) {
            return ['class_id' => 'Konflik jadwal: Kelas sudah memiliki jadwal di waktu yang sama.'];
        }

        return null;
    }

    private function authorizeTeacher(Schedule $schedule): void
    {
        $user = request()->user();
        if ($user->role_id !== 1 && $schedule->teacher_id !== $user->id) {
            abort(403, 'Anda tidak memiliki akses ke jadwal ini.');
        }
    }
}
