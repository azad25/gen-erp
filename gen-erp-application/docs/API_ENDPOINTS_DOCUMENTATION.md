# API Endpoints Documentation

## 📋 **Complete API Reference**
Comprehensive documentation of all 50+ migrated controllers and their 350+ endpoints in the Gen-ERP application, including new CMS, Project Management, Enhanced HR, and Public API domains.

## 🔐 **Authentication**

### **Public Routes (No Auth Required)**
| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/v1/auth/login` | User login with email/password |
| POST | `/api/v1/auth/register` | User registration |

### **Protected Routes (Requires Token)**
| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/v1/auth/logout` | User logout |
| GET | `/api/v1/auth/user` | Get authenticated user data |
| POST | `/api/v1/auth/setup-company` | Setup company after registration |
| POST | `/api/v1/auth/switch-company/{id}` | Switch active company |
| POST | `/api/v1/auth/two-factor/challenge` | 2FA verification |

## 👥 **User Management**

### **Users**
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/users` | List all users |
| POST | `/api/v1/users` | Create new user |
| GET | `/api/v1/users/{id}` | Get specific user |
| PUT | `/api/v1/users/{id}` | Update user |
| DELETE | `/api/v1/users/{id}` | Delete user |
| POST | `/api/v1/users/{id}/add-to-company` | Add user to company |
| POST | `/api/v1/users/{id}/remove-from-company` | Remove user from company |

### **Invitations**
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/invitations` | List team invitations |
| POST | `/api/v1/invitations` | Send team invitation |
| GET | `/api/v1/invitations/{id}` | Get specific invitation |
| DELETE | `/api/v1/invitations/{id}` | Cancel invitation |

## 🏢 **Company & Settings**

### **Companies**
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/companies` | List companies |
| POST | `/api/v1/companies` | Create company |
| GET | `/api/v1/companies/{id}` | Get company details |
| PUT | `/api/v1/companies/{id}` | Update company |
| DELETE | `/api/v1/companies/{id}` | Delete company |

### **Branches**
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/branches` | List branches |
| POST | `/api/v1/branches` | Create branch |
| GET | `/api/v1/branches/{id}` | Get branch details |
| PUT | `/api/v1/branches/{id}` | Update branch |
| DELETE | `/api/v1/branches/{id}` | Delete branch |

### **Warehouses**
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/warehouses` | List warehouses |
| POST | `/api/v1/warehouses` | Create warehouse |
| GET | `/api/v1/warehouses/{id}` | Get warehouse details |
| PUT | `/api/v1/warehouses/{id}` | Update warehouse |
| DELETE | `/api/v1/warehouses/{id}` | Delete warehouse |

## 👥 **Human Resources**

### **Employees**
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/employees` | List employees |
| POST | `/api/v1/employees` | Create employee |
| GET | `/api/v1/employees/{id}` | Get employee details |
| PUT | `/api/v1/employees/{id}` | Update employee |
| DELETE | `/api/v1/employees/{id}` | Delete employee |

### **Departments**
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/departments` | List departments |
| POST | `/api/v1/departments` | Create department |
| GET | `/api/v1/departments/{id}` | Get department details |
| PUT | `/api/v1/departments/{id}` | Update department |
| DELETE | `/api/v1/departments/{id}` | Delete department |

### **Designations**
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/designations` | List designations |
| POST | `/api/v1/designations` | Create designation |
| GET | `/api/v1/designations/{id}` | Get designation details |
| PUT | `/api/v1/designations/{id}` | Update designation |
| DELETE | `/api/v1/designations/{id}` | Delete designation |

### **Attendance**
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/attendance` | List attendance records |
| POST | `/api/v1/attendance` | Mark attendance |
| GET | `/api/v1/attendance/{id}` | Get attendance record |
| PUT | `/api/v1/attendance/{id}` | Update attendance |
| DELETE | `/api/v1/attendance/{id}` | Delete attendance |
| POST | `/api/v1/attendance/bulk` | Bulk mark attendance |

### **Leave Management**
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/leave-types` | List leave types |
| POST | `/api/v1/leave-types` | Create leave type |
| GET | `/api/v1/leave-types/{id}` | Get leave type |
| PUT | `/api/v1/leave-types/{id}` | Update leave type |
| DELETE | `/api/v1/leave-types/{id}` | Delete leave type |
| GET | `/api/v1/leave-requests` | List leave requests |
| POST | `/api/v1/leave-requests` | Create leave request |
| GET | `/api/v1/leave-requests/{id}` | Get leave request |
| PUT | `/api/v1/leave-requests/{id}` | Update leave request |
| DELETE | `/api/v1/leave-requests/{id}` | Delete leave request |
| POST | `/api/v1/leave-requests/{id}/approve` | Approve leave request |
| POST | `/api/v1/leave-requests/{id}/reject` | Reject leave request |

### **Payroll**
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/payroll` | List payroll records |
| POST | `/api/v1/payroll` | Create payroll |
| GET | `/api/v1/payroll/{id}` | Get payroll details |
| PUT | `/api/v1/payroll/{id}` | Update payroll |
| DELETE | `/api/v1/payroll/{id}` | Delete payroll |
| POST | `/api/v1/payroll/run` | Run payroll process |

### **Payslips**
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/payslips` | List payslips |
| POST | `/api/v1/payslips` | Create payslip |
| GET | `/api/v1/payslips/{id}` | Get payslip details |
| PUT | `/api/v1/payslips/{id}` | Update payslip |
| DELETE | `/api/v1/payslips/{id}` | Delete payslip |
| GET | `/api/v1/payslips/{id}/download` | Download payslip PDF |
## 🛒 **Sales & Customer Management**

### **Customers**
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/customers` | List customers |
| POST | `/api/v1/customers` | Create customer |
| GET | `/api/v1/customers/{id}` | Get customer details |
| PUT | `/api/v1/customers/{id}` | Update customer |
| DELETE | `/api/v1/customers/{id}` | Delete customer |

### **Contact Groups**
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/contact-groups` | List contact groups |
| POST | `/api/v1/contact-groups` | Create contact group |
| GET | `/api/v1/contact-groups/{id}` | Get contact group |
| PUT | `/api/v1/contact-groups/{id}` | Update contact group |
| DELETE | `/api/v1/contact-groups/{id}` | Delete contact group |

