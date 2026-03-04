<?php

namespace App\Domain\Logistics\Providers;

use App\Domain\Logistics\Contracts\ShipmentServiceInterface;
use App\Domain\Logistics\Contracts\TrackingServiceInterface;
use App\Domain\Logistics\Contracts\ReturnServiceInterface;
use App\Domain\Logistics\Contracts\CODManagementServiceInterface;
use App\Domain\Logistics\Services\ShipmentService;
use App\Domain\Logistics\Services\TrackingService;
use App\Domain\Logistics\Services\ReturnService;
use App\Domain\Logistics\Services\CODManagementService;
use Illuminate\Support\ServiceProvider;

class LogisticsServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->bind(ShipmentServiceInterface::class, ShipmentService::class);
        $this->app->bind(TrackingServiceInterface::class, TrackingService::class);
        $this->app->bind(ReturnServiceInterface::class, ReturnService::class);
        $this->app->bind(CODManagementServiceInterface::class, CODManagementService::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}