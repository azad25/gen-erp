# Invoice Domain Analysis

## Overview
The Invoice domain manages all invoice-related operations including invoice creation, sending, cancellation, and status management. This domain is critical for the Sales module, handling the billing process, stock deduction, customer transaction recording, and integration with accounting.

## Current Implementation

### Backend Implementation
**Status:** ✅ WELL-IMPLEMENTED - Core functionality complete with advanced patterns

**Location:** `app/Domain/Invoice/`

**Domain Structure:**
- `Actions/` - SendInvoiceAction, CancelInvoiceAction, ConvertOrderToInvoiceAction
- `Commands/` - CreateInvoiceCommand, SendInvoiceCommand
- `DTOs/` - CreateInvoiceData
- `EventSourcing/` - Invoice event sourcing
- `Events/` - InvoiceSent, InvoiceCancelled, InvoiceCreated
- `Handlers/` - Command and query handlers
- `Listeners/` - Invoice event listeners
- `Models/` - Invoice, InvoiceItem
- `Policies/` - Invoice authorization policies
- `Queries/` - GetInvoiceQuery, GetInvoicesQuery
- `Repositories/` - InvoiceRepository (Contracts & Eloquent)
- `Services/` - InvoiceService

**Models:**
- `Invoice.php` - Main invoice entity with status management
- `InvoiceItem.php` - Line items with tax calculations

**Services:**
- `InvoiceService.php` - Handles invoice operations

**Controllers:**
- `InvoiceController.php` (API) - REST API with CQRS pattern
- `InvoiceController.php` (Sales) - Web controller

**Key Features:**
- ✅ Invoice creation with auto-generated invoice numbers
- ✅ Invoice update (draft only)
- ✅ Invoice sending with stock deduction
- ✅ Invoice cancellation with stock reversal
- ✅ Invoice status management (draft, sent, paid, partial, overdue)
- ✅ Line items with products and variants
- ✅ Tax and discount calculations
- ✅ VAT/Mushak number support
- ✅ Customer transaction recording
- ✅ Integration with Sales Orders
- ✅ CQRS pattern with caching
- ✅ Event sourcing support
- ✅ Repository pattern
- ✅ Custom field support

**InvoiceService Methods:**
```php
paginateInvoices(Company $company, array $filters = [], int $perPage = 15): LengthAwarePaginator
convertToInvoice(SalesOrder $order): Invoice
createInvoice(Company $company, array $data, array $items): Invoice
updateInvoice(Invoice $invoice, array $data, array $items): Invoice
sendInvoice(Invoice $invoice): void
cancelInvoice(Invoice $invoice): void
calculateTotals(array $items, bool $vatRegistered = false): array
```

**Invoice Model Attributes:**
```php
protected $fillable = [
    'company_id',
    'branch_id',
    'sales_order_id',
    'customer_id',
    'warehouse_id',
    'invoice_number',
    'mushak_number',
    'invoice_date',
    'due_date',
    'status',
    'subtotal',
    'discount_amount',
    'tax_amount',
    'shipping_amount',
    'total_amount',
    'amount_paid',
    'notes',
    'terms_conditions',
    'custom_fields',
    'stock_deducted',
    'created_by',
];
```

**Auto-generated Invoice Numbers:**
- Format: `INV-YYYYMMDD-XXXX`
- Example: `INV-20260305-0001`

**Invoice Status Flow:**
```
draft → sent → paid/partial → overdue (if not paid by due date)
         → cancelled (reversible)
```

### Frontend Implementation
**Status:** ✅ COMPLETE - Full CRUD operations implemented

**Location:** `resources/js/Pages/Sales/`

**Page Created:**
- `Invoices.vue` - Comprehensive invoice management page

