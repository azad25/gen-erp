# GenERP BD — Project Status Report (Part 3 of 3)

---

## 7. Issue Catalogue

**Total: 42 issues — 8 Critical, 14 High, 14 Medium, 6 Low**

---

### 🔴 CRITICAL Issues

**ISS-001** | Integration | `routes/api.php` line 139 and line 390  
`apiResource('document-folders', ...)` registered **twice** in the same middleware group. Laravel will silently use one; the other wastes memory and causes confusion.  
**Fix:** Remove the duplicate at line 390 (keep one registration).

**ISS-002** | Backend | `routes/web.php` lines 48 and 104  
`/language/switch` registered as both GET (line 48) and POST (line 104) with different names (`locale.set` vs `language.switch`). Depending on navigation this may throw CSRF errors or method-not-allowed.  
**Fix:** Remove the GET version, keep POST only.

**ISS-003** | Frontend | Sidebar `hr.tasks` — The sidebar links `/hr/tasks` with routeName `hr.tasks`, but the actual route is `/hr/tasks/dashboard` (name `hr.tasks.dashboard`). Clicking this sidebar item navigates to a non-existent route, causing a 404.  
**Fix:** Change sidebar href to `/hr/tasks/dashboard` and routeName to `hr.tasks.dashboard`.

**ISS-004** | Frontend | **Entire Logistics module missing from sidebar.** 6+ Logistics pages exist (`/logistics/dashboard`, `/logistics/shipments`, `/logistics/tracking`, `/logistics/returns`, `/logistics/cod`, `/logistics/carriers`) with complete backend, but zero sidebar entries. Users cannot navigate to Logistics.  
**Fix:** Add Logistics group to `AppSidebar.vue`.

**ISS-005** | Security | **No Policies for major CRUD entities.** Customer, Product, Invoice, Employee, PurchaseOrder, Supplier, Warehouse, Account, JournalEntry — none have Laravel Policies. All authorization relies only on `ensure.company` middleware (which only verifies company context, not role-based permissions). Any authenticated user in a company can access/delete anything.  
**Fix:** Create Policies for all major entities and register in `AuthServiceProvider`. Gate checks must reference user role from company pivot.

**ISS-006** | Backend | **POS has no API backend.** `Pages/POS/Session.vue` exists and is in the web routes, but `POSService` has zero API endpoints. The POS page will fail to load any data or process any transaction.  
**Fix:** Create `POSController` with endpoints for session open/close, product search, cart management, checkout.

**ISS-007** | Backend | **HR Skills, HR Availability, HR Performance have no API controllers or routes.** Pages exist and migrations exist (employee_skills, employee_availability, performance_reviews tables), but zero API endpoints. The pages will be blank.  
**Fix:** Create controllers `EmployeeSkillController`, `EmployeeAvailabilityController`, `PerformanceReviewController` and register in `api.php`.

**ISS-008** | Backend | **Project Board has no board-specific API endpoint.** `Pages/Projects/Board.vue` renders a Kanban board, but there is no `GET /api/v1/projects/{id}/board` endpoint (only task list endpoint). The board page likely constructs its data by fetching tasks, but the board column structure (`BoardColumn` model) has no exposed endpoint.  
**Fix:** Add `GET /api/v1/projects/{id}/board` returning board with columns and tasks.

---

### 🟠 HIGH Issues

**ISS-009** | Frontend | 25+ duplicate Vue page files in old `Pages/` subdirectories (`Pages/Customers/`, `Pages/Employees/`, `Pages/Invoices/`, etc.) that have no web routes but exist alongside the correct domain-organised pages. These inflate bundle size and create developer confusion.  
**Fix:** Delete all 25+ orphaned pages listed in §5.2.

**ISS-010** | Frontend | 52 files in `resources/js/tailadmin/` are exact duplicates of `resources/js/Components/`. Both directories are in the codebase. The `tailadmin/` directory likely doesn't get imported in the active codebase but still inflates the repo.  
**Fix:** Verify no active imports use `tailadmin/` path, then delete the `tailadmin/` directory.

**ISS-011** | Integration | **Mushak VAT PDF reports (6.1, 6.2, 6.3, 6.6, 9.1) have no API endpoints and no UI.** Bangladesh NBR compliance requires these reports for VAT-registered companies. Services are fully implemented but inaccessible.  
**Fix:** Add `/api/v1/reports/vat/mushak-63` etc. endpoints and add a "VAT Reports" section to the Reports page.

