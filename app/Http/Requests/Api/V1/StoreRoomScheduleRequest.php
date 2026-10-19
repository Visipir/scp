<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreRoomScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array((int) $this->user()?->role_id, [1, 2, 3], true);
    }

    public function rules(): array
    {
        return [
            'room_id'     => ['required', 'exists:rooms,id'],
            'class_id'    => ['required', 'exists:classes,id'],
            'teacher_id'  => ['nullable', 'exists:users,id'],
            'subject'     => ['nullable', 'string', 'max:255'],
            'day_of_week' => ['required', 'in:Senin,Selasa,Rabu,Kamis,Jumat'],
            'period'      => ['required', 'integer', 'min:1', 'max:12'],
            'cycle_type'  => ['required', 'in:Putaran 1,Putaran 2'],
        ];
    }

    public function messages(): array
    {
        return [
            'room_id.required'     => 'Ruangan wajib dipilih.',
            'room_id.exists'       => 'Ruangan tidak ditemukan.',
            'class_id.required'    => 'Kelas wajib dipilih.',
            'class_id.exists'      => 'Kelas tidak ditemukan.',
            'teacher_id.exists'    => 'Guru tidak ditemukan.',
            'day_of_week.required' => 'Hari wajib dipilih.',
            'day_of_week.in'       => 'Pilihan hari harus Senin, Selasa, Rabu, Kamis, atau Jumat.',
            'period.required'      => 'Jam pelajaran (periode) wajib diisi.',
            'period.min'           => 'Jam pelajaran minimal 1.',
            'period.max'           => 'Jam pelajaran maksimal 12.',
            'cycle_type.required'  => 'Putaran jadwal wajib dipilih (Putaran 1 atau Putaran 2).',
            'cycle_type.in'        => 'Pilihan putaran harus Putaran 1 atau Putaran 2.',
        ];
    }
}
