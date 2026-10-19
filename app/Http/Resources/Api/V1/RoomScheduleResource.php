<?php

namespace App\Http\Resources\Api\V1;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoomScheduleResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'           => (int) $this->id,
            'room_id'      => (int) $this->room_id,
            'class_id'     => (int) $this->class_id,
            'teacher_id'   => $this->teacher_id !== null ? (int) $this->teacher_id : null,
            'subject'      => $this->subject,
            'day_of_week'  => $this->day_of_week,
            'period'       => (int) $this->period,
            'cycle_type'   => $this->cycle_type,
            'start_time'   => $this->start_time ? Carbon::parse($this->start_time)->format('H:i') : null,
            'end_time'     => $this->end_time ? Carbon::parse($this->end_time)->format('H:i') : null,
            'room'         => $this->whenLoaded('room', fn () => [
                'id'        => (int) $this->room->id,
                'room_name' => $this->room->room_name,
            ]),
            'class'        => $this->whenLoaded('schoolClass', fn () => [
                'id'            => (int) $this->schoolClass->id,
                'class_name'    => $this->schoolClass->class_name,
                'academic_year' => $this->schoolClass->academic_year,
            ]),
            'teacher'      => $this->whenLoaded('teacher', fn () => [
                'id'       => (int) $this->teacher->id,
                'fullname' => $this->teacher->fullname,
                'username' => $this->teacher->username,
            ]),
        ];
    }
}
