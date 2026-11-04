<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TestMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $userName
    ) {}

    public function build(): static
    {
        return $this->subject('Uji Coba Pengaturan Email - ' . config('app.name', 'SCP'))
            ->view('emails.test-mail');
    }
}