**Features Implemented:**
- ✅ Invoice list with pagination
- ✅ Search functionality
- ✅ Create invoice modal
- ✅ Edit invoice modal (draft only)
- ✅ View invoice details modal
- ✅ Send invoice action
- ✅ Mark as paid action
- ✅ Delete invoice action (draft only)
- ✅ Status badges with variants
- ✅ Customer selection
- ✅ Product selection
- ✅ Line item management
- ✅ Date selection (invoice date, due date)
- ✅ Export functionality
- ✅ Responsive design with dark mode

**Actions:**
- View invoice details
- Edit invoice (draft only)
- Send invoice
- Mark as paid
- Delete invoice (draft only)

### Sidebar Menu Integration
**Status:** ✅ FULLY INTEGRATED

**Location:** `resources/js/Components/Layout/AppSidebar.vue`

**Menu Item:**
```javascript
{
  icon: DocsIcon,
  title: $t('sidebar.sales.invoices'),
  href: "/sales/invoices",
  routeName: "sales.invoices",
}
```

The Invoices menu item is under the **Sales** section in the sidebar.

### Routes
**Web Route:** ✅ DEFINED
```php
Route::get('/sales/invoices', [\App\Http\Controllers\Sales\InvoiceController::class, 'index'])->name('sales.invoices');
```

**API Routes:** ✅ COMPLETE
```php
Route::apiResource('invoices', InvoiceController::class);
Route::post('invoices/{invoice}/send', [InvoiceController::class, 'send']);
Route::post('invoices/{invoice}/cancel', [InvoiceController::class, 'cancel']);
```

## Integration with Other Domains

### Customer Domain
**Integration Points:**
- Invoices linked to customers
- Customer transactions recorded on invoice send
- Customer credit days used for due date calculation

**Data Flow:**
```
InvoiceService → Customer Transactions → Customer Balance
```

### Sales Order Domain
**Integration Points:**
- Sales orders converted to invoices
- Invoice inherits customer and items from order

**Data Flow:**
```
Sales Order → ConvertAction → Invoice
```

### Inventory Domain
**Integration Points:**
- Stock deducted on invoice send
- Stock reversed on invoice cancellation
- Warehouse selection for stock location

**Data Flow:**
```
Invoice Send → InventoryService → Stock Movement
Invoice Cancel → InventoryService → Stock Reversal
```

### Accounting Domain
**Integration Points:**
- Invoice totals posted to accounting ledger
- Tax calculations for VAT-registered companies
- Journal entries on invoice send/cancel

**Data Flow:**
```
Invoice Send → Accounting Journal Entries → General Ledger
Invoice Cancel → Journal Reversals
```

### Product Domain
**Integration Points:**
- Products linked to invoice items
- Product variants supported
- Product prices used for line items

**Data Flow:**
```
Invoice → Invoice Items → Products
```

## What's Missing

### Critical Features (Required for Complete Functionality)
1. **Invoice Dashboard**
   - Invoice overview with metrics
   - Recent invoices
   - Pending invoices
   - Overdue invoices
   - Quick actions

2. **Invoice Reports**
   - Invoice PDF generation
   - Invoice summaries
   - Aging reports
   - Sales analysis by invoice

3. **Bulk Invoice Operations**
   - Bulk send invoices
   - Bulk mark as paid
   - Bulk export
   - Bulk print

4. **Invoice Templates**
   - Custom invoice templates
   - Branding customization
   - Multiple layouts

### Important Features (Enhanced Functionality)
5. **Invoice Reminders**
   - Payment due reminders
   - Overdue notifications
   - Automated follow-ups
   - Email reminders

6. **Invoice Analytics**
   - Invoice trends
   - Payment patterns
   - Customer invoice history
   - Revenue analysis

7. **Invoice Approval**
   - Invoice approval workflow
   - Multi-level approval
   - Discount approval

8. **Invoice Reconciliation**
   - Payment reconciliation
   - Invoice matching
   - Dispute resolution

### Nice-to-Have Features
9. **Invoice Integration**
   - Email integration
   - SMS integration
   - Accounting software sync
   - Payment gateway integration

