<?php

namespace App\Http\Resources\Api\V1;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AcademicCalendarResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'          => (int) $this->id,
            'title'       => $this->title,
            'start_date'  => $this->start_date ? Carbon::parse($this->start_date)->toDateString() : null,
            'end_date'    => $this->end_date ? Carbon::parse($this->end_date)->toDateString() : null,
            'category'    => $this->category,
            'is_kbm_off'  => (bool) $this->is_kbm_off,
            'description' => $this->description,
        ];
    }
}
