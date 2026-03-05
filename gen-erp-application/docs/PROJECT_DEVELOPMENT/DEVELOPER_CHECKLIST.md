# Developer Checklist — GenERP BD Completion

**Last Updated:** 2026-03-05  
**Total Tasks:** 55  
**Estimated Effort:** 16-18 days

---

## 🚀 Phase 1: Quick Wins (3 days)

### Sidebar Links (0.5 days)
- [ ] Add Accounting → Lock Date Management to sidebar
- [ ] Add CRM → Contacts to sidebar
- [ ] Add CMS → Reviews to sidebar
- [ ] Add CMS → Wishlist to sidebar
- [ ] Add CMS → SEO to sidebar
- [ ] Add CMS → Contacts to sidebar
- [ ] Add HR → Capacity to sidebar
- [ ] Add HR → Skills to sidebar
- [ ] Add HR → Availability to sidebar
- [ ] Add HR → Performance to sidebar
- [ ] Add HR → Payslips to sidebar
- [ ] Add Payments to sidebar (Accounting or top-level)
- [ ] Add Expenses to sidebar (Accounting or top-level)
- [ ] Add Reports to sidebar (top-level)
- [ ] Add POS to sidebar (top-level)
- [ ] Add Settings → Workflows to sidebar
- [ ] Add Approvals to sidebar (top-level or Settings)

**File:** `resources/js/Components/Layout/AppSidebar.vue`

### Fix Broken Links (0.5 days)
- [ ] Fix HR Tasks link (change `/hr/tasks` to `/hr/tasks/dashboard`)
- [ ] Fix Projects Reports link (remove static link or make dynamic)
- [ ] Verify all CRM routeNames have `.index` suffix

**Files:** `resources/js/Components/Layout/AppSidebar.vue`

### Security Cleanup (0.5 days)
- [ ] Remove `/test` route from web.php
- [ ] Remove `/test-no-middleware` route
- [ ] Remove `/test-no-auth` route
- [ ] Remove `/test-auth-status` route
- [ ] Remove `/test-sanctum-auth` route
- [ ] Remove `/debug-session` route
- [ ] Delete `Pages/Test.vue`
- [ ] Delete `Pages/TestSimple.vue`
- [ ] Delete `Pages/SimpleTest.vue`
- [ ] Delete `Pages/DebugAuth.vue`
- [ ] Delete `Pages/Auth/TestLogin.vue`

**Files:** `routes/web.php`, `resources/js/Pages/`

### Code Cleanup (1.5 days)
- [ ] Delete entire `resources/js/tailadmin/` directory (52 files)
- [ ] Delete 25+ orphaned Vue pages (see DOMAIN_BY_DOMAIN_AUDIT.md §5.2)
- [ ] Delete orphaned Blade files (Filament views, legacy auth)
- [ ] Consolidate duplicate factories (5 pairs)
- [ ] Fix duplicate `/language/switch` route

**Files:** Multiple directories

---

## 🎨 Phase 2: CMS Completion (3 days)

### MenuController (0.5 days)
- [ ] Create `app/Http/Controllers/Api/V1/CMS/MenuController.php`
- [ ] Add CRUD methods (index, store, show, update, destroy)
- [ ] Add menu item management methods
- [ ] Create MenuResource
- [ ] Add routes: `Route::apiResource('cms/menus', MenuController::class)`
- [ ] Test with `CMS/Menus/Index.vue`

### ReviewController (0.5 days)
- [ ] Create `app/Http/Controllers/Api/V1/CMS/ReviewController.php`
- [ ] Add CRUD methods
- [ ] Add approve/reject methods
- [ ] Create ReviewResource
- [ ] Add routes: `Route::apiResource('cms/reviews', ReviewController::class)`
- [ ] Test with `CMS/Reviews/Index.vue`

### WishlistController (0.5 days)
- [ ] Create `app/Http/Controllers/Api/V1/CMS/WishlistController.php`
- [ ] Add CRUD methods
- [ ] Add add/remove item methods
- [ ] Create WishlistResource
- [ ] Add routes: `Route::apiResource('cms/wishlists', WishlistController::class)`
- [ ] Test with `CMS/Wishlist/Index.vue`

