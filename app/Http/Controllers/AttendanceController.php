<?php

namespace App\Http\Controllers;

use App\Models\Journal;
use App\Services\AttendanceService;
use Carbon\Carbon;
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
        $userId = (int) $request->user()?->id;
        if ((int) $journal->schedule->teacher_id !== $userId && (int) $journal->teacher_id !== $userId) {
            abort(403);
        }

        if (! Carbon::parse($journal->date)->isSameDay(now('Asia/Jakarta'))) {
            abort(422, 'Jurnal hanya dapat diubah pada hari yang sama.');
        }

        $journal->load(['schedule.schoolClass', 'attendances.student']);

        return view('attendances.edit', compact('journal'));
    }

    /**
     * Bulk update attendance statuses for a journal.
     */
    public function update(Request $request, Journal $journal, AttendanceService $attendance): RedirectResponse
    {
        $userId = (int) $request->user()->id;
        if ((int) $journal->schedule->teacher_id !== $userId && (int) $journal->teacher_id !== $userId) {
            abort(403);
        }

        if (! Carbon::parse($journal->date)->isSameDay(now('Asia/Jakarta'))) {
            abort(422, 'Jurnal hanya dapat diubah pada hari yang sama.');
        }

        $validated = $request->validate([
            'attendances'          => ['required', 'array'],
            'attendances.*.id'     => ['required', 'exists:attendances,id'],
            'attendances.*.status' => ['required', 'in:Hadir,Izin,Sakit,Alpa,Terlambat'],
            'attendances.*.notes'  => ['nullable', 'string', 'max:255'],
        ]);

        $attendance->updateForJournal($journal, $validated['attendances']);

        return redirect()->route('journals.show', $journal)
            ->with('success', 'Presensi berhasil diperbarui.');
    }
}
