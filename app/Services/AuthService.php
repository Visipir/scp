<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    /**
     * Set a new password and clear the forced-change flag.
     */
    public function changePassword(User $user, string $password): User
    {
        $user->update([
            'password'             => Hash::make($password),
            'must_change_password' => false,
        ]);

        return $user;
    }

    /**
     * Update the user's own profile fields.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateProfile(User $user, array $data): User
    {
        $user->fill(array_intersect_key($data, array_flip(['fullname'])));
        $user->save();

        return $user;
    }
}
