<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClassDetailResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $class = $this->resource['class'];
        $students = $this->resource['students'];
        $summary = $this->resource['summary'];
        $recentAbsences = $this->resource['recent_absences'];

        return [
            'id'              => (int) $class->id,
            'class_name'      => $class->class_name,
            'academic_year'   => $class->academic_year,
            'grade_level'     => $class->grade_level,
            'major'           => $class->major,
            'students_count'  => $students->count(),
            'summary'         => $summary,
            'students'        => $students->map(fn ($s) => [
                'id'                => (int) $s->id,
                'username'          => $s->username,
                'fullname'          => $s->fullname,
                'gender'            => $s->gender,
                'attendance_number' => $s->attendance_number !== null ? (int) $s->attendance_number : null,
            ])->values()->all(),
            'recent_absences' => $recentAbsences->map(fn ($a) => [
                'id'           => (int) $a->id,
                'student_id'   => (int) $a->student_id,
                'student_name' => $a->student?->fullname,
                'date'         => $a->date ? \Carbon\Carbon::parse($a->date)->toDateString() : null,
                'status'       => $a->status,
                'notes'        => $a->notes,
                'subject'      => $a->journal?->schedule?->subject,
            ])->values()->all(),
        ];
    }
}
