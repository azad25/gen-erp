# GenERP BD — Domain-by-Domain Audit
**Generated:** 2026-03-05  
**Audit Type:** Systematic Backend → Frontend → Sidebar Check  
**Total Domains:** 29

---

## Audit Methodology

For each domain in `app/Domain/`, this audit checks:
1. ✅ **Backend**: Models, Services, Controllers, Routes
2. ✅ **Frontend**: Vue Pages, Components
3. ✅ **Admin Panel**: Sidebar links, Navigation

**Status Legend:**
- ✅ **Complete** - Backend + Frontend + Sidebar all present
- ⚠️ **Partial** - Backend exists but missing Frontend or Sidebar
- 🔴 **Critical Gap** - Major functionality missing
- 🟢 **Backend Only** - Infrastructure/support domain (no UI expected)

---

## Domain Audit Results

### 1. Auth Domain ✅ COMPLETE

**Backend:**
- ✅ Models: User, Company, Branch, Invitation, SecurityEvent (7 models)
- ✅ Services: AuthService, CompanyService, UserService
- ✅ Controllers: AuthController (API + Web)
- ✅ Routes: `/api/v1/auth/*`, `/login`, `/register`, `/setup-company`

**Frontend:**
- ✅ Pages: `Auth/Signin.vue`, `Auth/Signup.vue`, `Auth/CompanySetup.vue`
- ✅ Components: CompanySwitcher, LanguageSwitcher

**Admin Panel:**
- ✅ Company switcher in sidebar header
- ✅ Settings → Company, Users, Roles

**Status:** ✅ Fully functional

---

### 2. Accounting Domain ✅ COMPLETE

**Backend:**
- ✅ Models: Account, AccountGroup, JournalEntry, JournalEntryLine, CostCenter, Expense, PaymentMethod (7 models)
- ✅ Services: AccountingService, PostingService
- ✅ Controllers: AccountController, JournalEntryController, CostCenterController, LockDateController
- ✅ Routes: `/api/v1/accounts`, `/api/v1/journal-entries`, `/api/v1/cost-centers`

**Frontend:**
- ✅ Pages: `Accounting/Dashboard.vue`, `Accounting/ChartOfAccounts.vue`, `Accounting/JournalEntries.vue`, `Accounting/CostCenters/Index.vue`, `Accounting/TrialBalance.vue`, `Accounting/ProfitLoss.vue`, `Accounting/BalanceSheet.vue`, `Accounting/LockDateManagement.vue`

**Admin Panel:**
- ✅ Sidebar: Accounting group with 6 items
- ⚠️ **MISSING**: Lock Date Management not in sidebar

**Issues:**
- ISS-NEW-001: Lock Date Management page exists but not linked in sidebar

**Status:** ⚠️ Partial (1 page not in sidebar)

---

### 3. CRM Domain ✅ COMPLETE

**Backend:**
- ✅ Models: Lead, LeadNote, LeadTag, Opportunity, Pipeline, PipelineStage, CrmActivity, CrmContact (8 models)
- ✅ Services: LeadService, OpportunityService, PipelineService, ActivityService
- ✅ Controllers: LeadController, OpportunityController, PipelineController, ActivityController, DashboardController (in `app/Http/Controllers/Api/V1/CRM/`)
- ✅ Routes: `/api/v1/crm/leads`, `/api/v1/crm/opportunities`, `/api/v1/crm/pipelines`, `/api/v1/crm/activities`

**Frontend:**
- ✅ Pages: `CRM/Dashboard/Index.vue`, `CRM/Leads/Index.vue`, `CRM/Opportunities/Index.vue`, `CRM/Pipelines/Index.vue`, `CRM/Activities/Index.vue`, `CRM/Contacts/Index.vue`
- ✅ Components: PipelineBoard, LeadConversionWorkflow, ActivityTimeline, LeadScoringWidget

