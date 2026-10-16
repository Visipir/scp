<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceEntryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'student_id' => (int) $this->student_id,
            'fullname'   => $this->student?->fullname,
            'username'   => $this->student?->username,
            'status'     => $this->status,
            'notes'      => $this->notes,
        ];
    }
}
