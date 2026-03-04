# GenERP BD — Project Status Report (Part 2 of 3)

---

## 3. Frontend Analysis

### 3A — Router / Web Routes vs Vue Pages

The app uses **Inertia.js** (no client-side router). Routes are in `routes/web.php`. Vue files are in `resources/js/Pages/`.

| Route Path | Inertia Component | Vue File Exists? | In Sidebar? | Status |
|---|---|---|---|---|
| `/dashboard` | `Dashboard/Index` | ✅ `Pages/Dashboard/Index.vue` | ✅ | ✅ Full-stack |
| `/notifications` | `Notifications/Index` | ✅ | ✅ | ✅ Full-stack |
| `/sales/dashboard` | `Sales/Dashboard` | ✅ `Pages/Sales/Dashboard.vue` | ⚠️ via Sales group | ✅ |
| `/sales/orders` | `Sales/Orders` | ✅ `Pages/Sales/Orders.vue` | ✅ | ✅ Full-stack |
| `/sales/invoices` | `Sales/Invoices` | ✅ `Pages/Sales/Invoices.vue` | ✅ | ✅ Full-stack |
| `/sales/customers` | `Sales/Customers` | ✅ | ✅ | ✅ Full-stack |
| `/sales/credit-notes` | `Sales/CreditNotes` | ✅ | ✅ | ✅ Full-stack |
| `/sales/returns` | `Sales/Returns` | ✅ | ✅ | ✅ Full-stack |
| `/purchase/dashboard` | `Purchase/Dashboard` | ✅ | ⚠️ via Purchase group | ✅ |
| `/purchase/orders` | `Purchase/Orders` | ✅ | ✅ | ✅ Full-stack |
| `/purchase/receipts` | `Purchase/Receipts` | ✅ | ✅ | ✅ Full-stack |
| `/purchase/suppliers` | `Purchase/Suppliers` | ✅ | ✅ | ✅ Full-stack |
| `/purchase/returns` | `Purchase/Returns` | ✅ | ✅ | ✅ Full-stack |
| `/inventory/dashboard` | `Inventory/Dashboard` | ✅ | ⚠️ via Inventory group | ✅ |
| `/inventory/products` | `Inventory/Products` | ✅ | ✅ | ✅ Full-stack |
| `/inventory/stock` | `Inventory/Stock` | ✅ | ✅ | ✅ Full-stack |
| `/inventory/warehouses` | `Inventory/Warehouses` | ✅ | ✅ | ✅ Full-stack |
| `/inventory/transfers` | `Inventory/Transfers` | ✅ | ✅ | ✅ Full-stack |
| `/inventory/adjustments` | `Inventory/Adjustments` | ✅ | ✅ | ✅ Full-stack |
| `/accounting/dashboard` | `Accounting/Dashboard` | ✅ | ⚠️ via Accounting group | ✅ |
| `/accounting/chart-of-accounts` | `Accounting/ChartOfAccounts` | ✅ | ✅ | ✅ Full-stack |
| `/accounting/journal-entries` | `Accounting/JournalEntries` | ✅ | ✅ | ✅ Full-stack |
| `/accounting/trial-balance` | `Accounting/TrialBalance` | ✅ | ✅ | ✅ Full-stack |
| `/accounting/profit-loss` | `Accounting/ProfitLoss` | ✅ | ✅ | ✅ Full-stack |
| `/accounting/balance-sheet` | `Accounting/BalanceSheet` | ✅ | ✅ | ✅ Full-stack |
| `/accounting/cost-centers` | `Accounting/CostCenters/Index` | ✅ | ✅ sidebar entry present | ✅ Full-stack |
| `/accounting/lock-date` | `Accounting/LockDateManagement` | ✅ | ❌ Not in sidebar | ⚠️ Partial |
| `/hr/dashboard` | `HR/Dashboard` | ✅ | ⚠️ via HR group | ✅ |
| `/hr/employees` | `HR/Employees` | ✅ | ✅ | ✅ Full-stack |
| `/hr/attendance` | `HR/Attendance` | ✅ | ✅ | ✅ Full-stack |
| `/hr/leave` | `HR/Leave` | ✅ | ✅ | ✅ Full-stack |
| `/hr/payroll` | `HR/Payroll` | ✅ | ✅ | ✅ Full-stack |
| `/hr/tasks/dashboard` | `HR/Tasks/Dashboard` | ✅ | ⚠️ sidebar points to `/hr/tasks` (no route!) | 🔴 Route mismatch |
| `/hr/timesheet` | `HR/Timesheet/Index` | ✅ | ✅ | ✅ Full-stack |
| `/hr/capacity` | `HR/Capacity/Index` | ✅ | ❌ Not in sidebar | ⚠️ Partial |
| `/hr/skills` | `HR/Skills/Index` | ✅ | ❌ Not in sidebar | ⚠️ Partial |
| `/hr/availability` | `HR/Availability/Calendar` | ✅ | ❌ Not in sidebar | ⚠️ Partial |
| `/hr/performance` | `HR/Performance/Index` | ✅ | ❌ Not in sidebar | ⚠️ Partial |
| `/reports` | `Reports/Index` | ✅ | ❌ Not in sidebar | ⚠️ Partial |
| `/pos/session` | `POS/Session` | ✅ | ❌ Not in sidebar | ⚠️ Partial |
| `/profile` | `Profile/Index` | ✅ | ❌ Not in sidebar (accessible via user menu) | ⚠️ |
| `/settings/company` | `Settings/Company` | ✅ | ✅ | ✅ Full-stack |
| `/settings/users` | `Settings/Users` | ✅ | ✅ | ✅ Full-stack |
| `/settings/roles` | `Settings/Roles` | ✅ | ✅ | ✅ Full-stack |
| `/settings/integrations` | `Settings/Integrations` | ✅ | ✅ | ✅ Full-stack |
| `/settings/workflows` | `Settings/Workflows` | ✅ | ❌ Not in sidebar | ⚠️ Partial |
| `/projects` | `Projects/Index` | ✅ | ✅ | ✅ Full-stack |
| `/projects/dashboard` | `Projects/Dashboard` | ✅ | ⚠️ via Projects group | ✅ |
| `/projects/create` | `Projects/Create` | ✅ | ❌ (accessed via button) | ✅ |
| `/projects/{id}` | `Projects/Show` | ✅ | ❌ | ✅ |
| `/projects/{id}/board` | `Projects/Board` | ✅ | ❌ | ✅ |
| `/projects/{id}/reports` | `Projects/Reports` | ✅ | ❌ (projects.reports in sidebar) | ⚠️ Route mismatch |
| `/tasks` | `Tasks/Index` | ✅ | ✅ | ✅ Full-stack |
| `/crm/dashboard` | `CRM/Dashboard/Index` | ✅ | ⚠️ via CRM group | ✅ |
| `/crm/leads` | `CRM/Leads/Index` | ✅ | ✅ | ✅ Full-stack |
| `/crm/opportunities` | `CRM/Opportunities/Index` | ✅ | ✅ | ✅ Full-stack |
| `/crm/pipelines` | `CRM/Pipelines/Index` | ✅ | ✅ | ✅ Full-stack |
| `/crm/activities` | `CRM/Activities/Index` | ✅ | ✅ | ✅ Full-stack |
| `/crm/contacts` | `CRM/Contacts/Index` | ✅ | ❌ Not in sidebar | ⚠️ Partial |
| `/cms/dashboard` | `CMS/Dashboard/Index` | ✅ | ⚠️ via CMS group | ✅ |
| `/cms/sites` | `CMS/Sites/Index` | ✅ | ✅ | ✅ Full-stack |
| `/cms/pages` | `CMS/Pages/Index` | ✅ | ✅ | ✅ Full-stack |
| `/cms/blog` | `CMS/Blog/Index` | ✅ | ✅ | ✅ Full-stack |
| `/cms/menus` | `CMS/Menus/Index` | ✅ | ✅ | ✅ Full-stack |
| `/cms/contacts` | `CMS/Contacts/Index` | ✅ | ❌ Not in sidebar | ⚠️ Partial |
| `/cms/reviews` | `CMS/Reviews/Index` | ✅ | ❌ Not in sidebar | ⚠️ Partial |
| `/cms/wishlist` | `CMS/Wishlist/Index` | ✅ | ❌ Not in sidebar | ⚠️ Partial |
| `/cms/seo` | `CMS/SEO/Index` | ✅ | ❌ Not in sidebar | ⚠️ Partial |
| `/logistics/dashboard` | `Logistics/Dashboard/Index` | ✅ | ❌ No Logistics group in sidebar! | 🔴 Missing |
| `/logistics/shipments` | `Logistics/Shipments/Index` | ✅ | ❌ | 🔴 Missing |
| `/logistics/tracking` | `Logistics/Tracking/Index` | ✅ | ❌ | 🔴 Missing |
| `/logistics/returns` | `Logistics/Returns/Index` | ✅ | ❌ | 🔴 Missing |
| `/logistics/cod` | `Logistics/COD/Index` | ✅ | ❌ | 🔴 Missing |
| `/logistics/carriers` | `Logistics/Carriers/Index` | ✅ | ❌ | 🔴 Missing |
| `/documents/dashboard` | `Documents/Dashboard` | ✅ | ⚠️ via Documents group | ✅ |
| `/documents` | `Documents/Index` | ✅ | ✅ | ✅ Full-stack |
| `/documents/folders` | `Documents/Folders` | ✅ | ✅ | ✅ Full-stack |
| `/documents/recent` | `Documents/Recent` | ✅ | ✅ | ✅ Full-stack |
| `/documents/forms` | `Documents/Forms/Index` | ✅ | ✅ | ✅ Full-stack |
| `/documents/custom-fields` | `Documents/CustomFields/Index` | ✅ | ✅ | ✅ Full-stack |