**ISS-012** | Backend | **Two notification systems co-exist:** `NotificationController` (legacy, `app/Support/Enums/NotificationEvent.php` based) and the domain-driven `Domain/Notification/Http/Controllers/NotificationController.php`. Both are registered in `api.php` at different prefixes. The `Notifications/Index.vue` page may be calling one or the other inconsistently.  
**Fix:** Deprecate and remove the legacy notification controller. Migrate all consumers to the domain controller.

**ISS-013** | Backend | **Subscription/Plan system has no API.** `Plan`, `Subscription`, `SubscriptionInvoice` models and `SubscriptionService` exist with complete migrations, but zero API endpoints and zero UI. For a SaaS product this is a critical revenue-blocking gap.  
**Fix:** Create `SubscriptionController`, plan management UI, and billing portal.

**ISS-014** | Backend | **Alert Rules have no UI.** `AlertRule`, `AlertLog` models and `AlertRulesService` exist. These are business-critical (e.g., "notify when stock drops below threshold"), but no API endpoints or UI exist.  
**Fix:** Add `AlertRuleController` and an Alert Rules settings page.

**ISS-015** | Frontend | **CMS Menus page (`/cms/menus`) — no API endpoint for menu CRUD.** `CMS/Menus/Index.vue` and `CMS/Menus/Builder.vue` exist, but the `api.php` CMS group has no `/api/v1/cms/menus` route. The `Menu` and `MenuItem` models exist.  
**Fix:** Add menu CRUD API routes and a MenuController.

**ISS-016** | Security | **`test_auth.php` in project root.** This is a PHP script that may expose auth debugging information. Should never be deployed.  
**Fix:** Delete `gen-erp-application/test_auth.php`.

**ISS-017** | Frontend | **Multiple test pages in production routes:** `/test`, `/test-no-middleware`, `/test-no-auth`, `/test-auth-status`, `/test-sanctum-auth`, `/debug-session` — all exposed without auth on the web. Security risk (exposes session, cookie, and user data).  
**Fix:** Remove all test/debug routes from `web.php` before any production deployment.

**ISS-018** | Backend | **Goods Receipt (GRN) has no dedicated API resource route.** `GoodsReceipt` and `GoodsReceiptItem` models and Resources exist, but no `apiResource('goods-receipts', ...)` route. Purchase receipts are accessible via the Purchase dashboard controller only in Inertia form, not as a REST endpoint.  
**Fix:** Add `apiResource('goods-receipts', GoodsReceiptController::class)` to `api.php`.

**ISS-019** | Backend | **Invitation management has no Vue UI.** InvitationController and routes exist (CRUD), but no "Invite User" page exists in the frontend. Users currently cannot invite teammates via the UI.  
**Fix:** Add invitation management to `Settings/Users.vue` (send invite modal + pending invitations list).

**ISS-020** | Backend | **Import Jobs have no UI.** `ImportJobController` and routes exist, but no frontend page. The bulk import feature (products, etc.) is completely inaccessible to users.  
**Fix:** Add an "Import" button/dialog to relevant list pages (Products, Customers, Suppliers).

**ISS-021** | Backend | **`EnforceModuleAccess`, `CheckFeatureFlag`, `CheckSubscriptionStatus`, `EnsureActiveBranch` middleware are defined but never applied.** This means subscription limits and module gates are not enforced at runtime.  
**Fix:** Register these middleware in `bootstrap/app.php` and apply to appropriate route groups.

**ISS-022** | Backend | **Payment Methods and Leave Types have no dedicated Vue pages.** Both are full `apiResource` resources but no management UI exists. Admins cannot configure these without database access.  
**Fix:** Add basic management pages or integrate into the Settings area.

---

### 🟡 MEDIUM Issues

**ISS-023** | Frontend | `Pages/Shared/FormPage.vue`, `Pages/Shared/IndexPage.vue`, `Pages/Shared/ShowPage.vue` — generic page templates that appear unused (no routes load them as Inertia components directly).  
**Fix:** Either wire them up as proper base layouts or delete if not used.

**ISS-024** | Backend | `routes/documents-routes.php` AND `routes/documents.php` — two route files for documents with unclear relationship. Only one can be loaded via `bootstrap/app.php` routing. Risk of conflict or dead routes.  
**Fix:** Consolidate into one file and ensure it is the one referenced.

**ISS-025** | Frontend | Sidebar route detection (`isCurrentRoute`) uses string-based path matching rather than Laravel-generated named routes. This is fragile and will break if route paths change.  
**Fix:** Use Inertia's `$page.props.ziggy` (if ziggy is installed) or pass current route name from server for reliable active-state detection.

