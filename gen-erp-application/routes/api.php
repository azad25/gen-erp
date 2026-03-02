<?php

use App\Http\Controllers\Api\V1\UserController;
use App\Http\Controllers\Api\V1\PaymentMethodController;
use App\Http\Controllers\Api\V1\LeaveTypeController;
use App\Http\Controllers\Api\V1\ContactGroupController;
use App\Http\Controllers\Api\V1\CustomerController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\InvoiceController;
use App\Http\Controllers\Api\V1\SupplierController;
use App\Http\Controllers\Api\V1\EmployeeController;
use App\Http\Controllers\Api\V1\SalesOrderController;
use App\Http\Controllers\Api\V1\PurchaseOrderController;
use App\Http\Controllers\Api\V1\StockMovementController;
use App\Http\Controllers\Api\V1\ExpenseController;
use App\Http\Controllers\Api\V1\DocumentController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\CreditNoteController;
use App\Http\Controllers\Api\V1\AttendanceController;
use App\Http\Controllers\Api\V1\LeaveRequestController;
use App\Http\Controllers\Api\V1\PayslipController;
use App\Http\Controllers\Api\V1\PayrollController;
use App\Http\Controllers\Api\V1\WorkflowInstanceController;
use App\Http\Controllers\Api\V1\ApprovalRequestController;
use App\Http\Controllers\Api\V1\ReportController;
use App\Http\Controllers\Api\V1\BranchController;
use App\Http\Controllers\Api\V1\WarehouseController;
use App\Http\Controllers\Api\V1\ProductCategoryController;
use App\Http\Controllers\Api\V1\TaxGroupController;
use App\Http\Controllers\Api\V1\DepartmentController;
use App\Http\Controllers\Api\V1\DesignationController;
use App\Http\Controllers\Api\V1\CompanyController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\CustomFieldController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\AccountController;
use App\Http\Controllers\Api\V1\AccountGroupController;
use App\Http\Controllers\Api\V1\JournalEntryController;
use App\Http\Controllers\Api\V1\DocumentFolderController;
use App\Http\Controllers\Api\V1\InvitationController;
use App\Http\Controllers\Api\V1\ImportJobController;
use App\Http\Middleware\ApiRateLimiter;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — v1
|--------------------------------------------------------------------------
| All routes use Sanctum token auth and the standard response envelope.
*/

Route::prefix('v1')->middleware(['auth:sanctum', 'throttle:api', 'ensure.company'])->group(function (): void {

    // Users (Admin Panel)
    Route::apiResource('users', UserController::class);
    Route::post('users/{user}/add-to-company', [UserController::class, 'addToCompany']);
    Route::post('users/{user}/remove-from-company', [UserController::class, 'removeFromCompany']);

    // Payment Methods
    Route::apiResource('payment-methods', PaymentMethodController::class);

    // Leave Types
    Route::apiResource('leave-types', LeaveTypeController::class);

    // Contact Groups
    Route::apiResource('contact-groups', ContactGroupController::class);

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

    // Sales Orders
    Route::apiResource('sales-orders', SalesOrderController::class);
    Route::post('sales-orders/{salesOrder}/confirm', [SalesOrderController::class, 'confirm']);
    Route::post('sales-orders/{salesOrder}/convert-to-invoice', [SalesOrderController::class, 'convertToInvoice']);
    Route::post('sales-orders/{salesOrder}/cancel', [SalesOrderController::class, 'cancel']);

    // Purchase Orders
    Route::apiResource('purchase-orders', PurchaseOrderController::class);
    Route::post('purchase-orders/{purchaseOrder}/confirm', [PurchaseOrderController::class, 'confirm']);
    Route::post('purchase-orders/{purchaseOrder}/receive', [PurchaseOrderController::class, 'receive']);
    Route::post('purchase-orders/{purchaseOrder}/cancel', [PurchaseOrderController::class, 'cancel']);

    // Stock Movements
    Route::apiResource('stock-movements', StockMovementController::class);

    // Expenses
    Route::apiResource('expenses', ExpenseController::class);

    // Documents
    Route::apiResource('documents', DocumentController::class);
    Route::get('documents/{document}/download', [DocumentController::class, 'download']);
    Route::get('documents/{document}/thumbnail', [DocumentController::class, 'thumbnail']);
    Route::get('documents/{document}/preview', [DocumentController::class, 'preview']);

    // Payments
    Route::apiResource('payments', PaymentController::class);
    Route::post('payments/{payment}/allocate', [PaymentController::class, 'allocate']);

    // Credit Notes
    Route::apiResource('credit-notes', CreditNoteController::class);

    // HR - Attendance
    Route::apiResource('attendance', AttendanceController::class);
    Route::post('attendance/bulk', [AttendanceController::class, 'bulkMark']);

    // HR - Leave Requests
    Route::apiResource('leave-requests', LeaveRequestController::class);
    Route::post('leave-requests/{leaveRequest}/approve', [LeaveRequestController::class, 'approve']);
    Route::post('leave-requests/{leaveRequest}/reject', [LeaveRequestController::class, 'reject']);

    // HR - Payslips
    Route::apiResource('payslips', PayslipController::class);
    Route::get('payslips/{payslip}/download', [PayslipController::class, 'download']);

    // HR - Payroll
    Route::apiResource('payroll', PayrollController::class);
    Route::post('payroll/run', [PayrollController::class, 'run']);

    // Workflows
    Route::apiResource('workflow-instances', WorkflowInstanceController::class);
    Route::post('workflow-instances/{workflowInstance}/transition', [WorkflowInstanceController::class, 'transition']);
    Route::apiResource('approval-requests', ApprovalRequestController::class);
    Route::post('approval-requests/{approvalRequest}/approve', [ApprovalRequestController::class, 'approve']);
    Route::post('approval-requests/{approvalRequest}/reject', [ApprovalRequestController::class, 'reject']);

    // Reports
    Route::apiResource('reports', ReportController::class);
    Route::get('reports/{report}/generate', [ReportController::class, 'generate']);

    // Settings
    Route::apiResource('companies', CompanyController::class);
    Route::apiResource('branches', BranchController::class);
    Route::apiResource('warehouses', WarehouseController::class);
    Route::apiResource('product-categories', ProductCategoryController::class);
    Route::apiResource('tax-groups', TaxGroupController::class);
    Route::apiResource('departments', DepartmentController::class);
    Route::apiResource('designations', DesignationController::class);

    // Notifications
    Route::apiResource('notifications', NotificationController::class);
    Route::post('notifications/{notification}/mark-read', [NotificationController::class, 'markRead']);
    Route::post('notifications/mark-all-read', [NotificationController::class, 'markAllRead']);

    // Custom Fields
    Route::apiResource('custom-fields', CustomFieldController::class);

    // Dashboard
    Route::get('dashboard', [DashboardController::class, 'index']);

    // Chart of Accounts
    Route::apiResource('accounts', AccountController::class);
    Route::apiResource('account-groups', AccountGroupController::class);
    Route::apiResource('journal-entries', JournalEntryController::class);

    // Document Folders
    Route::apiResource('document-folders', DocumentFolderController::class);

    // Invitations
    Route::apiResource('invitations', InvitationController::class);

    // Import Jobs
    Route::apiResource('import-jobs', ImportJobController::class);
});

// Swagger/OpenAPI Documentation (no auth required)
Route::get('/documentation', function () {
    return redirect('/swagger.html');
});