**Vue pages that exist but have NO web route (unreachable):**
- `Pages/Accounts/Index.vue` — no `/accounts` route in web.php (accounting routes cover sub-pages)
- `Pages/Approvals/Index.vue` — no `/approvals` route in web.php
- `Pages/Attendance/Index.vue` — overlaps with `Pages/HR/Attendance.vue`
- `Pages/Branches/Index.vue` — no `/branches` route in web.php
- `Pages/Companies/Index.vue` — no `/companies` Vue route
- `Pages/CreditNotes/Index.vue` — duplicate of `Pages/Sales/CreditNotes.vue`
- `Pages/Customers/Create,Edit,Index,Show.vue` — duplicate of `Pages/Sales/Customers.vue`
- `Pages/Employees/Create,Index.vue` — duplicate of `Pages/HR/Employees.vue`
- `Pages/Expenses/Index.vue` — no `/expenses` web route
- `Pages/Invoices/Create,Index.vue` — duplicate of `Pages/Sales/Invoices.vue`
- `Pages/LeaveRequests/Index.vue` — no `/leave-requests` web route (is in `/hr/leave`)
- `Pages/Payroll/Index.vue` — duplicate of `Pages/HR/Payroll.vue`
- `Pages/Payslips/Index.vue` — no `/payslips` web route
- `Pages/Payments/Index.vue` — no `/payments` web route
- `Pages/Products/Create,Index.vue` — duplicate of `Pages/Inventory/Products.vue`
- `Pages/PurchaseOrders/Create,Index.vue` — duplicate of `Pages/Purchase/Orders.vue`
- `Pages/SalesOrders/Create,Index.vue` — duplicate of `Pages/Sales/Orders.vue`
- `Pages/StockMovements/Index.vue` — no web route
- `Pages/Suppliers/Create,Index.vue` — duplicate of `Pages/Purchase/Suppliers.vue`
- `Pages/Users/Index.vue` — no `/users` web route (is in `/settings/users`)
- `Pages/Warehouses/Index.vue` — no `/warehouses` web route
- `Pages/Workflows/Index.vue` — no `/workflows` web route
- `Pages/Test.vue`, `Pages/TestSimple.vue`, `Pages/SimpleTest.vue`, `Pages/DebugAuth.vue` — test pages, should be removed
- `Pages/Placeholder.vue` — stub, should be removed
- `Pages/Auth/TestLogin.vue` — test page
- `Pages/Auth/SelectCompany.vue` — rendered by CompanyAccessController but no named Inertia route

