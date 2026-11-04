<?php

namespace App\Services;

use App\Models\PasswordResetCode;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class PasswordResetService
{
    public function __construct(
        private readonly MailConfigService $mailConfig
    ) {}

    /**
     * Generate 6-digit code and send email if user exists.
     * Always returns generic response to prevent email enumeration.
     */
    public function requestResetCode(string $email): array
    {
        $normalizedEmail = strtolower(trim($email));
        $user = User::where('email', $normalizedEmail)->first();

        if ($user) {
            $code = sprintf('%06d', random_int(100000, 999999));

            PasswordResetCode::updateOrCreate(
                ['email' => $normalizedEmail],
                [
                    'code_hash'  => Hash::make($code),
                    'attempts'   => 0,
                    'expires_at' => now()->addMinutes(15),
                ]
            );

            $this->mailConfig->sendPasswordResetCode($normalizedEmail, $code);
        }

        return [
            'success' => true,
            'message' => 'Jika email terdaftar, kode verifikasi 6 digit telah dikirimkan ke email Anda.',
        ];
    }

    /**
     * Verify code and update password.
     *
     * @throws ValidationException
     */
    public function verifyAndReset(string $email, string $code, string $newPassword): array
    {
        $normalizedEmail = strtolower(trim($email));
        $record = PasswordResetCode::where('email', $normalizedEmail)->first();

        if (! $record || $record->isExpired()) {
            throw ValidationException::withMessages([
                'code' => ['Kode verifikasi tidak valid atau telah kedaluwarsa. Silakan ajukan permohonan baru.'],
            ]);
        }

        if ($record->attempts >= 5) {
            throw ValidationException::withMessages([
                'code' => ['Batas maksimal 5 kali percobaan telah tercapai. Silakan ajukan kode baru.'],
            ]);
        }

        $record->increment('attempts');

        if (! Hash::check(trim($code), $record->code_hash)) {
            $remaining = max(0, 5 - $record->attempts);
            throw ValidationException::withMessages([
                'code' => ["Kode verifikasi salah. Sisa percobaan: {$remaining} kali."],
            ]);
        }

        $user = User::where('email', $normalizedEmail)->first();
        if (! $user) {
            throw ValidationException::withMessages([
                'email' => ['Pengguna dengan email tersebut tidak ditemukan.'],
            ]);
        }

        // Update password & clear must_change_password
        $user->update([
            'password'             => Hash::make($newPassword),
            'must_change_password' => false,
        ]);

        // Revoke all Sanctum tokens to force re-login on all devices
        $user->tokens()->delete();

        // Invalidate used code
        $record->delete();

        return [
            'success' => true,
            'message' => 'Kata sandi berhasil diatur ulang. Silakan masuk menggunakan kata sandi baru Anda.',
        ];
    }
}