10. **Invoice Collaboration**
    - Invoice comments
    - Team collaboration
    - Invoice sharing
    - Internal notes

11. **Invoice Automation**
    - Recurring invoices
    - Automatic payment processing
    - Scheduled invoices
    - Auto-send on due date

12. **Invoice History**
    - Change tracking
    - Version history
    - Audit trail
    - Activity log

## Recommended Implementation Plan

### Phase 1: Dashboard & Reports (3-4 weeks)
**Week 1-2: Invoice Dashboard**
- Invoice overview with metrics
- Recent invoices
- Pending invoices
- Overdue invoices
- Quick actions

**Week 3-4: Invoice Reports**
- Invoice PDF generation
- Invoice summaries
- Aging reports
- Sales analysis by invoice

### Phase 2: Bulk Operations & Templates (2-3 weeks)
**Week 5-6: Bulk Operations**
- Bulk send invoices
- Bulk mark as paid
- Bulk export
- Bulk print

**Week 7: Invoice Templates**
- Custom invoice templates
- Branding customization
- Multiple layouts

### Phase 3: Reminders & Analytics (3-4 weeks)
**Week 8-9: Invoice Reminders**
- Payment due reminders
- Overdue notifications
- Automated follow-ups
- Email reminders

**Week 10-11: Invoice Analytics**
- Invoice trends
- Payment patterns
- Customer invoice history
- Revenue analysis

### Phase 4: Approval & Reconciliation (2-3 weeks)
**Week 12-13: Invoice Approval**
- Invoice approval workflow
- Multi-level approval
- Discount approval

**Week 14: Invoice Reconciliation**
- Payment reconciliation
- Invoice matching
- Dispute resolution

### Phase 5: Integration & Collaboration (2-3 weeks)
**Week 15-16: Invoice Integration**
- Email integration
- SMS integration
- Accounting software sync
- Payment gateway integration

**Week 17: Invoice Collaboration**
- Invoice comments
- Team collaboration
- Invoice sharing
- Internal notes

### Phase 6: Automation & History (2-3 weeks)
**Week 18-19: Invoice Automation**
- Recurring invoices
- Automatic payment processing
- Scheduled invoices
- Auto-send on due date

**Week 20: Invoice History**
- Change tracking
- Version history
- Audit trail
- Activity log

### Phase 7: Polish & Optimization (2 weeks)
**Week 21: Performance Optimization**
- Query optimization
- Caching layer
- Bulk operations optimization

**Week 22: Testing & Documentation**
- Unit tests
- Integration tests
- API documentation

## Technical Recommendations

### Database Schema
```sql
-- Invoice Reminders
CREATE TABLE invoice_reminders (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  company_id BIGINT NOT NULL,
  invoice_id BIGINT NOT NULL,
  reminder_type VARCHAR(50),
  reminder_date DATE,
  status VARCHAR(50),
  sent_at TIMESTAMP,
  created_at TIMESTAMP,
  FOREIGN KEY (company_id) REFERENCES companies(id),
  FOREIGN KEY (invoice_id) REFERENCES invoices(id)
);

-- Invoice Templates
CREATE TABLE invoice_templates (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  company_id BIGINT NOT NULL,
  name VARCHAR(255),
  description TEXT,
  template_data JSON,
  is_default BOOLEAN DEFAULT false,
  created_at TIMESTAMP,
  FOREIGN KEY (company_id) REFERENCES companies(id)
);

-- Invoice Approvals
CREATE TABLE invoice_approvals (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  company_id BIGINT NOT NULL,
  invoice_id BIGINT NOT NULL,
  approver_id BIGINT NOT NULL,
  status VARCHAR(50),
  notes TEXT,
  approved_at TIMESTAMP,
  created_at TIMESTAMP,
  FOREIGN KEY (company_id) REFERENCES companies(id),
  FOREIGN KEY (invoice_id) REFERENCES invoices(id),
  FOREIGN KEY (approver_id) REFERENCES users(id)
);

-- Invoice History
CREATE TABLE invoice_history (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  invoice_id BIGINT NOT NULL,
  action VARCHAR(50),
  changes JSON,
  user_id BIGINT,
  created_at TIMESTAMP,
  FOREIGN KEY (invoice_id) REFERENCES invoices(id),
  FOREIGN KEY (user_id) REFERENCES users(id)
);
```