### 3B — Page-to-API Mapping (Key Pages)

| Vue Page | API Calls | Backend Exists? |
|---|---|---|
| `Dashboard/Index.vue` | `GET /api/v1/dashboard` | ✅ |
| `Sales/Orders.vue` | `GET /api/v1/sales-orders` | ✅ |
| `Sales/Invoices.vue` | `GET /api/v1/invoices` | ✅ |
| `Sales/Customers.vue` | `GET /api/v1/customers` | ✅ |
| `CRM/Leads/Index.vue` | `GET /api/v1/crm/leads` | ✅ |
| `CRM/Pipelines/Index.vue` + KanbanBoard | `GET /api/v1/crm/pipelines` + `GET /api/v1/crm/opportunities` | ✅ |
| `Logistics/Shipments/Index.vue` | `GET /api/v1/logistics/shipments` | ✅ |
| `Logistics/COD/Index.vue` | `GET /api/v1/logistics/carriers/{id}/cod/summary` etc. | ✅ |
| `CMS/Sites/Index.vue` | `GET /api/v1/cms/sites` | ✅ |
| `CMS/PageBuilder/Index.vue` | `GET /api/v1/cms/page-builder/pages/{id}` | ✅ |
| `Projects/Board.vue` | `GET /api/v1/projects/{id}/board` (not in routes!) | ⚠️ No board endpoint |
| `HR/Capacity/Index.vue` | `GET /api/v1/hr/capacity/overview` | ✅ |
| `Accounting/LockDateManagement.vue` | `GET/PUT /api/v1/companies/{id}/lock-date` | ✅ |
| `Accounting/CostCenters/Index.vue` | `GET /api/v1/cost-centers` | ✅ |
| `POS/Session.vue` | No POS API endpoints exist! | 🔴 No backend |
| `Reports/Index.vue` | `GET /api/v1/reports` | ✅ (generic) |
| `Settings/Integrations.vue` | Integration endpoints? | ⚠️ No integration CRUD API exposed |
| `Auth/CompanySetup` | `POST /api/v1/auth/setup-company` | ✅ |

### 3C — Component Inventory

**Total Components:** ~156 in `resources/js/Components/` + ~52 in `resources/js/tailadmin/`

**Key component groups and their usage:**

