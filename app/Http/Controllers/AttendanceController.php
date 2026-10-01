<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Journal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AttendanceController extends Controller
{
    /**
     * Show attendance list for a journal (for teacher to update).
     */
    public function edit(Request $request, Journal $journal): View
    {
        if ($journal->schedule->teacher_id !== $request->user()?->id) {
            abort(403);
        }

        $journal->load(['schedule.schoolClass', 'attendances.student']);

        return view('attendances.edit', compact('journal'));
    }

    /**
     * Bulk update attendance statuses for a journal.
     */
    public function update(Request $request, Journal $journal): RedirectResponse
    {
        if ($journal->schedule->teacher_id !== $request->user()->id) {
            abort(403);
        }

        $validated = $request->validate([
            'attendances'          => ['required', 'array'],
            'attendances.*.id'     => ['required', 'exists:attendances,id'],
            'attendances.*.status' => ['required', 'in:Hadir,Izin,Sakit,Alpa,Terlambat'],
            'attendances.*.notes'  => ['nullable', 'string', 'max:255'],
        ]);

        foreach ($validated['attendances'] as $data) {
            Attendance::where('id', $data['id'])
                ->where('journal_id', $journal->id) // scope to this journal for safety
                ->update([
                    'status' => $data['status'],
                    'notes'  => $data['status'] === 'Hadir' ? null : ($data['notes'] ?? null),
                ]);
        }

        return redirect()->route('journals.show', $journal)
            ->with('success', 'Presensi berhasil diperbarui.');
    }
}
