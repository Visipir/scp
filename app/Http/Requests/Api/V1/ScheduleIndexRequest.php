<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class ScheduleIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'cycle'      => ['nullable', 'string', 'in:Putaran 1,Putaran 2'],
            'day'        => ['nullable', 'in:Senin,Selasa,Rabu,Kamis,Jumat,Sabtu,Minggu'],
            'teacher_id' => ['nullable', 'integer', 'exists:users,id'],
            'class_id'   => ['nullable', 'integer', 'exists:classes,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'cycle.in' => 'Putaran jadwal tidak valid. Pilihan yang tersedia: Putaran 1 atau Putaran 2.',
        ];
    }
}
