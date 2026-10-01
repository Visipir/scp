<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Schedule extends Model
{
    protected $guarded = [];

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function schoolClass()
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function room()
    {
        return $this->belongsTo(Room::class, 'room_id');
    }

    public function getPeriodLabelAttribute(): string
    {
        $start = is_numeric($this->start_period) ? (int) $this->start_period : ($this->start_period ?: 1);
        $end = is_numeric($this->end_period) ? (int) $this->end_period : ($this->end_period ?: $start);

        return 'Jam ' . $start . '-' . $end;
    }

    public function getTotalJpAttribute(): int
    {
        $start = is_numeric($this->start_period) ? (int) $this->start_period : null;
        $end = is_numeric($this->end_period) ? (int) $this->end_period : null;

        if ($start === null || $end === null) {
            return 1;
        }

        return max(1, $end - $start + 1);
    }
}
