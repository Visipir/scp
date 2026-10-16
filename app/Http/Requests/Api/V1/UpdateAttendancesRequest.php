<?php

namespace App\Http\Requests\Api\V1;

use App\Models\Journal;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAttendancesRequest extends FormRequest
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
        $journal = $this->route('journal');
        $journalId = $journal instanceof Journal ? $journal->id : $journal;

        return [
            'attendances'                 => ['required', 'array', 'min:1'],
            'attendances.*.student_id'    => [
                'required', 'integer',
                Rule::exists('attendances', 'student_id')->where('journal_id', $journalId),
            ],
            'attendances.*.status'        => ['required', 'in:Hadir,Izin,Sakit,Alpa,Terlambat'],
            'attendances.*.notes'         => ['nullable', 'string', 'max:255'],
        ];
    }
}