| Group | Component Files | Usage | Notes |
|---|---|---|---|
| `Components/Layout/` | AdminLayout, AppHeader, AppSidebar, AppLayout, AppLayoutMain, Sidebar, Topbar, NavItem, CompanySwitcher, LanguageSwitcher, ThemeProvider, SidebarProvider, SidebarWidget, Backdrop, FullScreenLayout + 4 header sub-components | Active layout | `Sidebar.vue` = old, `AppSidebar.vue` = active |
| `Components/UI/` | Alert, Avatar, Badge, Button, Card, ColorPicker, DataTable, FlashMessage, Icon, ImageUpload, Modal, Pagination, RichTextEditor, StatCard, YouTubeEmbed + image sub-components | Used across pages | |
| `Components/common/` | CommonGridShape, ComponentCard, CountDown, DataTable, DropdownMenu, FilterPanel, LoadingOverlay, NotificationSystem, PageBreadcrumb, Pagination, ThemeToggler, v-click-outside | TailAdmin commons | Duplicates UI/ components |
| `Components/Charts/` | AreaChart, BarChart, BarChart/BarChartOne, DonutChart, LineChart, LineChart/LineChartOne, PieChart | Data visualisation | |
| `Components/CMS/Sections/` | 8 section type renderers | CMS page builder | ✅ Active |
| `Components/CRM/` | ActivityTimeline, AddActivityModal, AdvancedLeadFilter, EditActivityModal, LeadConversionWorkflow, LeadScoringWidget, PipelineBoard, SaveFilterModal, ScoreHistoryModal | CRM features | ✅ Active |
| `Components/Projects/` | 20+ components incl. KanbanBoard, GanttChart, TaskDetailModal, TimeTracker | Project management | ✅ Active |
| `Components/HR/` | AvailabilityModal, CapacityModal, PerformanceReviewModal, SkillModal, TimeLogModal | HR enhancement | ✅ Active |
| `Components/Documents/` | CreateFolderModal, DocumentEditor, DocumentUpload, DocumentViewer, EditDocumentModal, EditFolderModal, FolderTreeItem | Document management | ✅ Active |
| `Components/Forms/` | 20+ form builder components incl. FormBuilder, FieldConfigPanel, CustomFieldManager, CustomFieldModal | Dynamic forms | ✅ Active |
| `Components/Integration/` | CrossDomainWidget, QuickActionsWidget, QuickStatsWidget, RecentActivitiesWidget, TrackingModal | Integration widgets | ⚠️ May be unused |
| `Components/Notifications/` | NotificationBell | Bell icon for header | ✅ |
| `Components/Logistics/` | CarrierSettings, TrackingMap | Logistics UI | ✅ |
| `Components/Home/` | CompaniesMarquee, FeaturesSection, HeroSection, Logo, ModulesSection, StatsSection | Public landing page | ✅ |
| `Components/Tasks/` | LogTimeModal | Tasks | ✅ |
| `Components/ecommerce/` | CustomerDemographic, EcommerceMetrics, MonthlySale, MonthlyTarget, RecentOrders, StatisticsChart | TailAdmin ecommerce widgets | ⚠️ Used on dashboard |
| `Components/profile/` | AddressCard, Modal, PersonalInfoCard, ProfileCard | Profile page | ✅ |
| `Components/tables/` | BasicTableOne | TailAdmin table | ⚠️ May be unused |
| `Components/docs/` | DocsApp, NavGroup, SearchModal | Internal docs | ✅ |
| `Components/ConfirmationModal.vue` | Generic confirm dialog | ✅ |
| `Components/Modal.vue` | Generic modal | ✅ |

**Orphaned/Potentially Unused Components:**
- `Components/tables/basic-tables/BasicTableOne.vue` — TailAdmin demo, likely not used in ERP pages
- `Components/Integration/CrossDomainWidget.vue`, `QuickActionsWidget.vue`, `QuickStatsWidget.vue`, `RecentActivitiesWidget.vue` — Check if imported anywhere
- All `resources/js/tailadmin/` components — duplicated by `Components/` equivalents

### 3D — Sidebar Analysis

**Current sidebar groups and items (from `Components/Layout/AppSidebar.vue`):**

| Group Key | Items in Sidebar | Section |
|---|---|---|
| `main` | Dashboard | Main |
| `notifications` | All Notifications | — |
| `documents` | Documents, Folders, Recent, Forms, Custom Fields | Documents |
| `sales` | Orders, Invoices, Customers, Credit Notes, Returns | Sales |
| `purchase` | Orders, Receipts, Suppliers, Returns | Purchase |
| `inventory` | Products, Stock, Warehouses, Transfers, Adjustments | Inventory |
| `accounting` | Chart of Accounts, Journal Entries, Cost Centers, Trial Balance, P&L, Balance Sheet | Accounting |
| `hr` | Employees, Attendance, Leave, Payroll, Tasks, Timesheet | HR |
| `projects` | Projects, Tasks, Reports | Projects |
| `crm` | Leads, Opportunities, Pipelines, Activities | CRM |
| `cms` | Sites, Pages, Blog, Menus | CMS |
| `settings` | Company, Users, Roles, Integrations | Settings |