### **Sales Orders**
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/sales-orders` | List sales orders |
| POST | `/api/v1/sales-orders` | Create sales order |
| GET | `/api/v1/sales-orders/{id}` | Get sales order |
| PUT | `/api/v1/sales-orders/{id}` | Update sales order |
| DELETE | `/api/v1/sales-orders/{id}` | Delete sales order |
| POST | `/api/v1/sales-orders/{id}/confirm` | Confirm sales order |
| POST | `/api/v1/sales-orders/{id}/convert-to-invoice` | Convert to invoice |
| POST | `/api/v1/sales-orders/{id}/cancel` | Cancel sales order |

### **Invoices**
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/invoices` | List invoices |
| POST | `/api/v1/invoices` | Create invoice |
| GET | `/api/v1/invoices/{id}` | Get invoice details |
| PUT | `/api/v1/invoices/{id}` | Update invoice |
| DELETE | `/api/v1/invoices/{id}` | Delete invoice |

## 🛍️ **Product Management**

### **Products**
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/products` | List products |
| POST | `/api/v1/products` | Create product |
| GET | `/api/v1/products/{id}` | Get product details |
| PUT | `/api/v1/products/{id}` | Update product |
| DELETE | `/api/v1/products/{id}` | Delete product |

### **Product Categories**
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/product-categories` | List product categories |
| POST | `/api/v1/product-categories` | Create category |
| GET | `/api/v1/product-categories/{id}` | Get category details |
| PUT | `/api/v1/product-categories/{id}` | Update category |
| DELETE | `/api/v1/product-categories/{id}` | Delete category |

### **Tax Groups**
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/tax-groups` | List tax groups |
| POST | `/api/v1/tax-groups` | Create tax group |
| GET | `/api/v1/tax-groups/{id}` | Get tax group |
| PUT | `/api/v1/tax-groups/{id}` | Update tax group |
| DELETE | `/api/v1/tax-groups/{id}` | Delete tax group |

## 🏭 **Purchase & Inventory**

### **Suppliers**
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/suppliers` | List suppliers |
| POST | `/api/v1/suppliers` | Create supplier |
| GET | `/api/v1/suppliers/{id}` | Get supplier details |
| PUT | `/api/v1/suppliers/{id}` | Update supplier |
| DELETE | `/api/v1/suppliers/{id}` | Delete supplier |

### **Purchase Orders**
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/purchase-orders` | List purchase orders |
| POST | `/api/v1/purchase-orders` | Create purchase order |
| GET | `/api/v1/purchase-orders/{id}` | Get purchase order |
| PUT | `/api/v1/purchase-orders/{id}` | Update purchase order |
| DELETE | `/api/v1/purchase-orders/{id}` | Delete purchase order |
| POST | `/api/v1/purchase-orders/{id}/confirm` | Confirm purchase order |
| POST | `/api/v1/purchase-orders/{id}/receive` | Receive goods |
| POST | `/api/v1/purchase-orders/{id}/cancel` | Cancel purchase order |

### **Stock Movements**
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/stock-movements` | List stock movements |
| POST | `/api/v1/stock-movements` | Create stock movement |
| GET | `/api/v1/stock-movements/{id}` | Get stock movement |
| PUT | `/api/v1/stock-movements/{id}` | Update stock movement |
| DELETE | `/api/v1/stock-movements/{id}` | Delete stock movement |
## 💰 **Accounting & Finance**

### **Chart of Accounts**
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/accounts` | List chart of accounts |
| POST | `/api/v1/accounts` | Create account |
| GET | `/api/v1/accounts/{id}` | Get account details |
| PUT | `/api/v1/accounts/{id}` | Update account |
| DELETE | `/api/v1/accounts/{id}` | Delete account |

### **Account Groups**
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/account-groups` | List account groups |
| POST | `/api/v1/account-groups` | Create account group |
| GET | `/api/v1/account-groups/{id}` | Get account group |
| PUT | `/api/v1/account-groups/{id}` | Update account group |
| DELETE | `/api/v1/account-groups/{id}` | Delete account group |

### **Journal Entries**
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/journal-entries` | List journal entries |
| POST | `/api/v1/journal-entries` | Create journal entry |
| GET | `/api/v1/journal-entries/{id}` | Get journal entry |
| PUT | `/api/v1/journal-entries/{id}` | Update journal entry |
| DELETE | `/api/v1/journal-entries/{id}` | Delete journal entry |

### **Expenses**
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/expenses` | List expenses |
| POST | `/api/v1/expenses` | Create expense |
| GET | `/api/v1/expenses/{id}` | Get expense details |
| PUT | `/api/v1/expenses/{id}` | Update expense |
| DELETE | `/api/v1/expenses/{id}` | Delete expense |

### **Payments**
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/payments` | List payments |
| POST | `/api/v1/payments` | Create payment |
| GET | `/api/v1/payments/{id}` | Get payment details |
| PUT | `/api/v1/payments/{id}` | Update payment |
| DELETE | `/api/v1/payments/{id}` | Delete payment |
| POST | `/api/v1/payments/{id}/allocate` | Allocate payment |

### **Payment Methods**
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/payment-methods` | List payment methods |
| POST | `/api/v1/payment-methods` | Create payment method |
| GET | `/api/v1/payment-methods/{id}` | Get payment method |
| PUT | `/api/v1/payment-methods/{id}` | Update payment method |
| DELETE | `/api/v1/payment-methods/{id}` | Delete payment method |

### **Credit Notes**
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/credit-notes` | List credit notes |
| POST | `/api/v1/credit-notes` | Create credit note |
| GET | `/api/v1/credit-notes/{id}` | Get credit note |
| PUT | `/api/v1/credit-notes/{id}` | Update credit note |
| DELETE | `/api/v1/credit-notes/{id}` | Delete credit note |

### **Cost Centers** 🆕
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/cost-centers` | List cost centers |
| POST | `/api/v1/cost-centers` | Create cost center |
| GET | `/api/v1/cost-centers/{id}` | Get cost center details |
| PUT | `/api/v1/cost-centers/{id}` | Update cost center |
| DELETE | `/api/v1/cost-centers/{id}` | Delete cost center |
| GET | `/api/v1/cost-centers-options` | Get cost centers for dropdown |

