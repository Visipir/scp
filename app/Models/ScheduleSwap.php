<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScheduleSwap extends Model
{
    protected $guarded = [];

    protected $casts = [
        'swap_date'    => 'date',
        'responded_at' => 'datetime',
    ];

    public function requester()
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function target()
    {
        return $this->belongsTo(User::class, 'target_id');
    }

    public function requesterSchedule()
    {
        return $this->belongsTo(Schedule::class, 'requester_schedule_id');
    }

    public function targetSchedule()
    {
        return $this->belongsTo(Schedule::class, 'target_schedule_id');
    }

    public function journals()
    {
        return $this->hasMany(Journal::class, 'swap_id');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }
}
