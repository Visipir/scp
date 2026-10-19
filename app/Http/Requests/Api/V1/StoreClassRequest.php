<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreClassRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array((int) $this->user()?->role_id, [1, 3], true);
    }

    public function rules(): array
    {
        return [
            'class_name'    => ['required', 'string', 'max:50'],
            'academic_year' => ['required', 'string', 'max:20'],
        ];
    }

    public function messages(): array
    {
        return [
            'class_name.required'    => 'Nama kelas wajib diisi.',
            'class_name.max'         => 'Nama kelas maksimal 50 karakter.',
            'academic_year.required' => 'Tahun ajaran wajib diisi (contoh: 2026/2027).',
            'academic_year.max'      => 'Tahun ajaran maksimal 20 karakter.',
        ];
    }
}