### **Lock Date Management** 🆕
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/companies/{company}/lock-date` | Get current lock date |
| PUT | `/api/v1/companies/{company}/lock-date` | Update lock date |
| POST | `/api/v1/companies/{company}/lock-date/month-end-close` | Perform month-end close |
| POST | `/api/v1/companies/{company}/lock-date/validate` | Validate lock date |

### **Financial Reports** 🆕
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/reports/trial-balance` | Generate trial balance |
| GET | `/api/v1/reports/profit-loss` | Generate P&L statement |
| GET | `/api/v1/reports/balance-sheet` | Generate balance sheet |
| GET | `/api/v1/reports/dimensional-pl` | Dimensional P&L report |
| GET | `/api/v1/reports/dimensional-balance-sheet` | Dimensional balance sheet |
| GET | `/api/v1/reports/ar-aging` | Accounts receivable aging |
| GET | `/api/v1/reports/ap-aging` | Accounts payable aging |
| GET | `/api/v1/reports/inventory-valuation` | Inventory valuation report |
| GET | `/api/v1/reports/vat-liability` | VAT liability report |
| GET | `/api/v1/reports/vat-transactions` | VAT transaction details |
| GET | `/api/v1/reports/comparative-yoy` | Year-over-year comparison |
| GET | `/api/v1/reports/comparative-mom` | Month-over-month comparison |
| GET | `/api/v1/reports/comparative-qoq` | Quarter-over-quarter comparison |
| GET | `/api/v1/reports/cash-flow` | Cash flow statement |
| GET | `/api/v1/reports/cash-flow-direct` | Direct method cash flow |
| GET | `/api/v1/reports/trend-analysis` | Trend analysis report |

### **Integrity & Compliance** 🆕
| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/v1/integrity/check` | Run integrity check |
| GET | `/api/v1/integrity/status` | Get integrity status |
| POST | `/api/v1/posting/reverse` | Reverse journal entry |
| POST | `/api/v1/posting/approve-invoice` | Approve invoice (atomic) |

## 📄 **Document Management**

### **Documents**
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/documents` | List documents |
| POST | `/api/v1/documents` | Upload document |
| GET | `/api/v1/documents/{id}` | Get document details |
| PUT | `/api/v1/documents/{id}` | Update document |
| DELETE | `/api/v1/documents/{id}` | Delete document |
| GET | `/api/v1/documents/{id}/download` | Download document |
| GET | `/api/v1/documents/{id}/thumbnail` | Get document thumbnail |
| GET | `/api/v1/documents/{id}/preview` | Preview document |

### **Document Folders**
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/document-folders` | List document folders |
| POST | `/api/v1/document-folders` | Create folder |
| GET | `/api/v1/document-folders/{id}` | Get folder details |
| PUT | `/api/v1/document-folders/{id}` | Update folder |
| DELETE | `/api/v1/document-folders/{id}` | Delete folder |

## 🔔 **System & Notifications**

### **Notifications**
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/notifications` | List notifications |
| GET | `/api/v1/notifications/{id}` | Get notification |
| POST | `/api/v1/notifications/{id}/mark-read` | Mark as read |
| POST | `/api/v1/notifications/mark-all-read` | Mark all as read |

### **Custom Fields**
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/custom-fields` | List custom fields |
| POST | `/api/v1/custom-fields` | Create custom field |
| GET | `/api/v1/custom-fields/{id}` | Get custom field |
| PUT | `/api/v1/custom-fields/{id}` | Update custom field |
| DELETE | `/api/v1/custom-fields/{id}` | Delete custom field |

### **Import Jobs**
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/import-jobs` | List import jobs |
| POST | `/api/v1/import-jobs` | Create import job |
| GET | `/api/v1/import-jobs/{id}` | Get import job status |
| PUT | `/api/v1/import-jobs/{id}` | Update import job |
| DELETE | `/api/v1/import-jobs/{id}` | Delete import job |
## 🔄 **Workflow Management**

### **Workflow Instances**
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/workflow-instances` | List workflow instances |
| POST | `/api/v1/workflow-instances` | Create workflow instance |
| GET | `/api/v1/workflow-instances/{id}` | Get workflow instance |
| PUT | `/api/v1/workflow-instances/{id}` | Update workflow instance |
| DELETE | `/api/v1/workflow-instances/{id}` | Delete workflow instance |
| POST | `/api/v1/workflow-instances/{id}/transition` | Execute transition |

### **Approval Requests**
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/approval-requests` | List approval requests |
| POST | `/api/v1/approval-requests` | Create approval request |
| GET | `/api/v1/approval-requests/{id}` | Get approval request |
| PUT | `/api/v1/approval-requests/{id}` | Update approval request |
| DELETE | `/api/v1/approval-requests/{id}` | Delete approval request |
| POST | `/api/v1/approval-requests/{id}/approve` | Approve request |
| POST | `/api/v1/approval-requests/{id}/reject` | Reject request |

## 📊 **Reports & Analytics**

### **Reports**
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/reports` | List available reports |
| POST | `/api/v1/reports` | Create custom report |
| GET | `/api/v1/reports/{id}` | Get report definition |
| PUT | `/api/v1/reports/{id}` | Update report |
| DELETE | `/api/v1/reports/{id}` | Delete report |
| GET | `/api/v1/reports/{id}/generate` | Generate report data |

### **Dashboard**
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/dashboard` | Get dashboard data |

## 🎯 **Customer Relationship Management (CRM)**

