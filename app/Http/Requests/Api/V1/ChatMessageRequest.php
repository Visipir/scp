<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class ChatMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'message'           => ['required', 'string', 'max:1000'],
            'history'           => ['nullable', 'array', 'max:20'],
            'history.*.role'    => ['required_with:history', 'string', 'in:user,model'],
            'history.*.content' => ['required_with:history', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'message.required'         => 'Pesan chat wajib diisi.',
            'message.max'              => 'Panjang pesan maksimal 1000 karakter.',
            'history.max'              => 'Riwayat percakapan maksimal 20 putaran pesan.',
            'history.*.role.in'        => 'Role riwayat pesan harus "user" atau "model".',
            'history.*.content.max'    => 'Panjang isi riwayat pesan maksimal 2000 karakter.',
        ];
    }
}