**Admin Panel:**
- ✅ Sidebar: CRM group with 4 items (Leads, Opportunities, Pipelines, Activities)
- ⚠️ **MISSING**: CRM Contacts not in sidebar

**Issues:**
- ISS-NEW-002: CRM Contacts page exists (`CRM/Contacts/Index.vue`) but not linked in sidebar
- ISS-NEW-003: CrmContact model exists but no dedicated API controller/routes

**Status:** ⚠️ Partial (Contacts not accessible)

---

### 4. CMS Domain ⚠️ PARTIAL

**Backend:**
- ✅ Models: Site, Page, Section, Menu, MenuItem, BlogPost, BlogCategory, ShoppingCart, CartItem, CustomerAccount, PublicOrder, ProductReview, Wishlist, ContactSubmission (15 models)
- ✅ Services: CMSService, PageBuilderService, CartService, ReviewService, WishlistService, SEOService, ContactService, CustomerService, ERPIntegrationService, PublicSiteService (10 services)
- ✅ Controllers: SiteController, PageController, SectionController, MediaController (in `app/Http/Controllers/Api/V1/CMS/`)
- ⚠️ **MISSING**: MenuController, ReviewController, WishlistController, ContactController, SEOController

**Frontend:**
- ✅ Pages: `CMS/Dashboard/Index.vue`, `CMS/Sites/Index.vue`, `CMS/Pages/Index.vue`, `CMS/Blog/Index.vue`, `CMS/Menus/Index.vue`, `CMS/Menus/Builder.vue`, `CMS/Reviews/Index.vue`, `CMS/Wishlist/Index.vue`, `CMS/SEO/Index.vue`, `CMS/Contacts/Index.vue`, `CMS/PageBuilder/Index.vue`
- ✅ Components: 8 section renderers, PageBuilderCanvas

**Admin Panel:**
- ✅ Sidebar: CMS group with 4 items (Sites, Pages, Blog, Menus)
- ⚠️ **MISSING**: Reviews, Wishlist, SEO, Contacts not in sidebar

**Issues:**
- ISS-011 (from original audit): CMS Menus page has no API endpoint
- ISS-NEW-004: CMS Reviews page exists but no API controller
- ISS-NEW-005: CMS Wishlist page exists but no API controller  
- ISS-NEW-006: CMS SEO page exists but no API controller
- ISS-NEW-007: CMS Contacts page exists but no API controller
- ISS-NEW-008: 4 CMS pages not linked in sidebar

**Status:** 🔴 Critical Gap (5 pages with no backend, 4 not in sidebar)

---

### 5. Customer Domain ✅ COMPLETE

**Backend:**
- ✅ Models: Customer, CustomerPayment, CreditNote, SalesReturn (4 models)
- ✅ Services: CustomerService, PaymentService, ContactService
- ✅ Controllers: CustomerController, PaymentController, CreditNoteController
- ✅ Routes: `/api/v1/customers`, `/api/v1/payments`, `/api/v1/credit-notes`

**Frontend:**
- ✅ Pages: `Sales/Customers.vue`, `Sales/CreditNotes.vue`, `Sales/Returns.vue`

**Admin Panel:**
- ✅ Sidebar: Sales → Customers, Credit Notes, Returns

**Status:** ✅ Fully functional

---

### 6. Document Domain ✅ COMPLETE

**Backend:**
- ✅ Models: Document, DocumentFolder, Form, FormField, FormSubmission (5 models)
- ✅ Services: DocumentService, FormService, InvoicePDFService, POSReceiptService
- ✅ Controllers: DocumentController, DocumentFolderController, FormController, CustomFieldController
- ✅ Routes: `/api/v1/documents`, `/api/v1/document-folders`, `/api/v1/forms`, `/documents/custom-fields`

**Frontend:**
- ✅ Pages: `Documents/Dashboard.vue`, `Documents/Index.vue`, `Documents/Folders.vue`, `Documents/Recent.vue`, `Documents/Forms/Index.vue`, `Documents/CustomFields/Index.vue`
- ✅ Components: DocumentViewer, DocumentUpload, FormBuilder, CustomFieldManager