### SEOController (0.5 days)
- [ ] Create `app/Http/Controllers/Api/V1/CMS/SEOController.php`
- [ ] Add meta management methods
- [ ] Add sitemap generation
- [ ] Add robots.txt management
- [ ] Add routes: `Route::prefix('cms/seo')->group(...)`
- [ ] Test with `CMS/SEO/Index.vue`

### ContactController (0.5 days)
- [ ] Create `app/Http/Controllers/Api/V1/CMS/ContactController.php`
- [ ] Add contact submission listing
- [ ] Add mark as read/replied
- [ ] Create ContactSubmissionResource
- [ ] Add routes: `Route::apiResource('cms/contacts', ContactController::class)`
- [ ] Test with `CMS/Contacts/Index.vue`

### Testing (0.5 days)
- [ ] Test all 5 CMS pages load without errors
- [ ] Test CRUD operations for each
- [ ] Write feature tests for new controllers

---

## 👥 Phase 3: HR Completion (2 days)

### EmployeeSkillController (0.5 days)
- [ ] Create `app/Http/Controllers/Api/V1/HR/EmployeeSkillController.php`
- [ ] Add CRUD methods
- [ ] Add skill assignment to employees
- [ ] Create EmployeeSkillResource
- [ ] Add routes: `Route::apiResource('hr/employee-skills', EmployeeSkillController::class)`
- [ ] Test with `HR/Skills/Index.vue`

### EmployeeAvailabilityController (0.5 days)
- [ ] Create `app/Http/Controllers/Api/V1/HR/EmployeeAvailabilityController.php`
- [ ] Add CRUD methods
- [ ] Add calendar view endpoint
- [ ] Create EmployeeAvailabilityResource
- [ ] Add routes: `Route::apiResource('hr/employee-availability', EmployeeAvailabilityController::class)`
- [ ] Test with `HR/Availability/Calendar.vue`

### PerformanceReviewController (0.5 days)
- [ ] Create `app/Http/Controllers/Api/V1/HR/PerformanceReviewController.php`
- [ ] Add CRUD methods
- [ ] Add submit/approve workflow
- [ ] Create PerformanceReviewResource
- [ ] Add routes: `Route::apiResource('hr/performance-reviews', PerformanceReviewController::class)`
- [ ] Test with `HR/Performance/Index.vue`

### Testing (0.5 days)
- [ ] Test all 3 HR pages load without errors
- [ ] Test CRUD operations for each
- [ ] Write feature tests for new controllers

---

## 🛒 Phase 4: POS System (2 days)

### POSController (1 day)
- [ ] Create `app/Http/Controllers/Api/V1/POSController.php`
- [ ] Add `POST /api/v1/pos/sessions` (open session)
- [ ] Add `POST /api/v1/pos/sessions/{id}/close` (close session)
- [ ] Add `GET /api/v1/pos/products/search` (product search)
- [ ] Add `POST /api/v1/pos/sales` (create sale)
- [ ] Add `GET /api/v1/pos/sales/{id}/receipt` (print receipt)
- [ ] Create POSSessionResource, POSSaleResource
- [ ] Add all routes to `routes/api.php`

### Frontend Integration (0.5 days)
- [ ] Wire `POS/Session.vue` to new API endpoints
- [ ] Test session open/close
- [ ] Test product search
- [ ] Test sale creation
- [ ] Test receipt generation

### Testing (0.5 days)
- [ ] Test complete POS workflow
- [ ] Test multi-terminal scenarios
- [ ] Write feature tests for POS controller

---

## 💳 Phase 5: Subscription System (3-5 days)

### SubscriptionController (1 day)
- [ ] Create `app/Http/Controllers/Api/V1/SubscriptionController.php`
- [ ] Add `GET /api/v1/plans` (list plans)
- [ ] Add `POST /api/v1/subscriptions` (subscribe)
- [ ] Add `PUT /api/v1/subscriptions/{id}/upgrade` (upgrade plan)
- [ ] Add `DELETE /api/v1/subscriptions/{id}` (cancel)
- [ ] Add `GET /api/v1/subscriptions/{id}/invoices` (billing history)
- [ ] Create PlanResource, SubscriptionResource
- [ ] Add all routes to `routes/api.php`

### Frontend Pages (1 day)
- [ ] Create `Pages/Subscription/Index.vue` (subscription management)
- [ ] Create `Pages/Subscription/Plans.vue` (plan selection)
- [ ] Create `Pages/Subscription/Billing.vue` (billing history)
- [ ] Add Subscription to sidebar

