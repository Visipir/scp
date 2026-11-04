<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PasswordResetCodeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $code
    ) {}

    public function build(): static
    {
        return $this->subject('Kode Verifikasi Reset Password - ' . config('app.name', 'SCP'))
            ->view('emails.password-reset-code');
    }
}
