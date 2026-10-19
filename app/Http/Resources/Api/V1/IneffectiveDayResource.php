<?php

namespace App\Http\Resources\Api\V1;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class IneffectiveDayResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'          => (int) $this->id,
            'date'        => $this->date ? Carbon::parse($this->date)->toDateString() : null,
            'description' => $this->description,
            'created_by'  => $this->created_by !== null ? (int) $this->created_by : null,
            'creator'     => $this->whenLoaded('creator', fn () => [
                'id'       => (int) $this->creator->id,
                'fullname' => $this->creator->fullname,
                'username' => $this->creator->username,
            ]),
        ];
    }
}
