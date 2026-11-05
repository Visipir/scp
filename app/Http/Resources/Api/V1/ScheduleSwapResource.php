<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ScheduleSwapResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                    => (int) $this->id,
            'requester_id'          => (int) $this->requester_id,
            'requester_name'        => $this->requester?->fullname,
            'target_id'             => (int) $this->target_id,
            'target_name'           => $this->target?->fullname,
            'requester_schedule_id' => (int) $this->requester_schedule_id,
            'requester_schedule'    => $this->whenLoaded('requesterSchedule', fn () => [
                'id'         => (int) $this->requesterSchedule->id,
                'subject'    => $this->requesterSchedule->subject,
                'class_name' => $this->requesterSchedule->schoolClass?->class_name,
                'room_name'  => $this->requesterSchedule->room?->room_name,
                'day_of_week'=> $this->requesterSchedule->day_of_week,
                'start_time' => substr($this->requesterSchedule->start_time, 0, 5),
                'end_time'   => substr($this->requesterSchedule->end_time, 0, 5),
                'period'     => $this->requesterSchedule->start_period && $this->requesterSchedule->end_period
                    ? "{$this->requesterSchedule->start_period}-{$this->requesterSchedule->end_period}"
                    : null,
            ]),
            'target_schedule_id'    => (int) $this->target_schedule_id,
            'target_schedule'       => $this->whenLoaded('targetSchedule', fn () => [
                'id'         => (int) $this->targetSchedule->id,
                'subject'    => $this->targetSchedule->subject,
                'class_name' => $this->targetSchedule->schoolClass?->class_name,
                'room_name'  => $this->targetSchedule->room?->room_name,
                'day_of_week'=> $this->targetSchedule->day_of_week,
                'start_time' => substr($this->targetSchedule->start_time, 0, 5),
                'end_time'   => substr($this->targetSchedule->end_time, 0, 5),
                'period'     => $this->targetSchedule->start_period && $this->targetSchedule->end_period
                    ? "{$this->targetSchedule->start_period}-{$this->targetSchedule->end_period}"
                    : null,
            ]),
            'cycle'                 => $this->cycle,
            'swap_date'             => $this->swap_date?->format('Y-m-d'),
            'status'                => $this->status,
            'note'                  => $this->note,
            'journal_filled'        => $this->isJournalFilled(),
            'journal_id'            => $this->relationLoaded('journals') ? $this->journals->first()?->id : null,
            'responded_at'          => $this->responded_at?->toIso8601String(),
            'created_at'            => $this->created_at?->toIso8601String(),
            'logs'                  => ScheduleSwapLogResource::collection($this->whenLoaded('logs')),
        ];
    }
}