**Admin Panel:**
- ✅ Sidebar: Documents group with 5 items

**Status:** ✅ Fully functional

---

### 7. HR Domain ⚠️ PARTIAL

**Backend:**
- ✅ Models: Employee, Attendance, LeaveRequest, LeaveType, PayrollRun, PayrollEntry, EmployeeTask, EmployeeTimeEntry, EmployeeCapacity, EmployeeSkill, EmployeeAvailability, PerformanceReview, Department, Designation (14 models)
- ✅ Services: HRService, PayrollService, CapacityPlanningService, TaskAssignmentService, TimeTrackingService
- ✅ Controllers: EmployeeController, AttendanceController, LeaveRequestController, PayrollController, PayslipController, EmployeeTaskController, EmployeeTimeEntryController, EmployeeCapacityController
- ⚠️ **MISSING**: EmployeeSkillController, EmployeeAvailabilityController, PerformanceReviewController

**Frontend:**
- ✅ Pages: `HR/Dashboard.vue`, `HR/Employees.vue`, `HR/Attendance.vue`, `HR/Leave.vue`, `HR/Payroll.vue`, `HR/Tasks/Dashboard.vue`, `HR/Timesheet/Index.vue`, `HR/Capacity/Index.vue`, `HR/Skills/Index.vue`, `HR/Availability/Calendar.vue`, `HR/Performance/Index.vue`
- ✅ Components: CapacityModal, SkillModal, AvailabilityModal, PerformanceReviewModal, TimeLogModal

**Admin Panel:**
- ✅ Sidebar: HR group with 6 items (Employees, Attendance, Leave, Payroll, Tasks, Timesheet)
- ⚠️ **MISSING**: Capacity, Skills, Availability, Performance not in sidebar

**Issues:**
- ISS-007 (from original audit): HR Skills/Availability/Performance have no API controllers
- ISS-NEW-009: 4 HR pages not linked in sidebar
- ISS-NEW-010: Payslips page exists but not in sidebar

**Status:** 🔴 Critical Gap (3 controllers missing, 4 pages not in sidebar)

---

### 8. Inventory Domain ✅ COMPLETE

**Backend:**
- ✅ Models: Warehouse, StockMovement, StockLayer, StockAdjustment (4 models)
- ✅ Controllers: WarehouseController, StockMovementController, StockAdjustmentController
- ✅ Routes: `/api/v1/warehouses`, `/api/v1/stock-movements`, `/api/v1/stock-adjustments`

**Frontend:**
- ✅ Pages: `Inventory/Dashboard.vue`, `Inventory/Products.vue`, `Inventory/Stock.vue`, `Inventory/Warehouses.vue`, `Inventory/Transfers.vue`, `Inventory/Adjustments.vue`

**Admin Panel:**
- ✅ Sidebar: Inventory group with 5 items

**Status:** ✅ Fully functional

---

### 9. Logistics Domain ✅ COMPLETE (Sidebar Fixed)

**Backend:**
- ✅ Models: Carrier, Shipment, ShipmentItem, ShipmentReturn, TrackingEvent (5 models)
- ✅ Services: ShipmentService, TrackingService, ReturnService, CODManagementService
- ✅ Controllers: ShipmentController, TrackingController, ReturnController, CODController (in `app/Domain/Logistics/Http/Controllers/`)
- ✅ Routes: `/api/v1/logistics/shipments`, `/api/v1/logistics/tracking`, `/api/v1/logistics/returns`, `/api/v1/logistics/cod`
- ✅ Integrations: Pathao, SteadFast, PaperFly, Custom carriers

**Frontend:**
- ✅ Pages: `Logistics/Dashboard/Index.vue`, `Logistics/Shipments/Index.vue`, `Logistics/Tracking/Index.vue`, `Logistics/Returns/Index.vue`, `Logistics/COD/Index.vue`, `Logistics/Carriers/Index.vue`
- ✅ Components: TrackingMap, CarrierSettings