**ISS-026** | Frontend | `Pages/Auth/SelectCompany.vue` — loaded by `CompanyAccessController` but has no named Inertia route in web.php. The page is rendered via the controller directly.  
**Fix:** Add `Route::inertia('/select-company', 'Auth/SelectCompany')` or ensure the controller passes the right component name.

**ISS-027** | Backend | Factory duplicates: 5 pairs of duplicate factories (root `database/factories/` vs `database/factories/Domain/*/`). Tests using the wrong factory will fail silently.  
**Fix:** Consolidate all factories into `database/factories/Domain/` and update all test `use` statements.

**ISS-028** | Frontend | `Components/common/DataTable.vue` and `Components/UI/DataTable.vue` — two DataTable implementations. Inconsistent usage across pages means different feature sets (sorting, filtering, pagination) available depending on which was used.  
**Fix:** Consolidate to one DataTable component.

**ISS-029** | Frontend | `Components/common/Pagination.vue` and `Components/UI/Pagination.vue` — same duplication as DataTable.  
**Fix:** Consolidate to one Pagination component.

**ISS-030** | Backend | `app/Http/Middleware/ShareInertiaData.php` and `app/Http/Middleware/HandleInertiaRequests.php` may overlap in what data they share with Inertia. Risk of double-sharing or one overriding the other.  
**Fix:** Review both middleware and merge into `HandleInertiaRequests` only.

**ISS-031** | Frontend | Sidebar uses `$t('sidebar.projects.reports')` with routeName `projects.reports`, but the actual project reports route is `projects.reports` with path `/{project}/reports` — a dynamic route requiring a project ID. A static sidebar link to `/projects/reports` (no ID) would 404.  
**Fix:** Change sidebar to link to `/projects/dashboard` and access reports from within the project view.

**ISS-032** | Backend | `app/Http/Controllers/CMS/CMSDashboardController.php` uses Inertia to render CMS dashboard. Some CMS sub-routes (blog, reviews, wishlist, SEO) have no corresponding API data fetching — they may render empty.  
**Fix:** Ensure CMS dashboard passes initial stats to Inertia.

**ISS-033** | Frontend | `Components/Integration/CrossDomainWidget.vue`, `QuickActionsWidget.vue`, `QuickStatsWidget.vue`, `RecentActivitiesWidget.vue` — integration widgets with no clear parent page or route that imports them.  
**Fix:** Verify if these are used on the dashboard or delete.

**ISS-034** | Backend | `app/Http/Controllers/Document/CustomFieldController.php` has an endpoint `GET /documents/custom-fields/api/entity-types` — this uses "api" as a literal URL segment inside a web route, which is confusing and may conflict with API middleware.  
**Fix:** Rename to `/documents/custom-fields/entity-types`.

**ISS-035** | Frontend | CRM sidebar links use routeName `crm.leads`, `crm.opportunities`, `crm.pipelines`, `crm.activities`, but the actual web route names are `crm.leads.index`, `crm.opportunities.index`, `crm.pipelines.index`, `crm.activities.index`. Active state detection in the sidebar will never work correctly for CRM.  
**Fix:** Update sidebar routeNames to match actual route names.

**ISS-036** | Database | **`custom_field_templates` table** exists in migration (2026_03_05_100004) but no corresponding `CustomFieldTemplate` model was found.  
**Fix:** Create the model or drop the migration if unused.

---

### 🟢 LOW Issues

**ISS-037** | Code Quality | 8+ unused Blade view files (Filament orphans) still in `resources/views/filament/`. Not harmful but add confusion.  
**Fix:** Delete all files listed in §5.3.

**ISS-038** | Code Quality | `Pages/Auth/Login.blade.php` and `Pages/Auth/register.blade.php` in `resources/views/auth/` are legacy Blade templates superseded by Vue Inertia pages. Having both causes confusion.  
**Fix:** Delete the legacy Blade auth views.

**ISS-039** | Code Quality | `CompanySetupRequest` exists in both `Requests/Auth/` and `Requests/Setup/`. Identical validation rules, two classes.  
**Fix:** Delete `Requests/Setup/CompanySetupRequest.php`, use `Requests/Auth/` version everywhere.

**ISS-040** | Code Quality | `utils/pagination.js` in `resources/js/utils/` provides pagination helpers. `Composables/usePagination.js` does the same. Two pagination helpers.  
**Fix:** Move utils/pagination.js logic into usePagination.js composable and delete the utils file.

