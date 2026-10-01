<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoomSchedule extends Model
{
    protected $guarded = [];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
    ];

    protected static function booted()
    {
        static::saving(function ($schedule) {
            if (empty($schedule->start_time) && !empty($schedule->period) && !empty($schedule->day_of_week)) {
                $cycle = $schedule->cycle_type ?? 'Putaran 1';
                $schedule->start_time = \App\Services\LessonHourService::getStartTime((int)$schedule->period, $schedule->day_of_week, $cycle) ?? '07:00:00';
            }
            if (empty($schedule->end_time) && !empty($schedule->period) && !empty($schedule->day_of_week)) {
                $cycle = $schedule->cycle_type ?? 'Putaran 1';
                $schedule->end_time = \App\Services\LessonHourService::getEndTime((int)$schedule->period, $schedule->day_of_week, $cycle) ?? '07:45:00';
            }
        });
    }

    /**
     * Get the room associated with the schedule.
     */
    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    /**
     * Get the class associated with the schedule.
     */
    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    /**
     * Get the teacher associated with the schedule.
     */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }
}
