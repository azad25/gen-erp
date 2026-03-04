<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        \App\Domain\Invoice\Models\Invoice::class => \App\Domain\Invoice\Policies\InvoicePolicy::class,
        \App\Domain\SalesOrder\Models\SalesOrder::class => \App\Domain\SalesOrder\Policies\SalesOrderPolicy::class,
        \App\Domain\CMS\Models\Site::class => \App\Domain\CMS\Policies\SitePolicy::class,
        \App\Domain\CMS\Models\Page::class => \App\Domain\CMS\Policies\PagePolicy::class,
        \App\Domain\CMS\Models\Section::class => \App\Domain\CMS\Policies\SectionPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        // Define additional gates if needed
        Gate::define('manage-company', function ($user) {
            return $user->hasRole('admin') || $user->hasRole('manager');
        });
    }
}