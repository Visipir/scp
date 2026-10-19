<?php

namespace App\Services;

use App\Models\RoomSchedule;
use Illuminate\Database\Eloquent\Collection;

class RoomScheduleService
{
    /**
     * List room schedules with filters.
     */
    public function listRoomSchedules(array $filters = []): Collection
    {
        $query = RoomSchedule::with(['room', 'schoolClass', 'teacher']);

        if (!empty($filters['day_of_week'])) {
            $query->where('day_of_week', $filters['day_of_week']);
        }

        if (!empty($filters['cycle_type'])) {
            $query->where('cycle_type', $filters['cycle_type']);
        }

        if (!empty($filters['room_id'])) {
            $query->where('room_id', $filters['room_id']);
        }

        if (!empty($filters['class_id'])) {
            $query->where('class_id', $filters['class_id']);
        }

        return $query->orderBy('day_of_week')
            ->orderBy('period')
            ->orderBy('start_time')
            ->get();
    }

    /**
     * Check if a room schedule slot has a conflict.
     */
    public function hasConflict(int $roomId, string $dayOfWeek, int $period, string $cycleType): bool
    {
        return RoomSchedule::where('room_id', $roomId)
            ->where('day_of_week', $dayOfWeek)
            ->where('period', $period)
            ->where('cycle_type', $cycleType)
            ->exists();
    }

    /**
     * Store new room schedule after checking conflicts.
     */
    public function createRoomSchedule(array $data): RoomSchedule
    {
        if ($this->hasConflict((int) $data['room_id'], $data['day_of_week'], (int) $data['period'], $data['cycle_type'])) {
            abort(422, 'Ruangan ini sudah digunakan pada hari, jam, dan putaran tersebut.');
        }

        $startTime = LessonHourService::getStartTime((int) $data['period'], $data['day_of_week'], $data['cycle_type']);
        $endTime = LessonHourService::getEndTime((int) $data['period'], $data['day_of_week'], $data['cycle_type']);

        $schedule = RoomSchedule::create([
            'room_id'     => $data['room_id'],
            'class_id'    => $data['class_id'],
            'teacher_id'  => $data['teacher_id'] ?? null,
            'subject'     => $data['subject'] ?? null,
            'day_of_week' => $data['day_of_week'],
            'period'      => $data['period'],
            'start_time'  => $startTime,
            'end_time'    => $endTime,
            'cycle_type'  => $data['cycle_type'],
        ]);

        return $schedule->load(['room', 'schoolClass', 'teacher']);
    }

    /**
     * Delete room schedule.
     */
    public function deleteRoomSchedule(RoomSchedule $roomSchedule): void
    {
        $roomSchedule->delete();
    }
}