### **Lead Management**
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/crm/leads` | List all leads with filtering |
| POST | `/api/v1/crm/leads` | Create new lead |
| GET | `/api/v1/crm/leads/my-leads` | Get leads assigned to current user |
| GET | `/api/v1/crm/leads/statistics` | Get lead statistics and metrics |
| GET | `/api/v1/crm/leads/scoring-statistics` | Get lead scoring statistics (Hot/Warm/Cold) |
| POST | `/api/v1/crm/leads/bulk-assign` | Bulk assign leads to user |
| POST | `/api/v1/crm/leads/bulk-update-status` | Bulk update lead status |
| POST | `/api/v1/crm/leads/bulk-score` | Bulk score multiple leads |
| POST | `/api/v1/crm/leads/bulk-qualify` | Bulk qualify multiple leads |
| GET | `/api/v1/crm/leads/{uuid}` | Get specific lead details |
| PUT | `/api/v1/crm/leads/{uuid}` | Update lead information |
| DELETE | `/api/v1/crm/leads/{uuid}` | Delete lead |
| POST | `/api/v1/crm/leads/{uuid}/assign` | Assign lead to user |
| POST | `/api/v1/crm/leads/{uuid}/update-score` | Update lead score manually |
| POST | `/api/v1/crm/leads/{uuid}/score` | Auto-calculate and update lead score |
| POST | `/api/v1/crm/leads/{uuid}/qualify` | Mark lead as qualified |
| POST | `/api/v1/crm/leads/{uuid}/notes` | Add note to lead |
| POST | `/api/v1/crm/leads/{uuid}/tags` | Add tag to lead |
| DELETE | `/api/v1/crm/leads/{uuid}/tags/{tagId}` | Remove tag from lead |

### **Opportunity Management**
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/crm/opportunities` | List all opportunities with filtering |
| POST | `/api/v1/crm/opportunities` | Create new opportunity |
| GET | `/api/v1/crm/opportunities/statistics` | Get opportunity statistics and metrics |
| GET | `/api/v1/crm/opportunities/forecast` | Get sales forecast data |
| POST | `/api/v1/crm/opportunities/bulk-move-to-stage` | Bulk move opportunities to stage |
| POST | `/api/v1/crm/opportunities/bulk-assign` | Bulk assign opportunities to user |
| GET | `/api/v1/crm/opportunities/pipeline/{pipelineId}` | Get opportunities by pipeline |
| GET | `/api/v1/crm/opportunities/stage/{stageId}` | Get opportunities by stage |
| GET | `/api/v1/crm/opportunities/{uuid}` | Get specific opportunity details |
| PUT | `/api/v1/crm/opportunities/{uuid}` | Update opportunity information |
| DELETE | `/api/v1/crm/opportunities/{uuid}` | Delete opportunity |
| POST | `/api/v1/crm/opportunities/{uuid}/move-to-stage` | Move opportunity to different stage |
| POST | `/api/v1/crm/opportunities/{uuid}/mark-as-won` | Mark opportunity as won |
| POST | `/api/v1/crm/opportunities/{uuid}/mark-as-lost` | Mark opportunity as lost |
| POST | `/api/v1/crm/opportunities/{uuid}/assign` | Assign opportunity to user |
| POST | `/api/v1/crm/opportunities/{uuid}/update-probability` | Update opportunity probability |

### **Pipeline Management**
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/crm/pipelines` | List all pipelines |
| POST | `/api/v1/crm/pipelines` | Create new pipeline |
| GET | `/api/v1/crm/pipelines/active` | Get active pipelines only |
| GET | `/api/v1/crm/pipelines/default` | Get default pipeline |
| GET | `/api/v1/crm/pipelines/{uuid}` | Get specific pipeline details |
| PUT | `/api/v1/crm/pipelines/{uuid}` | Update pipeline information |
| DELETE | `/api/v1/crm/pipelines/{uuid}` | Delete pipeline |
| POST | `/api/v1/crm/pipelines/{uuid}/set-default` | Set pipeline as default |
| POST | `/api/v1/crm/pipelines/{uuid}/activate` | Activate pipeline |
| POST | `/api/v1/crm/pipelines/{uuid}/deactivate` | Deactivate pipeline |
| POST | `/api/v1/crm/pipelines/{uuid}/duplicate` | Duplicate pipeline with stages |
| GET | `/api/v1/crm/pipelines/{uuid}/metrics` | Get pipeline performance metrics |
| POST | `/api/v1/crm/pipelines/{uuid}/stages` | Create new stage in pipeline |
| PUT | `/api/v1/crm/pipelines/{uuid}/stages/{stageId}` | Update pipeline stage |
| DELETE | `/api/v1/crm/pipelines/{uuid}/stages/{stageId}` | Delete pipeline stage |
| POST | `/api/v1/crm/pipelines/{uuid}/stages/reorder` | Reorder pipeline stages |

### **Activity Management**
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/crm/activities` | List all activities with filtering |
| POST | `/api/v1/crm/activities` | Create new activity |
| GET | `/api/v1/crm/activities/my-activities` | Get activities assigned to current user |
| GET | `/api/v1/crm/activities/scheduled` | Get scheduled activities |
| GET | `/api/v1/crm/activities/overdue` | Get overdue activities |
| GET | `/api/v1/crm/activities/due-today` | Get activities due today |
| GET | `/api/v1/crm/activities/statistics` | Get activity statistics and metrics |
| POST | `/api/v1/crm/activities/bulk-complete` | Bulk complete multiple activities |
| POST | `/api/v1/crm/activities/bulk-reschedule` | Bulk reschedule multiple activities |
| GET | `/api/v1/crm/activities/subject/{subjectType}/{subjectId}` | Get activities for specific subject |
| GET | `/api/v1/crm/activities/{uuid}` | Get specific activity details |
| PUT | `/api/v1/crm/activities/{uuid}` | Update activity information |
| DELETE | `/api/v1/crm/activities/{uuid}` | Delete activity |
| POST | `/api/v1/crm/activities/{uuid}/start` | Start activity |
| POST | `/api/v1/crm/activities/{uuid}/complete` | Complete activity |
| POST | `/api/v1/crm/activities/{uuid}/cancel` | Cancel activity |
| POST | `/api/v1/crm/activities/{uuid}/reschedule` | Reschedule activity |

