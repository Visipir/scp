<?php

namespace App\Http\Controllers;

use App\Models\IneffectiveDay;
use App\Models\SystemConfiguration;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class IneffectiveDayController extends Controller
{
    /**
     * Display a listing of ineffective school days (Redirected to Unified Academic Calendar).
     */
    public function index(): RedirectResponse
    {
        return redirect()->route('academic-calendar.index');
    }

    /**
     * Store a newly created ineffective day (or date range) in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'start_date'    => ['required', 'date'],
            'end_date'      => ['nullable', 'date', 'after_or_equal:start_date'],
            'description'   => ['required', 'string', 'max:255'],
            'academic_year' => ['nullable', 'string', 'max:20'],
        ], [
            'start_date.required'     => 'Tanggal mulai harus diisi.',
            'start_date.date'         => 'Format tanggal mulai tidak valid.',
            'end_date.after_or_equal' => 'Tanggal akhir tidak boleh lebih awal dari tanggal mulai.',
            'description.required'    => 'Keterangan atau alasan hari tidak efektif wajib diisi.',
        ]);

        $startDate = Carbon::parse($validated['start_date']);
        $endDate = !empty($validated['end_date']) ? Carbon::parse($validated['end_date']) : $startDate->copy();
        $academicYear = $validated['academic_year'] ?? '2026/2027';
        $userId = $request->user()?->id;

        $createdCount = 0;
        $period = CarbonPeriod::create($startDate, $endDate);

        foreach ($period as $date) {
            $formattedDate = $date->toDateString();
            $record = IneffectiveDay::firstOrNew(['date' => $formattedDate]);
            $record->description = $validated['description'];
            $record->academic_year = $academicYear;
            $record->created_by = $userId;
            $record->save();
            $createdCount++;
        }

        $msg = $createdCount > 1
            ? "Berhasil menetapkan {$createdCount} hari tidak efektif KBM ({$startDate->translatedFormat('d M Y')} s/d {$endDate->translatedFormat('d M Y')})."
            : "Berhasil menetapkan hari tidak efektif KBM untuk tanggal {$startDate->translatedFormat('d M Y')}.";

        return redirect()->route('academic-calendar.index')->with('success', $msg);
    }

    /**
     * Remove the specified ineffective day from storage.
     */
    public function destroy(IneffectiveDay $ineffectiveDay): RedirectResponse
    {
        $dateFormatted = $ineffectiveDay->date->translatedFormat('d M Y');
        $ineffectiveDay->delete();

        return redirect()->route('academic-calendar.index')
            ->with('success', "Hari tidak efektif untuk tanggal {$dateFormatted} berhasil dihapus.");
    }
}
