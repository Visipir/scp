<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreAcademicCalendarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array((int) $this->user()?->role_id, [1, 3], true);
    }

    public function rules(): array
    {
        return [
            'title'       => ['required', 'string', 'max:255'],
            'start_date'  => ['required', 'date'],
            'end_date'    => ['required', 'date', 'after_or_equal:start_date'],
            'category'    => ['required', 'in:libur_nasional,libur_bersama,agenda_sekolah'],
            'is_kbm_off'  => ['nullable', 'boolean'],
            'description' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required'          => 'Nama agenda / libur wajib diisi.',
            'start_date.required'     => 'Tanggal mulai wajib diisi.',
            'start_date.date'         => 'Format tanggal mulai tidak valid.',
            'end_date.required'       => 'Tanggal selesai wajib diisi.',
            'end_date.date'           => 'Format tanggal selesai tidak valid.',
            'end_date.after_or_equal' => 'Tanggal selesai tidak boleh lebih awal dari tanggal mulai.',
            'category.required'       => 'Kategori agenda wajib dipilih.',
            'category.in'             => 'Kategori harus libur_nasional, libur_bersama, atau agenda_sekolah.',
        ];
    }
}
