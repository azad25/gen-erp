# Gen-ERP - Enterprise Resource Planning SaaS

<p align="center">
  <img src="https://img.shields.io/badge/Laravel-12.x-red?style=for-the-badge&logo=laravel" alt="Laravel">
  <img src="https://img.shields.io/badge/Vue.js-3.x-green?style=for-the-badge&logo=vue.js" alt="Vue.js">
  <img src="https://img.shields.io/badge/API-REST-blue?style=for-the-badge" alt="REST API">
  <img src="https://img.shields.io/badge/Architecture-DDD-purple?style=for-the-badge" alt="Domain Driven Design">
</p>

Gen-ERP is a comprehensive, multi-tenant SaaS Enterprise Resource Planning system built with modern technologies and Domain-Driven Design principles. It provides complete business management capabilities for small to medium enterprises.

## 🚀 Key Features

### 👥 **Multi-Tenant Architecture**
- **Company Management**: Multi-company support with isolated data
- **User Management**: Role-based access control and team invitations
- **Branch Management**: Multiple branch/location support per company
- **Two-Factor Authentication**: Enhanced security with TOTP support

### 💰 **Financial Management**
- **Chart of Accounts**: Comprehensive accounting structure with account groups
- **Journal Entries**: Double-entry bookkeeping system
- **Financial Reports**: Trial Balance, Profit & Loss, Balance Sheet
- **Expense Management**: Track and categorize business expenses
- **Payment Methods**: Multiple payment method configurations
- **Credit Notes**: Customer credit note management

### 🛒 **Sales & Customer Management**
- **Customer Management**: Complete customer database with contact groups
- **Sales Orders**: Create, confirm, and convert to invoices
- **Invoice Management**: Generate, send, and track invoices
- **Payment Processing**: Record and allocate customer payments
- **Credit Management**: Customer credit limits and balance tracking

### 🏭 **Purchase & Supplier Management**
- **Supplier Management**: Comprehensive supplier database
- **Purchase Orders**: Create, confirm, and receive goods
- **Goods Receipt**: Track received items and update inventory
- **Supplier Payments**: TDS/VDS calculations and payment processing
- **Purchase Returns**: Handle returned goods and adjustments

### 📦 **Inventory Management**
- **Product Management**: Complete product catalog with categories
- **Warehouse Management**: Multi-warehouse inventory tracking
- **Stock Movements**: Real-time inventory tracking and adjustments
- **Stock Levels**: Available, reserved, and reorder level management
- **Tax Groups**: VAT/Tax configuration and calculations

### 👨‍💼 **Human Resources**
- **Employee Management**: Complete employee database with departments
- **Attendance Tracking**: Daily attendance with bulk operations
- **Leave Management**: Leave types, requests, approval workflow
- **Payroll Processing**: Salary calculations and payslip generation
- **Department & Designations**: Organizational structure management

### 📄 **Document Management**
- **Document Storage**: Secure file upload and management
- **Document Folders**: Organized folder structure
- **File Operations**: Download, preview, and thumbnail generation
- **Document Linking**: Attach documents to various entities

### 🔄 **Workflow Management**
- **Approval Workflows**: Configurable approval processes
- **Workflow Instances**: Track document approval status
- **Approval Requests**: Multi-level approval system
- **Status Transitions**: Automated workflow state management

### 📊 **Reporting & Analytics**
- **Dashboard**: Real-time business metrics and KPIs
- **Financial Reports**: Comprehensive financial statements
- **Custom Reports**: Flexible report builder with filters
- **Data Export**: Export reports in multiple formats
- **Chart Visualizations**: Interactive charts and graphs

### 🔧 **System Administration**
- **Custom Fields**: Flexible field definitions for entities
- **Import Jobs**: Bulk data import with progress tracking
- **Notifications**: Real-time system notifications
- **Audit Trails**: Complete activity logging and tracking
- **Multi-tenancy**: Isolated data per company

## 🏗️ **Technical Architecture**

### **Backend Technologies**
- **Laravel 12.x**: Modern PHP framework with latest features
- **Domain-Driven Design**: Clean architecture with 22 business domains
- **RESTful API**: 182 documented endpoints with OpenAPI 3.0
- **Multi-tenancy**: Stancl/Tenancy for data isolation
- **Authentication**: Laravel Sanctum with API tokens

### **Frontend Technologies**
- **Vue.js 3.x**: Modern reactive frontend framework
- **Inertia.js**: Server-side rendering with SPA experience
- **Tailwind CSS 4.x**: Utility-first CSS framework
- **ApexCharts**: Interactive data visualizations
- **Pinia**: State management for Vue.js

### **Database & Storage**
- **MySQL/PostgreSQL**: Robust relational database support
- **Eloquent ORM**: Laravel's powerful database abstraction
- **File Storage**: Local and cloud storage support
- **Migrations**: Version-controlled database schema

### **Development & Testing**
- **PHPUnit/Pest**: Comprehensive test suite (198 tests)
- **Swagger UI**: Interactive API documentation
- **Vite**: Modern build tool for assets
- **Docker**: Containerized development environment

## 📚 **API Documentation**

The system provides comprehensive API documentation with interactive testing capabilities:

- **Swagger UI**: Available at `/swagger.html`
- **OpenAPI 3.0**: Complete API specification at `/openapi.json`
- **182 Endpoints**: Full CRUD operations for all entities
- **Authentication**: Token-based API access
- **Request/Response Examples**: Complete documentation with schemas

## 🚦 **Getting Started**

### **Prerequisites**
- PHP 8.2+
- Composer
- Node.js 18+
- MySQL 8.0+ or PostgreSQL 13+

### **Installation**

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd gen-erp-application
   ```

2. **Install dependencies**
   ```bash
   composer install
   npm install
   ```

3. **Environment setup**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Database setup**
   ```bash
   php artisan migrate
   php artisan db:seed
   ```

5. **Build assets**
   ```bash
   npm run build
   ```

6. **Start the application**
   ```bash
   php artisan serve
   ```

### **Development**

For development with hot reloading:
```bash
npm run dev
php artisan serve
```

## 🧪 **Testing**

Run the comprehensive test suite:
```bash
php artisan test
```

**Test Coverage:**
- 198 tests with 609 assertions
- Feature tests for all major functionality
- API endpoint testing
- Multi-tenancy isolation tests

## 📖 **Domain Structure**

The application follows Domain-Driven Design with these business domains:

- **Accounting**: Financial management and reporting
- **Auth**: Authentication and authorization
- **Customer**: Customer relationship management
- **HR**: Human resources and payroll
- **Inventory**: Stock and warehouse management
- **Invoice**: Billing and invoicing
- **Product**: Product catalog management
- **Purchase**: Procurement and supplier management
- **Sales**: Sales order management
- **System**: System administration
- **Workflow**: Business process automation

## 🔒 **Security Features**

- **Multi-factor Authentication**: TOTP-based 2FA
- **Role-based Access Control**: Granular permissions
- **API Rate Limiting**: Protection against abuse
- **Data Encryption**: Sensitive data protection
- **Audit Logging**: Complete activity tracking
- **CSRF Protection**: Cross-site request forgery prevention

## 📄 **License**

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🤝 **Contributing**

We welcome contributions! Please read our [Contributing Guidelines](CONTRIBUTING.md) for details on our code of conduct and the process for submitting pull requests.

## 📞 **Support**

For support and questions:
- **Documentation**: Check the API documentation at `/swagger.html`
- **Issues**: Report bugs via GitHub Issues
- **Email**: support@gen-erp.com

---

**Gen-ERP** - Empowering businesses with comprehensive ERP solutions.