**ISS-041** | Code Quality | Icons are split across `resources/js/icons/` (26 custom SVG icons) and the `@heroicons/vue` package (imported elsewhere). Inconsistent icon system.  
**Fix:** Standardise on `lucide-vue-next` (already installed) or `@heroicons/vue` and remove the custom `icons/` directory.

**ISS-042** | Docs | `resources/views/docs/index.blade.php` and the DocsController serve internal documentation. The docs system (`DocsApp.vue`, `NavGroup.vue`, `SearchModal.vue`) is functional but not linked from the admin sidebar.  
**Fix:** Add a "Docs" link in the sidebar footer or user menu.

---

## 8. Completion Roadmap

---

### Sprint 1 — Fix Critical Bugs & Navigation (3 days)
**Goal:** Ensure all existing features are reachable and routing is correct.

**Tasks:**
- [ ] TASK-001: Fix duplicate `document-folders` route in `api.php` (remove line 390) — `routes/api.php`
- [ ] TASK-002: Fix duplicate `/language/switch` route in `web.php` (remove GET version) — `routes/web.php`
- [ ] TASK-003: Fix HR Tasks sidebar href (`/hr/tasks` → `/hr/tasks/dashboard`) — `Components/Layout/AppSidebar.vue`
- [ ] TASK-004: Fix CRM sidebar routeNames (add `.index` suffix for all CRM items) — `Components/Layout/AppSidebar.vue`
- [ ] TASK-005: Fix Projects Reports sidebar link (remove static `/projects/reports` — ISS-031) — `Components/Layout/AppSidebar.vue`
- [ ] TASK-006: **Add Logistics group to sidebar** (Dashboard, Shipments, Tracking, Returns, COD, Carriers) — `Components/Layout/AppSidebar.vue`
- [ ] TASK-007: Add missing sidebar entries — Reports, POS, Settings/Workflows, CRM Contacts, CMS Contacts/Reviews/Wishlist/SEO — `Components/Layout/AppSidebar.vue`
- [ ] TASK-008: Add missing sidebar entries — HR Capacity, Skills, Availability, Performance, Accounting Lock Date, Payslips, Payments, Expenses — `Components/Layout/AppSidebar.vue`
- [ ] TASK-009: Delete all test/debug routes from `web.php` (ISS-017) — `routes/web.php`
- [ ] TASK-010: Delete `test_auth.php` (ISS-016) — project root

**Definition of Done:** All existing pages reachable from sidebar. Zero 404s on sidebar navigation. No test routes in web.php.

---

### Sprint 2 — Remove Duplicates & Dead Code (2 days)
**Goal:** Clean up all duplicate files, dead code, and orphaned views.

**Tasks:**
- [ ] TASK-011: Verify no imports use `tailadmin/` paths, then delete `resources/js/tailadmin/` (52 files)
- [ ] TASK-012: Delete all 25+ orphaned Vue page files listed in §5.2
- [ ] TASK-013: Delete orphaned Blade files (Filament views, legacy auth views) listed in §5.3
- [ ] TASK-014: Consolidate `DataTable` — keep `Components/UI/DataTable.vue`, remove `Components/common/DataTable.vue`, update imports
- [ ] TASK-015: Consolidate `Pagination` — keep `Components/UI/Pagination.vue`, remove `Components/common/Pagination.vue`, update imports
- [ ] TASK-016: Consolidate duplicate factory files — move root factories to `Domain/` equivalents, update test imports
- [ ] TASK-017: Delete `LegacyProductService`, `LegacyContactService` — `app/Domain/Product/Services/`, `app/Domain/Customer/Services/`
- [ ] TASK-018: Delete orphaned controllers: `DocumentController.php`, `DocumentDownloadController.php`, `Web/Projects/ProjectController.php`
- [ ] TASK-019: Consolidate `CompanySetupRequest` duplicates
- [ ] TASK-020: Consolidate `documents-routes.php` and `documents.php` into one file
- [ ] TASK-021: Merge `ShareInertiaData` middleware into `HandleInertiaRequests`, delete the former
- [ ] TASK-022: Delete `utils/pagination.js`, merge into `usePagination.js`

**Definition of Done:** Zero duplicate component paths. Bundle size reduced. All tests still pass.

---

### Sprint 3 — Missing API Endpoints (4 days)
**Goal:** Wire backend-only features to API endpoints.

