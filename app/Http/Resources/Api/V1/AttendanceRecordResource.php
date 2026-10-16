<?php

namespace App\Http\Resources\Api\V1;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceRecordResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $schedule = $this->journal?->schedule;

        return [
            'id'           => (int) $this->id,
            'date'         => $this->date ? Carbon::parse($this->date)->toDateString() : null,
            'subject'      => $schedule?->subject,
            'period_label' => $schedule?->period_label,
            'start_time'   => ScheduleResource::time($schedule?->start_time),
            'end_time'     => ScheduleResource::time($schedule?->end_time),
            'status'       => $this->status,
            'notes'        => $this->notes,
        ];
    }
}
