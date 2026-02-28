<?php

namespace App\Providers;

use App\Models\CustomFieldDefinition;
use App\Models\EntityAlias;
use App\Observers\CustomFieldDefinitionObserver;
use App\Observers\EntityAliasObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        EntityAlias::observe(EntityAliasObserver::class);
        CustomFieldDefinition::observe(CustomFieldDefinitionObserver::class);
    }
}
