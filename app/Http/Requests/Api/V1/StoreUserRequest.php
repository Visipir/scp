<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasKesiswaanAccess() ?? false;
    }

    public function rules(): array
    {
        return [
            'fullname'          => ['required', 'string', 'max:255'],
            'username'          => ['required', 'string', 'max:255', 'unique:users,username'],
            'email'             => ['nullable', 'email', 'max:255', 'unique:users,email'],
            'role_id'           => ['required', 'integer', 'in:1,2,3,4'],
            'class_id'          => ['nullable', 'required_if:role_id,4', 'exists:classes,id'],
            'gender'            => ['nullable', 'string', 'in:L,P'],
            'attendance_number' => ['nullable', 'integer', 'min:1'],
            'password'          => ['required', 'string', 'min:6'],
        ];
    }

    public function messages(): array
    {
        return [
            'fullname.required'    => 'Nama lengkap wajib diisi.',
            'username.required'    => 'Username wajib diisi.',
            'username.unique'      => 'Username sudah digunakan.',
            'role_id.required'     => 'Peran (role) pengguna wajib dipilih.',
            'role_id.in'           => 'Peran pengguna tidak valid.',
            'class_id.required_if' => 'Kelas wajib dipilih untuk pengguna dengan peran Siswa.',
            'class_id.exists'      => 'Kelas yang dipilih tidak ditemukan.',
            'password.required'    => 'Kata sandi wajib diisi.',
            'password.min'         => 'Kata sandi minimal 6 karakter.',
        ];
    }
}
