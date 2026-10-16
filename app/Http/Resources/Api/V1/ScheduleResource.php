<?php

namespace App\Http\Resources\Api\V1;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ScheduleResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'           => (int) $this->id,
            'subject'      => $this->subject,
            'day_of_week'  => $this->day_of_week,
            'start_time'   => self::time($this->start_time),
            'end_time'     => self::time($this->end_time),
            'cycle_type'   => $this->cycle_type,
            'start_period' => $this->start_period !== null ? (int) $this->start_period : null,
            'end_period'   => $this->end_period !== null ? (int) $this->end_period : null,
            'period_label' => $this->period_label,
            'teacher'      => $this->whenLoaded('teacher', fn () => $this->teacher ? [
                'id'       => (int) $this->teacher->id,
                'fullname' => $this->teacher->fullname,
            ] : null),
            'class'        => $this->whenLoaded('schoolClass', fn () => $this->schoolClass ? [
                'id'         => (int) $this->schoolClass->id,
                'class_name' => $this->schoolClass->class_name,
            ] : null),
            'room'         => $this->whenLoaded('room', fn () => $this->room ? [
                'id'        => (int) $this->room->id,
                'room_name' => $this->room->room_name,
            ] : null),
        ];
    }

    public static function time(?string $value): ?string
    {
        return $value !== null ? Carbon::parse($value)->format('H:i') : null;
    }
}