**Tasks:**
- [ ] TASK-023: Create `GoodsReceiptController` with `apiResource('goods-receipts', ...)` — `app/Http/Controllers/Api/V1/`
- [ ] TASK-024: Create `EmployeeSkillController` — skill CRUD endpoints — `app/Http/Controllers/Api/V1/HR/`
- [ ] TASK-025: Create `EmployeeAvailabilityController` — availability endpoints — `app/Http/Controllers/Api/V1/HR/`
- [ ] TASK-026: Create `PerformanceReviewController` — review CRUD endpoints — `app/Http/Controllers/Api/V1/HR/`
- [ ] TASK-027: Add `GET /api/v1/projects/{id}/board` endpoint returning board with columns + tasks — `ProjectController`
- [ ] TASK-028: Add CMS Menu CRUD routes and `MenuController` — `api.php`, new controller
- [ ] TASK-029: Add VAT report API endpoints: `/api/v1/reports/vat/mushak-63`, `/mushak-61`, `/mushak-62`, `/mushak-66`, `/mushak-91` — new `VatReportController`
- [ ] TASK-030: Add Alert Rule CRUD API: `apiResource('alert-rules', AlertRuleController::class)` — new controller
- [ ] TASK-031: Add Payment Method API form request validation and dedicated management endpoints
- [ ] TASK-032: Create `CustomFieldTemplate` model and wire migration to API endpoint

**Definition of Done:** All new endpoints tested. HR enhancement pages (skills/availability/performance) return real data.

---

### Sprint 4 — POS & Subscription Systems (5 days)
**Goal:** Implement the two major missing backend systems.

**Tasks:**
- [ ] TASK-033: Create `POSController` with session open/close, product search, sale creation, receipt endpoints
- [ ] TASK-034: Wire `POS/Session.vue` to the new POS API endpoints
- [ ] TASK-035: Add POS to sidebar
- [ ] TASK-036: Create `SubscriptionController` with plan listing, subscribe, cancel, upgrade endpoints
- [ ] TASK-037: Create subscription management Vue page (`Pages/Subscription/`)
- [ ] TASK-038: Register and apply `CheckSubscriptionStatus` middleware to business routes
- [ ] TASK-039: Register and apply `CheckFeatureFlag` and `EnforceModuleAccess` middleware
- [ ] TASK-040: Add usage counter tracking calls to key business operations (invoices created, products added, etc.)

**Definition of Done:** POS session can be opened, products scanned, sale completed, receipt printed. Subscription limits enforced at runtime.

---

### Sprint 5 — Invitation, Import & Settings UI (3 days)
**Goal:** Close remaining backend-complete / frontend-missing gaps.

**Tasks:**
- [ ] TASK-041: Add "Invite User" modal to `Settings/Users.vue` — calls `POST /api/v1/invitations`
- [ ] TASK-042: Add pending invitations list to Users settings page
- [ ] TASK-043: Add "Import" bulk upload dialog to Products page — calls `POST /api/v1/import-jobs`
- [ ] TASK-044: Add payment methods management section to Settings
- [ ] TASK-045: Add leave types management section to Settings or HR area
- [ ] TASK-046: Add departments and designations management pages (migrations and models exist)
- [ ] TASK-047: Add branches management page (migration and model exist, `Branches/Index.vue` exists)
- [ ] TASK-048: Wire `Settings/Workflows.vue` to workflow API and add to sidebar

**Definition of Done:** Admins can invite users, import data via CSV, and manage all lookup tables from the UI.

---

### Sprint 6 — Security Hardening (2 days)
**Goal:** Add missing security controls.

**Tasks:**
- [ ] TASK-049: Create Policies for: Customer, Product, Invoice, Employee, PurchaseOrder, Supplier, JournalEntry, Warehouse, Account — register in `AuthServiceProvider`
- [ ] TASK-050: Add `$this->authorize(...)` checks to all API controllers currently lacking them
- [ ] TASK-051: Add missing FormRequests for: EmployeeController, AttendanceController, PayrollController, JournalEntryController, AccountController
- [ ] TASK-052: Verify session cookie settings (`HttpOnly`, `Secure`, `SameSite=Strict`) in `config/session.php`
- [ ] TASK-053: Apply `EnsureActiveBranch` middleware to branch-scoped routes
- [ ] TASK-054: Add rate limiting to sensitive endpoints (payroll run, report generation)

**Definition of Done:** All CRUD entities have Policies. No controller lacking authorization. All input validated via FormRequest.

---

### Sprint 7 — VAT / Mushak Reports UI (3 days)
**Goal:** Surface Bangladesh-specific compliance features.

