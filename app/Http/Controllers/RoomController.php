<?php

namespace App\Http\Controllers;

use App\Models\Room;
use App\Models\RoomSchedule;
use App\Models\Schedule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RoomController extends Controller
{
    /**
     * Display a listing of all registered rooms.
     */
    public function index(Request $request): View
    {
        $search = $request->query('search');

        $query = Room::query();
        if ($search) {
            $query->where('room_name', 'like', "%{$search}%");
        }

        $rooms = $query->withCount(['schedules', 'roomSchedules'])
            ->orderBy('room_name')
            ->paginate(15)
            ->withQueryString();

        return view('rooms.index', compact('rooms', 'search'));
    }

    /**
     * Store a newly created room.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'room_name' => ['required', 'string', 'max:100', 'unique:rooms,room_name'],
        ], [
            'room_name.required' => 'Nama ruangan wajib diisi.',
            'room_name.unique'   => 'Nama ruangan sudah terdaftar.',
        ]);

        $room = Room::create(['room_name' => trim($validated['room_name'])]);

        return back()->with('success', "Ruangan {$room->room_name} berhasil ditambahkan ke daftar.");
    }

    /**
     * Update the specified room name.
     */
    public function update(Request $request, Room $room): RedirectResponse
    {
        $validated = $request->validate([
            'room_name' => ['required', 'string', 'max:100', 'unique:rooms,room_name,' . $room->id],
        ], [
            'room_name.required' => 'Nama ruangan wajib diisi.',
            'room_name.unique'   => 'Nama ruangan sudah digunakan oleh ruangan lain.',
        ]);

        $oldName = $room->room_name;
        $room->update(['room_name' => trim($validated['room_name'])]);

        return back()->with('success', "Ruangan {$oldName} berhasil diperbarui menjadi {$room->room_name}.");
    }

    /**
     * Remove the specified room from storage.
     */
    public function destroy(Room $room): RedirectResponse
    {
        if ($room->schedules()->exists() || $room->roomSchedules()->exists()) {
            return back()->with('error', "Ruangan {$room->room_name} tidak dapat dihapus karena masih digunakan dalam jadwal pelajaran atau jadwal ruangan.");
        }

        $name = $room->room_name;
        $room->delete();

        return back()->with('success', "Ruangan {$name} berhasil dihapus dari daftar.");
    }
}
