<?php

use App\Http\Controllers\Api\V1\AccountController;
use App\Http\Controllers\Api\V1\AccountGroupController;
use App\Http\Controllers\Api\V1\ApprovalRequestController;
use App\Http\Controllers\Api\V1\AttendanceController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BranchController;
use App\Http\Controllers\Api\V1\CalendarController;
use App\Http\Controllers\Api\V1\CompanyController;
use App\Http\Controllers\Api\V1\ContactGroupController;
use App\Http\Controllers\Api\V1\CostCenterController;
use App\Http\Controllers\Api\V1\CreditNoteController;
use App\Http\Controllers\Api\V1\CustomerController;
use App\Http\Controllers\Api\V1\CustomFieldController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\DepartmentController;
use App\Http\Controllers\Api\V1\DesignationController;
use App\Http\Controllers\Api\V1\DocumentController;
use App\Http\Controllers\Api\V1\DocumentFolderController;
use App\Http\Controllers\Api\V1\EmployeeController;
use App\Http\Controllers\Api\V1\ERPIntegrationController;
use App\Domain\Integration\Http\Controllers\CompanyIntegrationController;
use App\Domain\Integration\Http\Controllers\IntegrationController;
use App\Http\Controllers\Api\V1\ExpenseController;
use App\Http\Controllers\Api\V1\ImportJobController;
use App\Http\Controllers\Api\V1\InvitationController;
use App\Http\Controllers\Api\V1\InvoiceController;
use App\Http\Controllers\Api\V1\JournalEntryController;
use App\Http\Controllers\Api\V1\LeaveRequestController;
use App\Http\Controllers\Api\V1\LeaveTypeController;
use App\Http\Controllers\Api\V1\LockDateController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\PageController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\PaymentMethodController;
use App\Http\Controllers\Api\V1\PayrollController;
use App\Http\Controllers\Api\V1\PayslipController;
use App\Http\Controllers\Api\V1\ProductCategoryController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\ProjectController;
use App\Http\Controllers\Api\V1\PurchaseOrderController;
use App\Http\Controllers\Api\V1\ReportController;
use App\Http\Controllers\Api\V1\SalesOrderController;
use App\Http\Controllers\Api\V1\VatReportController;
use App\Http\Controllers\Api\V1\SectionController;
use App\Http\Controllers\Api\V1\SiteController;
use App\Http\Controllers\Api\V1\StockMovementController;
use App\Http\Controllers\Api\V1\SubscriptionController;
use App\Http\Controllers\Api\V1\AdminSubscriptionController;
use App\Http\Controllers\Api\V1\SupplierController;
use App\Http\Controllers\Api\V1\TaskController;
use App\Http\Controllers\Api\V1\TaxGroupController;
use App\Http\Controllers\Api\V1\UserController;
use App\Http\Controllers\Api\V1\WarehouseController;
use App\Http\Controllers\Api\V1\WorkflowInstanceController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — v1
|--------------------------------------------------------------------------
| All routes use Sanctum token auth and the standard response envelope.
*/

// Public authentication routes (no auth required)
Route::prefix('v1/auth')->group(function (): void {
    Route::post('login', [AuthController::class, 'login']);
    Route::post('register', [AuthController::class, 'register']);
});

// 2FA challenge route (requires temporary token)
Route::prefix('v1/auth')->middleware(['auth:sanctum'])->group(function (): void {
    Route::post('two-factor/challenge', [AuthController::class, 'twoFactorChallenge']);
});

