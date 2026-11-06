<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        $actor = $this->user();
        if (!$actor) {
            return false;
        }

        $target = $this->route('user');
        if (!$target instanceof \App\Models\User) {
            $target = \App\Models\User::find($target);
        }

        if (!$target) {
            return true;
        }

        if (!app(\App\Services\UserService::class)->canManageUser($actor, $target)) {
            return false;
        }

        if ((int) $actor->role_id !== 1) {
            if ($this->has('role_id') && (int) $this->input('role_id') !== 4) {
                return false;
            }
            $allowedClassIds = $actor->authorizedClassIds();
            if ($allowedClassIds !== null && $this->has('class_id') && !in_array((int) $this->input('class_id'), $allowedClassIds, true)) {
                return false;
            }
        }

        return true;
    }

    public function rules(): array
    {
        $target = $this->route('user');
        $userId = $target instanceof \App\Models\User ? $target->id : $target;

        return [
            'fullname'          => ['required', 'string', 'max:255'],
            'username'          => ['required', 'string', 'max:255', Rule::unique('users', 'username')->ignore($userId)],
            'email'             => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'role_id'           => ['required', 'integer', 'in:1,2,3,4'],
            'class_id'          => ['nullable', 'required_if:role_id,4', 'exists:classes,id'],
            'gender'            => ['nullable', 'string', 'in:L,P'],
            'attendance_number' => ['nullable', 'integer', 'min:1'],
            'password'          => ['nullable', 'string', 'min:6'],
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
            'password.min'         => 'Kata sandi minimal 6 karakter.',
        ];
    }
}
