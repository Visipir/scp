<?php

namespace App\Services;

use App\Models\Schedule;

class ScheduleConflictService
{
    /**
     * Check for teacher/room/class time overlap conflicts.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, string>|null  Error bag keyed by field, or null when free.
     */
    public function findConflict(array $data, int $teacherId, ?int $excludeId = null): ?array
    {
        $baseQuery = fn () => Schedule::where('day_of_week', $data['day_of_week'])
            ->where('cycle_type', $data['cycle_type'])
            ->where('start_time', '<', $data['end_time'])->where('end_time', '>', $data['start_time'])
            ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId));

        // Teacher conflict
        if ($baseQuery()->where('teacher_id', $teacherId)->exists()) {
            return ['start_time' => 'Konflik jadwal: Guru sudah memiliki jadwal di waktu yang sama.'];
        }

        // Room conflict (only when a room is specified; null room must not match other room-less schedules)
        if (! empty($data['room_id']) && $baseQuery()->where('room_id', $data['room_id'])->exists()) {
            return ['room_id' => 'Konflik jadwal: Ruangan sudah digunakan di waktu yang sama.'];
        }

        // Class conflict
        if ($baseQuery()->where('class_id', $data['class_id'])->exists()) {
            return ['class_id' => 'Konflik jadwal: Kelas sudah memiliki jadwal di waktu yang sama.'];
        }

        return null;
    }
}
