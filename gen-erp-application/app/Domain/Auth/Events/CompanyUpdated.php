<?php

namespace App\Domain\Auth\Events;

use App\Domain\Auth\Models\Company;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired when a company is updated.
 */
class CompanyUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Company $company,
        public readonly array $oldData
    ) {}
}