// Authenticated routes
Route::prefix('v1')->middleware(['auth:sanctum,web', 'throttle:api'])->group(function (): void {
    // Authentication (requires auth)
    Route::prefix('auth')->group(function (): void {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('user', [AuthController::class, 'user']);
        Route::post('setup-company', [AuthController::class, 'setupCompany']);
        Route::post('switch-company/{companyId}', [AuthController::class, 'switchCompany']);
    });

    // Business routes (require company context)
    Route::middleware(['ensure.company'])->group(function (): void {

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
        Route::get('documents/storage-info', [DocumentController::class, 'storageInfo']);
        Route::get('documents/{document}/download', [DocumentController::class, 'download']);
        Route::get('documents/{document}/thumbnail', [DocumentController::class, 'thumbnail']);
        Route::get('documents/{document}/preview', [DocumentController::class, 'preview']);
        Route::apiResource('documents', DocumentController::class);

        // Document Folders
        Route::apiResource('document-folders', DocumentFolderController::class);

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

        // HR Enhancement - Employee Tasks
        Route::prefix('hr/employees/{employeeId}')->group(function (): void {
            Route::get('tasks/statistics', [\App\Http\Controllers\Api\V1\HR\EmployeeTaskController::class, 'statistics']);
            Route::get('tasks', [\App\Http\Controllers\Api\V1\HR\EmployeeTaskController::class, 'index']);
            Route::post('tasks', [\App\Http\Controllers\Api\V1\HR\EmployeeTaskController::class, 'store']);
            Route::get('tasks/{taskId}', [\App\Http\Controllers\Api\V1\HR\EmployeeTaskController::class, 'show']);
            Route::put('tasks/{taskId}', [\App\Http\Controllers\Api\V1\HR\EmployeeTaskController::class, 'update']);
            Route::delete('tasks/{taskId}', [\App\Http\Controllers\Api\V1\HR\EmployeeTaskController::class, 'destroy']);
            Route::post('tasks/{taskId}/start', [\App\Http\Controllers\Api\V1\HR\EmployeeTaskController::class, 'start']);
            Route::post('tasks/{taskId}/complete', [\App\Http\Controllers\Api\V1\HR\EmployeeTaskController::class, 'complete']);
        });

        // HR Enhancement - Time Tracking
        Route::prefix('hr/employees/{employeeId}')->group(function (): void {
            Route::get('time-entries/summary', [\App\Http\Controllers\Api\V1\HR\EmployeeTimeEntryController::class, 'summary']);
            Route::get('time-entries', [\App\Http\Controllers\Api\V1\HR\EmployeeTimeEntryController::class, 'index']);
            Route::post('time-entries', [\App\Http\Controllers\Api\V1\HR\EmployeeTimeEntryController::class, 'store']);
            Route::get('timesheet', [\App\Http\Controllers\Api\V1\HR\EmployeeTimeEntryController::class, 'timesheet']);
            Route::get('time-statistics', [\App\Http\Controllers\Api\V1\HR\EmployeeTimeEntryController::class, 'statistics']);
            Route::get('capacity', [\App\Http\Controllers\Api\V1\HR\EmployeeTimeEntryController::class, 'capacity']);
            Route::put('capacity', [\App\Http\Controllers\Api\V1\HR\EmployeeTimeEntryController::class, 'updateCapacity']);
        });

        Route::prefix('hr/time-entries')->group(function (): void {
            Route::get('{id}', [\App\Http\Controllers\Api\V1\HR\EmployeeTimeEntryController::class, 'show']);
            Route::put('{id}', [\App\Http\Controllers\Api\V1\HR\EmployeeTimeEntryController::class, 'update']);
            Route::delete('{id}', [\App\Http\Controllers\Api\V1\HR\EmployeeTimeEntryController::class, 'destroy']);
            Route::post('approve', [\App\Http\Controllers\Api\V1\HR\EmployeeTimeEntryController::class, 'approve']);
        });

        // Inbox / Messaging System
        Route::prefix('inbox')->group(function (): void {
            Route::get('conversations', [\App\Http\Controllers\Api\V1\InboxController::class, 'conversations']);
            Route::post('conversations/direct', [\App\Http\Controllers\Api\V1\InboxController::class, 'createDirectConversation']);
            Route::post('conversations/group', [\App\Http\Controllers\Api\V1\InboxController::class, 'createGroupConversation']);
            Route::delete('conversations/{conversationId}', [\App\Http\Controllers\Api\V1\InboxController::class, 'deleteConversation']);
            Route::post('conversations/{conversationId}/star', [\App\Http\Controllers\Api\V1\InboxController::class, 'toggleStar']);
            Route::post('conversations/{conversationId}/mute', [\App\Http\Controllers\Api\V1\InboxController::class, 'toggleMute']);
            Route::post('conversations/{conversationId}/read', [\App\Http\Controllers\Api\V1\InboxController::class, 'markAsRead']);
            Route::post('conversations/{conversationId}/participants', [\App\Http\Controllers\Api\V1\InboxController::class, 'addParticipants']);
            Route::delete('conversations/{conversationId}/participants/{userId}', [\App\Http\Controllers\Api\V1\InboxController::class, 'removeParticipant']);
            
            Route::get('conversations/{conversationId}/messages', [\App\Http\Controllers\Api\V1\InboxController::class, 'messages']);
            Route::post('conversations/{conversationId}/messages', [\App\Http\Controllers\Api\V1\InboxController::class, 'sendMessage']);
            Route::put('messages/{messageId}', [\App\Http\Controllers\Api\V1\InboxController::class, 'editMessage']);
            Route::delete('messages/{messageId}', [\App\Http\Controllers\Api\V1\InboxController::class, 'deleteMessage']);
            
            Route::get('attachments/{attachmentId}/download', [\App\Http\Controllers\Api\V1\InboxController::class, 'downloadAttachment'])->name('api.v1.inbox.attachments.download');
            Route::get('users', [\App\Http\Controllers\Api\V1\InboxController::class, 'companyUsers']);
        });

        // HR Enhancement - Capacity Planning (detailed with date ranges)
        Route::prefix('hr/employees/{employeeId}/capacity')->group(function (): void {
            Route::get('detailed', [\App\Http\Controllers\Api\V1\HR\EmployeeCapacityController::class, 'index']);
            Route::put('detailed', [\App\Http\Controllers\Api\V1\HR\EmployeeCapacityController::class, 'update']);
            Route::get('trends', [\App\Http\Controllers\Api\V1\HR\EmployeeCapacityController::class, 'trends']);
        });

        Route::prefix('hr/capacity')->group(function (): void {
            Route::get('overview', [\App\Http\Controllers\Api\V1\HR\EmployeeCapacityController::class, 'overview']);
            Route::get('available', [\App\Http\Controllers\Api\V1\HR\EmployeeCapacityController::class, 'available']);
            Route::get('overallocated', [\App\Http\Controllers\Api\V1\HR\EmployeeCapacityController::class, 'overallocated']);
        });

        // HR Enhancement - Employee Skills
        Route::prefix('hr')->group(function (): void {
            Route::get('skills', [\App\Http\Controllers\Api\V1\HR\EmployeeSkillController::class, 'all']);
            Route::get('skills/statistics', [\App\Http\Controllers\Api\V1\HR\EmployeeSkillController::class, 'statistics']);
        });

        Route::prefix('hr/employees/{employeeId}/skills')->group(function (): void {
            Route::get('/', [\App\Http\Controllers\Api\V1\HR\EmployeeSkillController::class, 'index']);
            Route::post('/', [\App\Http\Controllers\Api\V1\HR\EmployeeSkillController::class, 'store']);
            Route::get('{skillId}', [\App\Http\Controllers\Api\V1\HR\EmployeeSkillController::class, 'show']);
            Route::put('{skillId}', [\App\Http\Controllers\Api\V1\HR\EmployeeSkillController::class, 'update']);
            Route::delete('{skillId}', [\App\Http\Controllers\Api\V1\HR\EmployeeSkillController::class, 'destroy']);
        });

        // HR Enhancement - Employee Availability
        Route::prefix('hr')->group(function (): void {
            Route::get('availability/calendar', [\App\Http\Controllers\Api\V1\HR\EmployeeAvailabilityController::class, 'calendar']);
            Route::get('availability/statistics', [\App\Http\Controllers\Api\V1\HR\EmployeeAvailabilityController::class, 'statistics']);
        });

        Route::prefix('hr/employees/{employeeId}/availability')->group(function (): void {
            Route::get('/', [\App\Http\Controllers\Api\V1\HR\EmployeeAvailabilityController::class, 'index']);
            Route::post('/', [\App\Http\Controllers\Api\V1\HR\EmployeeAvailabilityController::class, 'store']);
            Route::post('bulk', [\App\Http\Controllers\Api\V1\HR\EmployeeAvailabilityController::class, 'bulkStore']);
            Route::get('{availabilityId}', [\App\Http\Controllers\Api\V1\HR\EmployeeAvailabilityController::class, 'show']);
            Route::put('{availabilityId}', [\App\Http\Controllers\Api\V1\HR\EmployeeAvailabilityController::class, 'update']);
            Route::delete('{availabilityId}', [\App\Http\Controllers\Api\V1\HR\EmployeeAvailabilityController::class, 'destroy']);
        });

        // HR Enhancement - Performance Reviews
        Route::prefix('hr/performance-reviews')->group(function (): void {
            Route::get('/', [\App\Http\Controllers\Api\V1\HR\PerformanceReviewController::class, 'index']);
            Route::post('/', [\App\Http\Controllers\Api\V1\HR\PerformanceReviewController::class, 'store']);
            Route::get('statistics', [\App\Http\Controllers\Api\V1\HR\PerformanceReviewController::class, 'statistics']);
            Route::get('{id}', [\App\Http\Controllers\Api\V1\HR\PerformanceReviewController::class, 'show']);
            Route::put('{id}', [\App\Http\Controllers\Api\V1\HR\PerformanceReviewController::class, 'update']);
            Route::delete('{id}', [\App\Http\Controllers\Api\V1\HR\PerformanceReviewController::class, 'destroy']);
            Route::post('{id}/submit', [\App\Http\Controllers\Api\V1\HR\PerformanceReviewController::class, 'submit']);
            Route::post('{id}/acknowledge', [\App\Http\Controllers\Api\V1\HR\PerformanceReviewController::class, 'acknowledge']);
        });

        Route::get('hr/employees/{employeeId}/performance-reviews', [\App\Http\Controllers\Api\V1\HR\PerformanceReviewController::class, 'employeeReviews']);

        // Workflows
        Route::apiResource('workflow-instances', WorkflowInstanceController::class);
        Route::post('workflow-instances/{workflowInstance}/transition', [WorkflowInstanceController::class, 'transition']);
        Route::apiResource('approval-requests', ApprovalRequestController::class);
        Route::post('approval-requests/{approvalRequest}/approve', [ApprovalRequestController::class, 'approve']);
        Route::post('approval-requests/{approvalRequest}/reject', [ApprovalRequestController::class, 'reject']);

        // Reports
        Route::apiResource('reports', ReportController::class);
        Route::get('reports/{report}/generate', [ReportController::class, 'generate']);

        // VAT Reports (Bangladesh Mushak)
        Route::prefix('vat-reports')->name('vat-reports.')->group(function (): void {
            Route::get('/mushak-61', [VatReportController::class, 'mushak61']);
            Route::get('/mushak-62', [VatReportController::class, 'mushak62']);
            Route::get('/mushak-66', [VatReportController::class, 'mushak66']);
            Route::get('/mushak-91', [VatReportController::class, 'mushak91']);
        });

        // Calendar & Events
        Route::prefix('calendar')->name('calendar.')->group(function (): void {
            Route::get('/', [CalendarController::class, 'index']);
            Route::post('/', [CalendarController::class, 'store']);
            Route::get('/user-events', [CalendarController::class, 'userEvents']);
            Route::get('/{calendar}', [CalendarController::class, 'show']);
            Route::put('/{calendar}', [CalendarController::class, 'update']);
            Route::delete('/{calendar}', [CalendarController::class, 'destroy']);
            Route::get('/{calendar}/events', [CalendarController::class, 'events']);
            Route::get('/{calendar}/upcoming', [CalendarController::class, 'upcoming']);
            Route::get('/{calendar}/statistics', [CalendarController::class, 'statistics']);
            Route::post('/{calendar}/check-conflicts', [CalendarController::class, 'checkConflicts']);
        });

        // Calendar Events
        Route::prefix('events')->name('events.')->group(function (): void {
            Route::post('/', [CalendarController::class, 'storeEvent']);
            Route::put('/{event}', [CalendarController::class, 'updateEvent']);
            Route::delete('/{event}', [CalendarController::class, 'destroyEvent']);
            Route::post('/{event}/complete', [CalendarController::class, 'completeEvent']);
            Route::post('/{event}/cancel', [CalendarController::class, 'cancelEvent']);
            Route::post('/{event}/reschedule', [CalendarController::class, 'rescheduleEvent']);
        });

        // Settings
        Route::apiResource('companies', CompanyController::class);
        Route::apiResource('branches', BranchController::class);
        Route::apiResource('warehouses', WarehouseController::class);
        Route::apiResource('product-categories', ProductCategoryController::class);
        Route::apiResource('tax-groups', TaxGroupController::class);
        Route::apiResource('departments', DepartmentController::class);
        Route::apiResource('designations', DesignationController::class);

        // Integrations
        Route::prefix('integrations')->name('integrations.')->group(function (): void {
            // Company-specific installed integrations (must come before {id} routes)
            Route::prefix('company')->name('company.')->group(function (): void {
                Route::get('/', [CompanyIntegrationController::class, 'index']);
                Route::get('/{id}', [CompanyIntegrationController::class, 'show']);
                Route::post('/', [CompanyIntegrationController::class, 'store']);
                Route::put('/{id}', [CompanyIntegrationController::class, 'update']);
                Route::delete('/{id}', [CompanyIntegrationController::class, 'destroy']);
                Route::post('/{id}/activate', [CompanyIntegrationController::class, 'activate']);
                Route::post('/{id}/deactivate', [CompanyIntegrationController::class, 'deactivate']);
                Route::post('/{id}/sync', [CompanyIntegrationController::class, 'sync']);
            });

            // Available integrations catalog
            Route::get('/', [IntegrationController::class, 'index']);
            Route::get('/{id}', [IntegrationController::class, 'show']);
            Route::post('/', [IntegrationController::class, 'store']);
            Route::put('/{id}', [IntegrationController::class, 'update']);
            Route::delete('/{id}', [IntegrationController::class, 'destroy']);
        });

        // Subscription (Customer)
        Route::prefix('subscription')->name('subscription.')->group(function (): void {
            Route::get('/current', [SubscriptionController::class, 'current']);
            Route::get('/usage', [SubscriptionController::class, 'usage']);
            Route::get('/plans', [SubscriptionController::class, 'plans']);
            Route::get('/plans/{id}', [SubscriptionController::class, 'plan']);
        });

        // Admin Subscription (Master Admin & Dev Admin only)
        Route::prefix('admin/subscription')->name('admin.subscription.')->group(function (): void {
            Route::get('/dashboard', [AdminSubscriptionController::class, 'dashboard']);
            Route::get('/subscriptions', [AdminSubscriptionController::class, 'index']);
            Route::get('/subscriptions/{id}', [AdminSubscriptionController::class, 'show']);
            Route::post('/subscriptions/{id}/pause', [AdminSubscriptionController::class, 'pause']);
            Route::post('/subscriptions/{id}/activate', [AdminSubscriptionController::class, 'activate']);
            Route::delete('/subscriptions/{id}', [AdminSubscriptionController::class, 'destroy']);
            Route::get('/payment-requests', [AdminSubscriptionController::class, 'paymentRequests']);
            Route::post('/payment-requests/{id}/verify', [AdminSubscriptionController::class, 'verifyPaymentRequest']);
            Route::post('/payment-requests/{id}/reject', [AdminSubscriptionController::class, 'rejectPaymentRequest']);
            Route::get('/invoices', [AdminSubscriptionController::class, 'invoices']);
            Route::get('/analytics', [AdminSubscriptionController::class, 'analytics']);
        });

        // ERP Notifications (Domain-Driven System)
        Route::prefix('erp-notifications')->name('erp-notifications.')->group(function (): void {
            Route::get('/', [\App\Domain\Notification\Http\Controllers\NotificationController::class, 'index']);
            Route::get('/unread-count', [\App\Domain\Notification\Http\Controllers\NotificationController::class, 'unreadCount']);
            Route::post('/{id}/read', [\App\Domain\Notification\Http\Controllers\NotificationController::class, 'markRead']);
            Route::post('/read-all', [\App\Domain\Notification\Http\Controllers\NotificationController::class, 'markAllRead']);
            Route::delete('/{id}', [\App\Domain\Notification\Http\Controllers\NotificationController::class, 'destroy']);
        });

        // Logistics Domain
        Route::prefix('logistics')->name('logistics.')->group(function (): void {
            // Shipments - specific routes before resource routes
            Route::post('shipments/bulk', [\App\Domain\Logistics\Http\Controllers\ShipmentController::class, 'bulkCreate']);
            Route::post('shipments/{id}/generate-label', [\App\Domain\Logistics\Http\Controllers\ShipmentController::class, 'generateLabel']);
            Route::post('shipments/{id}/schedule-pickup', [\App\Domain\Logistics\Http\Controllers\ShipmentController::class, 'schedulePickup']);
            Route::apiResource('shipments', \App\Domain\Logistics\Http\Controllers\ShipmentController::class);
            
            // Tracking
            Route::get('tracking/statistics', [\App\Domain\Logistics\Http\Controllers\TrackingController::class, 'statistics']);
            Route::post('tracking/bulk-sync', [\App\Domain\Logistics\Http\Controllers\TrackingController::class, 'bulkSync']);
            Route::get('tracking/{trackingNumber}', [\App\Domain\Logistics\Http\Controllers\TrackingController::class, 'track']);
            Route::get('shipments/{id}/tracking', [\App\Domain\Logistics\Http\Controllers\TrackingController::class, 'history']);
            Route::post('shipments/{id}/tracking/update', [\App\Domain\Logistics\Http\Controllers\TrackingController::class, 'updateStatus']);
            Route::post('shipments/{id}/tracking/sync', [\App\Domain\Logistics\Http\Controllers\TrackingController::class, 'syncWithCarrier']);
            
            // Returns
            Route::get('returns/statistics', [\App\Domain\Logistics\Http\Controllers\ReturnController::class, 'statistics']);
            Route::apiResource('returns', \App\Domain\Logistics\Http\Controllers\ReturnController::class);
            Route::post('returns/{id}/approve', [\App\Domain\Logistics\Http\Controllers\ReturnController::class, 'approve']);
            Route::post('returns/{id}/reject', [\App\Domain\Logistics\Http\Controllers\ReturnController::class, 'reject']);
            Route::post('returns/{id}/mark-received', [\App\Domain\Logistics\Http\Controllers\ReturnController::class, 'markReceived']);
            Route::post('returns/{id}/process-refund', [\App\Domain\Logistics\Http\Controllers\ReturnController::class, 'processRefund']);
            Route::post('returns/{id}/upload-images', [\App\Domain\Logistics\Http\Controllers\ReturnController::class, 'uploadImages']);
            
            // COD Management
            Route::post('cod/calculate-charge', [\App\Domain\Logistics\Http\Controllers\CODController::class, 'calculateCharge']);
            Route::post('shipments/{id}/cod/mark-collected', [\App\Domain\Logistics\Http\Controllers\CODController::class, 'markCollected']);
            Route::post('carriers/{carrierId}/cod/settle', [\App\Domain\Logistics\Http\Controllers\CODController::class, 'settle']);
            Route::get('carriers/{carrierId}/cod/summary', [\App\Domain\Logistics\Http\Controllers\CODController::class, 'summary']);
            Route::get('carriers/{carrierId}/cod/pending-collection', [\App\Domain\Logistics\Http\Controllers\CODController::class, 'pendingCollection']);
            Route::get('carriers/{carrierId}/cod/pending-settlement', [\App\Domain\Logistics\Http\Controllers\CODController::class, 'pendingSettlement']);
            Route::get('carriers/{carrierId}/cod/report', [\App\Domain\Logistics\Http\Controllers\CODController::class, 'report']);
            Route::post('carriers/{carrierId}/cod/sync-status', [\App\Domain\Logistics\Http\Controllers\CODController::class, 'syncStatus']);
        });

        // CRM Domain
        Route::prefix('crm')->name('crm.')->group(function (): void {
            // Dashboard
            Route::get('dashboard/metrics', [\App\Http\Controllers\Api\V1\CRM\DashboardController::class, 'metrics'])->name('dashboard.metrics');
            Route::get('dashboard/top-performers', [\App\Http\Controllers\Api\V1\CRM\DashboardController::class, 'topPerformers'])->name('dashboard.top-performers');

            // Lead Management
            Route::prefix('leads')->name('leads.')->group(function (): void {
                Route::get('/', [\App\Http\Controllers\Api\V1\CRM\LeadController::class, 'index'])->name('index');
                Route::post('/', [\App\Http\Controllers\Api\V1\CRM\LeadController::class, 'store'])->name('store');
                Route::get('/my-leads', [\App\Http\Controllers\Api\V1\CRM\LeadController::class, 'myLeads'])->name('my-leads');
                Route::get('/statistics', [\App\Http\Controllers\Api\V1\CRM\LeadController::class, 'statistics'])->name('statistics');
                Route::get('/scoring-statistics', [\App\Http\Controllers\Api\V1\CRM\LeadController::class, 'scoringStatistics'])->name('scoring-statistics');
                Route::post('/bulk-assign', [\App\Http\Controllers\Api\V1\CRM\LeadController::class, 'bulkAssign'])->name('bulk-assign');
                Route::post('/bulk-update-status', [\App\Http\Controllers\Api\V1\CRM\LeadController::class, 'bulkUpdateStatus'])->name('bulk-update-status');
                Route::post('/bulk-score', [\App\Http\Controllers\Api\V1\CRM\LeadController::class, 'bulkScore'])->name('bulk-score');
                Route::post('/bulk-qualify', [\App\Http\Controllers\Api\V1\CRM\LeadController::class, 'bulkQualify'])->name('bulk-qualify');
                Route::get('/{uuid}', [\App\Http\Controllers\Api\V1\CRM\LeadController::class, 'show'])->name('show');
                Route::put('/{uuid}', [\App\Http\Controllers\Api\V1\CRM\LeadController::class, 'update'])->name('update');
                Route::delete('/{uuid}', [\App\Http\Controllers\Api\V1\CRM\LeadController::class, 'destroy'])->name('destroy');
                Route::post('/{uuid}/assign', [\App\Http\Controllers\Api\V1\CRM\LeadController::class, 'assign'])->name('assign');
                Route::post('/{uuid}/update-score', [\App\Http\Controllers\Api\V1\CRM\LeadController::class, 'updateScore'])->name('update-score');
                Route::post('/{uuid}/score', [\App\Http\Controllers\Api\V1\CRM\LeadController::class, 'score'])->name('score');
                Route::post('/{uuid}/qualify', [\App\Http\Controllers\Api\V1\CRM\LeadController::class, 'qualify'])->name('qualify');
                Route::post('/{uuid}/notes', [\App\Http\Controllers\Api\V1\CRM\LeadController::class, 'addNote'])->name('add-note');
                Route::post('/{uuid}/tags', [\App\Http\Controllers\Api\V1\CRM\LeadController::class, 'addTag'])->name('add-tag');
                Route::delete('/{uuid}/tags/{tagId}', [\App\Http\Controllers\Api\V1\CRM\LeadController::class, 'removeTag'])->name('remove-tag');
            });

            // Opportunity Management
            Route::prefix('opportunities')->name('opportunities.')->group(function (): void {
                Route::get('/', [\App\Http\Controllers\Api\V1\CRM\OpportunityController::class, 'index'])->name('index');
                Route::post('/', [\App\Http\Controllers\Api\V1\CRM\OpportunityController::class, 'store'])->name('store');
                Route::get('/statistics', [\App\Http\Controllers\Api\V1\CRM\OpportunityController::class, 'statistics'])->name('statistics');
                Route::get('/forecast', [\App\Http\Controllers\Api\V1\CRM\OpportunityController::class, 'forecast'])->name('forecast');
                Route::post('/bulk-move-to-stage', [\App\Http\Controllers\Api\V1\CRM\OpportunityController::class, 'bulkMoveToStage'])->name('bulk-move-to-stage');
                Route::post('/bulk-assign', [\App\Http\Controllers\Api\V1\CRM\OpportunityController::class, 'bulkAssign'])->name('bulk-assign');
                Route::get('/pipeline/{pipelineId}', [\App\Http\Controllers\Api\V1\CRM\OpportunityController::class, 'pipelineView'])->name('pipeline-view');
                Route::get('/stage/{stageId}', [\App\Http\Controllers\Api\V1\CRM\OpportunityController::class, 'stageView'])->name('stage-view');
                Route::get('/{uuid}', [\App\Http\Controllers\Api\V1\CRM\OpportunityController::class, 'show'])->name('show');
                Route::put('/{uuid}', [\App\Http\Controllers\Api\V1\CRM\OpportunityController::class, 'update'])->name('update');
                Route::delete('/{uuid}', [\App\Http\Controllers\Api\V1\CRM\OpportunityController::class, 'destroy'])->name('destroy');
                Route::post('/{uuid}/move-to-stage', [\App\Http\Controllers\Api\V1\CRM\OpportunityController::class, 'moveToStage'])->name('move-to-stage');
                Route::post('/{uuid}/mark-as-won', [\App\Http\Controllers\Api\V1\CRM\OpportunityController::class, 'markAsWon'])->name('mark-as-won');
                Route::post('/{uuid}/mark-as-lost', [\App\Http\Controllers\Api\V1\CRM\OpportunityController::class, 'markAsLost'])->name('mark-as-lost');
                Route::post('/{uuid}/assign', [\App\Http\Controllers\Api\V1\CRM\OpportunityController::class, 'assign'])->name('assign');
                Route::post('/{uuid}/update-probability', [\App\Http\Controllers\Api\V1\CRM\OpportunityController::class, 'updateProbability'])->name('update-probability');
            });

            // Pipeline Management
            Route::prefix('pipelines')->name('pipelines.')->group(function (): void {
                Route::get('/', [\App\Http\Controllers\Api\V1\CRM\PipelineController::class, 'index'])->name('index');
                Route::get('/active', [\App\Http\Controllers\Api\V1\CRM\PipelineController::class, 'active'])->name('active');
                Route::get('/default', [\App\Http\Controllers\Api\V1\CRM\PipelineController::class, 'getDefault'])->name('default');
                Route::post('/', [\App\Http\Controllers\Api\V1\CRM\PipelineController::class, 'store'])->name('store');
                Route::get('/{uuid}', [\App\Http\Controllers\Api\V1\CRM\PipelineController::class, 'show'])->name('show');
                Route::put('/{uuid}', [\App\Http\Controllers\Api\V1\CRM\PipelineController::class, 'update'])->name('update');
                Route::delete('/{uuid}', [\App\Http\Controllers\Api\V1\CRM\PipelineController::class, 'destroy'])->name('destroy');
                Route::post('/{uuid}/set-as-default', [\App\Http\Controllers\Api\V1\CRM\PipelineController::class, 'setAsDefault'])->name('set-as-default');
                Route::post('/{uuid}/activate', [\App\Http\Controllers\Api\V1\CRM\PipelineController::class, 'activate'])->name('activate');
                Route::post('/{uuid}/deactivate', [\App\Http\Controllers\Api\V1\CRM\PipelineController::class, 'deactivate'])->name('deactivate');
                Route::post('/{uuid}/duplicate', [\App\Http\Controllers\Api\V1\CRM\PipelineController::class, 'duplicate'])->name('duplicate');
                Route::get('/{uuid}/metrics', [\App\Http\Controllers\Api\V1\CRM\PipelineController::class, 'metrics'])->name('metrics');
                Route::post('/{uuid}/stages', [\App\Http\Controllers\Api\V1\CRM\PipelineController::class, 'createStage'])->name('create-stage');
                Route::put('/{uuid}/stages/{stageUuid}', [\App\Http\Controllers\Api\V1\CRM\PipelineController::class, 'updateStage'])->name('update-stage');
                Route::delete('/{uuid}/stages/{stageUuid}', [\App\Http\Controllers\Api\V1\CRM\PipelineController::class, 'deleteStage'])->name('delete-stage');
                Route::post('/{uuid}/reorder-stages', [\App\Http\Controllers\Api\V1\CRM\PipelineController::class, 'reorderStages'])->name('reorder-stages');
            });

            // Activity Management
            Route::prefix('activities')->name('activities.')->group(function (): void {
                Route::get('/', [\App\Http\Controllers\Api\V1\CRM\ActivityController::class, 'index'])->name('index');
                Route::post('/', [\App\Http\Controllers\Api\V1\CRM\ActivityController::class, 'store'])->name('store');
                Route::get('/my-activities', [\App\Http\Controllers\Api\V1\CRM\ActivityController::class, 'myActivities'])->name('my-activities');
                Route::get('/scheduled', [\App\Http\Controllers\Api\V1\CRM\ActivityController::class, 'scheduled'])->name('scheduled');
                Route::get('/overdue', [\App\Http\Controllers\Api\V1\CRM\ActivityController::class, 'overdue'])->name('overdue');
                Route::get('/due-today', [\App\Http\Controllers\Api\V1\CRM\ActivityController::class, 'dueToday'])->name('due-today');
                Route::get('/statistics', [\App\Http\Controllers\Api\V1\CRM\ActivityController::class, 'statistics'])->name('statistics');
                Route::post('/bulk-complete', [\App\Http\Controllers\Api\V1\CRM\ActivityController::class, 'bulkComplete'])->name('bulk-complete');
                Route::post('/bulk-reschedule', [\App\Http\Controllers\Api\V1\CRM\ActivityController::class, 'bulkReschedule'])->name('bulk-reschedule');
                Route::get('/for-subject/{subjectType}/{subjectId}', [\App\Http\Controllers\Api\V1\CRM\ActivityController::class, 'forSubject'])->name('for-subject');
                Route::get('/{uuid}', [\App\Http\Controllers\Api\V1\CRM\ActivityController::class, 'show'])->name('show');
                Route::put('/{uuid}', [\App\Http\Controllers\Api\V1\CRM\ActivityController::class, 'update'])->name('update');
                Route::delete('/{uuid}', [\App\Http\Controllers\Api\V1\CRM\ActivityController::class, 'destroy'])->name('destroy');
                Route::post('/{uuid}/start', [\App\Http\Controllers\Api\V1\CRM\ActivityController::class, 'start'])->name('start');
                Route::post('/{uuid}/complete', [\App\Http\Controllers\Api\V1\CRM\ActivityController::class, 'complete'])->name('complete');
                Route::post('/{uuid}/cancel', [\App\Http\Controllers\Api\V1\CRM\ActivityController::class, 'cancel'])->name('cancel');
                Route::post('/{uuid}/reschedule', [\App\Http\Controllers\Api\V1\CRM\ActivityController::class, 'reschedule'])->name('reschedule');
            });
        });

        // Custom Fields
        Route::apiResource('custom-fields', CustomFieldController::class);

        // Dashboard
        Route::get('dashboard', [DashboardController::class, 'index']);

        // Chart of Accounts
        Route::apiResource('accounts', AccountController::class);
        Route::apiResource('account-groups', AccountGroupController::class);
        Route::apiResource('journal-entries', JournalEntryController::class);
        
        // Cost Centers
        Route::apiResource('cost-centers', CostCenterController::class);
        Route::get('cost-centers-options', [CostCenterController::class, 'options']);

        // Lock Date Management
        Route::prefix('companies/{company}/lock-date')->group(function (): void {
            Route::get('/', [LockDateController::class, 'show']);
            Route::put('/', [LockDateController::class, 'update']);
            Route::post('month-end-close', [LockDateController::class, 'monthEndClose']);
            Route::post('validate', [LockDateController::class, 'validateLockDate']);
        });

        // Invitations
        Route::apiResource('invitations', InvitationController::class);

        // Import Jobs
        Route::apiResource('import-jobs', ImportJobController::class);

        // CMS
        Route::prefix('cms')->group(function (): void {
            // Sites
            Route::apiResource('sites', SiteController::class);
            Route::post('sites/{site}/publish', [SiteController::class, 'publish']);
            Route::post('sites/{site}/unpublish', [SiteController::class, 'unpublish']);
            Route::get('sites/{site}/statistics', [SiteController::class, 'statistics']);

            // Pages
            Route::apiResource('pages', PageController::class);
            Route::post('pages/{page}/publish', [PageController::class, 'publish']);
            Route::post('pages/{page}/unpublish', [PageController::class, 'unpublish']);
            Route::post('pages/{page}/set-homepage', [PageController::class, 'setHomepage']);

            // Sections
            Route::apiResource('sections', SectionController::class);
            Route::post('sections/{section}/duplicate', [SectionController::class, 'duplicate']);
            Route::post('pages/{page}/sections/reorder', [SectionController::class, 'reorder']);
            Route::get('section-types', [SectionController::class, 'sectionTypes']);

            // Menus
            Route::apiResource('menus', \App\Http\Controllers\Api\V1\CMS\MenuController::class);
            Route::post('menus/{menu}/items', [\App\Http\Controllers\Api\V1\CMS\MenuController::class, 'addItem']);
            Route::put('menus/items/{item}', [\App\Http\Controllers\Api\V1\CMS\MenuController::class, 'updateItem']);
            Route::delete('menus/items/{item}', [\App\Http\Controllers\Api\V1\CMS\MenuController::class, 'deleteItem']);
            Route::post('menus/{menu}/reorder', [\App\Http\Controllers\Api\V1\CMS\MenuController::class, 'reorderItems']);

            // Media Management
            Route::prefix('media')->group(function (): void {
                Route::get('/', [\App\Http\Controllers\Api\V1\CMS\MediaController::class, 'index']);
                Route::post('upload', [\App\Http\Controllers\Api\V1\CMS\MediaController::class, 'upload']);
                Route::delete('{path}', [\App\Http\Controllers\Api\V1\CMS\MediaController::class, 'delete'])->where('path', '.*');
            });

            // Reviews (Admin Management)
            Route::get('reviews', [\App\Http\Controllers\Api\V1\ReviewController::class, 'index']);
            Route::post('reviews/{id}/approve', [\App\Http\Controllers\Api\V1\ReviewController::class, 'approve']);
            Route::post('reviews/{id}/reject', [\App\Http\Controllers\Api\V1\ReviewController::class, 'reject']);
            Route::delete('reviews/{id}', [\App\Http\Controllers\Api\V1\ReviewController::class, 'destroy']);
            Route::get('reviews/statistics', [\App\Http\Controllers\Api\V1\ReviewController::class, 'statistics']);

            // Wishlists (Admin Management)
            Route::get('wishlists', [\App\Http\Controllers\Api\V1\WishlistController::class, 'index']);
            Route::get('wishlists/statistics', [\App\Http\Controllers\Api\V1\WishlistController::class, 'statistics']);
            Route::delete('wishlists/{id}', [\App\Http\Controllers\Api\V1\WishlistController::class, 'destroy']);
            Route::delete('wishlists/customers/{customerId}/clear', [\App\Http\Controllers\Api\V1\WishlistController::class, 'clearCustomerWishlist']);

            // Page Builder
            Route::prefix('page-builder')->group(function (): void {
                Route::get('section-types', [\App\Http\Controllers\Api\V1\PageBuilderController::class, 'sectionTypes']);
                Route::get('pages/{pageId}', [\App\Http\Controllers\Api\V1\PageBuilderController::class, 'getPage']);
                Route::get('pages/{pageId}/preview', [\App\Http\Controllers\Api\V1\PageBuilderController::class, 'previewPage']);
                Route::post('pages/{pageId}/sections', [\App\Http\Controllers\Api\V1\PageBuilderController::class, 'addSection']);
                Route::put('pages/{pageId}/sections/reorder', [\App\Http\Controllers\Api\V1\PageBuilderController::class, 'reorderSections']);
                Route::put('sections/{sectionId}/content', [\App\Http\Controllers\Api\V1\PageBuilderController::class, 'updateSectionContent']);
                Route::post('sections/{sectionId}/toggle-visibility', [\App\Http\Controllers\Api\V1\PageBuilderController::class, 'toggleSectionVisibility']);
                Route::post('sections/{sectionId}/duplicate', [\App\Http\Controllers\Api\V1\PageBuilderController::class, 'duplicateSection']);
            });

            // Contact Management
            Route::get('contacts', [\App\Http\Controllers\Api\V1\ContactController::class, 'index']);
            Route::get('contacts/statistics', [\App\Http\Controllers\Api\V1\ContactController::class, 'statistics']);
            Route::get('contacts/export', [\App\Http\Controllers\Api\V1\ContactController::class, 'export']);
            Route::get('contacts/{id}', [\App\Http\Controllers\Api\V1\ContactController::class, 'show']);
            Route::post('contacts/{id}/contacted', [\App\Http\Controllers\Api\V1\ContactController::class, 'markAsContacted']);
            Route::post('contacts/{id}/resolved', [\App\Http\Controllers\Api\V1\ContactController::class, 'markAsResolved']);
            Route::post('contacts/{id}/spam', [\App\Http\Controllers\Api\V1\ContactController::class, 'markAsSpam']);
            Route::post('contacts/{id}/assign', [\App\Http\Controllers\Api\V1\ContactController::class, 'assign']);
            Route::delete('contacts/{id}', [\App\Http\Controllers\Api\V1\ContactController::class, 'destroy']);

            // SEO Management
            Route::prefix('seo')->group(function (): void {
                Route::get('dashboard', [\App\Http\Controllers\Api\V1\SEOController::class, 'dashboard']);
                Route::get('analysis', [\App\Http\Controllers\Api\V1\SEOController::class, 'analysis']);
                Route::get('sitemap-preview', [\App\Http\Controllers\Api\V1\SEOController::class, 'sitemapPreview']);
                Route::get('structured-data-preview', [\App\Http\Controllers\Api\V1\SEOController::class, 'structuredDataPreview']);
                Route::get('meta-tags-preview', [\App\Http\Controllers\Api\V1\SEOController::class, 'metaTagsPreview']);
            });

            // ERP Integration
            Route::prefix('erp')->group(function (): void {
                Route::get('products', [ERPIntegrationController::class, 'products']);
                Route::get('products/{productId}/related', [ERPIntegrationController::class, 'relatedProducts']);
                Route::get('team', [ERPIntegrationController::class, 'team']);
                Route::get('projects', [ERPIntegrationController::class, 'projects']);
                Route::get('stats', [ERPIntegrationController::class, 'stats']);
                Route::get('testimonials', [ERPIntegrationController::class, 'testimonials']);
                Route::get('search', [ERPIntegrationController::class, 'search']);
            });
        });

        // Project Management System (PMS)
        Route::prefix('projects')->group(function (): void {
            // Projects
            Route::get('/', [\App\Http\Controllers\Api\V1\ProjectController::class, 'index']);
            Route::post('/', [\App\Http\Controllers\Api\V1\ProjectController::class, 'store']);
            Route::get('/dashboard', [\App\Http\Controllers\Api\V1\ProjectController::class, 'dashboard']);
            Route::get('/{id}', [\App\Http\Controllers\Api\V1\ProjectController::class, 'show']);
            Route::put('/{id}', [\App\Http\Controllers\Api\V1\ProjectController::class, 'update']);
            Route::delete('/{id}', [\App\Http\Controllers\Api\V1\ProjectController::class, 'destroy']);
            Route::post('/{id}/archive', [\App\Http\Controllers\Api\V1\ProjectController::class, 'archive']);
            Route::post('/{id}/duplicate', [\App\Http\Controllers\Api\V1\ProjectController::class, 'duplicate']);
            Route::get('/{id}/statistics', [\App\Http\Controllers\Api\V1\ProjectController::class, 'statistics']);
            Route::get('/{id}/board', [\App\Http\Controllers\Api\V1\ProjectController::class, 'board']);
            
            // Project Members
            Route::post('/{id}/members', [\App\Http\Controllers\Api\V1\ProjectController::class, 'addMember']);
            Route::delete('/{id}/members/{employeeId}', [\App\Http\Controllers\Api\V1\ProjectController::class, 'removeMember']);
            Route::put('/{id}/members/{employeeId}', [\App\Http\Controllers\Api\V1\ProjectController::class, 'updateMemberRole']);
            
            // Project Tasks
            Route::get('/{projectId}/tasks', [\App\Http\Controllers\Api\V1\TaskController::class, 'index']);
            Route::post('/{projectId}/tasks', [\App\Http\Controllers\Api\V1\TaskController::class, 'store']);
            Route::get('/{projectId}/tasks/statistics', [\App\Http\Controllers\Api\V1\TaskController::class, 'statistics']);
        });

        // Tasks
        Route::prefix('tasks')->group(function (): void {
            Route::get('/{id}', [\App\Http\Controllers\Api\V1\TaskController::class, 'show']);
            Route::put('/{id}', [\App\Http\Controllers\Api\V1\TaskController::class, 'update']);
            Route::delete('/{id}', [\App\Http\Controllers\Api\V1\TaskController::class, 'destroy']);
            Route::post('/{id}/move', [\App\Http\Controllers\Api\V1\TaskController::class, 'move']);
            Route::post('/{id}/assign', [\App\Http\Controllers\Api\V1\TaskController::class, 'assign']);
            Route::post('/{id}/unassign', [\App\Http\Controllers\Api\V1\TaskController::class, 'unassign']);
            Route::post('/{id}/watchers', [\App\Http\Controllers\Api\V1\TaskController::class, 'addWatcher']);
            Route::delete('/{id}/watchers/{employeeId}', [\App\Http\Controllers\Api\V1\TaskController::class, 'removeWatcher']);
            Route::post('/{parentId}/subtasks', [\App\Http\Controllers\Api\V1\TaskController::class, 'createSubtask']);
            Route::get('/{id}/hierarchy', [\App\Http\Controllers\Api\V1\TaskController::class, 'hierarchy']);
            Route::post('/bulk-update-positions', [\App\Http\Controllers\Api\V1\TaskController::class, 'bulkUpdatePositions']);
        });

        // Employee Tasks
        Route::get('employees/{employeeId}/tasks', [\App\Http\Controllers\Api\V1\TaskController::class, 'employeeTasks']);

        // POS (Point of Sale) Domain
        Route::prefix('pos')->name('pos.')->group(function (): void {
            // POS Sessions
            Route::get('sessions', [\App\Domain\POS\Http\Controllers\POSSessionController::class, 'index'])->name('sessions.index');
            Route::get('sessions/active', [\App\Domain\POS\Http\Controllers\POSSessionController::class, 'active'])->name('sessions.active');
            Route::post('sessions', [\App\Domain\POS\Http\Controllers\POSSessionController::class, 'store'])->name('sessions.store');
            Route::get('sessions/{session}', [\App\Domain\POS\Http\Controllers\POSSessionController::class, 'show'])->name('sessions.show');
            Route::post('sessions/{session}/close', [\App\Domain\POS\Http\Controllers\POSSessionController::class, 'close'])->name('sessions.close');
            Route::get('sessions/{session}/summary', [\App\Domain\POS\Http\Controllers\POSSessionController::class, 'summary'])->name('sessions.summary');
            
            // POS Sales
            Route::get('sessions/{sessionId}/sales', [\App\Domain\POS\Http\Controllers\POSSaleController::class, 'index'])->name('sales.index');
            Route::post('sales', [\App\Domain\POS\Http\Controllers\POSSaleController::class, 'store'])->name('sales.store');
            Route::get('sales/{sale}', [\App\Domain\POS\Http\Controllers\POSSaleController::class, 'show'])->name('sales.show');
            Route::post('sales/{sale}/void', [\App\Domain\POS\Http\Controllers\POSSaleController::class, 'void'])->name('sales.void');
        });
    }); // End of company-required routes
});

