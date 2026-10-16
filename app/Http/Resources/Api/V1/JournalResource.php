<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class JournalResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                 => (int) $this->id,
            'date'               => $this->date?->toDateString(),
            'topic_description'  => $this->topic_description,
            'photo_url'          => $this->photo_path ? route('api.v1.journals.photo', $this->id) : null,
            'schedule'           => $this->whenLoaded('schedule', fn () => $this->schedule ? self::scheduleArray($this->schedule) : null),
            'attendance_summary' => self::summary($this),
            'created_at'         => $this->created_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function scheduleArray($schedule): array
    {
        return [
            'id'          => (int) $schedule->id,
            'subject'     => $schedule->subject,
            'day_of_week' => $schedule->day_of_week,
            'start_time'  => ScheduleResource::time($schedule->start_time),
            'end_time'    => ScheduleResource::time($schedule->end_time),
            'cycle_type'  => $schedule->cycle_type,
            'class'       => $schedule->relationLoaded('schoolClass') && $schedule->schoolClass ? [
                'id'         => (int) $schedule->schoolClass->id,
                'class_name' => $schedule->schoolClass->class_name,
            ] : null,
        ];
    }

    /**
     * @return array<string, int>
     */
    public static function summary($journal): array
    {
        return [
            'total'     => (int) ($journal->attendances_count ?? 0),
            'hadir'     => (int) ($journal->hadir_count ?? 0),
            'sakit'     => (int) ($journal->sakit_count ?? 0),
            'izin'      => (int) ($journal->izin_count ?? 0),
            'alpa'      => (int) ($journal->alpa_count ?? 0),
            'terlambat' => (int) ($journal->terlambat_count ?? 0),
        ];
    }
}