**Admin Panel:**
- ✅ Sidebar: Logistics group with 6 items (FIXED 2026-03-05)

**Status:** ✅ Fully functional

---

### 10. Notification Domain ⚠️ DUPLICATE SYSTEM

**Backend:**
- ✅ Models: ErpNotification (1 model)
- ✅ Services: NotificationDispatchService, NotificationTranslatorService
- ✅ Controllers: NotificationController (domain-based) + legacy NotificationController
- ✅ Routes: `/api/v1/notifications` (both systems)

**Frontend:**
- ✅ Pages: `Notifications/Index.vue`
- ✅ Components: NotificationBell

**Admin Panel:**
- ✅ Sidebar: Notifications (top-level item)

**Issues:**
- ISS-012 (from original audit): Two notification systems co-exist

**Status:** ⚠️ Partial (duplicate system needs consolidation)

---

### 11. POS Domain 🔴 CRITICAL GAP

**Backend:**
- ✅ Models: POSSession, POSSale, POSSaleItem (3 models)
- ✅ Services: POSService
- 🔴 **MISSING**: POSController - NO API ENDPOINTS AT ALL
- 🔴 **MISSING**: All routes (`/api/v1/pos/*`)

**Frontend:**
- ✅ Pages: `POS/Session.vue`
- ⚠️ Page will fail - no API to call

**Admin Panel:**
- ❌ Not in sidebar

**Issues:**
- ISS-006 (from original audit): POS has no API backend
- ISS-NEW-011: POS not in sidebar

**Status:** 🔴 Critical Gap (Complete backend missing)

---

### 12. Product Domain ✅ COMPLETE

**Backend:**
- ✅ Models: Product, ProductCategory, ProductVariant, TaxGroup, Unit (5 models)
- ✅ Services: ProductService, LegacyProductService (deprecated)
- ✅ Controllers: ProductController, ProductCategoryController, TaxGroupController
- ✅ Routes: `/api/v1/products`, `/api/v1/product-categories`, `/api/v1/tax-groups`

**Frontend:**
- ✅ Pages: `Inventory/Products.vue` (includes categories management)

**Admin Panel:**
- ✅ Sidebar: Inventory → Products

**Status:** ✅ Fully functional

---

### 13. Project Domain ⚠️ PARTIAL

**Backend:**
- ✅ Models: Project, Board, BoardColumn, Task, ProjectPhase, TaskComment, TaskAttachment, TaskChecklist, TimeEntry (9 models)
- ✅ Services: ProjectService, TaskService
- ✅ Controllers: ProjectController, TaskController
- ✅ Routes: `/api/v1/projects`, `/api/v1/tasks`
- ⚠️ **MISSING**: `/api/v1/projects/{id}/board` endpoint

**Frontend:**
- ✅ Pages: `Projects/Dashboard.vue`, `Projects/Index.vue`, `Projects/Board.vue`, `Projects/Reports.vue`, `Tasks/Index.vue`
- ✅ Components: KanbanBoard, GanttChart, TaskDetailModal, TimeTracker (20+ components)

**Admin Panel:**
- ✅ Sidebar: Projects group with 3 items
- ⚠️ Reports link points to static `/projects/reports` (needs project ID)

**Issues:**
- ISS-008 (from original audit): Project Board has no API endpoint
- ISS-031 (from original audit): Sidebar reports link broken

**Status:** ⚠️ Partial (Board API missing)

---

### 14. Purchase Domain ✅ COMPLETE

**Backend:**
- ✅ Models: PurchaseOrder, PurchaseOrderItem, GoodsReceipt, GoodsReceiptItem, Supplier, SupplierPayment (6 models)
- ✅ Services: PurchaseService
- ✅ Controllers: PurchaseOrderController, SupplierController, GoodsReceiptController
- ✅ Routes: `/api/v1/purchase-orders`, `/api/v1/suppliers`, `/api/v1/goods-receipts`

