<?php

namespace App\Mail;

use App\Models\Entreprise;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EntrepriseAccountValidatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public Entreprise $entreprise;

    public string $verificationUrl;

    /**
     * Create a new message instance.
     */
    public function __construct(Entreprise $entreprise, string $verificationUrl)
    {
        $this->entreprise = $entreprise;
        $this->verificationUrl = $verificationUrl;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Rahma GP - Confirmation et vérification du compte Entreprise',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.entreprise_validated',
            with: [
                'entreprise' => $this->entreprise,
                'gerant' => $this->entreprise->gerant,
                'verificationUrl' => $this->verificationUrl,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