**Tasks:**
- [ ] TASK-055: Add "VAT Reports" tab to `Reports/Index.vue`
- [ ] TASK-056: Wire Mushak 6.3 PDF generation to invoice view (download button)
- [ ] TASK-057: Wire Mushak 6.1, 6.2 input/output reports (monthly VAT return)
- [ ] TASK-058: Add VAT BIN display on invoices for VAT-registered companies
- [ ] TASK-059: Add Mushak 9.1 (annual return) report page

**Definition of Done:** Any VAT-registered company can generate all required NBR reports in one click.

---

### Sprint 8 — Analytics & Dashboard (2 days)
**Goal:** Make the dashboard actually useful with real data.

**Tasks:**
- [ ] TASK-060: Wire ecommerce widgets (`EcommerceMetrics`, `MonthlySale`, `RecentOrders`) to real API data from Dashboard endpoint
- [ ] TASK-061: Add chart visualisation (ApexCharts, already installed) for sales trends, purchase trends, inventory levels
- [ ] TASK-062: Add company-level KPI widgets (total revenue, unpaid invoices, low-stock alerts, pending approvals count)
- [ ] TASK-063: Wire `Components/Integration/QuickStatsWidget.vue` to cross-domain data or delete if unused

**Definition of Done:** Dashboard shows live company data without placeholder values.

---

## 9. Sidebar Completion Plan

### Missing Pages That Should Be in Sidebar

| Page/Feature | Vue File Path | Route Name | Sidebar Section | Icon Suggestion |
|---|---|---|---|---|
| Logistics Dashboard | `Pages/Logistics/Dashboard/Index.vue` | `logistics.dashboard` | Logistics (new group) | TruckIcon |
| Shipments | `Pages/Logistics/Shipments/Index.vue` | `logistics.shipments.index` | Logistics | PackageIcon |
| Shipment Tracking | `Pages/Logistics/Tracking/Index.vue` | `logistics.tracking.index` | Logistics | MapPinIcon |
| Returns | `Pages/Logistics/Returns/Index.vue` | `logistics.returns.index` | Logistics | ArchiveIcon |
| COD Management | `Pages/Logistics/COD/Index.vue` | `logistics.cod.index` | Logistics | BanknotesIcon |
| Carriers | `Pages/Logistics/Carriers/Index.vue` | `logistics.carriers.index` | Logistics | TruckIcon |
| Reports | `Pages/Reports/Index.vue` | `reports` | New top-level | ChartBarIcon |
| POS | `Pages/POS/Session.vue` | `pos.session` | New top-level | ShoppingCartIcon |
| CMS → Contacts | `Pages/CMS/Contacts/Index.vue` | `cms.contacts.index` | CMS group | ChatBubbleIcon |
| CMS → Reviews | `Pages/CMS/Reviews/Index.vue` | `cms.reviews.index` | CMS group | StarIcon |
| CMS → Wishlist | `Pages/CMS/Wishlist/Index.vue` | `cms.wishlist.index` | CMS group | HeartIcon |
| CMS → SEO | `Pages/CMS/SEO/Index.vue` | `cms.seo.index` | CMS group | GlobeIcon |
| CRM → Contacts | `Pages/CRM/Contacts/Index.vue` | `crm.contacts.index` | CRM group | UserGroupIcon |
| HR → Capacity | `Pages/HR/Capacity/Index.vue` | `hr.capacity.index` | HR group | ChartBarIcon |
| HR → Skills | `Pages/HR/Skills/Index.vue` | `hr.skills.index` | HR group | AcademicCapIcon |
| HR → Availability | `Pages/HR/Availability/Calendar.vue` | `hr.availability.calendar` | HR group | CalenderIcon |
| HR → Performance | `Pages/HR/Performance/Index.vue` | `hr.performance.index` | HR group | TrophyIcon |
| Accounting → Lock Date | `Pages/Accounting/LockDateManagement.vue` | `accounting.lock-date` | Accounting group | LockIcon |
| Settings → Workflows | `Pages/Workflows/Index.vue` | `settings.workflows` | Settings group | ArrowPathIcon |
| Payments | `Pages/Payments/Index.vue` | `payments.index` | Accounting or Sales | BanknotesIcon |
| Expenses | `Pages/Expenses/Index.vue` | `expenses.index` | Accounting or Sales | ReceiptIcon |

### Exact Sidebar Config to Add to `AppSidebar.vue`

Add these to the `menuGroups` computed array in `resources/js/Components/Layout/AppSidebar.vue`:

