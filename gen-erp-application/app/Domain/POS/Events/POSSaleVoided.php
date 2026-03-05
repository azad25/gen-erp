<?php

namespace App\Domain\POS\Events;

use App\Domain\POS\Models\POSSale;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class POSSaleVoided
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly POSSale $sale
    ) {}
}
