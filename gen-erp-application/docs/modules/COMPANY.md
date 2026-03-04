## Multi-Tenant Architecture Overview

GEN ERP uses a **3-tier organizational hierarchy**:

### 1. Company (Tenant Level)
- The top-level entity representing a business tenant
- Each company is completely isolated using the `BelongsToCompany` trait
- Automatic query scoping via `CompanyContext` ensures data isolation
- Key fields:
  - Business type (retail, manufacturing, service, etc.)
  - Currency, timezone, locale settings
  - VAT/BIN registration
  - Subscription plan
  - Settings (JSON)

### 2. Branch (Physical Locations)
- Represents physical locations/offices of a company
- Each branch belongs to one company
- Can have:
  - A manager (employee)
  - An associated warehouse
  - Headquarters designation (`is_headquarters`)
  - Active/inactive status
- Used for dimensional reporting and access control
- Many entities track `branch_id` for location-based operations

### 3. Warehouse (Storage/Factory Locations)
- Represents inventory storage locations
- In your system, **warehouses serve as "factories"** - there's no separate factory entity
- Each warehouse belongs to one company
- Can be linked to a branch
- Tracks stock levels and inventory movements
- Key fields:
  - Code (unique per company)
  - Name, address
  - `is_default` flag
  - Active status

## How They Work Together

```
Company (Tenant)
├── Branch 1 (HQ) → Warehouse 1 (Main Factory)
├── Branch 2 (Regional Office) → Warehouse 2 (Regional Warehouse)
└── Branch 3 (Retail Store) → Warehouse 3 (Store Inventory)
```

### Multi-Tenancy Implementation

1. **Company Context**: Uses `CompanyContext::setActive()` to set the active company in session
2. **Global Scopes**: The `BelongsToCompany` trait automatically filters all queries by `company_id`
3. **Branch Context**: Optional `BranchContext` for branch-level filtering
4. **Automatic Assignment**: When creating records, `company_id` is auto-set from context

### Entities That Track Branch

Many transactional entities include `branch_id` for dimensional reporting:
- Sales Orders, Invoices, POS Sales
- Purchase Orders, Goods Receipts
- Journal Entries (accounting)
- Payroll Runs, Attendance
- Customer/Supplier Payments
- Expenses

### API Structure

- **Companies API** (`/api/v1/companies`): List, view, update companies
- **Branches API** (`/api/v1/branches`): CRUD operations scoped to active company
- **Warehouses API** (`/api/v1/warehouses`): CRUD operations scoped to active company

The system uses `activeCompany()` helper throughout to get the current tenant context, ensuring complete data isolation between companies.