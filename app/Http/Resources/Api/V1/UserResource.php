<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                   => (int) $this->id,
            'username'             => $this->username,
            'fullname'             => $this->fullname,
            'nip'                  => $this->nip,
            'email'                => $this->email,
            'email_verified_at'    => $this->email_verified_at?->toIso8601String(),
            'is_email_verified'    => $this->isEmailVerified(),
            'role_id'              => (int) $this->role_id,
            'role'                 => $this->whenLoaded('role', fn () => $this->role?->role_name),
            'has_kesiswaan_access' => (bool) $this->hasKesiswaanAccess(),
            'is_homeroom_teacher'  => (bool) $this->isHomeroomTeacher(),
            'is_counselor'         => (bool) $this->isCounselor(),
            'homeroom_class_id'    => $this->homeroomAssignment?->class_id,
            'counselor_class_ids'  => $this->counselorAssignments->pluck('class_id')->all(),
            'class_id'             => $this->class_id !== null ? (int) $this->class_id : null,
            'class'                => $this->whenLoaded('schoolClass', fn () => $this->schoolClass?->class_name),
            'gender'               => $this->gender,
            'attendance_number'    => $this->attendance_number !== null ? (int) $this->attendance_number : null,
            'must_change_password' => (bool) $this->must_change_password,
            'children'             => $this->whenLoaded('children', fn () => $this->children->map(fn ($child) => [
                'id'       => (int) $child->id,
                'fullname' => $child->fullname,
                'class_id' => $child->class_id !== null ? (int) $child->class_id : null,
                'class'    => $child->schoolClass?->class_name,
            ])->values()->all()),
        ];
    }
}
