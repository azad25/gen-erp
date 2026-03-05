<?php

namespace App\Domain\POS\Events;

use App\Domain\POS\Models\POSSession;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class POSSessionOpened
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly POSSession $session
    ) {}
}
