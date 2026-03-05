<?php

use App\Http\Controllers\Auth\APITokenController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\TwoFactorController;
use App\Http\Controllers\CompanySwitchController;
use App\Http\Controllers\DocsController;
use App\Http\Controllers\DashboardController;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LanguageController;
use Illuminate\Support\Facades\Route;

// ── Emergency Company Fix Route (bypasses middleware) ──────
Route::get('/emergency-company-fix', function () {
    if (!auth()->check()) {
        return redirect()->route('login');
    }
    
    $user = auth()->user();
    
    // Get any active company for this user
    $company = $user->companies()
        ->where('companies.is_active', true)
        ->wherePivot('is_active', true)
        ->first();
    
    if (!$company) {
        return redirect()->route('company.setup')->with('error', 'No active companies found. Please create a company.');
    }
    
    // Force set session
    session(['active_company_id' => $company->id]);
    $user->update(['last_active_company_id' => $company->id]);
    
    // Clear any cached data
    \App\Services\CompanyContext::setActive($company);
    
    return redirect()->route('dashboard')->with('success', "Emergency fix applied. Active company: {$company->name}");
})->middleware('auth')->name('emergency.company.fix');

// ── Home Page (Vue) ────────────────────────────────────────
Route::inertia('/', 'Home')->name('home');
// Removed duplicate locale route - use POST /language/switch instead

// ── Language Switching ────────────────────────────────────
Route::post('/language/switch', [LanguageController::class, 'switch'])->name('language.switch');

// ── Public Tracking Page ────────────────────────────────────
Route::get('/track', function () {
    return view('public.tracking');
})->name('public.tracking');

// ── Public Form Routes ────────────────────────────────────
Route::get('/forms/{slug}', [\App\Http\Controllers\Document\FormController::class, 'publicForm'])->name('public.forms.show');
Route::get('/public/forms/{slug}', [\App\Http\Controllers\Document\FormController::class, 'publicForm'])->name('documents.forms.public');
Route::post('/forms/{slug}/submit', [\App\Http\Controllers\Document\FormController::class, 'submitPublicForm'])->name('public.forms.submit');

// ── Guest Auth Routes (Vue) ────────────────────────────────────
Route::middleware('guest')->group(function (): void {
    Route::inertia('/register', 'Auth/Signup')->name('register');
    Route::post('/register', [RegisterController::class, 'register']);

    Route::inertia('/login', 'Auth/Signin')->name('login');
    Route::post('/login', [LoginController::class, 'login'])->middleware('throttle:5,15');

    Route::inertia('/auth/two-factor/challenge', 'Auth/TwoFactorChallenge')->name('auth.two-factor.challenge');
});

// ── SPA Auth Routes (JSON API for Vue.js) ────────────────
Route::prefix('auth')->group(function (): void {
    // Guest routes
    Route::middleware('guest')->group(function (): void {
        Route::post('/login', [AuthController::class, 'login']);
        Route::post('/two-factor/challenge', [TwoFactorController::class, 'challenge']);
    });

    // Authenticated routes - use 'auth' middleware for session-based auth
    Route::middleware('auth')->group(function (): void {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/user', [AuthController::class, 'user']);

        // 2FA management
        Route::post('/two-factor/enable', [TwoFactorController::class, 'enable']);
        Route::post('/two-factor/confirm', [TwoFactorController::class, 'confirm']);
        Route::post('/two-factor/disable', [TwoFactorController::class, 'disable']);

        // API Token management
        Route::get('/tokens', [APITokenController::class, 'index']);
        Route::post('/tokens', [APITokenController::class, 'store']);
        Route::delete('/tokens/{tokenId}', [APITokenController::class, 'destroy']);
    });
});

// ── Web Auth Routes (for traditional web views) ─────────────
Route::middleware('auth')->group(function (): void {
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
    
    // Language switching
    Route::post('/language/switch', [LanguageController::class, 'switch'])->name('language.switch');

    // Company access management
    Route::get('/fix-company-access', [\App\Http\Controllers\CompanyAccessController::class, 'fixAccess'])->name('company.fix-access');
    Route::get('/select-company', [\App\Http\Controllers\CompanyAccessController::class, 'selectCompany'])->name('company.select');
    Route::post('/switch-company/{company}', [\App\Http\Controllers\CompanyAccessController::class, 'switchCompany'])->name('company.switch-to');

    // Company setup wizard (before ensure.company — user may have no company yet)
    Route::inertia('/setup/company', 'Auth/CompanySetup')->name('company.setup');

    // Company switcher (JSON API)
    Route::post('/api/switch-company/{companyId}', [CompanySwitchController::class, 'switchApi'])
        ->name('company.switch.api');

    // Company switcher (Web)
    Route::post('/app/switch-company/{companyId}', [CompanySwitchController::class, 'switch'])
        ->name('company.switch');

    // Document routes (signed URLs for security)
    Route::prefix('documents')->group(function () {
        Route::get('/{document}/download', [DocumentController::class, 'download'])
            ->name('documents.download')
            ->middleware('signed');
        Route::get('/{document}/thumbnail', [DocumentController::class, 'thumbnail'])
            ->name('documents.thumbnail')
            ->middleware('signed');
        Route::get('/{document}/preview', [DocumentController::class, 'preview'])
            ->name('documents.preview')
            ->middleware('signed');
    });
});

