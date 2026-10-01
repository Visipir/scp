<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IneffectiveDay extends Model
{
    protected $guarded = [];
    protected $casts = [
        'date' => 'date',
    ];

    public static function getForDate($date)
    {
        $ineffective = self::where('date', $date)->first();
        if ($ineffective) {
            return $ineffective;
        }

        $cal = \App\Services\AcademicCalendarService::isKbmSuspendedToday($date);
        if ($cal) {
            return (object)[
                'date' => \Carbon\Carbon::parse($date),
                'description' => $cal->title,
                'academic_year' => '2026/2027',
            ];
        }

        return null;
    }

    public static function isDateIneffective($date)
    {
        return self::where('date', $date)->exists()
            || \App\Services\AcademicCalendarService::isKbmSuspendedToday($date) !== null;
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
