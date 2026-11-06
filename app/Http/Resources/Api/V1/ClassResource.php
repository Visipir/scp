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
        $user = $request->user();
        $isPaginated = $request->has('page') || $request->has('per_page');
        if ($user && in_array((int) $user->role_id, [2, 4], true) && !$user->hasKesiswaanAccess() && !($isPaginated && $user->hasClassAccess())) {
            return [
                'id'         => (int) $this->id,
                'class_name' => $this->class_name,
            ];
        }

        return [
            'id'             => (int) $this->id,
            'class_name'     => $this->class_name,
            'academic_year'  => $this->academic_year,
            'grade_level'    => $this->grade_level,
            'major'          => $this->major,
            'students_count' => $this->when(
                isset($this->students_count) || $this->relationLoaded('users'),
                fn () => (int) ($this->students_count ?? $this->users_count ?? 0)
            ),
        ];
    }
}
