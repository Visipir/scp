<?php

namespace App\Services;

use App\Models\Attendance;

class AttendanceSummaryService
{
    public const STATUSES = ['Hadir', 'Sakit', 'Izin', 'Alpa', 'Terlambat'];

    /**
     * Build the attendance recap for a single student in a given month.
     *
     * @return array{summary: array<string, int>, records: \Illuminate\Support\Collection<int, Attendance>}
     */
    public function forStudent(int $studentId, string $month): array
    {
        [$year, $monthNumber] = array_map('intval', explode('-', $month));

        $base = Attendance::query()
            ->where('student_id', $studentId)
            ->whereYear('date', $year)
            ->whereMonth('date', $monthNumber);

        $counts = (clone $base)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $summary = [];
        foreach (self::STATUSES as $status) {
            $summary[$status] = (int) ($counts[$status] ?? 0);
        }

        $records = (clone $base)
            ->with(['journal.schedule'])
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->get();

        return [
            'summary' => $summary,
            'records' => $records,
        ];
    }
}