// ── Inertia App Routes ────────────────────────────────────
Route::middleware(['auth', 'verified', 'ensure.company'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Sales Routes
    Route::get('/sales/dashboard', [\App\Http\Controllers\Sales\SalesDashboardController::class, 'index'])->name('sales.dashboard');
    Route::get('/sales/invoice-dashboard', fn () => Inertia::render('Sales/InvoiceDashboard'))->name('sales.invoice-dashboard');
    Route::get('/sales/orders', [\App\Http\Controllers\Sales\SalesOrderController::class, 'index'])->name('sales.orders');
    Route::get('/sales/invoices', [\App\Http\Controllers\Sales\InvoiceController::class, 'index'])->name('sales.invoices');
    Route::get('/sales/customers', fn () => Inertia::render('Sales/Customers'))->name('sales.customers');
    Route::get('/sales/credit-notes', fn () => Inertia::render('Sales/CreditNotes'))->name('sales.credit-notes');
    Route::get('/sales/returns', fn () => Inertia::render('Sales/Returns'))->name('sales.returns');

    // Inbox / Messaging
    Route::get('/inbox', fn () => Inertia::render('Inbox/Index'))->name('inbox');

    // Purchase Routes
    Route::get('/purchase/dashboard', [\App\Http\Controllers\Purchase\PurchaseDashboardController::class, 'index'])->name('purchase.dashboard');
    Route::get('/purchase/orders', fn () => Inertia::render('Purchase/Orders'))->name('purchase.orders');
    Route::get('/purchase/receipts', fn () => Inertia::render('Purchase/Receipts'))->name('purchase.receipts');
    Route::get('/purchase/suppliers', fn () => Inertia::render('Purchase/Suppliers'))->name('purchase.suppliers');
    Route::get('/purchase/returns', fn () => Inertia::render('Purchase/Returns'))->name('purchase.returns');

    // Inventory Routes
    Route::get('/inventory/dashboard', [\App\Http\Controllers\Inventory\InventoryDashboardController::class, 'index'])->name('inventory.dashboard');
    Route::get('/inventory/products', fn () => Inertia::render('Inventory/Products'))->name('inventory.products');
    Route::get('/inventory/stock', fn () => Inertia::render('Inventory/Stock'))->name('inventory.stock');
    Route::get('/inventory/warehouses', fn () => Inertia::render('Inventory/Warehouses'))->name('inventory.warehouses');
    Route::get('/inventory/transfers', fn () => Inertia::render('Inventory/Transfers'))->name('inventory.transfers');
    Route::get('/inventory/adjustments', fn () => Inertia::render('Inventory/Adjustments'))->name('inventory.adjustments');

    // Accounting Routes
    Route::get('/accounting/dashboard', [\App\Http\Controllers\Accounting\AccountingDashboardController::class, 'index'])->name('accounting.dashboard');
    Route::get('/accounting/chart-of-accounts', fn () => Inertia::render('Accounting/ChartOfAccounts'))->name('accounting.chart-of-accounts');
    Route::get('/accounting/journal-entries', fn () => Inertia::render('Accounting/JournalEntries'))->name('accounting.journal-entries');
    Route::get('/accounting/trial-balance', fn () => Inertia::render('Accounting/TrialBalance'))->name('accounting.trial-balance');
    Route::get('/accounting/profit-loss', fn () => Inertia::render('Accounting/ProfitLoss'))->name('accounting.profit-loss');
    Route::get('/accounting/balance-sheet', fn () => Inertia::render('Accounting/BalanceSheet'))->name('accounting.balance-sheet');

    // HR & Payroll Routes
    Route::get('/hr/dashboard', [\App\Http\Controllers\HR\HRDashboardController::class, 'index'])->name('hr.dashboard');
    Route::get('/hr/employees', fn () => Inertia::render('HR/Employees'))->name('hr.employees');
    Route::get('/hr/attendance', fn () => Inertia::render('HR/Attendance'))->name('hr.attendance');
    Route::get('/hr/leave', fn () => Inertia::render('HR/Leave'))->name('hr.leave');
    Route::get('/hr/payroll', fn () => Inertia::render('HR/Payroll'))->name('hr.payroll');

    // HR Enhancement Routes
    Route::prefix('hr')->name('hr.')->group(function () {
        Route::get('/tasks/dashboard', function (Request $request) {
            $apiController = app(\App\Http\Controllers\Api\V1\HR\EmployeeTaskController::class);
            $response = $apiController->dashboard($request);
            $data = json_decode($response->getContent(), true);
            
            return Inertia::render('HR/Tasks/Dashboard', [
                'taskData' => $data['data']
            ]);
        })->name('tasks.dashboard');
        
        Route::get('/timesheet', function (Request $request) {
            $apiController = app(\App\Http\Controllers\Api\V1\HR\EmployeeTimeEntryController::class);
            $response = $apiController->index($request);
            $data = json_decode($response->getContent(), true);
            
            return Inertia::render('HR/Timesheet/Index', [
                'timeEntries' => $data['data']
            ]);
        })->name('timesheet.index');
        
        Route::get('/capacity', function (Request $request) {
            $apiController = app(\App\Http\Controllers\Api\V1\HR\EmployeeCapacityController::class);
            $response = $apiController->index($request);
            $data = json_decode($response->getContent(), true);
            
            return Inertia::render('HR/Capacity/Index', [
                'capacityData' => $data['data']
            ]);
        })->name('capacity.index');
        
        Route::get('/skills', fn () => Inertia::render('HR/Skills/Index'))->name('skills.index');
        Route::get('/availability', fn () => Inertia::render('HR/Availability/Calendar'))->name('availability.calendar');
        Route::get('/performance', fn () => Inertia::render('HR/Performance/Index'))->name('performance.index');
        Route::get('/performance/{review}', fn ($review) => Inertia::render('HR/Performance/Show', ['reviewId' => $review]))->name('performance.show');
    });

    // POS Routes
    Route::prefix('pos')->name('pos.')->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\POS\POSDashboardController::class, 'index'])->name('dashboard');
        Route::get('/terminal', fn () => Inertia::render('POS/Terminal'))->name('terminal');
        Route::get('/sessions', fn () => Inertia::render('POS/Sessions'))->name('sessions.index');
        Route::get('/sales', fn () => Inertia::render('POS/Sales'))->name('sales.index');
    });

    // Payment Routes
    Route::prefix('payments')->name('payments.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Payment\PaymentController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\Payment\PaymentController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\Payment\PaymentController::class, 'store'])->name('store');
        Route::get('/{id}', [\App\Http\Controllers\Payment\PaymentController::class, 'show'])->name('show');
        Route::get('/{id}/edit', [\App\Http\Controllers\Payment\PaymentController::class, 'edit'])->name('edit');
        Route::put('/{id}', [\App\Http\Controllers\Payment\PaymentController::class, 'update'])->name('update');
        Route::get('/{id}/allocate', [\App\Http\Controllers\Payment\PaymentController::class, 'allocate'])->name('allocate');
        Route::post('/{id}/allocate', [\App\Http\Controllers\Payment\PaymentController::class, 'storeAllocation'])->name('allocate.store');
    });

    // Custom Fields Routes (Documents)
    Route::prefix('documents/custom-fields')->name('documents.custom-fields.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Document\CustomFieldController::class, 'index'])->name('index');
        Route::post('/', [\App\Http\Controllers\Document\CustomFieldController::class, 'store'])->name('store');
        
        // Specific routes must come before parameter routes
        Route::get('/grouped/by-entity', [\App\Http\Controllers\Document\CustomFieldController::class, 'grouped'])->name('grouped');
        Route::get('/stats/overview', [\App\Http\Controllers\Document\CustomFieldController::class, 'stats'])->name('stats');
        Route::get('/domains/list', [\App\Http\Controllers\Document\CustomFieldController::class, 'domains'])->name('domains');
        Route::get('/entity-types/list', [\App\Http\Controllers\Document\CustomFieldController::class, 'entityTypes'])->name('entity-types');
        Route::post('/reorder', [\App\Http\Controllers\Document\CustomFieldController::class, 'updateOrder'])->name('update-order');
        
        // Parameter routes come last
        Route::get('/{id}', [\App\Http\Controllers\Document\CustomFieldController::class, 'show'])->name('show');
        Route::put('/{id}', [\App\Http\Controllers\Document\CustomFieldController::class, 'update'])->name('update');
        Route::delete('/{id}', [\App\Http\Controllers\Document\CustomFieldController::class, 'destroy'])->name('destroy');
    });

    // Reports Routes
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/dashboard', fn () => Inertia::render('Reports/Dashboard/Index'))->name('dashboard');
        
        // Financial Reports
        Route::prefix('financial')->name('financial.')->group(function () {
            Route::get('/trial-balance', fn () => Inertia::render('Reports/Financial/TrialBalance'))->name('trial-balance');
            Route::get('/profit-loss', fn () => Inertia::render('Reports/Financial/ProfitLoss'))->name('profit-loss');
            Route::get('/balance-sheet', fn () => Inertia::render('Reports/Financial/BalanceSheet'))->name('balance-sheet');
            Route::get('/cash-flow', fn () => Inertia::render('Reports/Financial/CashFlow'))->name('cash-flow');
        });
        
        // Aging Reports
        Route::prefix('aging')->name('aging.')->group(function () {
            Route::get('/accounts-receivable', fn () => Inertia::render('Reports/Aging/AccountsReceivable'))->name('ar');
            Route::get('/accounts-payable', fn () => Inertia::render('Reports/Aging/AccountsPayable'))->name('ap');
        });
        
        // Inventory Reports
        Route::prefix('inventory')->name('inventory.')->group(function () {
            Route::get('/valuation', fn () => Inertia::render('Reports/Inventory/Valuation'))->name('valuation');
        });
        
        // VAT Reports
        Route::prefix('vat')->name('vat.')->group(function () {
            Route::get('/', fn () => Inertia::render('Reports/VAT/Index'))->name('index');
            Route::get('/liability', fn () => Inertia::render('Reports/VAT/Liability'))->name('liability');
            Route::get('/mushak63', fn () => Inertia::render('Reports/VAT/Mushak63'))->name('mushak63');
        });
        
        // Comparative Reports
        Route::prefix('comparative')->name('comparative.')->group(function () {
            Route::get('/yoy-profit-loss', fn () => Inertia::render('Reports/Comparative/YoYProfitLoss'))->name('yoy-profit-loss');
        });
        
        // Report Builder
        Route::get('/builder', fn () => Inertia::render('Reports/Builder/Index'))->name('builder.index');
    });

    // Settings Routes
    Route::get('/settings/company', fn () => Inertia::render('Settings/Company'))->name('settings.company');
    Route::get('/settings/users', fn () => Inertia::render('Settings/Users'))->name('settings.users');
    Route::get('/settings/roles', fn () => Inertia::render('Settings/Roles'))->name('settings.roles');
    Route::get('/settings/workflows', fn () => Inertia::render('Settings/Workflows'))->name('settings.workflows');
    Route::get('/settings/integrations', fn () => Inertia::render('Settings/Integrations'))->name('settings.integrations');

    // Integrations Routes
    Route::prefix('integrations')->name('integrations.')->group(function () {
        Route::get('/', fn () => Inertia::render('Integrations/Index'))->name('index');
        Route::get('/{id}/configure', fn ($id) => Inertia::render('Integrations/Configure', ['id' => $id]))->name('configure');
    });

    // Subscription Routes
    Route::prefix('subscription')->name('subscription.')->group(function () {
        Route::get('/', fn () => Inertia::render('Subscription/Index'))->name('index');
        Route::get('/plans', fn () => Inertia::render('Subscription/Plans'))->name('plans');
    });

    // Admin Subscription Routes (Master Admin & Dev Admin only)
    Route::prefix('admin/subscription')->name('admin.subscription.')->group(function () {
        Route::get('/dashboard', fn () => Inertia::render('Admin/SubscriptionDashboard'))->name('dashboard');
        Route::get('/subscriptions', fn () => Inertia::render('Admin/Subscriptions'))->name('subscriptions');
        Route::get('/payment-requests', fn () => Inertia::render('Admin/PaymentRequests'))->name('payment-requests');
        Route::get('/invoices', fn () => Inertia::render('Admin/Invoices'))->name('invoices');
    });

    // Profile Route
    Route::get('/profile', fn () => Inertia::render('Profile/Index'))->name('profile');

    // Notifications Route
    Route::get('/notifications', fn () => Inertia::render('Notifications/Index'))->name('notifications.index');

    // Document Management Routes
    Route::prefix('documents')->name('documents.')->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\Documents\DocumentController::class, 'dashboard'])->name('dashboard');
        Route::get('/', [\App\Http\Controllers\Documents\DocumentController::class, 'index'])->name('index');
        Route::get('/folders', [\App\Http\Controllers\Documents\DocumentController::class, 'folders'])->name('folders');
        Route::get('/recent', [\App\Http\Controllers\Documents\DocumentController::class, 'recent'])->name('recent');
        
        // Forms Management Routes
        Route::prefix('forms')->name('forms.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Document\FormController::class, 'index'])->name('index');
            Route::get('/builder', [\App\Http\Controllers\Document\FormController::class, 'builder'])->name('builder');
            Route::get('/create', [\App\Http\Controllers\Document\FormController::class, 'create'])->name('create');
            Route::post('/', [\App\Http\Controllers\Document\FormController::class, 'store'])->name('store');
            Route::get('/{form}', [\App\Http\Controllers\Document\FormController::class, 'show'])->name('show');
            Route::get('/{form}/edit', [\App\Http\Controllers\Document\FormController::class, 'edit'])->name('edit');
            Route::put('/{form}', [\App\Http\Controllers\Document\FormController::class, 'update'])->name('update');
            Route::delete('/{form}', [\App\Http\Controllers\Document\FormController::class, 'destroy'])->name('destroy');
            Route::post('/{form}/submit', [\App\Http\Controllers\Document\FormController::class, 'submit'])->name('submit');
            Route::get('/{form}/submissions', [\App\Http\Controllers\Document\FormController::class, 'submissions'])->name('submissions');
            Route::get('/{form}/export', [\App\Http\Controllers\Document\FormController::class, 'exportSubmissions'])->name('export');
            Route::post('/{form}/duplicate', [\App\Http\Controllers\Document\FormController::class, 'duplicate'])->name('duplicate');
        });
        
        // Custom Fields Management Routes
        Route::prefix('custom-fields')->name('custom-fields.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Document\CustomFieldController::class, 'index'])->name('index');
            Route::get('/overview', [\App\Http\Controllers\Document\CustomFieldController::class, 'overview'])->name('overview');
            Route::get('/create', [\App\Http\Controllers\Document\CustomFieldController::class, 'create'])->name('create');
            Route::get('/grouped', [\App\Http\Controllers\Document\CustomFieldController::class, 'grouped'])->name('grouped');
            Route::get('/stats', [\App\Http\Controllers\Document\CustomFieldController::class, 'stats'])->name('stats');
            Route::get('/domains', [\App\Http\Controllers\Document\CustomFieldController::class, 'domains'])->name('domains');
            Route::get('/entity-types', [\App\Http\Controllers\Document\CustomFieldController::class, 'entityTypes'])->name('entity-types');
            Route::get('/api/entity-types', [\App\Http\Controllers\Document\CustomFieldController::class, 'getEntityTypes'])->name('api.entity-types');
            Route::get('/export', [\App\Http\Controllers\Document\CustomFieldController::class, 'export'])->name('export');
            Route::post('/', [\App\Http\Controllers\Document\CustomFieldController::class, 'store'])->name('store');
            Route::post('/bulk-action', [\App\Http\Controllers\Document\CustomFieldController::class, 'bulkAction'])->name('bulk-action');
            Route::post('/update-order', [\App\Http\Controllers\Document\CustomFieldController::class, 'updateOrder'])->name('update-order');
            Route::get('/{customField}', [\App\Http\Controllers\Document\CustomFieldController::class, 'show'])->name('show');
            Route::get('/{customField}/edit', [\App\Http\Controllers\Document\CustomFieldController::class, 'edit'])->name('edit');
            Route::put('/{customField}', [\App\Http\Controllers\Document\CustomFieldController::class, 'update'])->name('update');
            Route::delete('/{customField}', [\App\Http\Controllers\Document\CustomFieldController::class, 'destroy'])->name('destroy');
        });
    });

    // Project Management Routes
    Route::prefix('projects')->name('projects.')->group(function () {
        Route::get('/', function (Request $request) {
            $apiController = app(\App\Http\Controllers\Api\V1\ProjectController::class);
            $response = $apiController->index($request);
            $data = json_decode($response->getContent(), true);
            
            return Inertia::render('Projects/Index', [
                'projects' => $data['data']
            ]);
        })->name('index');
        
        Route::get('/dashboard', [\App\Http\Controllers\Projects\ProjectsDashboardController::class, 'index'])->name('dashboard');
        Route::get('/create', fn () => Inertia::render('Projects/Create'))->name('create');
        Route::get('/{project}', fn ($project) => Inertia::render('Projects/Show', ['projectId' => $project]))->name('show');
        Route::get('/{project}/edit', fn ($project) => Inertia::render('Projects/Edit', ['projectId' => $project]))->name('edit');
        Route::get('/{project}/board', fn ($project) => Inertia::render('Projects/Board', ['projectId' => $project]))->name('board');
        Route::get('/{project}/reports', fn ($project) => Inertia::render('Projects/Reports', ['projectId' => $project]))->name('reports');
    });

    // Task Management Routes
    Route::prefix('tasks')->name('tasks.')->group(function () {
        Route::get('/', fn () => Inertia::render('Tasks/Index'))->name('index');
        Route::get('/create', fn () => Inertia::render('Tasks/Create', ['project_id' => request('project_id')]))->name('create');
        Route::get('/{task}', fn ($task) => Inertia::render('Tasks/Show', ['taskId' => $task]))->name('show');
        Route::get('/{task}/edit', fn ($task) => Inertia::render('Tasks/Edit', ['taskId' => $task]))->name('edit');
    });

    // CRM Routes
    Route::prefix('crm')->name('crm.')->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\CRM\CRMDashboardController::class, 'index'])->name('dashboard');
        
        Route::get('/leads', function (Request $request) {
            $apiController = app(\App\Http\Controllers\Api\V1\CRM\LeadController::class);
            $response = $apiController->index($request);
            $data = json_decode($response->getContent(), true);
            
            return Inertia::render('CRM/Leads/Index', [
                'leads' => $data['data']
            ]);
        })->name('leads.index');
        
        Route::get('/leads/create', fn () => Inertia::render('CRM/Leads/Create'))->name('leads.create');
        Route::get('/leads/{lead}/edit', fn ($lead) => Inertia::render('CRM/Leads/Edit', ['leadId' => $lead]))->name('leads.edit');
        Route::get('/leads/scoring', fn () => Inertia::render('CRM/Leads/Scoring'))->name('leads.scoring');
        
        Route::get('/opportunities', function (Request $request) {
            $apiController = app(\App\Http\Controllers\Api\V1\CRM\OpportunityController::class);
            $response = $apiController->index($request);
            $data = json_decode($response->getContent(), true);
            
            return Inertia::render('CRM/Opportunities/Index', [
                'opportunities' => $data['data']
            ]);
        })->name('opportunities.index');
        
        Route::get('/pipelines', function (Request $request) {
            $apiController = app(\App\Http\Controllers\Api\V1\CRM\PipelineController::class);
            $response = $apiController->index($request);
            $data = json_decode($response->getContent(), true);
            
            return Inertia::render('CRM/Pipelines/Index', [
                'pipelines' => $data['data']
            ]);
        })->name('pipelines.index');
        
        Route::get('/activities', function (Request $request) {
            $apiController = app(\App\Http\Controllers\Api\V1\CRM\ActivityController::class);
            $response = $apiController->index($request);
            $data = json_decode($response->getContent(), true);
            
            return Inertia::render('CRM/Activities/Index', [
                'activities' => $data['data']
            ]);
        })->name('activities.index');
        
        Route::get('/contacts', fn () => Inertia::render('CRM/Contacts/Index'))->name('contacts.index');
    });

    // CMS Routes
    Route::prefix('cms')->name('cms.')->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\CMS\CMSDashboardController::class, 'index'])->name('dashboard');
        
        // Sites - using existing API controller with Inertia wrapper
        Route::get('/sites', function (Request $request) {
            $apiController = app(\App\Http\Controllers\Api\V1\SiteController::class);
            $response = $apiController->index($request);
            $data = json_decode($response->getContent(), true);
            
            return Inertia::render('CMS/Sites/Index', [
                'sites' => $data['data']
            ]);
        })->name('sites.index');
        
        Route::get('/sites/create', fn () => Inertia::render('CMS/Sites/Create'))->name('sites.create');
        Route::get('/sites/{site}/edit', function ($site) {
            return Inertia::render('CMS/Sites/Edit', ['siteId' => $site]);
        })->name('sites.edit');
        
        // Pages
        Route::get('/sites/{site}/pages', fn () => Inertia::render('CMS/Pages/Index'))->name('sites.pages.index');
        Route::get('/sites/{site}/pages/create', fn () => Inertia::render('CMS/Pages/Create'))->name('sites.pages.create');
        Route::get('/sites/{site}/pages/{page}/edit', fn () => Inertia::render('CMS/Pages/Edit'))->name('sites.pages.edit');
        Route::get('/sites/{site}/pages/{page}/builder', fn () => Inertia::render('CMS/PageBuilder/Index'))->name('sites.pages.builder');
        
        Route::get('/pages', fn () => Inertia::render('CMS/Pages/Index'))->name('pages.index');
        Route::get('/menus', fn () => Inertia::render('CMS/Menus/Index'))->name('menus.index');
        Route::get('/blog', fn () => Inertia::render('CMS/Blog/Index'))->name('blog.index');
        Route::get('/blog/create', fn () => Inertia::render('CMS/Blog/Create'))->name('blog.create');
        Route::get('/blog/{post}/edit', fn () => Inertia::render('CMS/Blog/Edit'))->name('blog.edit');
        Route::get('/contacts', fn () => Inertia::render('CMS/Contacts/Index'))->name('contacts.index');
        Route::get('/reviews', fn () => Inertia::render('CMS/Reviews/Index'))->name('reviews.index');
        Route::get('/wishlist', fn () => Inertia::render('CMS/Wishlist/Index'))->name('wishlist.index');
        Route::get('/seo', fn () => Inertia::render('CMS/SEO/Index'))->name('seo.index');
    });

    // Calendar Route
    Route::get('/calendar', fn () => Inertia::render('Calendar/Index'))->name('calendar');

    // Logistics Routes
    Route::prefix('logistics')->name('logistics.')->group(function () {
        Route::get('/dashboard', fn () => Inertia::render('Logistics/Dashboard/Index'))->name('dashboard');
        
        Route::get('/shipments', function (Request $request) {
            $apiController = app(\App\Domain\Logistics\Http\Controllers\ShipmentController::class);
            $response = $apiController->index($request);
            $data = json_decode($response->getContent(), true);
            
            return Inertia::render('Logistics/Shipments/Index', [
                'shipments' => $data['data']
            ]);
        })->name('shipments.index');
        
        Route::get('/shipments/create', fn () => Inertia::render('Logistics/Shipments/Create'))->name('shipments.create');
        Route::get('/shipments/{shipment}', fn ($shipment) => Inertia::render('Logistics/Shipments/Show', ['shipmentId' => $shipment]))->name('shipments.show');
        Route::get('/shipments/{shipment}/edit', fn ($shipment) => Inertia::render('Logistics/Shipments/Edit', ['shipmentId' => $shipment]))->name('shipments.edit');
        
        Route::get('/tracking', function (Request $request) {
            $apiController = app(\App\Domain\Logistics\Http\Controllers\TrackingController::class);
            $response = $apiController->statistics($request);
            $data = json_decode($response->getContent(), true);
            
            return Inertia::render('Logistics/Tracking/Index', [
                'trackingData' => $data['data']
            ]);
        })->name('tracking.index');
        
        Route::get('/tracking/{trackingNumber}', function ($trackingNumber, Request $request) {
            $apiController = app(\App\Domain\Logistics\Http\Controllers\TrackingController::class);
            $response = $apiController->track($request, $trackingNumber);
            $data = json_decode($response->getContent(), true);
            
            return Inertia::render('Logistics/Tracking/Show', [
                'trackingNumber' => $trackingNumber,
                'trackingInfo' => $data['data']
            ]);
        })->name('tracking.show');
        
        Route::get('/returns', function (Request $request) {
            $apiController = app(\App\Domain\Logistics\Http\Controllers\ReturnController::class);
            $response = $apiController->index($request);
            $data = json_decode($response->getContent(), true);
            
            return Inertia::render('Logistics/Returns/Index', [
                'returns' => $data['data']
            ]);
        })->name('returns.index');
        
        Route::get('/returns/create', fn () => Inertia::render('Logistics/Returns/Create'))->name('returns.create');
        Route::get('/returns/{return}', fn ($return) => Inertia::render('Logistics/Returns/Show', ['returnId' => $return]))->name('returns.show');
        
        Route::get('/cod', function (Request $request) {
            $apiController = app(\App\Domain\Logistics\Http\Controllers\CODController::class);
            $response = $apiController->summary($request);
            $data = json_decode($response->getContent(), true);
            
            return Inertia::render('Logistics/COD/Index', [
                'codData' => $data['data']
            ]);
        })->name('cod.index');
        
        Route::get('/cod/settlements', fn () => Inertia::render('Logistics/COD/Settlements'))->name('cod.settlements');
        
        Route::get('/carriers', fn () => Inertia::render('Logistics/Carriers/Index'))->name('carriers.index');
        Route::get('/carriers/create', fn () => Inertia::render('Logistics/Carriers/Create'))->name('carriers.create');
        Route::get('/carriers/{carrier}/edit', fn ($carrier) => Inertia::render('Logistics/Carriers/Edit', ['carrierId' => $carrier]))->name('carriers.edit');
    });

    // Removed redirect from / to /dashboard - home page should be public
});

