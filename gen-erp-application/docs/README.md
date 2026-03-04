# Gen-ERP — Multi-Tenant SaaS ERP System

## Table of Contents
- [Overview](#overview)
- [Who Uses This System](#who-uses-this-system)
- [Tech Stack](#tech-stack)
- [ERP Modules](#erp-modules)
- [Documentation Suite](#documentation-suite)
- [System Requirements](#system-requirements)
- [Quick Start](#quick-start)
- [Repository Structure](#repository-structure)

---

## Overview

Gen-ERP is a comprehensive, multi-tenant SaaS ERP system built for small to medium-sized businesses in Bangladesh and beyond. It provides a complete suite of business management tools including CRM, invoicing, inventory management, HR & payroll, accounting, logistics, and e-commerce capabilities.

### What Business Problem Does It Solve?

Gen-ERP eliminates the need for multiple disconnected business software by providing:
- **Unified Data**: All business data in one system with real-time synchronization
- **Multi-Tenant Architecture**: Secure data isolation for multiple companies on a single platform
- **Localization**: Full Bengali language support with VAT/BIN compliance for Bangladesh
- **Scalability**: Subscription-based plans that grow with your business
- **Integration**: Built-in integrations with local logistics providers (Pathao, PaperFly, SteadFast)
- **Automation**: Workflow automation, approval processes, and real-time notifications

---

## Who Uses This System

### Tenant Types
- **Small Businesses**: Retail shops, service providers, small manufacturers
- **Medium Enterprises**: Multi-branch operations, wholesale distributors
- **E-commerce Businesses**: Online stores with integrated inventory and logistics
- **Service Companies**: Consulting firms, agencies, professional services

### User Roles
- **System Admin**: Platform-level administration
- **Company Owner**: Full access to company data and settings
- **Company Admin**: User management, configuration, reporting
- **Manager**: Department-level access, approval workflows
- **Employee**: Task execution, data entry, time tracking
- **Accountant**: Financial data access, journal entries, reports
- **Sales Rep**: CRM, sales orders, customer management
- **Warehouse Staff**: Inventory management, stock movements
- **Customer**: Self-service portal for orders and tracking

---

## Tech Stack

### Backend
- **Framework**: Laravel 12.0
- **Language**: PHP 8.2+
- **Database**: MySQL 8.0+ / SQLite (development)
- **Authentication**: Laravel Sanctum (API token-based)
- **Real-time**: Laravel Reverb (WebSockets)
- **Queue**: Redis-backed job queues
- **Cache**: Redis / Database
- **Testing**: Pest PHP
- **PDF Generation**: DomPDF
- **Excel**: Maatwebsite Excel
- **Activity Logging**: Spatie Activity Log
- **Permissions**: Spatie Laravel Permission
- **API Documentation**: L5-Swagger (OpenAPI 3.0)

### Frontend
- **Framework**: Vue.js 3.5.29
- **SSR**: Inertia.js 2.3.17
- **State Management**: Pinia 3.0.4
- **Router**: Vue Router 5.0.3
- **Styling**: TailwindCSS 4.2.1
- **UI Theme**: TailAdmin (customized)
- **Build Tool**: Vite 7.0.7
- **Charts**: ApexCharts 5.7.0
- **Rich Text Editor**: TipTap 2.1.13
- **HTTP Client**: Axios 1.13.6
- **Utilities**: VueUse 14.2.1, Day.js 1.11.19, Lodash 4.17.21

### DevOps & Infrastructure
- **Queue Workers**: Supervisor
- **Task Scheduling**: Laravel Scheduler
- **File Storage**: Local / S3-compatible
- **Email**: SMTP / Log driver
- **Broadcasting**: Laravel Echo + Pusher.js

---

## ERP Modules

### Core Modules
1. **Authentication & Authorization** — User management, 2FA, role-based access control
2. **Company Management** — Multi-tenant company setup, branch management
3. **Dashboard** — Real-time widgets, KPIs, analytics

### Financial Modules
4. **Invoicing** — Sales invoices, credit notes, payment tracking
5. **Sales Orders** — Order management with conversion to invoices
6. **Purchase Orders** — Procurement with goods receipt tracking
7. **Accounting** — Chart of accounts, journal entries, financial reports
8. **Payments** — Customer/supplier payments with allocation tracking
9. **Expenses** — Expense tracking and approval workflows

### Customer & Sales
10. **CRM** — Lead management, opportunities, sales pipeline, activities
11. **Customer Management** — Customer master, contact groups, transaction history

### Inventory & Logistics
12. **Product Management** — Product catalog with variants, categories
13. **Inventory** — Stock tracking, movements, adjustments, transfers
14. **Warehouse Management** — Multi-warehouse support, stock levels
15. **Logistics** — Shipment tracking, carrier integration, COD management

### Human Resources
16. **HR Management** — Employee master, departments, designations
17. **Attendance** — Daily attendance tracking, time entries
18. **Leave Management** — Leave requests with approval workflows
19. **Payroll** — Payroll runs, payslip generation, tax calculations
20. **Task Management** — Employee task assignments, capacity planning

### Content & E-commerce
21. **CMS** — Multi-tenant website builder with 53+ section types
22. **Blog** — Blog posts with categories and SEO
23. **E-commerce** — Shopping cart, checkout, product reviews, wishlists

### System & Integration
24. **Notifications** — Real-time notifications (Bengali/English)
25. **Documents** — Document management with folder structure
26. **Workflows** — Approval workflows, status transitions
27. **Reports** — Custom reports, saved reports, scheduled reports
28. **Integrations** — Webhook support, IoT device management
29. **Audit Logs** — Complete audit trail for compliance
30. **Subscriptions** — Plan management, usage tracking, billing

---

## Documentation Suite

### For Developers
- [APPLICATION_FLOW.md](developer/APPLICATION_FLOW.md) — Request lifecycle, authentication flow, routing
- [ARCHITECTURE.md](developer/ARCHITECTURE.md) — Backend/frontend architecture, domain structure
- [MODULES.md](developer/MODULES.md) — Detailed module documentation
- [API_REFERENCE.md](developer/API_REFERENCE.md) — Complete API endpoint documentation
- [FRONTEND.md](developer/FRONTEND.md) — Vue.js architecture, components, stores
- [DATABASE.md](developer/DATABASE.md) — Database schema, relationships, ER diagrams
- [EVENTS_AND_JOBS.md](developer/EVENTS_AND_JOBS.md) — Events, listeners, jobs, observers
- [TESTING.md](developer/TESTING.md) — Test suite structure, running tests

### For Business & Product
- [BUSINESS_OVERVIEW.md](business/BUSINESS_OVERVIEW.md) — Business features in plain language

### For Design & UI
- [DESIGN_SYSTEM.md](design/DESIGN_SYSTEM.md) — Colors, typography, components, patterns

### For QA & Testing
- [TEST_PLAN.md](qa/TEST_PLAN.md) — Test cases, scenarios, checklists

### For DevOps & Deployment
- [DEPLOYMENT.md](devops/DEPLOYMENT.md) — Deployment guide, environment configuration
- [ONBOARDING.md](devops/ONBOARDING.md) — New developer onboarding guide

---

## System Requirements

### Backend Requirements
- **PHP**: 8.2 or higher
- **Composer**: 2.x
- **Database**: MySQL 8.0+ or SQLite 3.x
- **Redis**: 7.0+ (optional but recommended for production)
- **Extensions**: BCMath, Ctype, Fileinfo, JSON, Mbstring, OpenSSL, PDO, Tokenizer, XML, GD/Imagick

### Frontend Requirements
- **Node.js**: 18.x or higher
- **npm**: 9.x or higher

### Production Requirements
- **Web Server**: Nginx 1.18+ or Apache 2.4+
- **Supervisor**: For queue workers
- **SSL Certificate**: Required for production
- **Storage**: 10GB+ for file uploads

---

## Quick Start

### 1. Clone Repository
```bash
git clone <repository-url>
cd gen-erp-application
```

### 2. Backend Setup
```bash
# Install PHP dependencies
composer install

# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Configure database in .env
# For SQLite (development):
DB_CONNECTION=sqlite

# For MySQL (production):
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=gen_erp
DB_USERNAME=root
DB_PASSWORD=

# Run migrations
php artisan migrate

# Seed default data
php artisan db:seed

# Create development admin user
php artisan db:seed --class=DevAdminSeeder
# Login: admin@example.com / password
```

### 3. Frontend Setup
```bash
# Install Node dependencies
npm install

# Build assets for development
npm run dev
```

### 4. Start Development Servers
```bash
# Option 1: Use Laravel's dev script (recommended)
composer dev
# This starts: Laravel server, queue worker, logs, and Vite

# Option 2: Manual start
# Terminal 1: Laravel server
php artisan serve

# Terminal 2: Queue worker
php artisan queue:work

# Terminal 3: Vite dev server
npm run dev
```

### 5. Access Application
- **Web Application**: http://localhost:8000
- **API Base URL**: http://localhost:8000/api/v1
- **API Documentation**: http://localhost:8000/api/documentation

### 6. Default Login Credentials
After running `DevAdminSeeder`:
- **Email**: admin@example.com
- **Password**: password
- **Company**: Demo Company (auto-created)

---

## Repository Structure

```
gen-erp-application/
├── app/
│   ├── Domain/              # 30 business domains (DDD architecture)
│   │   ├── Accounting/
│   │   ├── Auth/
│   │   ├── CRM/
│   │   ├── HR/
│   │   ├── Invoice/
│   │   ├── Logistics/
│   │   └── [25 more domains]
│   ├── Http/
│   │   ├── Controllers/     # API and web controllers
│   │   ├── Middleware/      # Request middleware
│   │   ├── Requests/        # Form request validation
│   │   └── Resources/       # API resources
│   ├── Services/            # Cross-domain services
│   ├── Jobs/                # Background jobs
│   ├── Events/              # Application events
│   ├── Listeners/           # Event listeners
│   ├── Observers/           # Model observers
│   └── Support/             # Enums, traits, helpers
├── config/                  # Configuration files
├── database/
│   ├── migrations/          # 150+ database migrations
│   ├── seeders/             # Database seeders
│   └── factories/           # Model factories
├── resources/
│   ├── js/
│   │   ├── Pages/           # Vue pages (40+ modules)
│   │   ├── Components/      # Reusable Vue components
│   │   ├── Stores/          # Pinia stores
│   │   ├── Services/        # API service layer
│   │   ├── Composables/     # Vue composables
│   │   └── Utils/           # Utility functions
│   ├── css/                 # Stylesheets
│   └── views/               # Blade templates
├── routes/
│   ├── api.php              # API routes (v1)
│   ├── web.php              # Web routes
│   └── channels.php         # Broadcast channels
├── storage/                 # File storage, logs, cache
├── tests/                   # Pest PHP tests
├── public/                  # Public assets
├── docs/                    # This documentation
├── composer.json            # PHP dependencies
├── package.json             # Node dependencies
├── tailwind.config.js       # Tailwind configuration
├── vite.config.js           # Vite configuration
└── phpunit.xml              # Test configuration
```

### Monorepo Structure
This is a monorepo containing both backend (Laravel) and frontend (Vue.js) in a single repository. The frontend is integrated via Inertia.js for seamless server-side rendering.

---

## Next Steps

1. **For Developers**: Start with [ARCHITECTURE.md](developer/ARCHITECTURE.md) to understand the system design
2. **For API Integration**: Read [API_REFERENCE.md](developer/API_REFERENCE.md) for endpoint documentation
3. **For Frontend Work**: Check [FRONTEND.md](developer/FRONTEND.md) for Vue.js architecture
4. **For Deployment**: Follow [DEPLOYMENT.md](devops/DEPLOYMENT.md) for production setup
5. **For New Team Members**: Complete [ONBOARDING.md](devops/ONBOARDING.md) guide

---

**Last Updated**: March 4, 2026
