<?php

namespace App\Mail;

use App\Models\CustomerAccountRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CustomerRejectionMail extends Mailable
{
    use Queueable, SerializesModels;

    public $requestData;
    public $rejectionReason;

    /**
     * Create a new message instance.
     */
    public function __construct(CustomerAccountRequest $requestData, string $rejectionReason)
    {
        $this->requestData = $requestData;
        $this->rejectionReason = $rejectionReason;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Pemberitahuan Penolakan Akun - PLN UP3 Kudus',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.rejection',
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