### **Contact Management**
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/crm/contacts` | List all contacts with filtering |
| POST | `/api/v1/crm/contacts` | Create new contact |
| GET | `/api/v1/crm/contacts/{uuid}` | Get specific contact details |
| PUT | `/api/v1/crm/contacts/{uuid}` | Update contact information |
| DELETE | `/api/v1/crm/contacts/{uuid}` | Delete contact |

### **CRM Query Parameters**

#### **Lead Filtering**
- `status`: Filter by lead status (new, contacted, qualified, unqualified, converted)
- `assigned_to`: Filter by assigned user ID
- `source`: Filter by lead source (website, referral, social_media, etc.)
- `min_score`: Filter by minimum lead score (0-100)
- `score_range`: Filter by score range (hot, warm, cold, unscored)
- `search`: Search in lead name, email, company
- `date_from` / `date_to`: Filter by creation date range

#### **Opportunity Filtering**
- `status`: Filter by opportunity status (open, won, lost)
- `pipeline_id`: Filter by pipeline ID
- `stage_id`: Filter by stage ID
- `assigned_to`: Filter by assigned user ID
- `min_amount` / `max_amount`: Filter by opportunity value range
- `expected_close_date_from` / `expected_close_date_to`: Filter by expected close date

#### **Activity Filtering**
- `type`: Filter by activity type (call, email, meeting, task, etc.)
- `status`: Filter by activity status (scheduled, in_progress, completed, cancelled)
- `assigned_to`: Filter by assigned user ID
- `subject_type`: Filter by subject type (lead, opportunity, customer)
- `subject_id`: Filter by subject ID
- `due_date_from` / `due_date_to`: Filter by due date range

### **CRM Response Examples**

#### **Lead Response**
```json
{
  "data": {
    "id": 1,
    "uuid": "550e8400-e29b-41d4-a716-446655440000",
    "name": "John Doe",
    "email": "john@example.com",
    "phone": "+1234567890",
    "company_name": "Acme Corp",
    "job_title": "CEO",
    "status": {
      "value": "qualified",
      "label": "Qualified"
    },
    "source": "website",
    "score": 85,
    "expected_value": 50000,
    "currency": "BDT",
    "assigned_to": {
      "id": 1,
      "name": "Sales Rep",
      "email": "sales@company.com"
    },
    "created_at": "2026-03-04T10:00:00Z",
    "updated_at": "2026-03-04T15:30:00Z"
  }
}
```

#### **Opportunity Response**
```json
{
  "data": {
    "id": 1,
    "uuid": "550e8400-e29b-41d4-a716-446655440001",
    "name": "Enterprise Software Deal",
    "amount": 100000,
    "currency": "BDT",
    "probability": 75,
    "weighted_value": 75000,
    "status": {
      "value": "open",
      "label": "Open",
      "is_open": true,
      "is_won": false,
      "is_lost": false
    },
    "pipeline": {
      "id": 1,
      "name": "Sales Pipeline",
      "color": "#3B82F6"
    },
    "stage": {
      "id": 1,
      "name": "Proposal",
      "probability": 75,
      "color": "#10B981"
    },
    "expected_close_date": "2026-04-15",
    "created_at": "2026-03-04T10:00:00Z"
  }
}
```

#### **Lead Scoring Statistics Response**
```json
{
  "data": {
    "hot_leads": 15,
    "warm_leads": 32,
    "cold_leads": 28,
    "unscored_leads": 5,
    "average_score": 62.3,
    "total_leads": 80
  }
}
```

## 📋 **API Summary**

### **Total Endpoints: 250+**
- **Authentication**: 7 endpoints
- **User Management**: 9 endpoints  
- **Company & Settings**: 15 endpoints
- **Human Resources**: 35 endpoints
- **Sales & Customer**: 20 endpoints
- **Product Management**: 15 endpoints
- **Purchase & Inventory**: 15 endpoints
- **Accounting & Finance**: 65 endpoints 🆕 (+30 new financial engine endpoints)
- **Document Management**: 10 endpoints
- **System & Notifications**: 15 endpoints
- **Workflow Management**: 12 endpoints
- **Reports & Analytics**: 7 endpoints
- **CRM Management**: 70 endpoints 🆕 (Complete CRM system with lead scoring)
- **Document Management**: 10 endpoints
- **System & Notifications**: 15 endpoints
- **Workflow Management**: 12 endpoints
- **Reports & Analytics**: 7 endpoints

### **HTTP Methods Used**
- **GET**: List and retrieve operations
- **POST**: Create and action operations
- **PUT**: Update operations  
- **DELETE**: Delete operations

### **Authentication Requirements**
- **Public**: 2 endpoints (login, register)
- **Token Required**: 5 endpoints (auth operations)
- **Company Context Required**: 240+ endpoints (business operations)

### **Response Format**
All endpoints return standardized JSON responses with:
```json
{
  "success": true|false,
  "data": {...},
  "message": "Success message",
  "errors": {...}
}
```

### **Pagination**
List endpoints support pagination with:
- `per_page`: Items per page (default: 15)
- `page`: Page number (default: 1)

### **Filtering & Search**
Most list endpoints support:
- Search parameters
- Status filtering
- Date range filtering
- Company scoping (automatic)

---

## 🆕 **New Financial Engine Endpoints**

### **Enterprise-Grade Financial Features**

The financial engine introduces 30+ new endpoints providing enterprise-grade accounting capabilities:

#### **Cost Center Management**
- **Purpose**: Dimensional accounting for tracking profitability by department/project
- **Features**: CRUD operations, manager assignment, budget tracking
- **Usage**: Used in journal entries for dimensional reporting

#### **Lock Date Management** 
- **Purpose**: Period controls to prevent backdating and ensure audit compliance
- **Features**: Month-end close workflow, integrity validation
- **Usage**: Prevents modification of posted entries before lock date

#### **Advanced Financial Reports**
- **Dimensional Reports**: P&L and Balance Sheet by branch/cost center
- **Aging Reports**: AR/AP aging with detailed breakdowns
- **Comparative Reports**: Year-over-year, month-over-month analysis
- **VAT Reports**: Detailed VAT liability and transaction reports
- **Cash Flow**: Direct and indirect method cash flow statements
- **Inventory Valuation**: FIFO/LIFO valuation with COGS analysis

#### **Integrity & Compliance**
- **Integrity Checks**: Automated validation of financial data consistency
- **Journal Reversals**: Automatic reversal for credit notes and cancellations
- **Atomic Operations**: Invoice approval with stock deduction and journal posting

### **Key Technical Features**

#### **Idempotent Operations**
All posting operations use idempotency keys to prevent duplicate entries on retry.

#### **Multi-Tenant Isolation**
All endpoints are company-scoped with proper data isolation.

#### **Dimensional Accounting**
Support for branch, cost center, and custom JSON dimensions.

#### **FIFO Inventory Costing**
Automatic COGS computation using FIFO/Weighted Average methods.

#### **VAT Compliance**
Separate VAT tracking for automated tax reporting and compliance.

### **Example API Calls**

#### **Create Cost Center**
```bash
POST /api/v1/cost-centers
{
  "code": "CC001",
  "name": "Marketing Department",
  "description": "Marketing and advertising activities",
  "manager_id": 123,
  "budget": 50000.00,
  "is_active": true
}
```

#### **Generate Dimensional P&L**
```bash
GET /api/v1/reports/dimensional-pl?from_date=2024-01-01&to_date=2024-12-31&branch_id=1&cost_center_id=2
```

#### **Month-End Close**
```bash
POST /api/v1/companies/1/lock-date/month-end-close
{
  "lock_date": "2024-01-31",
  "run_integrity_check": true
}
```

#### **Year-over-Year Comparison**
```bash
GET /api/v1/reports/comparative-yoy?from_date=2024-01-01&to_date=2024-12-31&dimensions[branch_id]=1
```

### **Response Examples**

#### **Cost Center Response**
```json
{
  "data": {
    "id": 1,
    "code": "CC001",
    "name": "Marketing Department",
    "description": "Marketing and advertising activities",
    "manager_id": 123,
    "manager_name": "John Smith",
    "budget": 50000.00,
    "is_active": true,
    "created_at": "2024-03-04T10:00:00Z",
    "updated_at": "2024-03-04T10:00:00Z"
  }
}
```

#### **Comparative Report Response**
```json
{
  "comparison_type": "Year-over-Year",
  "current_period": {
    "period": "Jan 2024 - Dec 2024",
    "revenue": 1000000,
    "expenses": 600000,
    "net_income": 400000
  },
  "previous_period": {
    "period": "Jan 2023 - Dec 2023", 
    "revenue": 800000,
    "expenses": 500000,
    "net_income": 300000
  },
  "variance": {
    "revenue": {
      "amount": 200000,
      "percentage": 25.0,
      "direction": "increase"
    },
    "expenses": {
      "amount": 100000,
      "percentage": 20.0,
      "direction": "increase"
    },
    "net_income": {
      "amount": 100000,
      "percentage": 33.33,
      "direction": "increase"
    }
  }
}
```

### **Error Handling**

All financial engine endpoints follow standard HTTP status codes:

- **200**: Success
- **201**: Created successfully
- **400**: Bad request (validation errors)
- **401**: Unauthorized
- **403**: Forbidden (insufficient permissions)
- **404**: Resource not found
- **422**: Unprocessable entity (business logic errors)
- **500**: Internal server error

### **Rate Limiting**

Financial endpoints are subject to rate limiting:
- **60 requests per minute** for standard operations
- **10 requests per minute** for report generation
- **5 requests per minute** for month-end close operations

---

## 🔧 **Development Notes**

### **Testing the Financial Engine**

Use the following test data for API testing:

```bash
# Create test cost center
POST /api/v1/cost-centers
{
  "code": "TEST001",
  "name": "Test Department", 
  "is_active": true
}

