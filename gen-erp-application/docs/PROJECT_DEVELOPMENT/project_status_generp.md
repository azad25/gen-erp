# GenERP BD — Project Status Report (Part 1 of 3)
**Generated:** 2026-03-05  
**Analysed by:** Antigravity / Gemini 2.5 Pro  
**Project root:** `gen-erp-application/`

---

## Executive Summary

GenERP BD is a **large, sophisticated SaaS ERP** built with **Laravel 12 + Inertia.js + Vue 3 + Vite + TailwindCSS v4 + Pinia**. The backend follows a **Domain-Driven Design (DDD)** architecture with 20+ bounded domains. The frontend uses the **TailAdmin** theme, rendered through Inertia.js (server-side routing, Vue pages).

**What is fully working end-to-end:** Authentication (login/register/2FA/company setup), the main dashboard, Sales (orders/invoices/customers/credit-notes), Purchase (orders/receipts/suppliers/returns), Inventory (products/stock/warehouses/transfers/adjustments), Accounting (chart of accounts/journal entries/cost centers/trial balance/P&L/balance sheet), HR (employees/attendance/leave/payroll/tasks/timesheet), Projects (kanban board/tasks), CRM (leads/opportunities/pipelines/activities), CMS (sites/pages/page builder/blog/menus), Logistics (shipments/tracking/returns/COD/carriers), Documents (files/folders/forms/custom fields), Notifications, Settings (company/users/roles/integrations).

**Biggest gaps:** 1) **Sidebar is incomplete** — 12+ pages exist in route/Vue files but are NOT linked from the sidebar (Logistics, CMS contacts/reviews/wishlist/SEO, POS, Reports, Payslips, Payments, Expenses, Approvals, Branches, HR Capacity/Skills/Availability/Performance, CRM Contacts, Accounting Lock Date). 2) **Massive duplication** — TailAdmin UI components exist in BOTH `resources/js/tailadmin/` and `resources/js/Components/` (essentially the same set of components in two directories). The old `resources/js/Pages/` directories duplicate the new domain-organised pages (e.g., `Pages/Customers/` vs `Pages/Sales/Customers.vue`). 3) **Backend-only domains with no frontend** — POS (backend complete, no wired page), Subscription/Plans, Plugins, Event Sourcing, CQRS infrastructure, Alert Rules, PDF generation, Mushak VAT reports (6.1/6.2/6.3/6.6/9.1). 4) **Filament admin panel was removed** — references in views (`filament/pages/*`, `filament/widgets/*`, `filament/logo.blade.php`) still exist as orphaned Blade files. 5) **Multiple duplicate layout components** exist (`AppSidebar.vue`, `Sidebar.vue`, `tailadmin/layout/AppSidebar.vue`).

**Biggest wins:** The DDD architecture is clean and well-structured. There are 152 migrations, 50+ factories, comprehensive test coverage (100+ test files across Feature and Unit). Multi-tenancy via `company_id` is enforced throughout. Bangladesh-specific features (Mushak 6.3 PDFs, BDT currency, BD mobile validation, VAT BIN) are implemented in the backend.

---

## Table of Contents

