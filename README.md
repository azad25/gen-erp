<h1 align="center">GenERP BD</h1>
<p align="center">
  <strong>A Generalised Cloud ERP / SaaS Platform for Bangladesh 🇧🇩</strong>
</p>
<p align="center">
  <img src="https://img.shields.io/badge/Laravel_12-FF2D20?style=flat-square&logo=laravel&logoColor=white" alt="Laravel 12" />
  <img src="https://img.shields.io/badge/PHP_8.2+-777BB4?style=flat-square&logo=php&logoColor=white" alt="PHP 8.2+" />
  <img src="https://img.shields.io/badge/FilamentPHP_v4-F59E0B?style=flat-square&logo=laravel&logoColor=white" alt="FilamentPHP v4" />
  <img src="https://img.shields.io/badge/MySQL_8.0-4479A1?style=flat-square&logo=mysql&logoColor=white" alt="MySQL 8" />
  <img src="https://img.shields.io/badge/Redis-DC382D?style=flat-square&logo=redis&logoColor=white" alt="Redis" />
  <img src="https://img.shields.io/badge/TailwindCSS_v3-38B2AC?style=flat-square&logo=tailwind-css&logoColor=white" alt="TailwindCSS" />
  <img src="https://img.shields.io/badge/Pest-F7DE1C?style=flat-square&logo=testing-library&logoColor=black" alt="Pest Testing" />
</p>

## 🎯 Project Vision

**GenERP BD** aims to eliminate the need for custom ERP software for SMEs in Bangladesh. By providing a core engine with infinite configuration options, it adapts to the business rather than forcing the business to adapt to it. It operates on a **Freemium SaaS** model with a "Bangla-first" approach.

With **GenERP BD**, businesses get enterprise-grade features with completely dynamic configuration, eliminating the need for expensive custom development per industry. From a sole freelancer to a 1,000-employee RMG factory, this application scales and configures to map their operations inherently.

## ✨ Core Platform Capabilities

- **🏢 True Multi-Tenancy:** One login manages unlimited companies and branches. Architectural data isolation is enforced via a global Eloquent scope (`company_id`). A bug in application code cannot expose cross-company data.
- **🏷️ Entity Aliasing:** Dynamically rename core entities to match distinct business vocabularies (e.g., Customer → Patient, Student, Client, or Donor).
- **⚙️ Dynamic Custom Fields:** Add virtually any field (Text, Dropdown, Date, Multi-select, Formula, File) to any entity directly from the application UI, natively indexed.
- **🔄 Configurable Workflows:** Define custom approval chains, transition statuses, SLA timers, and role-based automation per document type.
- **🇧🇩 Absolute Bangladesh Compliance Built-In:**
  - Automated Mushak VAT form generation (6.1, 6.2, 6.3, 6.6, 9.1).
  - NBR-compliant invoice formats and direct BD income tax slab auto-calculation.
  - Wage board compliance for RMG integrations.
  - Automatic TDS/VDS deductions scaling to BD local laws.
- **💳 Manual Payment Verification:** Fully integrated with bKash, Nagad, Rocket, and physical Bank Cheques/NPSB. No restrictive payment gateways. 
- **🌐 Bangla-First Bilingual UI:** Full Bengali language default handling, standard Noto Sans Bengali font throughout, Bengali numeral conversions, and English bilingual switches per user and company profiles.
- **🧾 Configurable Number Sequencing:** Custom prefix, suffix, embeddable branch codes, and reset boundaries for document types (Invoices, Purchases, etc.).

## 📦 Functional Modules

- **Products & Inventory:** Support for physical stock, multi-warehouse, batch/FEFO tracking, expiry alerts, or a stripped-down Service Catalogue mode without inventory dependencies.
- **Sales & Invoicing:** Complete cycle from quotation to final invoice, capturing custom tax lines, manual MFS verification, and POS sessions.
- **Purchasing & AP:** Supplier ledger, goods received notes, and purchase/expense approvals.
- **Core Accounting:** Real-time general ledger, Trial Balance, P&L, balance sheets, automated double-entry journal logs.
- **HR & Payroll:** Biometric integrations, leave regulations aligned with the Bangladesh Labour Act, and custom salary structure templates.

## 🛠️ Tech Stack

- **Backend:** Laravel 12 (PHP 8.2+)
- **Admin Panel:** FilamentPHP v4 (TALL Stack)
- **Database:** MySQL 8 (UTF8mb4_unicode_ci)
- **Memory & Queues:** Redis + Laravel Horizon
- **Real-time Engine:** Laravel Reverb + Echo (WebSockets)
- **Auth & RBAC:** Laravel Sanctum + Spatie Permission + stancl/tenancy
- **Testing:** Pest + Laravel Dusk
- **PDF Generation:** DomPDF with embedded Noto Sans Bengali font.

## 🚀 Getting Started

### Prerequisites

Ensure you have the following installed on your local development environment:
- PHP >= 8.2
- Composer
- MySQL >= 8.0
- Redis
- Node.js & NPM

### Installation

1. **Clone the repository:**
   ```bash
   git clone <repository_url>
   cd gen-erp
   ```

2. **Install PHP dependencies:**
   ```bash
   cd gen-erp-application
   composer install
   ```

3. **Install NPM dependencies:**
   ```bash
   npm install
   npm run build
   ```

4. **Environment Setup:**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
   *Update the `.env` file with your database, Redis, Reverb, and mail credentials.*

5. **Run Migrations & Seeders:**
   ```bash
   php artisan migrate:fresh --seed
   ```

6. **Serve the Application:**
   ```bash
   php artisan serve
   ```
   *You can also start Horizon and Reverb in separate terminals:*
   ```bash
   php artisan horizon
   php artisan reverb:start
   ```

## 🔒 Enterprise Security Architecture

Security is baked into the foundation, ensuring robust enterprise-grade protection:
- **Strict Tenant Isolation:** Cross-tenant vulnerabilities are nullified. The `company_id` Eloquent scope operates uniformly across all system levels.
- **SQL Injection Defenses:** Application runs strictly on Laravel Query Builder and standard Eloquent APIs. Raw SQL (`DB::statement`) is unconditionally prohibited.
- **XSS & CSRF Guardrails:** Full escape on user data in Blade and Alpine.js, enforced `VerifyCsrfToken` middleware on all mutations. Complete whitelist MIME validation.
- **Brute Force Resiliency:** Login paths configured natively using caching throttle blocks integrated closely via Cloudflare CDN network layers.
- **Restricted Uploader Context:** Stored items leverage obfuscated UUID generation isolated inside private system scopes (no public web access).

## 🤖 AI-Assisted Architecture

This project is iteratively built leveraging modern AI-assisted engineering rules defined in the rigid `.cursor/rules` configurations. It establishes code-level guarantees avoiding anti-patterns, imposing strict compliance checks for the BD business standards, securing bilingual components, and enforcing proper Filament v4 coding implementations. For ongoing work, generating explicit Test (Pest) coverages and ensuring zero raw SQL is automated through systemic prompt guidelines.

## 📄 License & Ownership

Proprietary Software. All rights reserved by **GenERP BD**.
