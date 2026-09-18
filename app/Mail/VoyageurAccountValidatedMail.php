<?php

namespace App\Mail;

use App\Models\Voyageur;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class VoyageurAccountValidatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public Voyageur $voyageur;
    public string $verificationUrl;

    /**
     * Create a new message instance.
     */
    public function __construct(Voyageur $voyageur, string $verificationUrl)
    {
        $this->voyageur = $voyageur;
        $this->verificationUrl = $verificationUrl;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Rahma GP - Validation et vérification de votre compte Voyageur',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.voyageur_validated',
            with: [
                'user' => $this->voyageur->user,
                'voyageur' => $this->voyageur,
                'verificationUrl' => $this->verificationUrl,
            ],
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
