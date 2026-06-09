<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CustomerResetPasswordMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $url;
    public string $name;
    public string $token;

    public function __construct(string $url, string $name, string $token)
    {
        $this->url = $url;
        $this->name = $name;
        $this->token = $token;
    }

    public function build()
    {
        return $this->subject('Reset Password Pelanggan PLN UP3 Kudus')
            ->view('emails.customer-reset-password')
            ->with([
                'url' => $this->url,
                'name' => $this->name,
            ]);
    }
}