**Count: 12 groups, ~48 sidebar items**

**Missing from sidebar: 17 pages/groups**
1. Logistics (entire group — Dashboard, Shipments, Tracking, Returns, COD, Carriers)
2. CMS → Contacts
3. CMS → Reviews
4. CMS → Wishlist
5. CMS → SEO
6. HR → Capacity
7. HR → Skills
8. HR → Availability
9. HR → Performance
10. Accounting → Lock Date Management
11. Reports (top-level page)
12. POS (point of sale session)
13. Settings → Workflows
14. CRM → Contacts
15. Accounting → Payslips (accessible but not linked)
16. Accounting → Payments (accessible but not linked)
17. HR Tasks Dashboard (href points to wrong route)

### 3E — Pinia Stores

| Store File | State | API Calls | Used in Components? |
|---|---|---|---|
| `Stores/pageBuilderStore.js` | page builder draft state | CMS page-builder endpoints | ✅ CMS/PageBuilder/Index.vue |

**No other Pinia stores found.** Most data is managed via composables or direct `axios` calls within components. This is by design with Inertia (server-side initial data via `$page.props`).

### 3F — Composables

| Composable | Purpose | Used By |
|---|---|---|
| `useApi.js` | Axios wrapper with company context | Most page components |
| `useAuth.js` | Auth state helpers | Auth-related pages |
| `useCompany.js` | Active company helpers | Layout, Settings |
| `useErrorHandler.js` | Centralised error display | All API-calling pages |
| `useLoading.js` | Loading state management | All API-calling pages |
| `usePagination.js` | Pagination helpers | Table-based pages |
| `useResponsive.js` | Breakpoint detection | Layout components |
| `useSearch.js` | Search debouncing | Table pages |
| `useSidebar.ts` | Sidebar expand/collapse state | AppSidebar, AppHeader |
| `useToast.js` | Toast notification display | All pages |
| `useTranslations.js` | i18n helper (`$t()`) | All pages |
| `useWebSocket.js` | Laravel Echo / Reverb connection | Notifications |

All composables appear to be actively used.

### 3G — API Service Layer

| File | Purpose | All Methods Called? |
|---|---|---|
| `Services/api.js` | Axios instance with base URL and interceptors | ✅ used via useApi.js |
| `Services/auth.js` | Login/logout/register helpers | ✅ used by Auth pages |

---

## 4. Feature Coverage Matrix

