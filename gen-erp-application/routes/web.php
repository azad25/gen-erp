<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\CompanySwitchController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\InvitationController;
use Illuminate\Support\Facades\Route;

// ── Home Page ────────────────────────────────────────────
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/locale/{locale}', [HomeController::class, 'setLocale'])->name('locale.set');

// ── Guest Auth Routes ────────────────────────────────────
Route::middleware('guest')->group(function (): void {
    Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [RegisterController::class, 'register']);

    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->middleware('throttle:5,15');
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

    // Company switcher
    Route::post('/app/switch-company/{companyId}', [CompanySwitchController::class, 'switch'])
        ->name('company.switch');

    // Document download (signed URL)
    Route::get('/documents/{document}/download', \App\Http\Controllers\DocumentDownloadController::class)
        ->name('documents.download')
        ->middleware('signed');
});