1. [File Inventory](#1-file-inventory)
2. [Backend Analysis](#2-backend-analysis)
3. [Frontend Analysis](#3-frontend-analysis) *(Part 2)*
4. [Feature Coverage Matrix](#4-feature-coverage-matrix) *(Part 2)*
5. [Dead Code Register](#5-dead-code-register) *(Part 2)*
6. [Duplicate Register](#6-duplicate-register) *(Part 2)*
7. [Issue Catalogue](#7-issue-catalogue) *(Part 3)*
8. [Completion Roadmap](#8-completion-roadmap) *(Part 3)*
9. [Sidebar Completion Plan](#9-sidebar-completion-plan) *(Part 3)*
10. [Recommended File Deletions](#10-recommended-file-deletions) *(Part 3)*

---

## 1. File Inventory

### 1.1 Total Counts

| Category | Count |
|---|---|
| PHP files (excl. vendor/storage/tests) | ~700 |
| PHP test files | ~100 |
| Database migrations | 152 |
| Database seeders | 10 |
| Database factories | 50+ |
| Vue page files (`resources/js/Pages/`) | 162 |
| Vue component files (`resources/js/Components/`) | 156 |
| TailAdmin duplicates (`resources/js/tailadmin/`) | 52 |
| Composables | 12 |
| Blade views | 48 |
| API routes (endpoints) | ~220 |
| Web routes (Inertia pages) | ~80 |

### 1.2 PHP Application Files by Directory

| Directory | Files | Purpose |
|---|---|---|
| `app/Http/Controllers/Api/V1/` | 47 | REST API v1 controllers |
| `app/Http/Controllers/Api/Public/` | 9 | Public storefront API |
| `app/Http/Controllers/Api/V1/CRM/` | 5 | CRM API (leads/opps/pipelines/activities) |
| `app/Http/Controllers/Api/V1/HR/` | 3 | HR task/time/capacity API |
| `app/Http/Controllers/Api/V1/CMS/` | 1 | CMS media upload |
| `app/Http/Controllers/Auth/` | 5 | Session auth controllers |
| `app/Http/Controllers/Sales/` | 3 | Inertia sales dashboard controllers |
| `app/Http/Controllers/Purchase/` | 1 | Inertia purchase dashboard |
| `app/Http/Controllers/Inventory/` | 1 | Inertia inventory dashboard |
| `app/Http/Controllers/HR/` | 1 | Inertia HR dashboard |
| `app/Http/Controllers/CRM/` | 1 | Inertia CRM dashboard |
| `app/Http/Controllers/CMS/` | 1 | Inertia CMS dashboard |
| `app/Http/Controllers/Projects/` | 1 | Inertia projects dashboard |
| `app/Http/Controllers/Documents/` | 1 | Inertia document management |
| `app/Http/Controllers/Document/` | 2 | Forms + Custom Fields controllers |
| `app/Http/Controllers/Web/Projects/` | 1 | Duplicate web project controller |
| `app/Http/Controllers/Accounting/` | 1 | Inertia accounting dashboard |
| `app/Http/Controllers/` (root) | 12 | Other controllers (Dashboard, Docs, etc.) |
| `app/Http/Resources/` | 50+ | API Resource transformers |
| `app/Http/Requests/` | 20+ | Form Request validation classes |
| `app/Http/Middleware/` | 9 | Custom middleware |
| `app/Domain/` | 400+ | All DDD domain code |
| `app/Services/` | 30+ | App-level services |
| `app/Support/Enums/` | 30+ | PHP 8 BackedEnums for status fields |

### 1.3 Domain Architecture

| Domain | Models | Services | Controllers | Events | Jobs |
|---|---|---|---|---|---|
| Auth | User, Company, Invitation, Branch, Dept, Designation | TwoFactorService, SequenceService | AuthController (API+Web) | — | SendInvitationEmail |
| Product | Product, ProductCategory, ProductVariant, TaxGroup, Unit | ProductService, LegacyProductService | ProductController, ProductCategoryController | ProductCreated | ImportProductsJob |
| Purchase | PurchaseOrder, PurchaseOrderItem, GoodsReceipt, Supplier, SupplierPayment | PurchaseService | PurchaseOrderController | — | — |
| SalesOrder | SalesOrder, SalesOrderItem | SalesOrderService | SalesOrderController | SalesOrderConfirmed/Cancelled | — |
| Sales | Invoice (via Sales\InvoiceController) | SalesService | InvoiceController | — | — |
| Customer | Customer, CustomerPayment, CreditNote, SalesReturn | CustomerService, PaymentService, ContactService | CustomerController | CustomerTransactionRecorded | — |
| Inventory | Warehouse, StockMovement, StockLayer, StockAdjustment | — | StockMovementController, WarehouseController | — | — |
| Accounting | Account, AccountGroup, JournalEntry, JournalEntryLine, CostCenter | — | AccountController, JournalEntryController, CostCenterController, LockDateController | — | — |
| HR | Employee, Attendance, LeaveRequest, LeaveType, Payroll, Payslip, EmployeeTask, EmployeeTimeEntry, EmployeeCapacity | — | EmployeeController, AttendanceController, LeaveRequestController, PayrollController, PayslipController, EmployeeTaskController, EmployeeTimeEntryController, EmployeeCapacityController | — | — |
| Project | Project, Board, BoardColumn, Task, ProjectPhase, TaskComment, TaskAttachment, TaskChecklist, TimeEntry | ProjectService, TaskService | ProjectController, TaskController | TaskAssigned, TaskCompleted, TimeLogged | — |
| CRM | Lead, LeadNote, LeadTag, Opportunity, Pipeline, PipelineStage, CrmActivity, CrmContact | LeadService, OpportunityService, PipelineService, ActivityService | LeadController, OpportunityController, PipelineController, ActivityController, DashboardController | — | — |
| CMS | Site, Page, Section, Menu, MenuItem, BlogCategory, BlogPost, ShoppingCart, CartItem, CustomerAccount, PublicOrder, PublicOrderItem, ProductReview, Wishlist, ContactSubmission | CMSService, CartService, CustomerService, ReviewService, WishlistService, PageBuilderService, SEOService | SiteController, PageController, SectionController, MediaController, ReviewController, WishlistController, PageBuilderController, ContactController, SEOController | SiteCreated, PagePublished, OrderPlaced, ReviewSubmitted | — |
| Logistics | Carrier, Shipment, ShipmentItem, TrackingEvent, ShipmentReturn | ShipmentService, TrackingService, ReturnService, CODManagementService | ShipmentController, TrackingController, ReturnController, CODController | — | — |
| Notification | ErpNotification | NotificationDispatchService, NotificationTranslatorService | NotificationController (domain) | SystemAlertFired | SendNotificationJob |
| Document | Document, DocumentFolder, Form, FormField, FormSubmission | DocumentService, FormService, InvoicePDFService, POSReceiptService | DocumentController, DocumentFolderController, FormController, CustomFieldController | — | — |
| POS | POSSession, POSSale, POSSaleItem | POSService | — | — | — |
| Workflow | WorkflowDefinition, WorkflowInstance, WorkflowApproval, WorkflowStatus, WorkflowTransition, WorkflowHistory | WorkflowService | WorkflowInstanceController, ApprovalRequestController | — | — |
| Report | SavedReport | 13 report services (aging, VAT, cash flow, inventory valuation, etc.) | ReportController | — | — |
| Subscription | Plan, Subscription, PaymentRequest, SubscriptionInvoice, UsageCounter | SubscriptionService | — | — | — |
| Shared | AlertRule, AlertLog, CustomFieldDefinition, CustomFieldValue, EntityAlias | CustomFieldService | — | SystemAlertFired | — |
| Payment | — | PaymentService | PaymentController | — | — |
| Compliance | — | Mushak63Service | — | — | — |

### 1.4 Migration Summary

| Date Range | Tables/Changes | Count |
|---|---|---|
| Early core | users, companies, products, invoices, orders, employees, accounts, journals, warehouses, etc. | ~60 |
| 2026-02-28 | Various core business tables | ~20 |
| 2026-03-01 | Tax groups, TDS fields, security events, plugins, plans, subscriptions, webhooks | 9 |
| 2026-03-02 | Event store | 1 |
| 2026-03-03 | CMS (sites/pages/sections/menus/blog/cart/orders/reviews/wishlists/contacts), Notifications, Logistics (carriers/shipments/tracking/returns), CRM (leads/opportunities/pipelines/activities), HR tasks/time entries/capacity/skills | ~45 |
| 2026-03-04 | Financial engine enhancements, stock layers, cost centers, reversal columns, lock date, projects (boards/tasks/phases/comments/attachments/checklists) | ~20 |
| 2026-03-05 | Forms, form fields, form submissions, custom field templates | 5 |

---

## 2. Backend Analysis

### 2A — Routes Summary

**`routes/api.php`** — 591 lines, ~220 endpoints total

| Group | Endpoints | Auth Required | Notes |
|---|---|---|---|
| `POST /api/v1/auth/login` | 1 | No | ✅ Frontend calls `Services/auth.js` |
| `POST /api/v1/auth/register` | 1 | No | ✅ Frontend calls on Signup page |
| `POST /api/v1/auth/logout` | 1 | Sanctum | ✅ |
| `GET /api/v1/auth/user` | 1 | Sanctum | ✅ |
| `POST /api/v1/auth/setup-company` | 1 | Sanctum | ✅ |
| `POST /api/v1/auth/switch-company/{id}` | 1 | Sanctum | ✅ |
| `apiResource users` | 5 | company | ✅ Used in Settings/Users.vue |
| `apiResource payment-methods` | 5 | company | ⚠️ No dedicated Vue page |
| `apiResource leave-types` | 5 | company | ⚠️ No dedicated Vue page |
| `apiResource contact-groups` | 5 | company | ⚠️ No dedicated Vue page |
| `apiResource customers` | 5 | company | ✅ Customers pages exist |
| `apiResource products` | 5 | company | ✅ Products pages exist |
| `apiResource invoices` | 5 | company | ✅ Invoices pages exist |
| `apiResource suppliers` | 5 | company | ✅ Suppliers pages exist |
| `apiResource employees` | 5 | company | ✅ HR/Employees page exists |
| `apiResource sales-orders` + confirm/convert/cancel | 8 | company | ✅ SalesOrders pages exist |
| `apiResource purchase-orders` + confirm/receive/cancel | 8 | company | ✅ PurchaseOrders pages exist |
| `apiResource stock-movements` | 5 | company | ✅ StockMovements/Index.vue |
| `apiResource expenses` | 5 | company | ✅ Expenses/Index.vue |
| `apiResource documents` + download/thumbnail/preview | 8 | company | ✅ Documents pages exist |
| `apiResource document-folders` | 5+5 | company | ⚠️ Duplicated in routes (2 registrations) |
| `apiResource payments` + allocate | 6 | company | ✅ Payments/Index.vue |
| `apiResource credit-notes` | 5 | company | ✅ CreditNotes/Index.vue |
| `apiResource attendance` + bulk | 6 | company | ✅ HR/Attendance.vue |
| `apiResource leave-requests` + approve/reject | 7 | company | ✅ LeaveRequests/Index.vue |
| `apiResource payslips` + download | 6 | company | ✅ Payslips/Index.vue |
| `apiResource payroll` + run | 6 | company | ✅ Payroll/Index.vue |
| HR Enhancement (tasks, time, capacity) | 18 | company | ⚠️ HR/Tasks/Dashboard.vue exists, sidebar points to `/hr/tasks` which is NOT in routes |
| `apiResource workflow-instances` + transition | 6 | company | ✅ Workflows/Index.vue |
| `apiResource approval-requests` + approve/reject | 7 | company | ✅ Approvals/Index.vue |
| `apiResource reports` + generate | 6 | company | ✅ Reports/Index.vue |
| `apiResource companies/branches/warehouses/product-categories/tax-groups/departments/designations` | 35 | company | ⚠️ Only Warehouses/Index.vue; Branches/Index.vue exists; others no dedicated page |
| Notifications (legacy) | 7 | company | ✅ Notifications/Index.vue |
| ERP Notifications (domain) | 5 | company | ⚠️ Both notification systems exist (duplicate) |
| Logistics (shipments/tracking/returns/COD) | 25 | company | ✅ Logistics pages exist |
| CRM (leads/opps/pipelines/activities/dashboard) | 35 | company | ✅ CRM pages exist |
| Custom Fields | 5 | company | ✅ Documents/CustomFields/Index.vue |
| Dashboard | 1 | company | ✅ Dashboard/Index.vue |
| Chart of Accounts/Journal Entries/Cost Centers | 15 | company | ✅ Accounting pages exist |
| Lock Date | 4 | company | ✅ Accounting/LockDateManagement.vue |
| Invitations | 5 | company | ⚠️ No invitation management Vue page |
| Import Jobs | 5 | company | ⚠️ No import jobs Vue page |
| CMS (sites/pages/sections/media/reviews/wishlists/page-builder/contacts/seo/erp-integration) | 40 | company | ✅ Most CMS pages exist |
| Projects (CRUD, members, tasks) | 13 | company | ✅ Projects pages exist |
| Tasks (CRUD, move, assign, watchers, subtasks) | 12 | company | ✅ Tasks pages exist |
| Public API `/public/{tenant}/...` | 20 | No | ⚠️ No frontend consumer in admin app |

**`routes/web.php`** — 489 lines, ~80 Inertia routes

**🔴 CRITICAL ISSUE:** `Route::get('/language/switch', ...)` registered twice (lines 48 and 104 — once as GET, once as POST).

**`routes/documents-routes.php`** and **`routes/documents.php`** — two separate route files for documents. Need to verify which is active/loaded.

### 2B — Controllers

**Controllers with no corresponding route (orphaned):**
- `app/Http/Controllers/DocumentController.php` and `app/Http/Controllers/DocumentDownloadController.php` — legacy, superseded by `Documents/DocumentController.php`
- `app/Http/Controllers/Web/Projects/ProjectController.php` — duplicate of API-based project routes in web.php
- `app/Http/Controllers/InvitationController.php` (web) — different from API `InvitationController`

**Duplicate controller patterns:**
- `DocumentController` exists in 3 places: `Controllers/DocumentController.php`, `Controllers/Documents/DocumentController.php`, `Controllers/DocumentDownloadController.php`
- Two `InvitationController` classes: `Controllers/InvitationController.php` and `Controllers/Api/V1/InvitationController.php`

### 2C — Models

All major models reside in `app/Domain/*/Models/`. There is NO `app/Models/` directory — pure DDD layout.

**Models confirmed to have migrations:** User, Company, Invoice, Product, Customer, Supplier, Employee, SalesOrder, PurchaseOrder, Warehouse, AccountGroup, Account, JournalEntry, CostCenter, Lead, Opportunity, Pipeline, PipelineStage, CrmActivity, Carrier, Shipment, Site, Page, Section — all confirmed.

**Models with no API Resource transformer:**
- `POSSession`, `POSSale`, `POSSaleItem` — POS models have no Resource classes
- `WorkflowDefinition`, `WorkflowHistory`, `WorkflowTransition` — Workflow models have no Resources
- `AlertRule`, `AlertLog` — no Resources
- `Plan`, `Subscription`, `SubscriptionInvoice` — Subscription models have no Resources
- `StockLayer`, `StockLayerAllocation` — no dedicated Resources (StockLevel Resource exists)

### 2D — API Resources

50+ Resource classes in `app/Http/Resources/`. All major CRUD entities have Resources.

**Gaps:**
- `EmployeeResource` exists but no `PayrollResource` — PayrollController likely returns raw data
- `SalesOrderResource` exists but no `SalesOrderItemResource`
- No `ExpenseResource` listed — need to verify

### 2E — Services

**Duplicate service pattern detected:**

| Service | File A | File B | Status |
|---|---|---|---|
| ProductService | `app/Domain/Product/Services/ProductService.php` | `app/Domain/Product/Services/LegacyProductService.php` | LegacyProductService is superseded |
| ContactService | `app/Domain/Customer/Services/ContactService.php` | `app/Domain/Contact/Services/ContactService.php` + `app/Domain/Customer/Services/LegacyContactService.php` | Three versions — LegacyContactService is superseded |
| PaymentService | `app/Domain/Customer/Services/PaymentService.php` | `app/Domain/Payment/Services/PaymentService.php` | Two payment services — likely different scopes |
| PluginManager | `app/Services/PluginManager.php` | `app/Domain/Plugin/Services/PluginManager.php` | Duplicate |
| NotificationService | `app/Services/NotificationService.php` | `app/Domain/Notification/Services/NotificationDispatchService.php` | Legacy vs new domain service |

**Backend-only services (no frontend consumer):**
- `app/Domain/Report/Services/Mushak61ReportService.php` (VAT input report)
- `app/Domain/Report/Services/Mushak62ReportService.php` (VAT output report)
- `app/Domain/Report/Services/Mushak66Service.php` (Mushak 6.6)
- `app/Domain/Report/Services/Mushak91Service.php` (Mushak 9.1)
- `app/Domain/Compliance/Services/Mushak63Service.php` (Mushak 6.3 — invoice VAT)
- `app/Domain/Report/Services/AgingReportService.php`
- `app/Domain/Report/Services/CashFlowReportService.php`
- `app/Domain/Report/Services/ComparativeReportService.php`
- `app/Domain/Report/Services/DimensionalReportService.php`
- `app/Domain/Report/Services/InventoryValuationReportService.php`
- `app/Services/AlertRulesService.php`
- `app/Domain/Subscription/Services/SubscriptionService.php`
- `app/Domain/POS/Services/POSService.php`

### 2F — Form Requests

| Request | Used By | Coverage |
|---|---|---|
| `LoginRequest`, `RegisterRequest`, `CompanySetupRequest` | Auth controllers | ✅ |
| `StoreCustomerRequest`, `UpdateCustomerRequest` | CustomerController | ✅ |
| `StoreProductRequest`, `UpdateProductRequest` | ProductController | ✅ |
| `StoreSupplierRequest`, `UpdateSupplierRequest` | SupplierController | ✅ |
| `CreateInvoiceRequest`, `UpdateInvoiceRequest` | InvoiceController | ✅ |
| `CreateSalesOrderRequest`, `UpdateSalesOrderRequest` | SalesOrderController | ✅ |
| `CreateLeadRequest`, `UpdateLeadRequest`, etc. | CRM Controllers | ✅ |
| `CreateShipmentRequest`, `CreateReturnRequest` | Logistics Controllers | ✅ |
| `ImportRequest` | ImportJobController | ✅ |
| `Setup/CompanySetupRequest` | Duplicate of Auth version | ⚠️ Duplicate |

**Missing FormRequests:** EmployeeController, AttendanceController, PayrollController, JournalEntryController, AccountController — these may be using inline validation or no validation.

### 2G — Middleware

| Middleware | Purpose | Applied |
|---|---|---|
| `EnsureActiveCompany` | Requires active company in session | All authenticated business routes |
| `HandleInertiaRequests` | Shares common data with Inertia | Web routes |
| `ShareInertiaData` | Additional Inertia data sharing | ⚠️ May overlap with HandleInertiaRequests |
| `SecurityHeaders` | Adds security HTTP headers | Global |
| `SetLocale` | Sets app locale from user preference | Global |
| `EnforceModuleAccess` | Module access control | Not seen in routes — may be unused |
| `CheckFeatureFlag` | Feature flag checks | Not seen in routes |
| `CheckSubscriptionStatus` | Subscription validation | Not seen in routes |
| `EnsureActiveBranch` | Branch context | Not seen in routes |

**Issue:** `EnforceModuleAccess`, `CheckFeatureFlag`, `CheckSubscriptionStatus`, and `EnsureActiveBranch` are defined but not applied in routes — dead middleware.

### 2H — Migrations (Summary)

152 total migrations. Every major domain has corresponding migrations. Key observations:
- **`document-folders` registered twice** in `routes/api.php` (lines 139 and 390) — duplicate route registration
- Tables with no corresponding domain models in `app/Domain/`: `performance_reviews` (migration exists, model in `Domain/HR/` needs verification), `employee_worklogs`, `employee_skills`, `employee_availability`
- `create_custom_field_templates_table` migration exists but `CustomFieldTemplate` model not found

### 2I — Seeders & Factories

| Seeder | Status |
|---|---|
| `DatabaseSeeder` | ✅ Orchestrates all seeders |
| `DevAdminSeeder` | ✅ Creates dev admin account |
| `DevSampleDataSeeder` | ✅ Orchestrates 3 sample scenarios |
| `RuposhiRetailSeeder` | ✅ Ruposhi Retail scenario |
| `ShifaPharmacySeeder` | ✅ Shifa Pharmacy scenario |
| `ApexGarmentsSeeder` | ✅ Apex Garments scenario |
| `TaxGroupSeeder` | ✅ BD VAT groups |
| `DefaultUnitsSeeder` | ✅ Units of measure |
| `IntegrationSeeder` | ✅ Integration configs |
| `PlanSeeder` | ✅ Subscription plans |

**Duplicate factories detected:**
- `database/factories/CompanyFactory.php` AND `database/factories/Domain/Auth/Models/CompanyUserFactory.php` 
- `database/factories/EmployeeFactory.php` AND `database/factories/Domain/HR/Models/EmployeeFactory.php`
- `database/factories/SupplierFactory.php` AND `database/factories/Domain/Purchase/Models/SupplierFactory.php`
- `database/factories/WarehouseFactory.php` AND `database/factories/Domain/Inventory/Models/WarehouseFactory.php`
- `database/factories/CustomFieldDefinitionFactory.php` AND `database/factories/Domain/Shared/Models/CustomFieldDefinitionFactory.php`

### 2J — Jobs, Events, Listeners

| File | Purpose | Wired? |
|---|---|---|
| `SendInvitationEmail` | Emails company invitations | ✅ Used in InvitationController |
| `RecordAuditLog` | Writes audit log entries | ✅ Used via spatie/activitylog |
| `SendNotificationJob` | Dispatches ERP notifications | ✅ Domain-driven |
| `ImportProductsJob`, `ProcessImportJob` | Bulk product CSV import | ⚠️ ImportController exists but no import UI page |
| `FilterableCustomFieldJob` | Custom field indexing | ✅ Observer-triggered |
| `SendLockoutNotificationJob` | Security lockout | ✅ SecurityEventService |
| `RunHookHandlerJob`, `RunSyncJob`, `SyncDeviceJob` | Integration hooks | ⚠️ Integration UI exists only in Settings/Integrations.vue |
| `SalesOrderConfirmed/Cancelled` events | Order lifecycle | ✅ EventServiceProvider |
| `TaskAssigned/Completed` events | Project task lifecycle | ✅ |
| `ProductCreated` | Product lifecycle | ✅ |
| `CustomerTransactionRecorded` | Customer balance update | ✅ |
| `CreditNoteReversalListener` | Credit note reversal | ✅ |

### 2K — Policies

| Policy | Model | Applied? |
|---|---|---|
| `SalesOrderPolicy` | SalesOrder | ✅ in SalesOrderPolicy.php |
| `PagePolicy`, `SectionPolicy`, `SitePolicy` | CMS models | ✅ in Domain/CMS/Policies |
| `ResourcePermissionChecker` | Shared | ✅ |

**Missing Policies:** No explicit policies for Customer, Product, Invoice, Employee, PurchaseOrder, Supplier, HR models, Warehouse, Account, JournalEntry — these are major CRUD entities without policies, relying only on middleware.

---

*[See Part 2 for Frontend Analysis, Feature Coverage Matrix, Dead Code, and Duplicate Registers]*
*[See Part 3 for Issue Catalogue, Completion Roadmap, Sidebar Plan, and File Deletions]*
