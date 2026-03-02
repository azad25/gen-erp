<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\TwoFactorController;
use App\Http\Controllers\Auth\APITokenController;
use App\Http\Controllers\CompanySwitchController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\InvitationController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// ── Home Page (Vue) ────────────────────────────────────────
Route::inertia('/', 'Home')->name('home');
Route::get('/locale/{locale}', [HomeController::class, 'setLocale'])->name('locale.set');

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

    // Authenticated routes
    Route::middleware('auth:sanctum')->group(function (): void {
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

// ── Invitation Accept (works for both guests and authenticated) ──
Route::get('/invite/{token}', [InvitationController::class, 'accept'])->name('invitation.accept');

// ── Authenticated Routes ─────────────────────────────────
Route::middleware('auth')->group(function (): void {
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

    // Company setup wizard (before ensure.company — user may have no company yet)
    Route::get('/setup/company', function () {
        return view('setup.company');
    })->name('setup.company');

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
Route::middleware(['auth','verified','ensure.company'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    // Sales Routes
    Route::get('/sales/orders', fn() => Inertia::render('Sales/Orders'))->name('sales.orders');
    Route::get('/sales/invoices', fn() => Inertia::render('Sales/Invoices'))->name('sales.invoices');
    Route::get('/sales/customers', fn() => Inertia::render('Sales/Customers'))->name('sales.customers');
    Route::get('/sales/credit-notes', fn() => Inertia::render('Sales/CreditNotes'))->name('sales.credit-notes');
    Route::get('/sales/returns', fn() => Inertia::render('Sales/Returns'))->name('sales.returns');
    
    // Purchase Routes
    Route::get('/purchase/orders', fn() => Inertia::render('Purchase/Orders'))->name('purchase.orders');
    Route::get('/purchase/receipts', fn() => Inertia::render('Purchase/Receipts'))->name('purchase.receipts');
    Route::get('/purchase/suppliers', fn() => Inertia::render('Purchase/Suppliers'))->name('purchase.suppliers');
    Route::get('/purchase/returns', fn() => Inertia::render('Purchase/Returns'))->name('purchase.returns');
    
    // Inventory Routes
    Route::get('/inventory/products', fn() => Inertia::render('Inventory/Products'))->name('inventory.products');
    Route::get('/inventory/stock', fn() => Inertia::render('Inventory/Stock'))->name('inventory.stock');
    Route::get('/inventory/warehouses', fn() => Inertia::render('Inventory/Warehouses'))->name('inventory.warehouses');
    Route::get('/inventory/transfers', fn() => Inertia::render('Inventory/Transfers'))->name('inventory.transfers');
    Route::get('/inventory/adjustments', fn() => Inertia::render('Inventory/Adjustments'))->name('inventory.adjustments');
    
    // Accounting Routes
    Route::get('/accounting/chart-of-accounts', fn() => Inertia::render('Accounting/ChartOfAccounts'))->name('accounting.chart-of-accounts');
    Route::get('/accounting/journal-entries', fn() => Inertia::render('Accounting/JournalEntries'))->name('accounting.journal-entries');
    Route::get('/accounting/trial-balance', fn() => Inertia::render('Accounting/TrialBalance'))->name('accounting.trial-balance');
    Route::get('/accounting/profit-loss', fn() => Inertia::render('Accounting/ProfitLoss'))->name('accounting.profit-loss');
    Route::get('/accounting/balance-sheet', fn() => Inertia::render('Accounting/BalanceSheet'))->name('accounting.balance-sheet');
    
    // HR & Payroll Routes
    Route::get('/hr/employees', fn() => Inertia::render('HR/Employees'))->name('hr.employees');
    Route::get('/hr/attendance', fn() => Inertia::render('HR/Attendance'))->name('hr.attendance');
    Route::get('/hr/leave', fn() => Inertia::render('HR/Leave'))->name('hr.leave');
    Route::get('/hr/payroll', fn() => Inertia::render('HR/Payroll'))->name('hr.payroll');
    
    // POS Routes
    Route::get('/pos/session', fn() => Inertia::render('POS/Session'))->name('pos.session');
    
    // Reports Routes
    Route::get('/reports', fn() => Inertia::render('Reports/Index'))->name('reports');

    // Settings Routes
    Route::get('/settings/company', fn() => Inertia::render('Settings/Company'))->name('settings.company');
    Route::get('/settings/users', fn() => Inertia::render('Settings/Users'))->name('settings.users');
    Route::get('/settings/roles', fn() => Inertia::render('Settings/Roles'))->name('settings.roles');
    Route::get('/settings/workflows', fn() => Inertia::render('Settings/Workflows'))->name('settings.workflows');
    Route::get('/settings/integrations', fn() => Inertia::render('Settings/Integrations'))->name('settings.integrations');
    
    // Profile Route
    Route::get('/profile', fn() => Inertia::render('Profile/Index'))->name('profile');
    
    // Removed redirect from / to /dashboard - home page should be public
});

// ── Test Route ────────────────────────────────────────────
Route::inertia('/test', 'Test');