### API Endpoints
```
// Invoice Management
GET  /api/v1/invoices - List invoices
POST /api/v1/invoices - Create invoice
GET  /api/v1/invoices/{id} - Get invoice
PUT  /api/v1/invoices/{id} - Update invoice
DELETE /api/v1/invoices/{id} - Delete invoice

// Invoice Actions
POST /api/v1/invoices/{invoice}/send - Send invoice
POST /api/v1/invoices/{invoice}/cancel - Cancel invoice
POST /api/v1/invoices/{invoice}/mark-paid - Mark as paid

// Invoice Reports
GET  /api/v1/invoices/{id}/pdf - Generate PDF
GET  /api/v1/invoices/reports/summary - Invoice summary
GET  /api/v1/invoices/reports/aging - Aging report
GET  /api/v1/invoices/reports/sales - Sales analysis

// Invoice Reminders
GET  /api/v1/invoices/{id}/reminders - List reminders
POST /api/v1/invoices/{id}/reminders - Create reminder
POST /api/v1/invoices/reminders/send - Send reminders

// Invoice Templates
GET  /api/v1/invoice-templates - List templates
POST /api/v1/invoice-templates - Create template
GET  /api/v1/invoice-templates/{id} - Get template
PUT  /api/v1/invoice-templates/{id} - Update template
DELETE /api/v1/invoice-templates/{id} - Delete template

// Bulk Operations
POST /api/v1/invoices/bulk/send - Bulk send
POST /api/v1/invoices/bulk/mark-paid - Bulk mark paid
POST /api/v1/invoices/bulk/export - Bulk export
```

### Libraries to Consider
- **PDF Generation:** DomPDF, Snappy, TCPDF
- **Email:** Laravel Mail, SendGrid, Mailgun
- **SMS:** Twilio, Vonage
- **Scheduling:** Laravel Scheduler
- **Queue:** Laravel Queues
- **Caching:** Redis, Memcached
- **Event Sourcing:** spatie/laravel-event-sourcing

## Summary

**Current Status:** ✅ WELL-IMPLEMENTED - Advanced architecture with CQRS and event sourcing

**Completion:** ~70% (Backend 85%, Frontend 75%)

**Priority:** HIGH - Invoice domain is critical for Sales and financial operations

**Recommendation:** Focus on Phase 1 (Invoice Dashboard & Reports) to enhance user experience and provide better visibility into invoice status and financial metrics.

**Estimated Total Time:** 22 weeks for full implementation

**Quick Win:** Implement invoice dashboard (1-2 weeks) to provide immediate value with invoice overview, pending/overdue invoices, and quick actions.

**Architecture Strengths:**
- CQRS pattern with command/query separation
- Event sourcing support
- Repository pattern
- Caching layer for performance
- Comprehensive domain structure
- Integration with multiple domains

**Missing Critical Features:**
- Invoice dashboard with metrics
- Invoice PDF generation
- Invoice reports
- Bulk operations
- Invoice templates
- Payment reminders
- Invoice analytics

**Integration Strengths:**
- Well-integrated with Customer, Sales Order, Inventory, and Accounting domains
- Proper stock deduction on send and reversal on cancel
- Customer transaction recording
- VAT/Mushak support
- Sales order conversion

**Advanced Features:**
- Event sourcing for audit trail
- CQRS pattern for scalability
- Caching for performance
- Repository pattern for data access
- Custom field support
- Multi-tenancy support
