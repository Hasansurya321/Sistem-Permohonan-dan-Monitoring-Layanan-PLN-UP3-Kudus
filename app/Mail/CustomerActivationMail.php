<?php

namespace App\Mail;

use App\Models\CustomerAccountRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CustomerActivationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $requestData;
    public $token;

    /**
     * Create a new message instance.
     */
    public function __construct(CustomerAccountRequest $requestData, string $token)
    {
        $this->requestData = $requestData;
        $this->token = $token;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Aktivasi Akun Pelanggan - PLN UP3 Kudus',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.activation',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
