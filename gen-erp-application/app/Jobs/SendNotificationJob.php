<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

/** Sends a notification email to a specific user. */
class SendNotificationJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(
        public readonly string $toEmail,
        public readonly string $toName,
        public readonly string $subject,
        public readonly string $body,
        public readonly string $eventKey,
    ) {
        $this->onQueue('notifications');
    }

    public function handle(): void
    {
        try {
            Mail::send('emails.notification', [
                'body' => $this->body,
                'eventKey' => $this->eventKey,
            ], function ($message): void {
                $message->to($this->toEmail, $this->toName)
                    ->subject($this->subject);
            });
        } catch (\Throwable $e) {
            Log::error('SendNotificationJob failed', [
                'to' => $this->toEmail,
                'event' => $this->eventKey,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