| Module | DB Migration | Model | Controller | API Route | API Resource | Vue Page | In Sidebar | Status |
|---|---|---|---|---|---|---|---|---|
| **Auth / Users** | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ Complete |
| **Companies** | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ Complete |
| **Roles / Permissions** | ✅ (spatie) | ✅ | ✅ | ✅ | ⚠️ | ✅ Settings/Roles | ✅ | ⚠️ Partial (no Role CRUD API resource) |
| **Products** | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ via Inventory | ✅ Complete |
| **Product Categories** | ✅ | ✅ | ✅ | ✅ | ✅ | ❌ No dedicated page | ❌ | ⚠️ Backend-only |
| **Inventory / Stock** | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ Complete |
| **Warehouses** | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ Complete |
| **Customers** | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ Complete |
| **Suppliers** | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ Complete |
| **Sales Orders** | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ Complete |
| **Invoicing** | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ Complete |
| **Invoice PDF / Mushak 6.3** | ✅ | — | ✅ service | ❌ no API endpoint | — | ❌ No UI button | ❌ | 🔴 Backend-only |
| **Purchase Orders** | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ Complete |
| **Goods Receipts** | ✅ | ✅ | ⚠️ | ⚠️ no dedicated route | ✅ | ✅ via receipts | ✅ | ⚠️ Partial |
| **Credit Notes** | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ Complete |
| **Payments** | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ❌ | ⚠️ No sidebar link |
| **Expenses** | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ❌ | ⚠️ No sidebar link |
| **Accounting / CoA** | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ Complete |
| **Journal Entries** | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ Complete |
| **Cost Centers** | ✅ | ✅ | ✅ | ✅ | ⚠️ | ✅ | ✅ | ✅ Complete |
| **Lock Date** | ✅ | ✅ field | ✅ | ✅ | — | ✅ | ❌ | ⚠️ No sidebar link |
| **Trial Balance / Reports** | — | — | ✅ | ✅ | — | ✅ | ✅ | ✅ Complete |
| **VAT Reports (Mushak)** | — | — | ✅ services | ❌ no API endpoints | — | ❌ | ❌ | 🔴 Backend-only |
| **HR / Employees** | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ Complete |
| **Attendance** | ✅ | ✅ | ✅ | ✅ | ⚠️ | ✅ | ✅ | ✅ Complete |
| **Leave Requests** | ✅ | ✅ | ✅ | ✅ | ⚠️ | ✅ | ✅ | ✅ Complete |
| **Payroll / Payslips** | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅/❌ | ⚠️ Payslips page not in sidebar |
| **HR Tasks** | ✅ | ✅ | ✅ | ✅ | — | ✅ | ⚠️ wrong route | ⚠️ Sidebar route broken |
| **HR Timesheet** | ✅ | ✅ | ✅ | ✅ | — | ✅ | ✅ | ✅ Complete |
| **HR Capacity** | ✅ | ✅ | ✅ | ✅ | — | ✅ | ❌ | ⚠️ No sidebar link |
| **HR Skills** | ✅ | ✅ | ❌ no controller | ❌ | — | ✅ page | ❌ | 🔴 Frontend only |
| **HR Availability** | ✅ | ✅ | ❌ no controller | ❌ | — | ✅ page | ❌ | 🔴 Frontend only |
| **HR Performance** | ✅ | ✅ | ❌ no controller | ❌ | — | ✅ page | ❌ | 🔴 Frontend only |
| **Projects** | ✅ | ✅ | ✅ | ✅ | — | ✅ | ✅ | ✅ Complete |
| **Project Board/Kanban** | ✅ | ✅ | ✅ | ⚠️ no /board endpoint | — | ✅ | ❌ | ⚠️ No board API endpoint |
| **Tasks** | ✅ | ✅ | ✅ | ✅ | — | ✅ | ✅ | ✅ Complete |
| **CRM Leads** | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ Complete |
| **CRM Opportunities** | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ Complete |
| **CRM Pipelines** | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ Complete |
| **CRM Activities** | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ Complete |
| **CRM Contacts** | ✅ | ✅ | ⚠️ | ✅ cms/contacts | ✅ | ✅ | ❌ | ⚠️ No sidebar link |
| **CMS Sites** | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ Complete |
| **CMS Pages / Builder** | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ Complete |
| **CMS Blog** | ✅ | ✅ | ⚠️ | ✅ | ✅ | ✅ | ✅ | ✅ Complete |
| **CMS Menus** | ✅ | ✅ | ⚠️ | ✅ route missing | ✅ | ✅ `Menus/Index.vue`, `Menus/Builder.vue` | ✅ | ⚠️ No menus API endpoint |
| **CMS Reviews** | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ❌ | ⚠️ No sidebar link |
| **CMS Wishlist** | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ❌ | ⚠️ No sidebar link |
| **CMS SEO** | ✅ | — | ✅ | ✅ | — | ✅ | ❌ | ⚠️ No sidebar link |
| **CMS Contacts (form submissions)** | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ❌ | ⚠️ No sidebar link |
| **Logistics / Shipments** | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ❌ | 🔴 Entire group missing from sidebar |
| **Logistics / Tracking** | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ❌ | 🔴 Missing from sidebar |
| **Logistics / Returns** | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ❌ | 🔴 Missing from sidebar |
| **Logistics / COD** | ✅ | — | ✅ | ✅ | — | ✅ | ❌ | 🔴 Missing from sidebar |
| **Logistics / Carriers** | ✅ | ✅ | ✅ | ✅ | — | ✅ | ❌ | 🔴 Missing from sidebar |
| **Documents** | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ Complete |
| **Forms / Form Builder** | ✅ | ✅ | ✅ | ✅ | — | ✅ | ✅ | ✅ Complete |
| **Custom Fields** | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ Complete |
| **Notifications** | ✅ | ✅ | ✅ ×2 | ✅ ×2 | ✅ | ✅ | ✅ | ⚠️ Duplicate notification system |
| **Workflows / Approvals** | ✅ | ✅ | ✅ | ✅ | ⚠️ | ✅ | ❌ Settings/Workflows missing | ⚠️ Partial |
| **POS** | ✅ | ✅ | ❌ no controller | ❌ no API | — | ✅ POS/Session.vue | ❌ | 🔴 Frontend stub only |
| **Subscriptions / Plans** | ✅ | ✅ | ❌ no controller | ❌ no API | — | ❌ | ❌ | 🔴 Backend-only |
| **Plugins** | ✅ | ✅ | ❌ | ❌ | — | ❌ | ❌ | 🔴 Backend-only |
| **Alert Rules** | ✅ | ✅ | ❌ | ❌ | — | ❌ | ❌ | 🔴 Backend-only |
| **Event Sourcing** | ✅ event_store | ✅ | — | — | — | — | — | ⚠️ Infrastructure only |
| **CQRS Bus** | — | — | ✅ CommandBus, QueryBus | — | — | — | — | ⚠️ Infrastructure only |
| **Invitations** | ✅ | ✅ | ✅ | ✅ | ✅ | ❌ no management page | ❌ | ⚠️ Backend complete, no UI |
| **Import Jobs** | ✅ | ✅ | ✅ | ✅ | ✅ | ❌ no UI | ❌ | ⚠️ Backend complete, no UI |
| **Integrations** | ✅ | ✅ | ✅ services | ❌ no CRUD API | — | ✅ Settings/Integrations | ✅ | ⚠️ No REST API, config-only |