```javascript
// Add after 'cms' group, before 'settings' group:
{
  key: "logistics",
  title: $t('sidebar.logistics.title'),
  icon: BoxIcon, // or a truck icon from lucide-vue-next
  items: [
    { icon: BoxIcon, title: $t('sidebar.logistics.shipments'), href: "/logistics/shipments", routeName: "logistics.shipments.index" },
    { icon: ListIcon, title: $t('sidebar.logistics.tracking'), href: "/logistics/tracking", routeName: "logistics.tracking.index" },
    { icon: ArchiveIcon, title: $t('sidebar.logistics.returns'), href: "/logistics/returns", routeName: "logistics.returns.index" },
    { icon: DocsIcon, title: $t('sidebar.logistics.cod'), href: "/logistics/cod", routeName: "logistics.cod.index" },
    { icon: SettingsIcon, title: $t('sidebar.logistics.carriers'), href: "/logistics/carriers", routeName: "logistics.carriers.index" },
  ],
},
{
  key: "reports",
  title: $t('sidebar.reports.title'),
  icon: BarChartIcon,
  items: [
    { icon: BarChartIcon, title: $t('sidebar.reports.all'), href: "/reports", routeName: "reports" },
  ],
},
{
  key: "pos",
  title: $t('sidebar.pos.title'),
  icon: DocsIcon,
  items: [
    { icon: DocsIcon, title: $t('sidebar.pos.session'), href: "/pos/session", routeName: "pos.session" },
  ],
},
```

**And extend existing groups:**

```javascript
// In 'cms' items array, add:
{ icon: ChatIcon, title: $t('sidebar.cms.contacts'), href: "/cms/contacts", routeName: "cms.contacts.index" },
{ icon: ListIcon, title: $t('sidebar.cms.reviews'), href: "/cms/reviews", routeName: "cms.reviews.index" },
{ icon: PageIcon, title: $t('sidebar.cms.wishlist'), href: "/cms/wishlist", routeName: "cms.wishlist.index" },
{ icon: BarChartIcon, title: $t('sidebar.cms.seo'), href: "/cms/seo", routeName: "cms.seo.index" },

// In 'crm' items array, add:
{ icon: UserCircleIcon, title: $t('sidebar.crm.contacts'), href: "/crm/contacts", routeName: "crm.contacts.index" },

// In 'hr' items array, add:
{ icon: BarChartIcon, title: $t('sidebar.hr.capacity'), href: "/hr/capacity", routeName: "hr.capacity.index" },
{ icon: TaskIcon, title: $t('sidebar.hr.skills'), href: "/hr/skills", routeName: "hr.skills.index" },
{ icon: CalenderIcon, title: $t('sidebar.hr.availability'), href: "/hr/availability", routeName: "hr.availability.calendar" },
{ icon: BarChartIcon, title: $t('sidebar.hr.performance'), href: "/hr/performance", routeName: "hr.performance.index" },

// In 'accounting' items array, add:
{ icon: SettingsIcon, title: $t('sidebar.accounting.lock_date'), href: "/accounting/lock-date", routeName: "accounting.lock-date" },

// In 'settings' items array, add:
{ icon: PlugInIcon, title: $t('sidebar.settings.workflows'), href: "/settings/workflows", routeName: "settings.workflows" },

// Fix HR tasks entry:
// Change: href: "/hr/tasks", routeName: "hr.tasks"
// To: href: "/hr/tasks/dashboard", routeName: "hr.tasks.dashboard"

// Fix CRM routeNames:
// crm.leads.index not crm.leads, crm.opportunities.index not crm.opportunities, etc.
```

Also add lang keys to both `lang/en/sidebar.php` and `lang/bn/sidebar.php` for all new items.

---

## 10. Recommended File Deletions

The following files should be deleted immediately (safe to remove, no active consumers):

### PHP Files to Delete
```
gen-erp-application/test_auth.php
gen-erp-application/app/Http/Controllers/DocumentController.php
gen-erp-application/app/Http/Controllers/DocumentDownloadController.php
gen-erp-application/app/Http/Controllers/Web/Projects/ProjectController.php
gen-erp-application/app/Domain/Product/Services/LegacyProductService.php
gen-erp-application/app/Domain/Customer/Services/LegacyContactService.php
gen-erp-application/app/Http/Requests/Setup/CompanySetupRequest.php
gen-erp-application/routes/documents-routes.php  (after consolidation)
gen-erp-application/database/factories/EmployeeFactory.php     (root duplicate)
gen-erp-application/database/factories/SupplierFactory.php     (root duplicate)
gen-erp-application/database/factories/WarehouseFactory.php    (root duplicate)
gen-erp-application/database/factories/CustomFieldDefinitionFactory.php (root duplicate)
```