# Generate test report
GET /api/v1/reports/trial-balance?as_of_date=2024-03-04

# Check integrity
POST /api/v1/integrity/check
```

### **Production Considerations**

- All financial operations are logged for audit trails
- Lock date enforcement prevents historical data manipulation
- Idempotency keys prevent duplicate transactions
- Multi-tenant isolation ensures data security
- FIFO inventory costing provides accurate valuations

---

## 🌐 **CMS (Content Management System)**

### **Sites**
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/cms/sites` | List tenant's sites |
| POST | `/api/v1/cms/sites` | Create new site |
| GET | `/api/v1/cms/sites/{id}` | Get site details |
| PUT | `/api/v1/cms/sites/{id}` | Update site |
| DELETE | `/api/v1/cms/sites/{id}` | Delete site |
| POST | `/api/v1/cms/sites/{id}/publish` | Publish site |
| POST | `/api/v1/cms/sites/{id}/unpublish` | Unpublish site |
| GET | `/api/v1/cms/sites/{id}/statistics` | Get site statistics |

### **Pages**
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/cms/pages` | List pages |
| POST | `/api/v1/cms/pages` | Create page |
| GET | `/api/v1/cms/pages/{id}` | Get page with sections |
| PUT | `/api/v1/cms/pages/{id}` | Update page |
| DELETE | `/api/v1/cms/pages/{id}` | Delete page |
| POST | `/api/v1/cms/pages/{id}/publish` | Publish page |
| POST | `/api/v1/cms/pages/{id}/unpublish` | Unpublish page |
| POST | `/api/v1/cms/pages/{id}/set-homepage` | Set page as homepage |

### **Sections (Page Builder)**
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/cms/sections` | List sections |
| POST | `/api/v1/cms/sections` | Create section |
| GET | `/api/v1/cms/sections/{id}` | Get section details |
| PUT | `/api/v1/cms/sections/{id}` | Update section |
| DELETE | `/api/v1/cms/sections/{id}` | Delete section |
| POST | `/api/v1/cms/sections/{id}/duplicate` | Duplicate section |
| POST | `/api/v1/cms/pages/{page}/sections/reorder` | Reorder page sections |
| GET | `/api/v1/cms/section-types` | Get available section types |

### **Media Management**
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/cms/media` | List media files |
| POST | `/api/v1/cms/media/upload` | Upload media file |
| DELETE | `/api/v1/cms/media/{path}` | Delete media file |

### **Reviews (Admin Management)**
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/cms/reviews` | List product reviews |
| POST | `/api/v1/cms/reviews/{id}/approve` | Approve review |
| POST | `/api/v1/cms/reviews/{id}/reject` | Reject review |
| DELETE | `/api/v1/cms/reviews/{id}` | Delete review |
| GET | `/api/v1/cms/reviews/statistics` | Get review statistics |

### **Wishlists (Admin Management)**
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/cms/wishlists` | List customer wishlists |
| GET | `/api/v1/cms/wishlists/statistics` | Get wishlist statistics |
| DELETE | `/api/v1/cms/wishlists/{id}` | Delete wishlist item |
| DELETE | `/api/v1/cms/wishlists/customers/{customerId}/clear` | Clear customer wishlist |

### **Page Builder**
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/cms/page-builder/section-types` | Get available section types |
| GET | `/api/v1/cms/page-builder/pages/{pageId}` | Get page for editing |
| GET | `/api/v1/cms/page-builder/pages/{pageId}/preview` | Preview page |
| POST | `/api/v1/cms/page-builder/pages/{pageId}/sections` | Add section to page |
| PUT | `/api/v1/cms/page-builder/pages/{pageId}/sections/reorder` | Reorder sections |
| PUT | `/api/v1/cms/page-builder/sections/{sectionId}/content` | Update section content |
| POST | `/api/v1/cms/page-builder/sections/{sectionId}/toggle-visibility` | Toggle section visibility |
| POST | `/api/v1/cms/page-builder/sections/{sectionId}/duplicate` | Duplicate section |

