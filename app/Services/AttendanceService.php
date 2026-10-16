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
}
