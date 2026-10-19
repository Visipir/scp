<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreExamRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array((int) $this->user()?->role_id, [1, 2], true);
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('class_id') && !$this->has('class_ids')) {
            $this->merge([
                'class_ids' => [(int) $this->input('class_id')],
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'category'      => ['required', 'in:materi,bank_soal,ujian'],
            'title'         => ['required', 'string', 'max:255'],
            'subject'       => ['required', 'string', 'max:255'],
            'class_ids'     => ['required', 'array', 'min:1'],
            'class_ids.*'   => ['exists:classes,id'],
            'exam_date'     => ['required', 'date'],
            'start_time'    => ['nullable', 'date_format:H:i'],
            'end_time'      => ['nullable', 'date_format:H:i'],
            'description'   => ['nullable', 'string'],
            'external_link' => ['nullable', 'url', 'max:500'],
            'material_file' => ['nullable', 'file', 'mimes:pdf,doc,docx,ppt,pptx,zip,xls,xlsx,jpg,jpeg,png,rar', 'max:51200'],
        ];
    }

    public function messages(): array
    {
        return [
            'category.required'      => 'Pilih jenis konten (Materi, Bank Soal, atau Ujian).',
            'category.in'            => 'Jenis konten harus materi, bank_soal, atau ujian.',
            'title.required'         => 'Judul materi/ujian wajib diisi.',
            'subject.required'       => 'Mata pelajaran wajib diisi.',
            'class_ids.required'     => 'Pilih minimal satu kelas tujuan materi/ujian.',
            'class_ids.min'          => 'Pilih minimal satu kelas tujuan materi/ujian.',
            'class_ids.*.exists'     => 'Kelas yang dipilih tidak ditemukan.',
            'exam_date.required'     => 'Tanggal pelaksanaan/publikasi wajib diisi.',
            'exam_date.date'         => 'Format tanggal tidak valid.',
            'start_time.date_format' => 'Format waktu mulai harus HH:mm (contoh: 07:30).',
            'end_time.date_format'   => 'Format waktu selesai harus HH:mm (contoh: 09:00).',
            'external_link.url'      => 'Format tautan eksternal tidak valid (harus diawali http:// atau https://).',
            'material_file.max'      => 'Ukuran berkas maksimal adalah 50MB.',
            'material_file.mimes'    => 'Format berkas harus berupa dokumen, presentasi, arsip ZIP/RAR, atau gambar.',
        ];
    }
}
