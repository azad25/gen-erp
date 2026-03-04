<?php

use App\Http\Controllers\Auth\APITokenController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\TwoFactorController;
use App\Http\Controllers\CompanySwitchController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

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
Route::get('/locale/{locale}', [HomeController::class, 'setLocale'])->name('locale.set');

// ── Public Tracking Page ────────────────────────────────────
Route::get('/track', function () {
    return view('public.tracking');
})->name('public.tracking');

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
    Route::get('/sales/orders', [\App\Http\Controllers\Sales\SalesOrderController::class, 'index'])->name('sales.orders');
    Route::get('/sales/invoices', [\App\Http\Controllers\Sales\InvoiceController::class, 'index'])->name('sales.invoices');
    Route::get('/sales/customers', fn () => Inertia::render('Sales/Customers'))->name('sales.customers');
    Route::get('/sales/credit-notes', fn () => Inertia::render('Sales/CreditNotes'))->name('sales.credit-notes');
    Route::get('/sales/returns', fn () => Inertia::render('Sales/Returns'))->name('sales.returns');

    // Purchase Routes
    Route::get('/purchase/orders', fn () => Inertia::render('Purchase/Orders'))->name('purchase.orders');
    Route::get('/purchase/receipts', fn () => Inertia::render('Purchase/Receipts'))->name('purchase.receipts');
    Route::get('/purchase/suppliers', fn () => Inertia::render('Purchase/Suppliers'))->name('purchase.suppliers');
    Route::get('/purchase/returns', fn () => Inertia::render('Purchase/Returns'))->name('purchase.returns');

    // Inventory Routes
    Route::get('/inventory/products', fn () => Inertia::render('Inventory/Products'))->name('inventory.products');
    Route::get('/inventory/stock', fn () => Inertia::render('Inventory/Stock'))->name('inventory.stock');
    Route::get('/inventory/warehouses', fn () => Inertia::render('Inventory/Warehouses'))->name('inventory.warehouses');
    Route::get('/inventory/transfers', fn () => Inertia::render('Inventory/Transfers'))->name('inventory.transfers');
    Route::get('/inventory/adjustments', fn () => Inertia::render('Inventory/Adjustments'))->name('inventory.adjustments');

    // Accounting Routes
    Route::get('/accounting/chart-of-accounts', fn () => Inertia::render('Accounting/ChartOfAccounts'))->name('accounting.chart-of-accounts');
    Route::get('/accounting/journal-entries', fn () => Inertia::render('Accounting/JournalEntries'))->name('accounting.journal-entries');
    Route::get('/accounting/trial-balance', fn () => Inertia::render('Accounting/TrialBalance'))->name('accounting.trial-balance');
    Route::get('/accounting/profit-loss', fn () => Inertia::render('Accounting/ProfitLoss'))->name('accounting.profit-loss');
    Route::get('/accounting/balance-sheet', fn () => Inertia::render('Accounting/BalanceSheet'))->name('accounting.balance-sheet');

    // HR & Payroll Routes
    Route::get('/hr/employees', fn () => Inertia::render('HR/Employees'))->name('hr.employees');
    Route::get('/hr/attendance', fn () => Inertia::render('HR/Attendance'))->name('hr.attendance');
    Route::get('/hr/leave', fn () => Inertia::render('HR/Leave'))->name('hr.leave');
    Route::get('/hr/payroll', fn () => Inertia::render('HR/Payroll'))->name('hr.payroll');

    // HR Enhancement Routes
    Route::prefix('hr')->name('hr.')->group(function () {
        Route::get('/tasks/dashboard', fn () => Inertia::render('HR/Tasks/Dashboard'))->name('tasks.dashboard');
        Route::get('/timesheet', fn () => Inertia::render('HR/Timesheet/Index'))->name('timesheet.index');
        Route::get('/capacity', fn () => Inertia::render('HR/Capacity/Index'))->name('capacity.index');
        Route::get('/skills', fn () => Inertia::render('HR/Skills/Index'))->name('skills.index');
        Route::get('/availability', fn () => Inertia::render('HR/Availability/Calendar'))->name('availability.calendar');
        Route::get('/performance', fn () => Inertia::render('HR/Performance/Index'))->name('performance.index');
        Route::get('/performance/{review}', fn ($review) => Inertia::render('HR/Performance/Show', ['reviewId' => $review]))->name('performance.show');
    });

    // POS Routes
    Route::get('/pos/session', fn () => Inertia::render('POS/Session'))->name('pos.session');

    // Reports Routes
    Route::get('/reports', fn () => Inertia::render('Reports/Index'))->name('reports');

    // Settings Routes
    Route::get('/settings/company', fn () => Inertia::render('Settings/Company'))->name('settings.company');
    Route::get('/settings/users', fn () => Inertia::render('Settings/Users'))->name('settings.users');
    Route::get('/settings/roles', fn () => Inertia::render('Settings/Roles'))->name('settings.roles');
    Route::get('/settings/workflows', fn () => Inertia::render('Settings/Workflows'))->name('settings.workflows');
    Route::get('/settings/integrations', fn () => Inertia::render('Settings/Integrations'))->name('settings.integrations');

    // Profile Route
    Route::get('/profile', fn () => Inertia::render('Profile/Index'))->name('profile');

    // Project Management Routes
    Route::prefix('projects')->name('projects.')->group(function () {
        Route::get('/', fn () => Inertia::render('Projects/Index'))->name('index');
        Route::get('/dashboard', fn () => Inertia::render('Projects/Dashboard'))->name('dashboard');
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
        Route::get('/dashboard', fn () => Inertia::render('CRM/Dashboard/Index'))->name('dashboard');
        Route::get('/leads', fn () => Inertia::render('CRM/Leads/Index'))->name('leads.index');
        Route::get('/leads/create', fn () => Inertia::render('CRM/Leads/Create'))->name('leads.create');
        Route::get('/leads/{lead}/edit', fn ($lead) => Inertia::render('CRM/Leads/Edit', ['leadId' => $lead]))->name('leads.edit');
        Route::get('/leads/scoring', fn () => Inertia::render('CRM/Leads/Scoring'))->name('leads.scoring');
        Route::get('/opportunities', fn () => Inertia::render('CRM/Opportunities/Index'))->name('opportunities.index');
        Route::get('/pipelines', fn () => Inertia::render('CRM/Pipelines/Index'))->name('pipelines.index');
        Route::get('/activities', fn () => Inertia::render('CRM/Activities/Index'))->name('activities.index');
        Route::get('/contacts', fn () => Inertia::render('CRM/Contacts/Index'))->name('contacts.index');
    });

    // CMS Routes
    Route::prefix('cms')->name('cms.')->group(function () {
        Route::get('/sites', fn () => Inertia::render('CMS/Sites/Index'))->name('sites.index');
        Route::get('/sites/create', fn () => Inertia::render('CMS/Sites/Create'))->name('sites.create');
        Route::get('/sites/{site}/edit', fn () => Inertia::render('CMS/Sites/Edit'))->name('sites.edit');
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

    // Removed redirect from / to /dashboard - home page should be public
});

// ── Test Route ────────────────────────────────────────────
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
