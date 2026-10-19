<?php

namespace App\Http\Resources\Api\V1;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExamResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                  => (int) $this->id,
            'category'            => $this->category,
            'category_label'      => $this->category_label,
            'title'               => $this->title,
            'subject'             => $this->subject,
            'exam_date'           => $this->exam_date ? Carbon::parse($this->exam_date)->toDateString() : null,
            'start_time'          => $this->start_time ? Carbon::parse($this->start_time)->format('H:i') : null,
            'end_time'            => $this->end_time ? Carbon::parse($this->end_time)->format('H:i') : null,
            'time_window'         => $this->time_window,
            'status'              => $this->status,
            'description'         => $this->description,
            'external_link'       => $this->external_link,
            'has_file'            => !empty($this->material_file_path),
            'file_name'           => $this->file_name,
            'file_size'           => $this->file_size !== null ? (int) $this->file_size : null,
            'file_extension'      => $this->file_extension,
            'formatted_file_size' => $this->formatted_file_size,
            'class'               => $this->whenLoaded('schoolClass', fn () => [
                'id'            => (int) $this->schoolClass->id,
                'class_name'    => $this->schoolClass->class_name,
                'academic_year' => $this->schoolClass->academic_year,
            ], [
                'id' => (int) $this->class_id,
            ]),
            'teacher'             => $this->whenLoaded('teacher', fn () => [
                'id'       => (int) $this->teacher->id,
                'fullname' => $this->teacher->fullname,
                'username' => $this->teacher->username,
            ], [
                'id' => (int) $this->teacher_id,
            ]),
            'created_at'          => $this->created_at?->toISOString(),
            'updated_at'          => $this->updated_at?->toISOString(),
        ];
    }
}
