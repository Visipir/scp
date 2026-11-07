<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Handle API login and return a Sanctum token.
     */
    public function login(Request $request)
    {
        $request->validate([
            'username' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'max:255'],
        ]);

        $user = User::where('username', $request->username)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Username atau password salah.'
            ], 401);
        }

        // Generate token (e.g. Android Device token)
        $token = $user->createToken('android-device-token')->plainTextToken;

        return response()->json([
            'message' => 'Login berhasil',
            'user' => $user->load('role'), // Eager load role if you need it
            'token' => $token,
        ]);
    }

    /**
     * Get authenticated user details.
     */
    public function me(Request $request)
    {
        return response()->json([
            'user' => $request->user()->load('role')
        ]);
    }

    /**
     * Handle API logout by revoking current token.
     */
    public function logout(Request $request)
    {
        // Revoke the token that was used to authenticate the current request
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logout berhasil'
        ]);
    }
}
