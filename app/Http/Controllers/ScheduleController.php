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
use App\Services\ScheduleService;
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

    public function store(StoreScheduleRequest $request, ScheduleService $schedules): RedirectResponse
    {
        $schedules->create($request->user(), $request->validated());

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

    public function update(UpdateScheduleRequest $request, Schedule $schedule, ScheduleService $schedules): RedirectResponse
    {
        $schedules->update($request->user(), $schedule, $request->validated());

        return redirect()->route('schedules.index')
            ->with('success', 'Jadwal berhasil diperbarui.');
    }

    public function destroy(Schedule $schedule, ScheduleService $schedules): RedirectResponse
    {
        $schedules->authorizeTeacher(request()->user(), $schedule);

        $schedule->delete();

        return redirect()->route('schedules.index')
            ->with('success', 'Jadwal berhasil dihapus.');
    }
}
