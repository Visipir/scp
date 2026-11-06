<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class ExportClassAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        if (!$user) {
            return false;
        }

        $class = $this->route('class');
        return $class ? $user->canAccessClass($class) : $user->hasClassAccess();
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('date') && !$this->filled('start_date') && !$this->filled('end_date')) {
            $this->merge([
                'start_date' => $this->input('date'),
                'end_date'   => $this->input('date'),
            ]);
            return;
        }

        if ($this->filled('month') && !$this->filled('start_date') && !$this->filled('end_date')) {
            try {
                $dt = \Carbon\Carbon::createFromFormat('Y-m', $this->input('month'));
                if ($dt) {
                    $this->merge([
                        'start_date' => $dt->copy()->startOfMonth()->toDateString(),
                        'end_date'   => $dt->copy()->endOfMonth()->toDateString(),
                    ]);
                }
            } catch (\Throwable) {
                // Let standard validation handle invalid month/dates
            }
        }
    }

    public function rules(): array
    {
        return [
            'start_date' => ['required', 'date'],
            'end_date'   => ['required', 'date', 'after_or_equal:start_date'],
        ];
    }

    public function messages(): array
    {
        return [
            'start_date.required'     => 'Tanggal awal wajib diisi.',
            'start_date.date'         => 'Format tanggal awal tidak valid.',
            'end_date.required'       => 'Tanggal akhir wajib diisi.',
            'end_date.date'           => 'Format tanggal akhir tidak valid.',
            'end_date.after_or_equal' => 'Tanggal akhir harus sama dengan atau setelah tanggal awal.',
        ];
    }
}
