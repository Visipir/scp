<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ScheduleSwapLogResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'         => (int) $this->id,
            'swap_id'    => (int) $this->swap_id,
            'event'      => $this->event,
            'actor_id'   => $this->actor_id ? (int) $this->actor_id : null,
            'actor_name' => $this->actor?->fullname,
            'actor_role' => $this->actor_role,
            'message'    => $this->message,
            'snapshot'   => $this->snapshot,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
