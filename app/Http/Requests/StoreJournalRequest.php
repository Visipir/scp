<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreJournalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('absent_students') && is_array($this->absent_students)) {
            $filtered = array_values(array_filter($this->absent_students, function ($item) {
                return is_array($item) && !empty($item['student_id']);
            }));
            $this->merge(['absent_students' => $filtered]);
        }
    }

    public function rules(): array
    {
        return [
            'schedule_id'       => ['required', 'exists:schedules,id'],
            'date'              => [
                'required',
                'date',
                function ($attribute, $value, $fail) {
                    $exists = \App\Models\Journal::where('schedule_id', $this->input('schedule_id'))
                        ->whereDate('date', $value)
                        ->exists();

                    if ($exists) {
                        $fail('Jurnal untuk jadwal ini pada tanggal tersebut sudah ada.');
                    }
                },
            ],
            'topic_description' => ['required', 'string', 'max:5000'],
            'photo'             => ['required', 'image', 'mimes:jpeg,png,jpg', 'max:5120'],
            'absent_students'                => ['nullable', 'array'],
            'absent_students.*.student_id'   => ['required_with:absent_students', 'exists:users,id'],
            'absent_students.*.status'       => ['required_with:absent_students', 'in:Sakit,Izin,Alpa,Terlambat'],
            'absent_students.*.notes'        => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'photo.required' => 'Dokumentasi foto kegiatan KBM wajib diunggah.',
            'photo.max'      => 'Ukuran foto maksimal 5MB.',
            'photo.image'    => 'File harus berupa gambar.',
            'photo.mimes'    => 'Format foto harus berupa JPG, JPEG, atau PNG.',
            'date.unique'    => 'Jurnal untuk jadwal ini pada tanggal tersebut sudah ada.',
        ];
    }
}