### **Contact Management**
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/cms/contacts` | List contact submissions |
| GET | `/api/v1/cms/contacts/statistics` | Get contact statistics |
| GET | `/api/v1/cms/contacts/export` | Export contacts |
| GET | `/api/v1/cms/contacts/{id}` | Get contact details |
| POST | `/api/v1/cms/contacts/{id}/contacted` | Mark as contacted |
| POST | `/api/v1/cms/contacts/{id}/resolved` | Mark as resolved |
| POST | `/api/v1/cms/contacts/{id}/spam` | Mark as spam |
| POST | `/api/v1/cms/contacts/{id}/assign` | Assign to user |
| DELETE | `/api/v1/cms/contacts/{id}` | Delete contact |

### **SEO Management**
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/cms/seo/dashboard` | SEO dashboard data |
| GET | `/api/v1/cms/seo/analysis` | SEO analysis report |
| GET | `/api/v1/cms/seo/sitemap-preview` | Preview sitemap |
| GET | `/api/v1/cms/seo/structured-data-preview` | Preview structured data |
| GET | `/api/v1/cms/seo/meta-tags-preview` | Preview meta tags |

### **ERP Integration**
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/cms/erp/products` | Get ERP products for CMS |
| GET | `/api/v1/cms/erp/products/{productId}/related` | Get related products |
| GET | `/api/v1/cms/erp/team` | Get team members for CMS |
| GET | `/api/v1/cms/erp/projects` | Get projects for CMS |
| GET | `/api/v1/cms/erp/stats` | Get company statistics |
| GET | `/api/v1/cms/erp/testimonials` | Get testimonials |
| GET | `/api/v1/cms/erp/search` | Search ERP data |

## 📋 **Project Management System (PMS)**

### **Projects**
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/projects` | List projects |
| POST | `/api/v1/projects` | Create project |
| GET | `/api/v1/projects/dashboard` | Project dashboard data |
| GET | `/api/v1/projects/{id}` | Get project details |
| PUT | `/api/v1/projects/{id}` | Update project |
| DELETE | `/api/v1/projects/{id}` | Delete project |
| POST | `/api/v1/projects/{id}/archive` | Archive project |
| POST | `/api/v1/projects/{id}/duplicate` | Duplicate project |
| GET | `/api/v1/projects/{id}/statistics` | Get project statistics |

### **Project Members**
| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/v1/projects/{id}/members` | Add project member |
| DELETE | `/api/v1/projects/{id}/members/{employeeId}` | Remove project member |
| PUT | `/api/v1/projects/{id}/members/{employeeId}` | Update member role |

### **Project Tasks**
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/projects/{projectId}/tasks` | List project tasks |
| POST | `/api/v1/projects/{projectId}/tasks` | Create project task |
| GET | `/api/v1/projects/{projectId}/tasks/statistics` | Get task statistics |

## 📝 **Task Management**

### **Tasks**
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/tasks/{id}` | Get task details |
| PUT | `/api/v1/tasks/{id}` | Update task |
| DELETE | `/api/v1/tasks/{id}` | Delete task |
| POST | `/api/v1/tasks/{id}/move` | Move task to different status |
| POST | `/api/v1/tasks/{id}/assign` | Assign task to employee |
| POST | `/api/v1/tasks/{id}/unassign` | Unassign task |
| POST | `/api/v1/tasks/{id}/watchers` | Add task watcher |
| DELETE | `/api/v1/tasks/{id}/watchers/{employeeId}` | Remove task watcher |
| POST | `/api/v1/tasks/{parentId}/subtasks` | Create subtask |
| GET | `/api/v1/tasks/{id}/hierarchy` | Get task hierarchy |
| POST | `/api/v1/tasks/bulk-update-positions` | Bulk update task positions |

### **Employee Tasks**
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/employees/{employeeId}/tasks` | Get employee tasks |

## 👥 **Enhanced HR Domain**

### **Employee Tasks**
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/hr/employees/{employeeId}/tasks` | List employee tasks |
| POST | `/api/v1/hr/employees/{employeeId}/tasks` | Create employee task |
| GET | `/api/v1/hr/employees/{employeeId}/tasks/{taskId}` | Get task details |
| PUT | `/api/v1/hr/employees/{employeeId}/tasks/{taskId}` | Update task |
| DELETE | `/api/v1/hr/employees/{employeeId}/tasks/{taskId}` | Delete task |
| POST | `/api/v1/hr/employees/{employeeId}/tasks/{taskId}/start` | Start task |
| POST | `/api/v1/hr/employees/{employeeId}/tasks/{taskId}/complete` | Complete task |
| GET | `/api/v1/hr/employees/{employeeId}/tasks/statistics` | Get task statistics |

### **Time Tracking**
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/hr/employees/{employeeId}/time-entries` | List time entries |
| POST | `/api/v1/hr/employees/{employeeId}/time-entries` | Create time entry |
| GET | `/api/v1/hr/employees/{employeeId}/timesheet` | Get employee timesheet |
| GET | `/api/v1/hr/employees/{employeeId}/time-statistics` | Get time statistics |
| GET | `/api/v1/hr/time-entries/{id}` | Get time entry details |
| PUT | `/api/v1/hr/time-entries/{id}` | Update time entry |
| DELETE | `/api/v1/hr/time-entries/{id}` | Delete time entry |
| POST | `/api/v1/hr/time-entries/approve` | Approve time entries |

### **Capacity Planning**
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/hr/employees/{employeeId}/capacity` | Get employee capacity |
| PUT | `/api/v1/hr/employees/{employeeId}/capacity` | Update employee capacity |
| GET | `/api/v1/hr/employees/{employeeId}/capacity/trends` | Get capacity trends |
| GET | `/api/v1/hr/capacity/overview` | Get capacity overview |
| GET | `/api/v1/hr/capacity/available` | Get available employees |
| GET | `/api/v1/hr/capacity/overallocated` | Get overallocated employees |

## 🌍 **Public API (E-commerce & Website)**

### **Site Rendering**
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/public/{tenant}` | Get homepage |
| GET | `/api/public/{tenant}/site` | Get site configuration |
| GET | `/api/public/{tenant}/pages` | List public pages |
| GET | `/api/public/{tenant}/pages/{slug}` | Get page by slug |
| GET | `/api/public/{tenant}/blog` | List blog posts |
| GET | `/api/public/{tenant}/blog/{slug}` | Get blog post by slug |
| GET | `/api/public/{tenant}/search` | Search site content |