---

## 5. Dead Code Register

### 5.1 Orphaned PHP Files

| File | Reason Dead |
|---|---|
| `app/Http/Controllers/DocumentController.php` | Superseded by `Controllers/Documents/DocumentController.php` |
| `app/Http/Controllers/DocumentDownloadController.php` | Superseded by document download routes in documents group |
| `app/Http/Controllers/Web/Projects/ProjectController.php` | Duplicate — web.php uses the API controller directly |
| `app/Http/Controllers/InvitationController.php` | Conflicts with API version; web invitation routes unclear |
| `app/Domain/Product/Services/LegacyProductService.php` | Superseded by ProductService |
| `app/Domain/Customer/Services/LegacyContactService.php` | Superseded by ContactService |
| `app/Http/Middleware/EnforceModuleAccess.php` | Defined but not applied in bootstrap/app.php or routes |
| `app/Http/Middleware/CheckFeatureFlag.php` | Defined but not applied |
| `app/Http/Middleware/CheckSubscriptionStatus.php` | Defined but not applied |
| `app/Http/Middleware/EnsureActiveBranch.php` | Defined but not applied |
| `app/Http/Middleware/ShareInertiaData.php` | Possibly redundant with HandleInertiaRequests |
| `routes/documents-routes.php` | Unclear if loaded — may conflict with `routes/documents.php` |
| `test_auth.php` (project root) | Debug script, should never be in production |

### 5.2 Orphaned Vue Files

| File | Reason Dead |
|---|---|
| `Pages/Test.vue`, `Pages/TestSimple.vue`, `Pages/SimpleTest.vue` | Test pages for dev debugging |
| `Pages/DebugAuth.vue` | Auth debugging page |
| `Pages/Auth/TestLogin.vue` | Test login page |
| `Pages/Placeholder.vue` | Empty stub |
| `Pages/Customers/Create.vue`, `Pages/Customers/Edit.vue`, `Pages/Customers/Index.vue`, `Pages/Customers/Show.vue` | Duplicated by `Pages/Sales/Customers.vue` |
| `Pages/Employees/Create.vue`, `Pages/Employees/Index.vue` | Duplicated by `Pages/HR/Employees.vue` |
| `Pages/Invoices/Create.vue`, `Pages/Invoices/Index.vue` | Duplicated by `Pages/Sales/Invoices.vue` |
| `Pages/Products/Create.vue`, `Pages/Products/Index.vue` | Duplicated by `Pages/Inventory/Products.vue` |
| `Pages/PurchaseOrders/Create.vue`, `Pages/PurchaseOrders/Index.vue` | Duplicated by `Pages/Purchase/Orders.vue` |
| `Pages/SalesOrders/Create.vue`, `Pages/SalesOrders/Index.vue` | Duplicated by `Pages/Sales/Orders.vue` |
| `Pages/Suppliers/Create.vue`, `Pages/Suppliers/Index.vue` | Duplicated by `Pages/Purchase/Suppliers.vue` |
| `Pages/CreditNotes/Index.vue` | Duplicated by `Pages/Sales/CreditNotes.vue` |
| `Pages/Payroll/Index.vue` | Duplicated by `Pages/HR/Payroll.vue` |
| `Pages/Attendance/Index.vue` | Duplicated by `Pages/HR/Attendance.vue` |
| `Pages/Accounts/Index.vue` | No route; accounting uses sub-pages |
| `Pages/Branches/Index.vue` | No web route |
| `Pages/Companies/Index.vue` | No web route |
| `Pages/StockMovements/Index.vue` | No web route (stock is in inventory section) |
| `Pages/Users/Index.vue` | No standalone web route (in settings) |
| `Pages/Warehouses/Index.vue` | No standalone web route (in inventory) |
| `Pages/Workflows/Index.vue` | No web route (in settings) |
| `Pages/LeaveRequests/Index.vue` | No standalone web route (in hr/leave) |
| `Pages/Payslips/Index.vue` | No web route (payslips in hr section) |
| `Pages/Payments/Index.vue` | No web route |
| `Pages/Expenses/Index.vue` | No web route |
| All files in `resources/js/tailadmin/` (52 files) | Duplicated by `Components/` equivalents |

### 5.3 Orphaned Blade Views