// ══════════════════════════════════════════════════════════════════════════════
// ⚠️  TEST/DEBUG ROUTES - COMMENTED OUT FOR PRODUCTION
// ══════════════════════════════════════════════════════════════════════════════
// Uncomment these routes ONLY in local development environment
// NEVER enable these in production - they expose sensitive session/auth data
// ══════════════════════════════════════════════════════════════════════════════

/*
// ── Test Routes (Development Only) ────────────────────────────────────────────
Route::inertia('/test', 'Test');
Route::get('/test-no-middleware', function () {
    \Log::info('[Route] Test no middleware accessed');
    return Inertia::render('SimpleTest');
});
Route::get('/test-no-auth', function () {
    return response()->json([
        'message' => 'This route works without auth',
        'session_id' => session()->getId(),
        'csrf_token' => csrf_token(),
        'cookies' => request()->cookies->all(),
    ]);
});
Route::get('/test-auth-status', function () {
    return response()->json([
        'authenticated' => auth()->check(),
        'user_id' => auth()->id(),
        'user_name' => auth()->user()?->name,
        'session_id' => session()->getId(),
        'guard' => config('auth.defaults.guard'),
        'session_driver' => config('session.driver'),
    ]);
});
Route::get('/test-sanctum-auth', function () {
    return response()->json([
        'web_auth' => auth('web')->check(),
        'sanctum_auth' => auth('sanctum')->check(),
        'web_user' => auth('web')->user()?->name,
        'sanctum_user' => auth('sanctum')->user()?->name,
        'request_has_session' => request()->hasSession(),
        'session_token' => request()->session()->token(),
    ]);
});

// Test API route with web guard instead of sanctum
Route::middleware(['auth:web'])->group(function () {
    Route::get('/api/test-invoices', function () {
        return response()->json([
            'success' => true,
            'data' => [],
            'message' => 'Test API with web auth works',
            'user' => auth()->user()->name,
        ]);
    });
});
Route::middleware(['auth', 'verified', 'ensure.company'])->group(function () {
    Route::get('/test-simple', fn () => Inertia::render('TestSimple'))->name('test.simple');
    Route::get('/debug-auth', fn () => Inertia::render('DebugAuth'))->name('debug.auth');
});

// Debug route to check session and auth state
Route::get('/debug-session', function () {
    $user = auth()->user();
    
    return response()->json([
        'authenticated' => auth()->check(),
        'user' => $user ? [
            'id' => $user->id,
            'email' => $user->email,
            'companies_count' => $user->companies()->count(),
            'last_active_company_id' => $user->last_active_company_id,
            'companies' => $user->companies()->get(['companies.id', 'companies.name', 'companies.is_active'])->map(function($company) {
                return [
                    'id' => $company->id,
                    'name' => $company->name,
                    'is_active' => $company->is_active,
                    'pivot' => [
                        'role' => $company->pivot->role,
                        'is_owner' => $company->pivot->is_owner,
                        'is_active' => $company->pivot->is_active,
                    ]
                ];
            }),
        ] : null,
        'session' => [
            'id' => session()->getId(),
            'active_company_id' => session('active_company_id'),
            'all_data' => session()->all(),
        ],
        'company_context' => [
            'has_active' => \App\Services\CompanyContext::hasActive(),
        ],
    ]);
})->middleware('auth');
*/

// ══════════════════════════════════════════════════════════════════════════════
// END TEST/DEBUG ROUTES
// ══════════════════════════════════════════════════════════════════════════════

// ── Internal Documentation (Public / Optional Auth) ─────────────────────
Route::prefix('docs')->name('docs.')->group(function () {
    Route::get('/{any?}', [DocsController::class, 'index'])
        ->where('any', '.*')
        ->name('index');
});

Route::prefix('docs-api')->name('docs.api.')->group(function () {
    Route::get('/navigation', [DocsController::class, 'navigation'])->name('navigation');
    Route::get('/page', [DocsController::class, 'page'])->name('page');
    Route::get('/search', [DocsController::class, 'search'])->name('search');
});
