<?php

use App\Http\Controllers\Api\V1\CustomerController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\InvoiceController;
use App\Http\Controllers\Api\V1\SupplierController;
use App\Http\Controllers\Api\V1\EmployeeController;
use App\Http\Middleware\ApiRateLimiter;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — v1
|--------------------------------------------------------------------------
| All routes use Sanctum token auth and the standard response envelope.
*/

Route::prefix('v1')->middleware(['auth:sanctum'])->group(function (): void {

    // Customers
    Route::apiResource('customers', CustomerController::class);

    // Products
    Route::apiResource('products', ProductController::class);

    // Invoices
    Route::apiResource('invoices', InvoiceController::class);

    // Suppliers
    Route::apiResource('suppliers', SupplierController::class);

    // Employees
    Route::apiResource('employees', EmployeeController::class);
});
