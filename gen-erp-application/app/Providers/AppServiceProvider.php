<?php

namespace App\Providers;


use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;

use Laravel\Sanctum\Sanctum;

use App\Events\ModelSaved;
use App\Listeners\EvaluateAlertRules;
use App\Domain\Shared\Models\CustomFieldDefinition;
use App\Domain\Shared\Models\EntityAlias;
use App\Observers\CustomFieldDefinitionObserver;
use App\Observers\EntityAliasObserver;

use App\Domain\Invoice\Repositories\Contracts\InvoiceRepositoryInterface;
use App\Domain\Invoice\Repositories\Eloquent\InvoiceRepository;
use App\Domain\Customer\Repositories\Contracts\CustomerRepositoryInterface;
use App\Domain\Customer\Repositories\Eloquent\CustomerRepository;
use App\Domain\SalesOrder\Repositories\Contracts\SalesOrderRepositoryInterface;
use App\Domain\SalesOrder\Repositories\Eloquent\SalesOrderRepository;
use App\Domain\Product\Repositories\Contracts\ProductRepositoryInterface;
use App\Domain\Product\Repositories\Eloquent\ProductRepository;
use App\Domain\Customer\Services\ContactService;

use App\Domain\Product\Services\ProductService;
use App\Domain\Purchase\Services\PurchaseService;
use App\Domain\Inventory\Services\InventoryService;
use App\Domain\Customer\Services\PaymentService;
use App\Domain\Accounting\Services\AccountingService;
use App\Domain\Accounting\Contracts\AccountingServiceInterface;
use App\Domain\Auth\Services\AuthService;
use App\Domain\Report\Services\ReportBuilderService;
use App\Domain\HR\Services\HRService;
use App\Domain\HR\Services\PayrollService;
use App\Domain\POS\Services\POSService;
use App\Domain\Auth\Services\UserService;
use App\Domain\Auth\Contracts\UserServiceInterface;
use App\Domain\Report\Services\ReportService;
use App\Domain\Subscription\Services\SubscriptionService;
use App\Domain\Document\Services\DocumentService;
use App\Domain\Plugin\Services\PluginManager;
use App\Domain\Purchase\Contracts\PurchaseServiceInterface;
use App\Domain\Inventory\Contracts\InventoryServiceInterface;
use App\Domain\HR\Contracts\HRServiceInterface;
use App\Domain\System\Models\PersonalAccessToken;
use App\Domain\System\Services\SystemService;
use App\Domain\System\Contracts\SystemServiceInterface;
use App\Domain\Document\Contracts\DocumentServiceInterface;
use App\Domain\Product\Contracts\ProductServiceInterface;
use App\Domain\Auth\Contracts\CompanyServiceInterface;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register repository bindings
        $this->app->bind(
            InvoiceRepositoryInterface::class,
            InvoiceRepository::class
        );
        
        $this->app->bind(
            CustomerRepositoryInterface::class,
            CustomerRepository::class
        );
        
        $this->app->bind(
            SalesOrderRepositoryInterface::class,
            SalesOrderRepository::class
        );
        
        $this->app->bind(
            ProductRepositoryInterface::class,
            ProductRepository::class
        );

        // Register domain services as singletons
        $this->app->singleton(ProductService::class);
        $this->app->bind(ProductServiceInterface::class, ProductService::class);
        $this->app->singleton(ContactService::class);
        $this->app->bind(\App\Domain\Customer\Contracts\ContactServiceInterface::class, ContactService::class);
        $this->app->singleton(PurchaseService::class);
        $this->app->bind(PurchaseServiceInterface::class, PurchaseService::class);
        $this->app->singleton(InventoryService::class);
        $this->app->bind(InventoryServiceInterface::class, InventoryService::class);
        $this->app->singleton(PaymentService::class);
        $this->app->bind(\App\Domain\Customer\Contracts\PaymentServiceInterface::class, PaymentService::class);
        $this->app->singleton(AccountingService::class);
        $this->app->bind(AccountingServiceInterface::class, AccountingService::class);
        $this->app->singleton(AuthService::class);
        $this->app->singleton(CompanyService::class);
        $this->app->bind(CompanyServiceInterface::class, CompanyService::class);
        $this->app->singleton(ReportBuilderService::class);
        $this->app->singleton(HRService::class);
        $this->app->bind(HRServiceInterface::class, HRService::class);
        $this->app->singleton(PayrollService::class);
        $this->app->singleton(POSService::class);
        $this->app->singleton(UserService::class);
        $this->app->bind(UserServiceInterface::class, UserService::class);
        $this->app->singleton(ReportService::class);
        $this->app->singleton(SubscriptionService::class);
        $this->app->singleton(DocumentService::class);
        $this->app->bind(DocumentServiceInterface::class, DocumentService::class);
        $this->app->singleton(PluginManager::class);
        $this->app->singleton(SystemService::class);
        $this->app->bind(SystemServiceInterface::class, SystemService::class);

        // Bind old service names to new domain services for backward compatibility
        $this->app->bind(\App\Services\HRService::class, HRService::class);
        $this->app->bind(\App\Services\SubscriptionService::class, SubscriptionService::class);
        $this->app->bind(\App\Services\DocumentService::class, DocumentService::class);
        $this->app->bind(\App\Services\PluginManager::class, PluginManager::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        EntityAlias::observe(EntityAliasObserver::class);
        CustomFieldDefinition::observe(CustomFieldDefinitionObserver::class);

        Event::listen(ModelSaved::class, EvaluateAlertRules::class);

        // Configure Sanctum to use our custom PersonalAccessToken model
        Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);

        // Configure rate limiters
        $this->configureRateLimiting();
    }

    /**
     * Configure the rate limiters for the application.
     */
    protected function configureRateLimiting(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });
    }
}
