<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreIneffectiveDayRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array((int) $this->user()?->role_id, [1, 3], true);
    }

    public function rules(): array
    {
        return [
            'start_date'  => ['required', 'date'],
            'end_date'    => ['nullable', 'date', 'after_or_equal:start_date'],
            'description' => ['required', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'start_date.required'     => 'Tanggal mulai harus diisi.',
            'start_date.date'         => 'Format tanggal mulai tidak valid.',
            'end_date.after_or_equal' => 'Tanggal akhir tidak boleh lebih awal dari tanggal mulai.',
            'description.required'    => 'Keterangan atau alasan hari tidak efektif wajib diisi.',
        ];
    }
}
