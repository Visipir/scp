<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScheduleSwapLog extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'snapshot'   => 'array',
        'created_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (ScheduleSwapLog $log) {
            if (empty($log->created_at)) {
                $log->created_at = now();
            }
        });

        static::updating(function () {
            throw new \RuntimeException('ScheduleSwapLog is append-only and cannot be updated.');
        });

        static::deleting(function () {
            throw new \RuntimeException('ScheduleSwapLog is append-only and cannot be deleted.');
        });
    }

    public function swap(): BelongsTo
    {
        return $this->belongsTo(ScheduleSwap::class, 'swap_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
