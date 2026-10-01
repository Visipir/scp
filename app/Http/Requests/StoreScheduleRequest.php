<?php

namespace App\Http\Requests;

use App\Services\LessonHourService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $day = $this->input('day_of_week', 'Senin');
        $cycle = $this->input('cycle_type', 'Putaran 1');

        if ($this->filled('start_period') && $this->filled('end_period')) {
            $start = LessonHourService::getStartTime((int) $this->start_period, $day, $cycle);
            $end = LessonHourService::getEndTime((int) $this->end_period, $day, $cycle);

            if ($start && $end) {
                $this->merge([
                    'start_time' => $start,
                    'end_time'   => $end,
                ]);
            }
        } elseif ($this->filled('start_time') && $this->filled('end_time')) {
            $inferred = LessonHourService::inferPeriodsFromTime($this->start_time, $this->end_time, $day, $cycle);
            $this->merge([
                'start_period' => $this->start_period ?? $inferred['start_period'],
                'end_period'   => $this->end_period ?? $inferred['end_period'],
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'class_id'     => ['required', 'exists:classes,id'],
            'room_id'      => ['nullable', 'exists:rooms,id'],
            'teacher_id'   => ['sometimes', 'nullable', 'exists:users,id'],
            'subject'      => ['required', 'string', 'max:100'],
            'day_of_week'  => ['required', 'in:Senin,Selasa,Rabu,Kamis,Jumat,Sabtu'],
            'start_period' => ['nullable', 'integer', 'min:1', 'max:12'],
            'end_period'   => ['nullable', 'integer', 'min:1', 'max:12', 'gte:start_period'],
            'start_time'   => ['required', 'date_format:H:i'],
            'end_time'     => ['required', 'date_format:H:i', 'after:start_time'],
            'cycle_type'   => ['required', 'in:Putaran 1,Putaran 2'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($v) {
            $cycle = $this->input('cycle_type');
            $day = $this->input('day_of_week');
            $start = $this->input('start_time');
            $end = $this->input('end_time');
            $startPeriod = $this->input('start_period');
            $endPeriod = $this->input('end_period');

            if ($cycle === 'Putaran 2') {
                // Hari Senin: Upacara Bendera 07.00 - 08.00 (Non-KBM)
                if ($day === 'Senin' && $start < '08:00') {
                    $v->errors()->add('start_time', 'Hari Senin (Putaran 2) KBM dimulai pukul 08.00 WIB setelah Upacara Bendera (07.00–08.00).');
                }

                // Hari Selasa: Literasi 07.00 - 07.30 (Non-KBM)
                if ($day === 'Selasa' && $start < '07:30') {
                    $v->errors()->add('start_time', 'Hari Selasa (Putaran 2) KBM dimulai pukul 07.30 WIB setelah Literasi (07.00–07.30).');
                }

                // Hari Jumat: Maksimal 8 Jam Pelajaran (sampai 13.50)
                if ($day === 'Jumat') {
                    if ($endPeriod && (int) $endPeriod > 8) {
                        $v->errors()->add('end_period', 'Hari Jumat (Putaran 2) hanya tersedia maksimal 8 Jam Pelajaran.');
                    }
                    if ($end && $end > '13:50') {
                        $v->errors()->add('end_time', 'Hari Jumat (Putaran 2) jam KBM selesai maksimal pukul 13.50 WIB.');
                    }
                } elseif ($endPeriod && (int) $endPeriod > 10) {
                    $v->errors()->add('end_period', "Hari {$day} (Putaran 2) hanya tersedia maksimal 10 Jam Pelajaran.");
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'end_period.gte'  => 'Jam pelajaran selesai harus sama atau setelah jam mulai.',
            'end_time.after'  => 'Waktu selesai harus setelah waktu mulai.',
            'class_id.exists' => 'Kelas tidak ditemukan.',
            'room_id.exists'  => 'Ruangan tidak ditemukan.',
        ];
    }
}
