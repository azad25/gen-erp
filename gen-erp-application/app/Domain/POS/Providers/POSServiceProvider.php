<?php

namespace App\Domain\POS\Providers;

use App\Domain\POS\Contracts\POSServiceInterface;
use App\Domain\POS\Services\POSService;
use Illuminate\Support\ServiceProvider;

class POSServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(POSServiceInterface::class, POSService::class);
    }

    public function boot(): void
    {
        //
    }
}
