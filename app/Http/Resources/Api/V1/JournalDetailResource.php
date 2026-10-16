<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class JournalDetailResource extends JsonResource
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
            'schedule'           => $this->whenLoaded('schedule', fn () => $this->schedule ? JournalResource::scheduleArray($this->schedule) : null),
            'attendance_summary' => JournalResource::summary($this),
            'attendances'        => AttendanceEntryResource::collection($this->whenLoaded('attendances'))->resolve(),
            'created_at'         => $this->created_at?->toIso8601String(),
        ];
    }
}
