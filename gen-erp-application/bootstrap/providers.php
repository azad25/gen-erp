<?php

return [
    App\Providers\AppServiceProvider::class,
    App\Providers\EventServiceProvider::class,
    App\Providers\AuthServiceProvider::class,
    App\Providers\CqrsServiceProvider::class,
    App\Providers\CRMServiceProvider::class,
    App\Domain\Logistics\Providers\LogisticsServiceProvider::class,
    App\Domain\POS\Providers\POSServiceProvider::class,
];