**Frontend:**
- ✅ Pages: `Purchase/Dashboard.vue`, `Purchase/Orders.vue`, `Purchase/Receipts.vue`, `Purchase/Suppliers.vue`, `Purchase/Returns.vue`

**Admin Panel:**
- ✅ Sidebar: Purchase group with 4 items

**Status:** ✅ Fully functional

---

### 15. Sales Domain ✅ COMPLETE

**Backend:**
- ✅ Models: Invoice (via Sales\InvoiceController)
- ✅ Services: SalesService
- ✅ Controllers: InvoiceController
- ✅ Routes: `/api/v1/invoices`

**Frontend:**
- ✅ Pages: `Sales/Dashboard.vue`, `Sales/Invoices.vue`

**Admin Panel:**
- ✅ Sidebar: Sales → Invoices

**Status:** ✅ Fully functional

---

### 16. SalesOrder Domain ✅ COMPLETE

**Backend:**
- ✅ Models: SalesOrder, SalesOrderItem (2 models)
- ✅ Services: SalesOrderService
- ✅ Controllers: SalesOrderController
- ✅ Routes: `/api/v1/sales-orders` + confirm/convert/cancel

**Frontend:**
- ✅ Pages: `Sales/Orders.vue`

**Admin Panel:**
- ✅ Sidebar: Sales → Orders

**Status:** ✅ Fully functional

---

### 17. Subscription Domain 🔴 CRITICAL GAP

**Backend:**
- ✅ Models: Plan, Subscription, SubscriptionInvoice, PaymentRequest, UsageCounter (5 models)
- ✅ Services: SubscriptionService
- 🔴 **MISSING**: SubscriptionController - NO API ENDPOINTS
- 🔴 **MISSING**: All routes (`/api/v1/plans`, `/api/v1/subscriptions`)

**Frontend:**
- ❌ No pages exist

**Admin Panel:**
- ❌ Not in sidebar

**Issues:**
- ISS-013 (from original audit): Subscription/Plan system has no API (revenue-blocking for SaaS)

**Status:** 🔴 Critical Gap (Complete system missing)

---

### 18. Workflow Domain ⚠️ PARTIAL

**Backend:**
- ✅ Models: WorkflowDefinition, WorkflowInstance, WorkflowApproval, WorkflowStatus, WorkflowTransition, WorkflowHistory (6 models)
- ✅ Services: WorkflowService
- ✅ Controllers: WorkflowInstanceController, ApprovalRequestController
- ✅ Routes: `/api/v1/workflow-instances`, `/api/v1/approval-requests`

**Frontend:**
- ✅ Pages: `Workflows/Index.vue`, `Approvals/Index.vue`

**Admin Panel:**
- ⚠️ **MISSING**: Workflows not in sidebar (should be in Settings)
- ⚠️ **MISSING**: Approvals not in sidebar

**Issues:**
- ISS-NEW-012: Workflows page exists but not in Settings sidebar
- ISS-NEW-013: Approvals page exists but not in sidebar

**Status:** ⚠️ Partial (2 pages not accessible)

---

## Infrastructure/Support Domains (No UI Expected)

### 19. Audit Domain 🟢 BACKEND ONLY
- ✅ Models: AuditLog (via spatie/activitylog)
- ✅ Services: Audit logging via observers
- Status: Infrastructure only

### 20. Compliance Domain 🟢 BACKEND ONLY
- ✅ Services: Mushak63Service (VAT invoice PDF)
- ⚠️ No API endpoints (ISS-011)
- Status: Backend service only

### 21. Contact Domain 🟢 BACKEND ONLY
- ✅ Models: Contact, ContactGroup
- ✅ Services: ContactService
- Status: Shared domain for Customer/CRM

### 22. Deployment Domain 🟢 BACKEND ONLY
- Status: Deployment scripts/configs

