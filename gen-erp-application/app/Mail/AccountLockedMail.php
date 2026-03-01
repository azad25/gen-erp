<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AccountLockedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly User $user,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Account Has Been Locked',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.account-locked',
            with: [
                'user' => $this->user,
                'lockedUntil' => $this->user->locked_until?->format('F j, Y, g:i a'),
            ],
        );
    }
}
