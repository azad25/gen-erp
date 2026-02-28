<?php

namespace App\Jobs;

use App\Models\Invitation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

/**
 * Sends an invitation email to join a company on GenERP BD.
 */
class SendInvitationEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly Invitation $invitation,
    ) {
        $this->onQueue('default');
    }

    public function handle(): void
    {
        $this->invitation->loadMissing(['company', 'invitedBy']);

        Mail::to($this->invitation->email)->send(
            new InvitationMailable($this->invitation)
        );
    }
}

/**
 * Mailable for team invitations.
 */
class InvitationMailable extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Invitation $invitation,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('You\'ve been invited to join :company on GenERP BD', [
                'company' => $this->invitation->company->name,
            ]),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.invitation',
            with: [
                'invitation' => $this->invitation,
                'acceptUrl' => route('invitation.accept', $this->invitation->token),
            ],
        );
    }
}
