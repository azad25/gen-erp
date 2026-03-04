<?php

namespace App\Domain\CMS\Events;

use App\Domain\CMS\Models\ContactSubmission;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event fired when a contact form is submitted.
 */
class ContactFormSubmitted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly ContactSubmission $submission
    ) {}
}