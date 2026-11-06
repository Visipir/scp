<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class UpdateClassRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        if (!$user) {
            return false;
        }

        $class = $this->route('class');
        return $class ? $user->canSuperviseClass($class) : $user->hasKesiswaanAccess();
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
            'academic_year.required' => 'Tahun ajaran wajib diisi.',
            'academic_year.max'      => 'Tahun ajaran maksimal 20 karakter.',
        ];
    }
}