### **Contact Forms**
| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/public/{tenant}/contact` | Submit contact form |
| POST | `/api/public/{tenant}/newsletter` | Subscribe to newsletter |

### **SEO**
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/public/{tenant}/sitemap.xml` | Get XML sitemap |
| GET | `/api/public/{tenant}/robots.txt` | Get robots.txt |
| GET | `/api/public/{tenant}/structured-data` | Get structured data |
| GET | `/api/public/{tenant}/meta-tags` | Get meta tags |

### **Shopping Cart**
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/public/{tenant}/cart` | Get shopping cart |
| POST | `/api/public/{tenant}/cart/items` | Add item to cart |
| PUT | `/api/public/{tenant}/cart/items/{itemId}` | Update cart item |
| DELETE | `/api/public/{tenant}/cart/items/{itemId}` | Remove cart item |
| DELETE | `/api/public/{tenant}/cart` | Clear cart |
| GET | `/api/public/{tenant}/cart/count` | Get cart item count |

### **Checkout**
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/public/{tenant}/checkout/payment-methods` | Get payment methods |
| POST | `/api/public/{tenant}/checkout/place-order` | Place order |

### **Customer Accounts**
| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/public/{tenant}/register` | Customer registration |
| POST | `/api/public/{tenant}/login` | Customer login |
| GET | `/api/public/{tenant}/profile` | Get customer profile |
| PUT | `/api/public/{tenant}/profile` | Update customer profile |
| GET | `/api/public/{tenant}/orders` | Get customer orders |
| POST | `/api/public/{tenant}/convert-guest` | Convert guest to customer |

### **Product Reviews**
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/public/{tenant}/products/{productId}/reviews` | List product reviews |
| GET | `/api/public/{tenant}/products/{productId}/reviews/stats` | Get review statistics |
| POST | `/api/public/{tenant}/products/{productId}/reviews` | Submit product review |
| POST | `/api/public/{tenant}/products/{productId}/reviews/{reviewId}/helpful` | Mark review helpful |
| GET | `/api/public/{tenant}/customer/reviews` | Get customer reviews |

### **Wishlist**
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/public/{tenant}/wishlist` | Get customer wishlist |
| POST | `/api/public/{tenant}/wishlist` | Add item to wishlist |
| DELETE | `/api/public/{tenant}/wishlist/products/{productId}` | Remove from wishlist |
| GET | `/api/public/{tenant}/wishlist/products/{productId}/check` | Check if in wishlist |
| GET | `/api/public/{tenant}/wishlist/count` | Get wishlist count |
| DELETE | `/api/public/{tenant}/wishlist/clear` | Clear wishlist |
| POST | `/api/public/{tenant}/wishlist/{wishlistItemId}/move-to-cart` | Move to cart |

### **Public Shipment Tracking**
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/public/{tenant}/track/{trackingNumber}` | Track shipment publicly |

## 📋 **API Summary**

### **Total Endpoints: 350+**
- **Authentication**: 7 endpoints
- **User Management**: 9 endpoints  
- **Company & Settings**: 15 endpoints
- **Human Resources**: 35 endpoints
- **Enhanced HR Domain**: 15 endpoints 🆕
- **Sales & Customer**: 20 endpoints
- **Product Management**: 15 endpoints
- **Purchase & Inventory**: 15 endpoints
- **Accounting & Finance**: 65 endpoints
- **Document Management**: 10 endpoints
- **System & Notifications**: 15 endpoints
- **Workflow Management**: 12 endpoints
- **Reports & Analytics**: 7 endpoints
- **CRM Management**: 70 endpoints
- **CMS Domain**: 35 endpoints 🆕
- **Project Management**: 12 endpoints 🆕
- **Task Management**: 12 endpoints 🆕
- **Public API**: 25 endpoints 🆕

### **New Domain Features**

#### **CMS Domain (35 endpoints)**
- **Multi-tenant websites**: Each company can build custom websites
- **Page builder**: Drag-and-drop interface with 50+ section types
- **E-commerce integration**: Shopping cart, checkout, customer accounts
- **SEO optimization**: Sitemap, structured data, meta tags
- **Content management**: Pages, blog posts, media library
- **Review system**: Customer reviews with moderation
- **Contact forms**: Lead capture and newsletter signup

#### **Project Management (12 endpoints)**
- **Project tracking**: Create, manage, and monitor projects
- **Team collaboration**: Add members, assign roles
- **Task management**: Create tasks, track progress
- **Project analytics**: Statistics and reporting
- **Project templates**: Duplicate successful projects

#### **Enhanced HR Domain (15 endpoints)**
- **Employee task tracking**: Assign and monitor employee tasks
- **Time tracking**: Log work hours, generate timesheets
- **Capacity planning**: Monitor workload and availability
- **Performance analytics**: Track productivity metrics

#### **Public API (25 endpoints)**
- **Multi-tenant support**: Separate API per tenant
- **E-commerce functionality**: Complete online store
- **Customer self-service**: Account management, order tracking
- **Content delivery**: Public website rendering
- **SEO-friendly**: Structured data and sitemap generation

### **HTTP Methods Used**
- **GET**: List and retrieve operations
- **POST**: Create and action operations
- **PUT**: Update operations  
- **DELETE**: Delete operations

### **Authentication Requirements**
- **Public**: 27 endpoints (public website, e-commerce)
- **Token Required**: 5 endpoints (auth operations)
- **Company Context Required**: 320+ endpoints (business operations)

### **Response Format**
All endpoints return standardized JSON responses with:
```json
{
  "success": true|false,
  "data": {...},
  "message": "Success message",
  "errors": {...}
}
```

### **Pagination**
List endpoints support pagination with:
- `per_page`: Items per page (default: 15)
- `page`: Page number (default: 1)

### **Filtering & Search**
Most list endpoints support:
- Search parameters
- Status filtering
- Date range filtering
- Company scoping (automatic)

---

**Last Updated**: March 4, 2026  
**Financial Engine Version**: 1.0.0  
**New Domains Version**: 1.0.0  
**Total API Endpoints**: 350+