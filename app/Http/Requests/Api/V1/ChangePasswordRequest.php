<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class ChangePasswordRequest extends FormRequest
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
        // During the forced first-time change the current password is not required,
        // mirroring the web first-change-password flow.
        $currentPasswordRules = $this->user()?->must_change_password
            ? ['nullable']
            : ['required', 'current_password'];

        return [
            'current_password' => $currentPasswordRules,
            'password'         => [
                'required',
                Password::defaults(),
                'confirmed',
                function ($attribute, $value, $fail) {
                    if ($value === $this->user()?->username) {
                        $fail('Kata sandi baru tidak boleh sama dengan NIS/Username Anda.');
                    }
                },
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'current_password.required'  => 'Kata sandi saat ini wajib diisi.',
            'current_password.current_password' => 'Kata sandi saat ini tidak cocok.',
            'password.required'          => 'Kata sandi baru wajib diisi.',
            'password.confirmed'         => 'Konfirmasi kata sandi baru tidak cocok.',
        ];
    }
}