### Vue Files to Delete (~30 files)
```
resources/js/Pages/Test.vue
resources/js/Pages/TestSimple.vue
resources/js/Pages/SimpleTest.vue
resources/js/Pages/DebugAuth.vue
resources/js/Pages/Placeholder.vue
resources/js/Pages/Auth/TestLogin.vue
resources/js/Pages/Customers/Create.vue
resources/js/Pages/Customers/Edit.vue
resources/js/Pages/Customers/Index.vue
resources/js/Pages/Customers/Show.vue
resources/js/Pages/Employees/Create.vue
resources/js/Pages/Employees/Index.vue
resources/js/Pages/Invoices/Create.vue
resources/js/Pages/Invoices/Index.vue
resources/js/Pages/Products/Create.vue
resources/js/Pages/Products/Index.vue
resources/js/Pages/PurchaseOrders/Create.vue
resources/js/Pages/PurchaseOrders/Index.vue
resources/js/Pages/SalesOrders/Create.vue
resources/js/Pages/SalesOrders/Index.vue
resources/js/Pages/Suppliers/Create.vue
resources/js/Pages/Suppliers/Index.vue
resources/js/Pages/CreditNotes/Index.vue
resources/js/Pages/Payroll/Index.vue
resources/js/Pages/Attendance/Index.vue
resources/js/Pages/Accounts/Index.vue
resources/js/Pages/Branches/Index.vue  (until web route is added)
resources/js/Pages/Companies/Index.vue
resources/js/Pages/StockMovements/Index.vue  (add web route first, then keep)
resources/js/Pages/Warehouses/Index.vue  (use inventory group)
resources/js/Pages/Users/Index.vue      (use settings/users)
resources/js/Pages/Workflows/Index.vue  (move to settings/workflows)
resources/js/Pages/LeaveRequests/Index.vue
resources/js/Pages/Payslips/Index.vue   (add web route first, then keep)
resources/js/Pages/Payments/Index.vue   (add web route first, then keep)
resources/js/Pages/Expenses/Index.vue   (add web route first, then keep)
resources/js/tailadmin/ (entire directory — 52 files)
resources/js/Components/common/DataTable.vue  (after merging into UI/DataTable.vue)
resources/js/Components/common/Pagination.vue (after merging into UI/Pagination.vue)
```

### Blade Files to Delete
```
resources/views/filament/pages/auth/login.blade.php
resources/views/filament/pages/auth/register.blade.php
resources/views/filament/pages/company-settings.blade.php
resources/views/filament/pages/dashboard.blade.php
resources/views/filament/pages/financial-reports.blade.php
resources/views/filament/pages/modern-create.blade.php
resources/views/filament/pages/modern-edit.blade.php
resources/views/filament/pages/modern-list-page.blade.php
resources/views/filament/pages/modern-list.blade.php
resources/views/filament/pages/modern-view.blade.php
resources/views/filament/pages/team-settings.blade.php
resources/views/filament/widgets/activity-feed-widget.blade.php
resources/views/filament/widgets/company-switcher.blade.php
resources/views/filament/widgets/language-switcher.blade.php
resources/views/filament/logo.blade.php
resources/views/auth/login.blade.php
resources/views/auth/register.blade.php
resources/views/auth/modern-login.blade.php
```

---

## SaaS / Multi-Tenancy Gaps

| Concern | Current Status | Recommended Fix |
|---|---|---|
| **Subscription enforcement** | Models exist, service exists, zero enforcement at runtime | Apply `CheckSubscriptionStatus` middleware and usage counters |
| **Plan limits** | `UsageCounter` model exists, `UsageCounterService` exists, not called | Wire usage tracking to invoice/product/employee creation |
| **Feature flags** | `CheckFeatureFlag` middleware exists, not applied | Apply per-module in `bootstrap/app.php` |
| **Tenant subdomain routing** | `stancl/tenancy` package installed but no tenant-specific subdomain routes found | Verify if single-DB tenancy (company_id) or subdomain tenancy is intended |
| **Plugin system** | `PluginManager` service and `Plugin` model exist but no admin UI | Add plugin management page |
| **Outbound webhooks** | `OutboundWebhookService` and migration exist, no API or UI | Add webhook management to Settings/Integrations |

---

*End of Report — GenERP BD Project Status Audit*  
*Combined word count across 3 parts: ~8,000 words*  
*Total issues found: 42 (8 Critical, 14 High, 14 Medium, 6 Low)*  
*Full-stack complete modules: ~28 | Backend-only: ~8 | Frontend-only stubs: ~4 | Missing from sidebar: ~21 pages*
