<?php

namespace App\Domain\Integration\Events;

use App\Domain\Integration\Models\CompanyIntegration;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class IntegrationUninstalled
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly CompanyIntegration $companyIntegration
    ) {}
}
