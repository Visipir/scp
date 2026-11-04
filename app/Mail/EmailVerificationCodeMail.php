<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class EmailVerificationCodeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $code
    ) {}

    public function build(): static
    {
        return $this->subject('Kode Verifikasi Email - ' . config('app.name', 'SCP'))
            ->view('emails.email-verification-code');
    }
}
