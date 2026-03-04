<?php

namespace App\Providers;

use App\Domain\CRM\Contracts\ActivityServiceInterface;
use App\Domain\CRM\Contracts\LeadServiceInterface;
use App\Domain\CRM\Contracts\OpportunityServiceInterface;
use App\Domain\CRM\Contracts\PipelineServiceInterface;
use App\Domain\CRM\Services\ActivityService;
use App\Domain\CRM\Services\LeadService;
use App\Domain\CRM\Services\OpportunityService;
use App\Domain\CRM\Services\PipelineService;
use Illuminate\Support\ServiceProvider;

class CRMServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Bind CRM service interfaces to their implementations
        $this->app->bind(LeadServiceInterface::class, LeadService::class);
        $this->app->bind(OpportunityServiceInterface::class, OpportunityService::class);
        $this->app->bind(PipelineServiceInterface::class, PipelineService::class);
        $this->app->bind(ActivityServiceInterface::class, ActivityService::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}