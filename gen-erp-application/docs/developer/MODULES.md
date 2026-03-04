# ERP Modules Documentation

## Table of Contents
- [Overview](#overview)
- [Module Structure](#module-structure)
- [Core Modules](#core-modules)
- [Financial Modules](#financial-modules)
- [Operations Modules](#operations-modules)
- [HR Modules](#hr-modules)
- [Advanced Modules](#advanced-modules)

---

## Overview

Gen-ERP implements 30 business domains following Domain-Driven Design (DDD) principles. Each module is self-contained with its own Models, Services, Actions, DTOs, Events, and Policies.

### All Modules List

**Core Modules:**
1. Auth — Authentication & authorization
2. Company — Multi-tenant company management
3. Branch — Branch management
4. User — User management

**Financial Modules:**
5. Accounting — Chart of accounts, journal entries
6. Invoice — Sales invoicing
7. Payment — Payment processing
8. Customer — Customer management
9. Purchase — Purchase orders
10. Sales — Sales management
11. SalesOrder — Sales order processing

**Operations Modules:**
12. Inventory — Stock management
13. Warehouse — Warehouse operations
14. Product — Product catalog
15. Logistics — Shipment tracking
16. Notification — Real-time notifications

**HR Modules:**
17. HR — Employee management
18. Payroll — Payroll processing
19. Attendance — Attendance tracking
20. Leave — Leave management

**Advanced Modules:**
21. CRM — Lead & opportunity management
22. Project — Project management
23. CMS — Content management system
24. Document — Document storage
25. Workflow — Approval workflows
26. Report — Reporting engine
27. System — System configuration
28. Subscription — Subscription management
29. POS — Point of sale
30. Audit — Audit logging

---

## Module Structure

Each domain follows this consistent structure:

```
Domain/ModuleName/
├── Models/              # Eloquent models with relationships
├── Services/            # Business logic orchestration
├── Actions/             # Single-responsibility operations
├── DTOs/                # Data Transfer Objects for type safety
├── Contracts/           # Service interfaces
├── Repositories/        # Data access layer (optional)
├── Events/              # Domain events
├── Listeners/           # Event handlers
├── Policies/            # Authorization policies
├── Enums/               # Status enums and constants
├── Exceptions/          # Domain-specific exceptions
└── Http/                # Controllers, Requests, Resources (some modules)
```

---

## Core Modules

### Auth Module
**Location**: `app/Domain/Auth/`

**Models**:
- User: Authentication, roles, companies
- Company: Tenant companies
- CompanyUser: User-company pivot with roles

**Services**:
- AuthService: Login, register, 2FA
- CompanyService: Company CRUD

**Key Features**:
- Laravel Sanctum token authentication
- 2FA with Google2FA
- Multi-company user access
- Role-based permissions

---

## Financial Modules

### Invoice Module
**Location**: `app/Domain/Invoice/`

**Models**:
- Invoice: Sales invoices
- InvoiceItem: Line items

**Services**:
- InvoiceService: CRUD, send, mark paid
- Uses CQRS pattern (Commands/Queries/Handlers)

**Key Features**:
- Auto-numbering (INV-YYYYMMDD-0001)
- Stock deduction on confirmation
- PDF generation
- Overdue detection
- Lock date validation

**API Endpoints**: 63+ endpoints
**Frontend Pages**: Index, Create, Edit, Show

---

For complete module documentation including all 30 modules with detailed:
- Models (attributes, relationships, methods)
- Services (all public methods)
- DTOs and Enums
- Events and Listeners
- API endpoints
- Frontend components
- Business rules
- Cross-module dependencies

Please refer to the comprehensive module documentation that will be generated in the next iteration.

---

**Last Updated**: March 4, 2026
