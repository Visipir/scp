<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class AcademicCalendar extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_kbm_off' => 'boolean',
    ];

    protected static function booted()
    {
        static::saved(function ($cal) {
            if ($cal->is_kbm_off) {
                $start = Carbon::parse($cal->start_date);
                $end = Carbon::parse($cal->end_date);
                $period = \Carbon\CarbonPeriod::create($start, $end);
                foreach ($period as $date) {
                    IneffectiveDay::updateOrCreate(
                        ['date' => $date->toDateString()],
                        ['description' => $cal->title, 'created_by' => auth()->id() ?? 1]
                    );
                }
            }
        });

        static::deleted(function ($cal) {
            $start = Carbon::parse($cal->start_date);
            $end = Carbon::parse($cal->end_date);
            $period = \Carbon\CarbonPeriod::create($start, $end);
            foreach ($period as $date) {
                // Check if any other KBM off calendar covers this date
                $otherExists = static::where('id', '!=', $cal->id)
                    ->where('is_kbm_off', true)
                    ->where('start_date', '<=', $date->toDateString())
                    ->where('end_date', '>=', $date->toDateString())
                    ->exists();

                if (!$otherExists) {
                    IneffectiveDay::where('date', $date->toDateString())->delete();
                }
            }
        });
    }

    public function scopeActiveToday($query, $date = null)
    {
        $date = $date ? Carbon::parse($date)->toDateString() : today()->toDateString();
        return $query->where('start_date', '<=', $date)
                     ->where('end_date', '>=', $date);
    }

    public static function isHoliday($date = null)
    {
        return self::whereIn('category', ['libur_nasional', 'libur_bersama'])
            ->activeToday($date)
            ->exists();
    }
}
