<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreScheduleSwapRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (int) $this->user()?->role_id === 2;
    }

    protected function prepareForValidation(): void
    {
        if (! $this->has('target_id') && $this->has('target_schedule_id')) {
            $targetSchedule = \App\Models\Schedule::find($this->input('target_schedule_id'));
            if ($targetSchedule) {
                $this->merge(['target_id' => $targetSchedule->teacher_id]);
            }
        }
    }

    public function rules(): array
    {
        return [
            'target_id'             => ['required', 'integer', 'exists:users,id'],
            'requester_schedule_id' => ['required', 'integer', 'exists:schedules,id'],
            'target_schedule_id'    => ['required', 'integer', 'exists:schedules,id'],
            'swap_date'             => ['required', 'date', 'after_or_equal:today'],
            'note'                  => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'target_id.required'             => 'Guru target wajib dipilih.',
            'target_id.exists'               => 'Guru target tidak ditemukan.',
            'requester_schedule_id.required' => 'Jadwal yang ingin ditukar wajib dipilih.',
            'requester_schedule_id.exists'   => 'Jadwal pemohon tidak ditemukan.',
            'target_schedule_id.required'    => 'Jadwal guru target wajib dipilih.',
            'target_schedule_id.exists'      => 'Jadwal guru target tidak ditemukan.',
            'swap_date.required'             => 'Tanggal berlaku pertukaran wajib diisi.',
            'swap_date.after_or_equal'       => 'Tanggal pertukaran tidak boleh tanggal yang sudah lewat.',
            'note.max'                       => 'Catatan maksimal 500 karakter.',
        ];
    }
}
