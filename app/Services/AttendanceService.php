<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Journal;

class AttendanceService
{
    /**
     * Bulk update attendance rows for a journal.
     *
     * @param  array<int, array<string, mixed>>  $rows
     */
    public function updateForJournal(Journal $journal, array $rows): void
    {
        foreach ($rows as $data) {
            Attendance::where('id', $data['id'])
                ->where('journal_id', $journal->id) // scope to this journal for safety
                ->update([
                    'status' => $data['status'],
                    'notes'  => $data['status'] === 'Hadir' ? null : ($data['notes'] ?? null),
                ]);
        }
    }

    /**
     * Bulk update attendance addressed by student_id (API shape).
     *
     * @param  array<int, array<string, mixed>>  $rows
     */
    public function updateForJournalByStudent(Journal $journal, array $rows): void
    {
        $byStudent = $journal->attendances()->get()->keyBy('student_id');

        $mapped = [];
        foreach ($rows as $row) {
            $attendance = $byStudent->get((int) $row['student_id']);
            if ($attendance === null) {
                continue;
            }

            $mapped[] = [
                'id'     => $attendance->id,
                'status' => $row['status'],
                'notes'  => $row['notes'] ?? null,
            ];
        }

        $this->updateForJournal($journal, $mapped);
    }
}
