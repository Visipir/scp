<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LeaveRequestResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'              => (int) $this->id,
            'student'         => $this->whenLoaded('student', fn () => $this->student ? [
                'id'       => (int) $this->student->id,
                'fullname' => $this->student->fullname,
                'username' => $this->student->username,
                'class_id' => $this->student->class_id !== null ? (int) $this->student->class_id : null,
                'class'    => $this->student->relationLoaded('schoolClass') ? $this->student->schoolClass?->class_name : null,
            ] : null),
            'applicant'       => $this->whenLoaded('applicant', fn () => $this->applicant ? [
                'id'       => (int) $this->applicant->id,
                'fullname' => $this->applicant->fullname,
            ] : null),
            'start_date'      => $this->start_date?->toDateString(),
            'end_date'        => $this->end_date?->toDateString(),
            'reason'          => $this->reason,
            'status_approval' => $this->status_approval,
            'verifier'        => $this->whenLoaded('verifier', fn () => $this->verifier ? [
                'id'       => (int) $this->verifier->id,
                'fullname' => $this->verifier->fullname,
            ] : null),
            'document_url'    => $this->document_path ? route('api.v1.leave-requests.document', $this->id) : null,
            'created_at'      => $this->created_at?->toIso8601String(),
        ];
    }
}
