<?php

namespace App\Mail;

use App\Models\InvitationAgent;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvitationAgentMail extends Mailable
{
    use Queueable, SerializesModels;

    public InvitationAgent $invitation;
    public string $nomEntreprise;

    /**
     * Create a new message instance.
     */
    public function __construct(InvitationAgent $invitation, string $nomEntreprise = 'Rahma GP')
    {
        $this->invitation = $invitation;
        $this->nomEntreprise = $nomEntreprise;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Invitation à rejoindre l'équipe Agent GP - {$this->nomEntreprise}",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.invitation_agent',
            with: [
                'invitation' => $this->invitation,
                'nomEntreprise' => $this->nomEntreprise,
                'lienRegister' => $this->invitation->lien_invitation,
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
