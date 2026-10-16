<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClassResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'            => (int) $this->id,
            'class_name'    => $this->class_name,
            'academic_year' => $this->academic_year,
            'grade_level'   => $this->grade_level,
            'major'         => $this->major,
        ];
    }
}
