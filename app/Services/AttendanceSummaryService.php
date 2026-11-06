<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\User;

class AttendanceSummaryService
{
    public const STATUSES = ['Hadir', 'Sakit', 'Izin', 'Alpa', 'Terlambat'];

    /**
     * Resolve which student a role-4 user is allowed to view.
     * Parents may only pick their own children; a plain student is always self.
     *
     * @return array{student: User, student_id: int, is_parent: bool, children: \Illuminate\Support\Collection<int, User>}
     */
    public function resolveTargetStudent(User $user, ?int $studentId = null): array
    {
        $children = $user->children()->where('role_id', 4)->orderBy('fullname')->get();
        $isParent = $children->isNotEmpty();

        if ($isParent) {
            $studentId = (int) ($studentId ?? $children->first()->id);

            abort_unless($children->pluck('id')->contains($studentId), 403, 'Akses ditolak.');

            $student = $children->firstWhere('id', $studentId);
        } else {
            abort_if($studentId !== null && (int) $studentId !== (int) $user->id, 403, 'Akses ditolak.');
            $studentId = (int) $user->id;
            $student = $user;
        }

        return [
            'student'    => $student,
            'student_id' => $studentId,
            'is_parent'  => $isParent,
            'children'   => $children,
        ];
    }

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