### 23. Integration Domain 🟢 BACKEND ONLY
- ✅ Models: Integration, IntegrationLog, Webhook
- ✅ Services: IntegrationService, WebhookService
- ✅ UI: Settings → Integrations
- Status: Configuration only

### 24. Invoice Domain 🟢 BACKEND ONLY
- ✅ Models: Invoice (shared with Sales)
- Status: Part of Sales domain

### 25. Payment Domain ⚠️ PARTIAL
- ✅ Services: PaymentService
- ✅ Controllers: PaymentController
- ✅ Routes: `/api/v1/payments`
- ⚠️ Pages: `Payments/Index.vue` exists but not in sidebar
- Status: Backend complete, UI not accessible

### 26. Plugin Domain 🔴 CRITICAL GAP
- ✅ Models: Plugin
- ✅ Services: PluginManager
- 🔴 **MISSING**: No API endpoints
- 🔴 **MISSING**: No UI
- Status: Backend only, no management interface

### 27. Report Domain ⚠️ PARTIAL
- ✅ Services: 13 report services (Aging, Cash Flow, VAT reports, etc.)
- ✅ Controllers: ReportController
- ✅ Routes: `/api/v1/reports`
- ⚠️ Pages: `Reports/Index.vue` exists but not in sidebar
- ⚠️ **MISSING**: VAT report UI (ISS-011)
- Status: Generic reports work, VAT reports inaccessible

### 28. Shared Domain 🟢 BACKEND ONLY
- ✅ Models: AlertRule, AlertLog, CustomFieldDefinition, CustomFieldValue, EntityAlias
- ✅ Services: CustomFieldService
- ⚠️ **MISSING**: AlertRule API/UI (ISS-014)
- Status: Shared infrastructure

### 29. System Domain 🟢 BACKEND ONLY
- Status: System-level utilities

---

## Summary Statistics

### By Status
- ✅ **Complete**: 11 domains (38%)
- ⚠️ **Partial**: 10 domains (34%)
- 🔴 **Critical Gap**: 3 domains (10%)
- 🟢 **Backend Only**: 5 domains (17%)

### Critical Issues Found
1. **POS Domain** - Complete backend missing (ISS-006)
2. **Subscription Domain** - Complete backend missing (ISS-013)
3. **CMS Domain** - 5 pages with no API controllers
4. **HR Domain** - 3 controllers missing (Skills, Availability, Performance)
5. **Plugin Domain** - No management interface

### Pages Not in Sidebar (17 total)
1. Accounting → Lock Date Management
2. CRM → Contacts
3. CMS → Reviews
4. CMS → Wishlist
5. CMS → SEO
6. CMS → Contacts
7. HR → Capacity
8. HR → Skills
9. HR → Availability
10. HR → Performance
11. HR → Payslips
12. Payments (top-level or in Accounting)
13. Expenses (top-level or in Accounting)
14. Reports (top-level)
15. POS (top-level)
16. Settings → Workflows
17. Approvals (top-level or in Settings)

---

## Recommended Actions

### Immediate (Sprint 1)
1. Add 17 missing sidebar links
2. Fix duplicate notification system (ISS-012)
3. Remove test routes and dead code

### High Priority (Sprint 2-3)
1. Build POS API (ISS-006) - 2 days
2. Build HR Skills/Availability/Performance APIs (ISS-007) - 2 days
3. Build CMS Reviews/Wishlist/SEO/Contacts APIs - 2 days
4. Add Project Board API endpoint (ISS-008) - 0.5 days

### Business Critical (Sprint 4)
1. Build Subscription/Plan API + UI (ISS-013) - 3-5 days
2. Add VAT Reports UI (ISS-011) - 2-3 days
3. Build Plugin management UI - 1-2 days
4. Build Alert Rules UI (ISS-014) - 1-2 days

---

**Document Version:** 1.0  
**Last Updated:** 2026-03-05  
**Next Review:** After Sprint 1 completion