// Swagger/OpenAPI Documentation (no auth required)
Route::get('/documentation', function () {
    return redirect('/swagger.html');
});

// Public API Routes (no authentication required)
Route::prefix('public/{tenant}')->group(function (): void {
    // Site Rendering
    Route::get('/', [\App\Http\Controllers\Api\Public\SiteController::class, 'homepage']);
    Route::get('/site', [\App\Http\Controllers\Api\Public\SiteController::class, 'show']);
    Route::get('/pages', [\App\Http\Controllers\Api\Public\SiteController::class, 'pages']);
    Route::get('/pages/{slug}', [\App\Http\Controllers\Api\Public\SiteController::class, 'page']);
    Route::get('/blog', [\App\Http\Controllers\Api\Public\SiteController::class, 'blog']);
    Route::get('/blog/{slug}', [\App\Http\Controllers\Api\Public\SiteController::class, 'blogPost']);
    Route::get('/search', [\App\Http\Controllers\Api\Public\SiteController::class, 'search']);
    
    // Contact Forms
    Route::post('/contact', [\App\Http\Controllers\Api\Public\ContactController::class, 'submit']);
    Route::post('/newsletter', [\App\Http\Controllers\Api\Public\ContactController::class, 'newsletter']);
    
    // SEO
    Route::get('/sitemap.xml', [\App\Http\Controllers\Api\Public\SEOController::class, 'sitemap']);
    Route::get('/robots.txt', [\App\Http\Controllers\Api\Public\SEOController::class, 'robots']);
    Route::get('/structured-data', [\App\Http\Controllers\Api\Public\SEOController::class, 'structuredData']);
    Route::get('/meta-tags', [\App\Http\Controllers\Api\Public\SEOController::class, 'metaTags']);
    
    // Shopping Cart
    Route::get('cart', [\App\Http\Controllers\Api\Public\CartController::class, 'show']);
    Route::post('cart/items', [\App\Http\Controllers\Api\Public\CartController::class, 'addItem']);
    Route::put('cart/items/{itemId}', [\App\Http\Controllers\Api\Public\CartController::class, 'updateItem']);
    Route::delete('cart/items/{itemId}', [\App\Http\Controllers\Api\Public\CartController::class, 'removeItem']);
    Route::delete('cart', [\App\Http\Controllers\Api\Public\CartController::class, 'clear']);
    Route::get('cart/count', [\App\Http\Controllers\Api\Public\CartController::class, 'count']);
    
    // Checkout
    Route::get('checkout/payment-methods', [\App\Http\Controllers\Api\Public\CheckoutController::class, 'paymentMethods']);
    Route::post('checkout/place-order', [\App\Http\Controllers\Api\Public\CheckoutController::class, 'placeOrder']);
    
    // Customer Accounts
    Route::post('register', [\App\Http\Controllers\Api\Public\CustomerController::class, 'register']);
    Route::post('login', [\App\Http\Controllers\Api\Public\CustomerController::class, 'login']);
    Route::get('profile', [\App\Http\Controllers\Api\Public\CustomerController::class, 'profile']);
    Route::put('profile', [\App\Http\Controllers\Api\Public\CustomerController::class, 'updateProfile']);
    Route::get('orders', [\App\Http\Controllers\Api\Public\CustomerController::class, 'orders']);
    Route::post('convert-guest', [\App\Http\Controllers\Api\Public\CustomerController::class, 'convertGuest']);
    
    // Product Reviews
    Route::get('products/{productId}/reviews', [\App\Http\Controllers\Api\Public\ReviewController::class, 'index']);
    Route::get('products/{productId}/reviews/stats', [\App\Http\Controllers\Api\Public\ReviewController::class, 'stats']);
    Route::post('products/{productId}/reviews', [\App\Http\Controllers\Api\Public\ReviewController::class, 'store']);
    Route::post('products/{productId}/reviews/{reviewId}/helpful', [\App\Http\Controllers\Api\Public\ReviewController::class, 'markHelpful']);
    Route::get('customer/reviews', [\App\Http\Controllers\Api\Public\ReviewController::class, 'customerReviews']);
    
    // Wishlist
    Route::get('wishlist', [\App\Http\Controllers\Api\Public\WishlistController::class, 'index']);
    Route::post('wishlist', [\App\Http\Controllers\Api\Public\WishlistController::class, 'store']);
    Route::delete('wishlist/products/{productId}', [\App\Http\Controllers\Api\Public\WishlistController::class, 'destroy']);
    Route::get('wishlist/products/{productId}/check', [\App\Http\Controllers\Api\Public\WishlistController::class, 'check']);
    Route::get('wishlist/count', [\App\Http\Controllers\Api\Public\WishlistController::class, 'count']);
    Route::delete('wishlist/clear', [\App\Http\Controllers\Api\Public\WishlistController::class, 'clear']);
    Route::post('wishlist/{wishlistItemId}/move-to-cart', [\App\Http\Controllers\Api\Public\WishlistController::class, 'moveToCart']);
    
    // Public Shipment Tracking
    Route::get('track/{trackingNumber}', [\App\Domain\Logistics\Http\Controllers\TrackingController::class, 'publicTrack']);
});

// Debug routes to test company context
Route::prefix('v1/debug')->middleware(['auth:sanctum,web', 'throttle:api'])->group(function () {
    Route::get('auth-status', function () {
        return response()->json([
            'authenticated' => auth()->check(),
            'user_id' => auth()->id(),
            'user_name' => auth()->user()?->name,
            'session_company_id' => session('active_company_id'),
            'company_header' => request()->header('X-Company-ID'),
            'active_company' => activeCompany()?->only(['id', 'name']),
            'user_companies' => auth()->user()?->companies()->get(['companies.id', 'companies.name']),
        ]);
    });

    Route::get('company-context', function () {
        try {
            $company = activeCompany();
            return response()->json([
                'success' => true,
                'company' => $company ? $company->only(['id', 'name']) : null,
                'session_company_id' => session('active_company_id'),
                'company_header' => request()->header('X-Company-ID'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'session_company_id' => session('active_company_id'),
                'company_header' => request()->header('X-Company-ID'),
            ]);
        }
    });
});