| File | Reason Dead |
|---|---|
| `resources/views/filament/pages/auth/login.blade.php` | Filament was removed; unused |
| `resources/views/filament/pages/auth/register.blade.php` | Filament was removed |
| `resources/views/filament/pages/company-settings.blade.php` | Filament removed |
| `resources/views/filament/pages/dashboard.blade.php` | Filament removed |
| `resources/views/filament/pages/financial-reports.blade.php` | Filament removed |
| `resources/views/filament/pages/modern-*.blade.php` (5 files) | Filament removed |
| `resources/views/filament/pages/team-settings.blade.php` | Filament removed |
| `resources/views/filament/widgets/*.blade.php` (3 files) | Filament removed |
| `resources/views/filament/logo.blade.php` | Filament removed |
| `resources/views/auth/login.blade.php` | Superseded by Inertia `Auth/Signin.vue` |
| `resources/views/auth/register.blade.php` | Superseded by Inertia `Auth/Signup.vue` |
| `resources/views/auth/modern-login.blade.php` | Duplicate login view |

---

## 6. Duplicate Implementation Register

| Feature | File A (Keep) | File B (Delete) | Unique Logic to Migrate |
|---|---|---|---|
| **Layout sidebar** | `Components/Layout/AppSidebar.vue` | `Components/Layout/Sidebar.vue` + all `tailadmin/layout/AppSidebar.vue` | Sidebar.vue may have different nav items — verify |
| **App layout** | `Components/Layout/AdminLayout.vue` | `tailadmin/layout/AdminLayout.vue` | None expected |
| **UI Alert** | `Components/UI/Alert.vue` | `tailadmin/ui/Alert.vue` | None |
| **UI Avatar** | `Components/UI/Avatar.vue` | `tailadmin/ui/Avatar.vue` | None |
| **UI Badge** | `Components/UI/Badge.vue` | `tailadmin/ui/Badge.vue` | None |
| **UI Button** | `Components/UI/Button.vue` | `tailadmin/ui/Button.vue` | None |
| **UI Modal** | `Components/UI/Modal.vue` | `tailadmin/ui/Modal.vue` + `tailadmin/profile/Modal.vue` | None |
| **DataTable** | `Components/UI/DataTable.vue` | `Components/common/DataTable.vue` | Check which has more features |
| **Pagination** | `Components/UI/Pagination.vue` | `Components/common/Pagination.vue` | None |
| **BarChart** | `Components/Charts/BarChart/BarChartOne.vue` | `tailadmin/charts/BarChart/BarChartOne.vue` | None |
| **LineChart** | `Components/Charts/LineChart/LineChartOne.vue` | `tailadmin/charts/LineChart/LineChartOne.vue` | None |
| **ThemeToggler** | `Components/common/ThemeToggler.vue` | `tailadmin/common/ThemeToggler.vue` | None |
| **PageBreadcrumb** | `Components/common/PageBreadcrumb.vue` | `tailadmin/common/PageBreadcrumb.vue` | None |
| **ProductService** | `Domain/Product/Services/ProductService.php` | `Domain/Product/Services/LegacyProductService.php` | Verify all methods migrated |
| **ContactService** | `Domain/Customer/Services/ContactService.php` | `Domain/Contact/Services/ContactService.php` + `LegacyContactService.php` | Verify contact-specific logic |
| **PaymentService** | `Domain/Payment/Services/PaymentService.php` | `Domain/Customer/Services/PaymentService.php` | May serve different use cases |
| **PluginManager** | `Domain/Plugin/Services/PluginManager.php` | `Services/PluginManager.php` | Verify app-level calls |
| **NotificationService** | `Domain/Notification/Services/NotificationDispatchService.php` | `Services/NotificationService.php` | Verify notification triggers |
| **Notification API** | `Domain/Notification/Http/Controllers/NotificationController.php` | `Http/Controllers/Api/V1/NotificationController.php` | Legacy vs new — check if both used |
| **Document download** | `Controllers/Documents/DocumentController.php` | `Controllers/DocumentController.php` + `Controllers/DocumentDownloadController.php` | Download logic |
| **CompanySetupRequest** | `Requests/Auth/CompanySetupRequest.php` | `Requests/Setup/CompanySetupRequest.php` | None |
| **Factory duplicates** | `database/factories/Domain/HR/Models/EmployeeFactory.php` | `database/factories/EmployeeFactory.php` | None |
| **Sales page duplicates** | `Pages/Sales/Orders.vue` | `Pages/SalesOrders/Index.vue` + `Pages/SalesOrders/Create.vue` | Create form logic |
| **Customers page duplicates** | `Pages/Sales/Customers.vue` | `Pages/Customers/Index.vue`, `Create.vue`, `Edit.vue`, `Show.vue` | CRUD sub-pages need integration |
| **CRM PipelineBoard** | `Components/CRM/PipelineBoard.vue` | `Pages/CRM/Pipelines/KanbanBoard.vue` | Check overlap |
| **document-folders route** | One registration | Duplicate in `api.php` lines 139 AND 390 | Remove duplicate |

---

*[See Part 3 for Issue Catalogue, Completion Roadmap, Sidebar Plan, and Recommended File Deletions]*
