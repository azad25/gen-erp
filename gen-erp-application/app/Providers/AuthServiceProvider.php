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
        
        // Major CRUD entities
        \App\Domain\Customer\Models\Customer::class => \App\Policies\CustomerPolicy::class,
        \App\Domain\Product\Models\Product::class => \App\Policies\ProductPolicy::class,
        \App\Domain\Sales\Models\Invoice::class => \App\Policies\InvoicePolicy::class,
        \App\Domain\HR\Models\Employee::class => \App\Policies\EmployeePolicy::class,
        \App\Domain\Purchase\Models\PurchaseOrder::class => \App\Policies\PurchaseOrderPolicy::class,
        
        // CMS Policies (Sprint 4)
        \App\Domain\CMS\Models\Wishlist::class => \App\Policies\WishlistPolicy::class,
        \App\Domain\CMS\Models\Review::class => \App\Policies\ReviewPolicy::class,
        \App\Domain\CMS\Models\Contact::class => \App\Policies\ContactPolicy::class,
        \App\Domain\CMS\Models\Menu::class => \App\Policies\MenuPolicy::class,
        // SEOPolicy doesn't have a model - it's for general SEO management
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