### Middleware & Enforcement (1 day)
- [ ] Register `CheckSubscriptionStatus` middleware in `bootstrap/app.php`
- [ ] Apply middleware to business routes
- [ ] Add usage tracking to key operations (invoices, products, employees)
- [ ] Test subscription limits enforcement

### Testing (1 day)
- [ ] Test plan selection
- [ ] Test subscription creation
- [ ] Test upgrade/downgrade
- [ ] Test cancellation
- [ ] Test usage limit enforcement
- [ ] Write feature tests

---

## 🎨 Phase 6: Polish & Reports (3 days)

### VAT Reports UI (1 day)
- [ ] Add "VAT Reports" tab to `Reports/Index.vue`
- [ ] Create `app/Http/Controllers/Api/V1/VatReportController.php`
- [ ] Add `GET /api/v1/reports/vat/mushak-61` (VAT input)
- [ ] Add `GET /api/v1/reports/vat/mushak-62` (VAT output)
- [ ] Add `GET /api/v1/reports/vat/mushak-63` (Invoice VAT)
- [ ] Add `GET /api/v1/reports/vat/mushak-66`
- [ ] Add `GET /api/v1/reports/vat/mushak-91`
- [ ] Add Mushak 6.3 PDF download button to invoice view
- [ ] Test all VAT reports

### Project Board API (0.5 days)
- [ ] Add `GET /api/v1/projects/{id}/board` to ProjectController
- [ ] Return board with columns and tasks
- [ ] Test with `Projects/Board.vue`

### Notification System Consolidation (0.5 days)
- [ ] Audit which controller `Notifications/Index.vue` uses
- [ ] Remove legacy notification controller
- [ ] Remove legacy notification routes
- [ ] Test notifications still work

### Alert Rules UI (0.5 days)
- [ ] Create `app/Http/Controllers/Api/V1/AlertRuleController.php`
- [ ] Add `apiResource('alert-rules', AlertRuleController::class)`
- [ ] Create `Pages/Settings/AlertRules/Index.vue`
- [ ] Add "Alert Rules" to Settings sidebar
- [ ] Test alert rule creation

### Plugin Management UI (0.5 days)
- [ ] Create `app/Http/Controllers/Api/V1/PluginController.php`
- [ ] Add `apiResource('plugins', PluginController::class)`
- [ ] Create `Pages/Settings/Plugins/Index.vue`
- [ ] Add "Plugins" to Settings sidebar
- [ ] Test plugin enable/disable

---

## ✅ Final Checklist

### Code Quality
- [ ] All test routes removed
- [ ] All duplicate files deleted
- [ ] All orphaned files deleted
- [ ] No console errors in browser
- [ ] No PHP errors in logs

### Testing
- [ ] All feature tests pass
- [ ] All unit tests pass
- [ ] Manual QA completed for all new features
- [ ] Cross-browser testing done

### Documentation
- [ ] API documentation updated
- [ ] User guide updated
- [ ] Developer guide updated
- [ ] Deployment guide updated

### Security
- [ ] All policies created and registered
- [ ] All middleware applied
- [ ] All FormRequests have validation
- [ ] CSRF protection verified
- [ ] Rate limiting applied

### Performance
- [ ] Dashboard loads in < 2s
- [ ] API responses < 500ms (95th percentile)
- [ ] No N+1 queries
- [ ] Eager loading applied where needed

### Deployment
- [ ] Environment variables documented
- [ ] Database migrations tested
- [ ] Seeders tested
- [ ] Backup strategy in place
- [ ] Rollback plan documented

---

## 📊 Progress Tracking

**Phase 1:** ⬜ Not Started | 🟡 In Progress | ✅ Complete  
**Phase 2:** ⬜ Not Started | 🟡 In Progress | ✅ Complete  
**Phase 3:** ⬜ Not Started | 🟡 In Progress | ✅ Complete  
**Phase 4:** ⬜ Not Started | 🟡 In Progress | ✅ Complete  
**Phase 5:** ⬜ Not Started | 🟡 In Progress | ✅ Complete  
**Phase 6:** ⬜ Not Started | 🟡 In Progress | ✅ Complete  

**Overall Progress:** 0% (0/55 tasks complete)

---

**Document Version:** 1.0  
**Last Updated:** 2026-03-05  
**Next Review:** After each phase completion
