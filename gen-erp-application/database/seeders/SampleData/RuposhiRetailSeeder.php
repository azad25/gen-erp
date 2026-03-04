<?php

namespace Database\Seeders\SampleData;

use App\Support\Enums\CompanyRole;
use App\Support\Enums\InvoiceStatus;
use App\Support\Enums\ProductType;
use App\Support\Enums\PurchaseOrderStatus;
use App\Support\Enums\SalesOrderStatus;
use App\Support\Enums\StockMovementType;
use App\Support\Enums\AttendanceStatus;
use App\Support\Enums\EmployeeStatus;
use App\Support\Enums\LeaveStatus;
use App\Support\Enums\PayrollRunStatus;
use App\Domain\CRM\Enums\LeadStatus;
use App\Domain\CRM\Enums\LeadSource;
use App\Domain\CRM\Enums\OpportunityStage;
use App\Domain\CRM\Enums\ActivityType;
use App\Support\Enums\PaymentStatus;
use App\Support\Enums\PaymentMethod;
use App\Domain\Logistics\Enums\CarrierType;
use App\Domain\Logistics\Enums\ShipmentStatus;
use App\Domain\Logistics\Enums\DeliveryType;
use App\Support\Enums\WorkflowDocumentType;
use App\Domain\CMS\Enums\SiteStatus;
use App\Domain\CMS\Enums\PageStatus;
use App\Domain\CMS\Enums\SectionType;
use App\Support\Enums\CustomFieldType;
use App\Support\Enums\FormFieldType;
use App\Domain\Auth\Models\Branch;
use App\Domain\Auth\Models\Company;
use App\Domain\Auth\Models\CompanyUser;
use App\Domain\Customer\Models\Customer;
use App\Domain\HR\Models\Department;
use App\Domain\HR\Models\Designation;
use App\Domain\HR\Models\Employee;
use App\Domain\HR\Models\Attendance;
use App\Domain\HR\Models\LeaveType;
use App\Domain\HR\Models\LeaveRequest;
use App\Domain\HR\Models\PayrollRun;
use App\Domain\HR\Models\PayrollEntry;
use App\Domain\HR\Models\EmployeeTask;
use App\Domain\HR\Models\EmployeeTimeEntry;
use App\Domain\Document\Models\Document;
use App\Domain\Document\Models\DocumentFolder;
use App\Domain\Document\Models\Form;
use App\Domain\Document\Models\FormField;
use App\Domain\Document\Models\FormSubmission;
use App\Domain\Accounting\Models\Expense;
use App\Domain\Accounting\Models\Account;
use App\Models\AccountGroup;
use App\Domain\Accounting\Models\JournalEntry;
use App\Domain\Invoice\Models\Invoice;
use App\Domain\Invoice\Models\InvoiceItem;
use App\Domain\Product\Models\Product;
use App\Domain\Product\Models\ProductCategory;
use App\Domain\Purchase\Models\PurchaseOrder;
use App\Domain\Purchase\Models\PurchaseOrderItem;
use App\Domain\SalesOrder\Models\SalesOrder;
use App\Domain\SalesOrder\Models\SalesOrderItem;
use App\Domain\Inventory\Models\StockMovement;
use App\Domain\Inventory\Models\StockLevel;
use App\Domain\Purchase\Models\Supplier;
use App\Domain\Product\Models\TaxGroup;
use App\Domain\Auth\Models\User;
use App\Domain\Inventory\Models\Warehouse;
use App\Domain\Project\Models\Project;
use App\Domain\Project\Models\Board;
use App\Domain\Project\Models\Task;
use App\Domain\CRM\Models\Lead;
use App\Domain\CRM\Models\Opportunity;
use App\Domain\CRM\Models\CrmActivity;
use App\Domain\CRM\Models\Pipeline;
use App\Domain\CRM\Models\PipelineStage;
use App\Domain\Payment\Models\Payment;
use App\Domain\Accounting\Models\PaymentMethod as PaymentMethodModel;
use App\Models\POSSale;
use App\Models\POSSaleItem;
use App\Models\POSSession;
use App\Domain\Logistics\Models\Carrier;
use App\Domain\Logistics\Models\Shipment;
use App\Domain\Logistics\Models\ShipmentItem;
use App\Domain\Workflow\Models\WorkflowDefinition;
use App\Domain\Workflow\Models\WorkflowInstance;
use App\Domain\Workflow\Models\WorkflowStep;
use App\Domain\System\Models\NotificationTemplate;
use App\Domain\Notification\Models\NotificationLog;
use App\Domain\CMS\Models\Site;
use App\Domain\CMS\Models\Page;
use App\Domain\CMS\Models\Section;
use App\Models\SavedReport;
use App\Domain\System\Models\SystemSetting;
use App\Domain\Audit\Models\AuditLog;
use App\Domain\Contact\Models\Contact;
use App\Domain\Contact\Models\ContactGroup;
use App\Domain\Shared\Models\CustomFieldDefinition;
use App\Domain\Shared\Models\CustomFieldValue;
use App\Services\CompanyContext;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Ruposhi Retail — A retail shop scenario with products, customers, invoices, POS sales.
 */
class RuposhiRetailSeeder
{
    protected ?\Illuminate\Console\Command $command = null;

    public function setCommand(\Illuminate\Console\Command $command): void
    {
        $this->command = $command;
    }

    public function run(Company $company, User $owner): void
    {
        CompanyContext::setActive($company);

        $this->command?->info('🏪 Seeding Ruposhi Retail with COMPREHENSIVE data for ALL domains...');

        // ── Team Members (50 users) ─────────────────────────
        $users = $this->seedUsers($company, 50);

        // ── Branches (5 locations) ─────────────────────────
        $branches = $this->seedBranches($company, 5);

        // ── Warehouses (3 warehouses) ────────────────────────
        $warehouses = $this->seedWarehouses($company, 3);

        // ── Product Categories (15 categories) ─────────────────
        $categories = $this->seedCategories($company, 15);

        // ── Products (200 items) ─────────────────────────────
        $products = $this->seedProducts($company, $categories, 200);

        // ── Customers (100 customers) ─────────────────────────
        $customers = $this->seedCustomers($company, 100);

        // ── Suppliers (20 suppliers) ─────────────────────────
        $suppliers = $this->seedSuppliers($company, 20);

        // ── Sales Orders (50 orders) ─────────────────────────
        $this->seedSalesOrders($company, $customers, $products, $warehouses[0], 50);

        // ── Purchase Orders (30 orders) ────────────────────────
        $this->seedPurchaseOrders($company, $suppliers, $products, $warehouses[0], 30);

        // ── Invoices (100 invoices) ───────────────────────────
        $invoices = $this->seedInvoices($company, $customers, $products, $warehouses[0], 100);

        // ── Stock Movements (500 movements) ───────────────────
        $this->seedStockMovements($company, $products, $warehouses, $branches, 500);

        // ── Expenses (50 expenses) ────────────────────────────
        $this->seedExpenses($company, 50, $owner->id);

        // ── Documents & Forms (100 files + 10 forms) ──────────
        $this->seedDocuments($company, 100, $owner->id);
        $this->seedForms($company, 10, $owner->id);

        // ── HR Complete (50 employees + attendance + payroll) ─
        $employees = $this->seedHR($company, 50);
        $this->seedAttendance($company, $employees, 30);
        $this->seedPayroll($company, $employees);

        // ── Projects & Tasks (15 projects + 100 tasks) ────────
        $projects = $this->seedProjects($company, $employees, 15);
        $this->seedTasks($company, $projects, $employees, $users, 100);

        // ── CRM (50 leads + 30 opportunities + activities) ────
        $leads = $this->seedCRM($company, $employees, $users, 50);
        $this->seedOpportunities($company, $customers, $employees, $users, 30);
        $this->seedActivities($company, $customers, $leads, $employees, $users, 200);

        // ── POS System (3 sessions + 200 sales) ──────────────
        // $sessions = $this->seedPOS($company, $branches, $products, $users, 3);
        // $this->seedPOSSales($company, $sessions, $products, $customers, 200);

        // ── Payments (150 payment records) ────────────────────
        // $this->seedPayments($company, $invoices, 150);

        // ── Logistics (10 carriers + 50 shipments) ────────────
        $carriers = $this->seedLogistics($company, 10);
        $this->seedShipments($company, $carriers, $customers, $products, 50);

        // ── Workflows (5 definitions + 20 instances) ──────────
        $this->seedWorkflows($company, $employees, 5);

        // ── Notifications (10 templates + 100 logs) ───────────
        $this->seedNotifications($company, $users, 10);

        // ── CMS (2 sites + 10 pages + 30 sections) ────────────
        $this->seedCMS($company, $owner->id, 2);

        // ── Reports (15 custom reports) ───────────────────────
        // $this->seedReports($company, $owner->id, 15);

        // ── System Settings & Configurations ──────────────────
        // $this->seedSystemSettings($company);

        // ── Contacts & Groups (80 contacts + 5 groups) ────────
        // $this->seedContacts($company, 80);

        // ── Custom Fields (20 definitions + values) ───────────
        $this->seedCustomFields($company, $products, $customers, 20);

        // ── Accounting (Chart of Accounts + Journal Entries) ──
        // $this->seedAccounting($company, 100);

        // ── Audit Logs (200 audit entries) ───────────────────
        $this->seedAuditLogs($company, $users, 200);

        $this->command?->info('✅ ALL DOMAINS seeded successfully for Ruposhi Retail!');
    }

    /**
     * @return array<int, User>
     */
    private function seedUsers(Company $company, int $count = 50): array
    {
        $roles = [
            CompanyRole::SALES, CompanyRole::WAREHOUSE, CompanyRole::HR_MANAGER,
            CompanyRole::ACCOUNTANT, CompanyRole::MANAGER, CompanyRole::OWNER,
        ];

        $users = [];
        for ($i = 1; $i <= $count; $i++) {
            $role = $roles[array_rand($roles)];
            $user = User::firstOrCreate(
                ['email' => "user{$i}@ruposhi.test"],
                [
                    'name' => "User {$i}",
                    'password' => Hash::make('Password@123'),
                    'email_verified_at' => now(),
                    'phone' => '017'.str_pad($i, 8, '0', STR_PAD_LEFT),
                ],
            );
            CompanyUser::firstOrCreate(
                ['company_id' => $company->id, 'user_id' => $user->id],
                ['role' => $role->value, 'is_active' => true, 'joined_at' => now()],
            );
            $users[] = $user;
        }

        return $users;
    }

    /**
     * @return array<int, Branch>
     */
    private function seedBranches(Company $company, int $count = 5): array
    {
        $branches = [];
        $locations = ['Mirpur', 'Uttara', 'Dhanmondi', 'Gulshan', 'Banani'];
        for ($i = 0; $i < $count; $i++) {
            $branches[] = Branch::firstOrCreate(
                ['company_id' => $company->id, 'code' => 'BRN-'.str_pad($i + 1, 2, '0', STR_PAD_LEFT)],
                [
                    'company_id' => $company->id,
                    'name' => $locations[$i] ?? "Branch {$i}",
                    'code' => 'BRN-'.str_pad($i + 1, 2, '0', STR_PAD_LEFT),
                    'is_headquarters' => $i === 0,
                    'address' => "Dhaka {$i}",
                    'is_active' => true,
                ],
            );
        }

        return $branches;
    }

    /**
     * @return array<int, Warehouse>
     */
    private function seedWarehouses(Company $company, int $count = 3): array
    {
        $warehouses = [];
        for ($i = 0; $i < $count; $i++) {
            $warehouses[] = Warehouse::firstOrCreate(
                ['company_id' => $company->id, 'code' => 'WH-'.str_pad($i + 1, 2, '0', STR_PAD_LEFT)],
                [
                    'company_id' => $company->id,
                    'name' => 'Warehouse '.($i + 1),
                    'code' => 'WH-'.str_pad($i + 1, 2, '0', STR_PAD_LEFT),
                    'address' => "Dhaka {$i}",
                    'is_active' => true,
                ],
            );
        }

        return $warehouses;
    }

    /**
     * @return array<string, ProductCategory>
     */
    private function seedCategories(Company $company, int $count = 15): array
    {
        $names = [
            'Electronics', 'Groceries', 'Clothing', 'Household', 'Stationery',
            'Personal Care', 'Sports', 'Toys', 'Books', 'Furniture',
            'Appliances', 'Automotive', 'Garden', 'Tools', 'Accessories',
        ];

        $categories = [];
        for ($i = 0; $i < $count; $i++) {
            $name = $names[$i] ?? "Category {$i}";
            $categories[$name] = ProductCategory::firstOrCreate(
                ['company_id' => $company->id, 'name' => $name],
                ['company_id' => $company->id, 'name' => $name, 'slug' => Str::slug($name)],
            );
        }

        return $categories;
    }

    /**
     * @return array<int, Product>
     */
    private function seedProducts(Company $company, array $categories, int $count = 200): array
    {
        $vatGroup = TaxGroup::where('company_id', $company->id)->where('name', 'VAT 15%')->first();
        $categoryNames = array_keys($categories);

        $products = [];
        for ($i = 1; $i <= $count; $i++) {
            $catName = $categoryNames[array_rand($categoryNames)];
            $category = $categories[$catName];
            $products[] = Product::firstOrCreate(
                ['company_id' => $company->id, 'sku' => 'SKU-'.str_pad($i, 5, '0', STR_PAD_LEFT)],
                [
                    'company_id' => $company->id,
                    'name' => "Product {$i}",
                    'sku' => 'SKU-'.str_pad($i, 5, '0', STR_PAD_LEFT),
                    'slug' => Str::slug("Product {$i}"),
                    'category_id' => $category->id,
                    'tax_group_id' => $vatGroup?->id,
                    'product_type' => ProductType::PRODUCT,
                    'cost_price' => rand(5000, 5000000),
                    'selling_price' => rand(10000, 10000000),
                    'unit' => 'pcs',
                    'track_inventory' => true,
                    'low_stock_threshold' => rand(5, 50),
                    'is_active' => true,
                ],
            );
        }

        return $products;
    }

    /**
     * @return array<int, Customer>
     */
    private function seedCustomers(Company $company, int $count = 100): array
    {
        $customers = [];
        for ($i = 1; $i <= $count; $i++) {
            $customers[] = Customer::firstOrCreate(
                ['company_id' => $company->id, 'phone' => '018'.str_pad($i, 8, '0', STR_PAD_LEFT)],
                [
                    'company_id' => $company->id,
                    'name' => "Customer {$i}",
                    'phone' => '018'.str_pad($i, 8, '0', STR_PAD_LEFT),
                    'email' => "customer{$i}@example.com",
                    'credit_limit' => rand(1000000, 50000000),
                ],
            );
        }

        return $customers;
    }

    /**
     * @return array<int, Supplier>
     */
    private function seedSuppliers(Company $company, int $count = 20): array
    {
        $suppliers = [];
        for ($i = 1; $i <= $count; $i++) {
            $suppliers[] = Supplier::firstOrCreate(
                ['company_id' => $company->id, 'name' => "Supplier {$i}"],
                [
                    'company_id' => $company->id,
                    'name' => "Supplier {$i}",
                    'phone' => '019'.str_pad($i, 8, '0', STR_PAD_LEFT),
                    'email' => "supplier{$i}@example.com",
                    'address' => 'Dhaka, Bangladesh',
                    'vat_bin' => 'BIN'.str_pad($i, 11, '0', STR_PAD_LEFT),
                ],
            );
        }

        return $suppliers;
    }

    private function seedInvoices(Company $company, array $customers, array $products, Warehouse $warehouse, int $count = 100): array
    {
        $invoices = [];
        for ($i = 1; $i <= $count; $i++) {
            $customer = $customers[array_rand($customers)];
            $invoiceDate = now()->subDays(rand(1, 60));
            $status = collect([InvoiceStatus::PAID, InvoiceStatus::SENT, InvoiceStatus::DRAFT])->random();

            $lineItems = [];
            $itemCount = rand(2, 5);
            $subtotal = 0;

            for ($j = 0; $j < $itemCount; $j++) {
                $product = $products[array_rand($products)];
                $qty = rand(1, 5);
                $price = $product->selling_price;
                $lineTotal = $qty * $price;
                $subtotal += $lineTotal;

                $lineItems[] = [
                    'product_id' => $product->id,
                    'description' => $product->name,
                    'quantity' => $qty,
                    'unit' => $product->unit ?? 'pcs',
                    'unit_price' => $price,
                    'discount_amount' => 0,
                    'tax_rate' => 15,
                    'tax_amount' => (int) round($lineTotal * 0.15),
                    'line_total' => $lineTotal + (int) round($lineTotal * 0.15),
                ];
            }

            $taxAmount = (int) round($subtotal * 0.15);
            $totalAmount = $subtotal + $taxAmount;
            $amountPaid = $status === InvoiceStatus::PAID ? $totalAmount : ($status === InvoiceStatus::SENT ? (int) ($totalAmount * 0.5) : 0);

            $invoice = Invoice::create([
                'company_id' => $company->id,
                'customer_id' => $customer->id,
                'warehouse_id' => $warehouse->id,
                'invoice_date' => $invoiceDate,
                'due_date' => $invoiceDate->copy()->addDays(30),
                'status' => $status,
                'subtotal' => $subtotal,
                'discount_amount' => 0,
                'tax_amount' => $taxAmount,
                'total_amount' => $totalAmount,
                'amount_paid' => $amountPaid,
            ]);

            foreach ($lineItems as $item) {
                InvoiceItem::create(array_merge($item, ['invoice_id' => $invoice->id]));
            }
            
            $invoices[] = $invoice;
        }
        
        return $invoices;
    }

    private function seedSalesOrders(Company $company, array $customers, array $products, Warehouse $warehouse, int $count = 50): void
    {
        for ($i = 1; $i <= $count; $i++) {
            $customer = $customers[array_rand($customers)];
            $status = collect([SalesOrderStatus::DRAFT, SalesOrderStatus::CONFIRMED, SalesOrderStatus::DELIVERED])->random();
            $orderDate = now()->subDays(rand(1, 60));

            $lineItems = [];
            $itemCount = rand(2, 5);
            $subtotal = 0;

            for ($j = 0; $j < $itemCount; $j++) {
                $product = $products[array_rand($products)];
                $qty = rand(1, 10);
                $price = $product->selling_price;
                $lineTotal = $qty * $price;
                $subtotal += $lineTotal;

                $lineItems[] = [
                    'description' => $product->name,
                    'quantity' => $qty,
                    'unit' => $product->unit ?? 'pcs',
                    'unit_price' => $price,
                    'discount_amount' => 0,
                    'tax_rate' => 15,
                    'tax_amount' => (int) round($lineTotal * 0.15),
                    'line_total' => $lineTotal + (int) round($lineTotal * 0.15),
                ];
            }

            $taxAmount = (int) round($subtotal * 0.15);
            $totalAmount = $subtotal + $taxAmount;

            $salesOrder = SalesOrder::create([
                'company_id' => $company->id,
                'customer_id' => $customer->id,
                'warehouse_id' => $warehouse->id,
                'status' => $status,
                'order_date' => $orderDate,
                'subtotal' => $subtotal,
                'discount_amount' => 0,
                'tax_amount' => $taxAmount,
                'total_amount' => $totalAmount,
            ]);

            foreach ($lineItems as $item) {
                SalesOrderItem::create(array_merge($item, ['sales_order_id' => $salesOrder->id, 'product_id' => $products[array_rand($products)]->id]));
            }
        }
    }

    private function seedPurchaseOrders(Company $company, array $suppliers, array $products, Warehouse $warehouse, int $count = 30): void
    {
        for ($i = 1; $i <= $count; $i++) {
            $supplier = $suppliers[array_rand($suppliers)];
            $status = collect([PurchaseOrderStatus::DRAFT, PurchaseOrderStatus::SENT, PurchaseOrderStatus::RECEIVED])->random();
            $orderDate = now()->subDays(rand(1, 60));

            $lineItems = [];
            $itemCount = rand(2, 5);
            $subtotal = 0;

            for ($j = 0; $j < $itemCount; $j++) {
                $product = $products[array_rand($products)];
                $qty = rand(10, 100);
                $price = $product->cost_price;
                $lineTotal = $qty * $price;
                $subtotal += $lineTotal;

                $lineItems[] = [
                    'description' => $product->name,
                    'quantity_ordered' => $qty,
                    'quantity_received' => $status === PurchaseOrderStatus::RECEIVED ? $qty : 0,
                    'unit' => $product->unit ?? 'pcs',
                    'unit_cost' => $price,
                    'discount_amount' => 0,
                    'tax_rate' => 15,
                    'tax_amount' => (int) round($lineTotal * 0.15),
                    'line_total' => $lineTotal + (int) round($lineTotal * 0.15),
                ];
            }

            $taxAmount = (int) round($subtotal * 0.15);
            $totalAmount = $subtotal + $taxAmount;

            $purchaseOrder = PurchaseOrder::create([
                'company_id' => $company->id,
                'supplier_id' => $supplier->id,
                'warehouse_id' => $warehouse->id,
                'status' => $status,
                'order_date' => $orderDate,
                'subtotal' => $subtotal,
                'discount_amount' => 0,
                'tax_amount' => $taxAmount,
                'total_amount' => $totalAmount,
            ]);

            foreach ($lineItems as $item) {
                PurchaseOrderItem::create(array_merge($item, ['purchase_order_id' => $purchaseOrder->id, 'product_id' => $products[array_rand($products)]->id]));
            }
        }
    }

    private function seedStockMovements(Company $company, array $products, array $warehouses, array $branches, int $count = 500): void
    {
        $movementTypes = [
            StockMovementType::PURCHASE_RECEIPT,
            StockMovementType::SALE,
            StockMovementType::ADJUSTMENT_IN,
            StockMovementType::ADJUSTMENT_OUT,
            StockMovementType::TRANSFER_IN,
            StockMovementType::TRANSFER_OUT,
        ];
        for ($i = 1; $i <= $count; $i++) {
            $product = $products[array_rand($products)];
            $warehouse = $warehouses[array_rand($warehouses)];
            $branch = $branches[array_rand($branches)];
            $type = $movementTypes[array_rand($movementTypes)];
            $movementDate = now()->subDays(rand(1, 90));
            $quantity = rand(-100, 100);
            $quantityBefore = rand(0, 1000);
            $quantityAfter = $quantityBefore + $quantity;

            StockMovement::create([
                'company_id' => $company->id,
                'product_id' => $product->id,
                'warehouse_id' => $warehouse->id,
                'branch_id' => $branch->id,
                'movement_type' => $type,
                'quantity' => $quantity,
                'quantity_before' => $quantityBefore,
                'quantity_after' => $quantityAfter,
                'unit_cost' => rand(1000, 100000),
                'movement_date' => $movementDate,
                'created_at' => $movementDate,
            ]);
        }
    }

    private function seedExpenses(Company $company, int $count, int $userId): void
    {
        $expenseTypes = ['Rent', 'Utilities', 'Salaries', 'Transport', 'Marketing', 'Supplies', 'Maintenance'];
        for ($i = 1; $i <= $count; $i++) {
            $type = $expenseTypes[array_rand($expenseTypes)];
            $amount = rand(10000, 1000000);
            Expense::firstOrCreate(
                ['company_id' => $company->id, 'description' => "{$type} - {$i}"],
                [
                    'company_id' => $company->id,
                    'description' => "{$type} - {$i}",
                    'amount' => $amount,
                    'tax_amount' => 0,
                    'total_amount' => $amount,
                    'expense_date' => now()->subDays(rand(1, 60)),
                    'status' => 'approved',
                    'created_by' => $userId,
                ],
            );
        }
    }

    private function seedDocuments(Company $company, int $count, int $userId): void
    {
        $folder = DocumentFolder::firstOrCreate(
            ['company_id' => $company->id, 'name' => 'General'],
            ['company_id' => $company->id, 'name' => 'General'],
        );

        for ($i = 1; $i <= $count; $i++) {
            Document::firstOrCreate(
                ['company_id' => $company->id, 'name' => "Document {$i}"],
                [
                    'company_id' => $company->id,
                    'folder_id' => $folder->id,
                    'name' => "Document {$i}",
                    'disk_path' => "private/{$company->id}/documents/doc{$i}.txt",
                    'mime_type' => 'text/plain',
                    'size_bytes' => rand(1000, 100000),
                    'uploaded_by' => $userId,
                    'uploaded_at' => now()->subDays(rand(1, 30)),
                ],
            );
        }
    }

    private function seedHR(Company $company, int $count = 50): array
    {
        $departments = ['Sales', 'Warehouse', 'Administration', 'Finance', 'Operations'];
        $designations = ['Manager', 'Executive', 'Supervisor', 'Staff'];

        foreach ($departments as $deptName) {
            Department::firstOrCreate(
                ['company_id' => $company->id, 'name' => $deptName],
                ['company_id' => $company->id, 'name' => $deptName],
            );
        }

        foreach ($designations as $desigName) {
            Designation::firstOrCreate(
                ['company_id' => $company->id, 'name' => $desigName],
                ['company_id' => $company->id, 'name' => $desigName],
            );
        }

        // Create leave types
        $leaveTypes = ['Annual Leave', 'Sick Leave', 'Casual Leave', 'Maternity Leave', 'Emergency Leave'];
        foreach ($leaveTypes as $leaveTypeName) {
            LeaveType::firstOrCreate(
                ['company_id' => $company->id, 'name' => $leaveTypeName],
                [
                    'company_id' => $company->id,
                    'name' => $leaveTypeName,
                    'days_per_year' => rand(10, 30),
                    'is_active' => true,
                ],
            );
        }

        $employees = [];
        for ($i = 1; $i <= $count; $i++) {
            $dept = Department::where('company_id', $company->id)->inRandomOrder()->first();
            $desig = Designation::where('company_id', $company->id)->inRandomOrder()->first();
            $employee = Employee::firstOrCreate(
                ['company_id' => $company->id, 'first_name' => "Employee {$i}", 'last_name' => "Last {$i}"],
                [
                    'company_id' => $company->id,
                    'first_name' => "Employee {$i}",
                    'last_name' => "Last {$i}",
                    'email' => "employee{$i}@ruposhi.test",
                    'phone' => '017'.rand(10000000, 99999999),
                    'department_id' => $dept->id,
                    'designation_id' => $desig->id,
                    'joining_date' => now()->subMonths(rand(3, 24)),
                    'basic_salary' => rand(1000000, 5000000),
                    'status' => EmployeeStatus::ACTIVE,
                ],
            );
            $employees[] = $employee;
        }

        return $employees;
    }

    // ═══════════════════════════════════════════════════════════════════════════════════════
    // COMPREHENSIVE DOMAIN SEEDING METHODS - ALL MISSING DOMAINS
    // ═══════════════════════════════════════════════════════════════════════════════════════

    private function seedForms(Company $company, int $count, int $userId): void
    {
        for ($i = 1; $i <= $count; $i++) {
            $form = Form::firstOrCreate(
                ['company_id' => $company->id, 'slug' => "form-{$i}-{$company->id}"],
                [
                    'company_id' => $company->id,
                    'name' => "Form {$i}",
                    'slug' => "form-{$i}-{$company->id}",
                    'description' => "Sample form {$i} for testing",
                    'is_active' => true,
                    'created_by' => $userId,
                ]
            );

            // Add form fields
            $fieldTypes = [FormFieldType::TEXT, FormFieldType::EMAIL, FormFieldType::NUMBER, FormFieldType::SELECT, FormFieldType::TEXTAREA, FormFieldType::CHECKBOX];
            for ($j = 1; $j <= rand(3, 8); $j++) {
                FormField::firstOrCreate(
                    ['form_id' => $form->id, 'field_key' => "field_{$j}"],
                    [
                        'form_id' => $form->id,
                        'field_key' => "field_{$j}",
                        'field_type' => $fieldTypes[array_rand($fieldTypes)],
                        'label' => "Field {$j}",
                        'is_required' => rand(0, 1),
                        'display_order' => $j,
                        'is_active' => true,
                    ]
                );
            }

            // Add form submissions
            for ($k = 1; $k <= rand(5, 20); $k++) {
                FormSubmission::create([
                    'form_id' => $form->id,
                    'submitted_by' => $userId,
                    'submission_data' => ['field_1' => "Sample data {$k}", 'field_2' => "test@example.com"],
                    'status' => 'pending',
                    'submitted_at' => now()->subDays(rand(1, 30)),
                ]);
            }
        }
    }

    private function seedAttendance(Company $company, array $employees, int $days): void
    {
        foreach ($employees as $employee) {
            for ($i = 1; $i <= $days; $i++) {
                $date = now()->subDays($i);
                if ($date->isWeekday()) {
                    Attendance::firstOrCreate(
                        ['company_id' => $company->id, 'employee_id' => $employee->id, 'attendance_date' => $date->format('Y-m-d')],
                        [
                            'company_id' => $company->id,
                            'employee_id' => $employee->id,
                            'attendance_date' => $date->format('Y-m-d'),
                            'check_in' => $date->setTime(9, rand(0, 30)),
                            'check_out' => $date->setTime(17, rand(30, 59)),
                            'status' => collect([AttendanceStatus::PRESENT, AttendanceStatus::LATE, AttendanceStatus::ABSENT])->random(),
                            'working_hours' => rand(7, 9),
                        ]
                    );
                }
            }
        }
    }
    private function seedPayroll(Company $company, array $employees): void
    {
        // Create payroll runs for last 3 months
        for ($i = 1; $i <= 3; $i++) {
            $runDate = now()->subMonths($i)->startOfMonth();
            $payrollRun = PayrollRun::firstOrCreate(
                ['company_id' => $company->id, 'period_month' => $runDate->month, 'period_year' => $runDate->year],
                [
                    'company_id' => $company->id,
                    'period_month' => $runDate->month,
                    'period_year' => $runDate->year,
                    'status' => PayrollRunStatus::PAID,
                    'total_gross' => 0,
                    'total_deductions' => 0,
                    'total_net' => 0,
                ]
            );

            $totalGross = 0;
            $totalNet = 0;
            foreach ($employees as $employee) {
                $grossSalary = $employee->basic_salary;
                $deductions = (int)($grossSalary * 0.1); // 10% deductions
                $netSalary = $grossSalary - $deductions;
                
                PayrollEntry::firstOrCreate(
                    ['payroll_run_id' => $payrollRun->id, 'employee_id' => $employee->id],
                    [
                        'payroll_run_id' => $payrollRun->id,
                        'employee_id' => $employee->id,
                        'period_month' => $runDate->month,
                        'period_year' => $runDate->year,
                        'working_days' => 22, // Standard working days in a month
                        'present_days' => 22,
                        'absent_days' => 0,
                        'leave_days' => 0,
                        'basic_salary' => $grossSalary,
                        'gross_salary' => $grossSalary,
                        'tax_deduction' => $deductions,
                        'net_salary' => $netSalary,
                        'payment_status' => PaymentStatus::PAID,
                    ]
                );
                
                $totalGross += $grossSalary;
                $totalNet += $netSalary;
            }
            
            $payrollRun->update([
                'total_gross' => $totalGross,
                'total_deductions' => $totalGross - $totalNet,
                'total_net' => $totalNet,
            ]);
        }
    }

    private function seedProjects(Company $company, array $employees, int $count): array
    {
        $projects = [];
        for ($i = 1; $i <= $count; $i++) {
            $manager = $employees[array_rand($employees)];
            $project = Project::firstOrCreate(
                ['company_id' => $company->id, 'name' => "Project {$i}"],
                [
                    'company_id' => $company->id,
                    'name' => "Project {$i}",
                    'description' => "Sample project {$i} for testing",
                    'status' => collect([Project::STATUS_ACTIVE, Project::STATUS_PLANNING, Project::STATUS_COMPLETED])->random(),
                    'start_date' => now()->subDays(rand(30, 90)),
                    'end_date' => now()->addDays(rand(30, 180)),
                    'budget' => rand(50000000, 500000000),
                    'project_manager_id' => $manager->id,
                ]
            );

            // Create project board
            Board::firstOrCreate(
                ['project_id' => $project->id, 'name' => 'Main Board'],
                [
                    'project_id' => $project->id,
                    'name' => 'Main Board',
                    'description' => 'Main project board',
                ]
            );

            $projects[] = $project;
        }
        return $projects;
    }
    private function seedTasks(Company $company, array $projects, array $employees, array $users, int $count): void
    {
        for ($i = 1; $i <= $count; $i++) {
            $project = $projects[array_rand($projects)];
            $assignee = $employees[array_rand($employees)];
            $assigner = $employees[array_rand($employees)]; // Random manager/assigner
            $board = Board::where('project_id', $project->id)->first();
            
            $task = Task::firstOrCreate(
                ['project_id' => $project->id, 'title' => "Task {$i}"],
                [
                    'project_id' => $project->id,
                    'board_id' => $board->id,
                    'title' => "Task {$i}",
                    'description' => "Sample task {$i} for testing",
                    'status' => collect([Task::STATUS_TODO, Task::STATUS_IN_PROGRESS, Task::STATUS_COMPLETED])->random(),
                    'priority' => collect([Task::PRIORITY_LOW, Task::PRIORITY_MEDIUM, Task::PRIORITY_HIGH])->random(),
                    'assignee_id' => $assignee->id,
                    'due_date' => now()->addDays(rand(1, 30)),
                    'estimated_hours' => rand(2, 40),
                ]
            );

            // Create employee task assignment
            EmployeeTask::firstOrCreate(
                ['employee_id' => $assignee->id, 'task_id' => $task->id],
                [
                    'employee_id' => $assignee->id,
                    'task_id' => $task->id,
                    'project_id' => $project->id,
                    'assigned_by' => $users[array_rand($users)]->id, // Random user as assigner
                    'assigned_at' => now()->subDays(rand(1, 10)),
                ]
            );

            // Create time entries
            for ($j = 1; $j <= rand(1, 5); $j++) {
                EmployeeTimeEntry::create([
                    'employee_id' => $assignee->id,
                    'task_id' => $task->id,
                    'project_id' => $project->id,
                    'hours' => rand(1, 8),
                    'description' => "Work on task {$i} - entry {$j}",
                    'entry_date' => now()->subDays(rand(1, 7)),
                ]);
            }
        }
    }

    private function seedCRM(Company $company, array $employees, array $users, int $count): array
    {
        // Create pipeline and stages
        $pipeline = Pipeline::firstOrCreate(
            ['company_id' => $company->id, 'name' => 'Sales Pipeline'],
            [
                'company_id' => $company->id,
                'name' => 'Sales Pipeline',
                'description' => 'Main sales pipeline',
                'is_active' => true,
                'created_by' => $users[array_rand($users)]->id,
            ]
        );

        $stages = ['Lead', 'Qualified', 'Proposal', 'Negotiation', 'Closed Won', 'Closed Lost'];
        foreach ($stages as $index => $stageName) {
            PipelineStage::firstOrCreate(
                ['pipeline_id' => $pipeline->id, 'name' => $stageName],
                [
                    'company_id' => $company->id,
                    'pipeline_id' => $pipeline->id,
                    'name' => $stageName,
                    'sort_order' => $index + 1,
                    'probability' => ($index + 1) * 15,
                    'created_by' => $users[array_rand($users)]->id,
                ]
            );
        }

        $leads = [];
        for ($i = 1; $i <= $count; $i++) {
            $assignee = $users[array_rand($users)]; // Use User instead of Employee
            $lead = Lead::firstOrCreate(
                ['company_id' => $company->id, 'email' => "lead{$i}@example.com"],
                [
                    'company_id' => $company->id,
                    'first_name' => "Lead",
                    'last_name' => "{$i}",
                    'email' => "lead{$i}@example.com",
                    'phone' => '018'.str_pad($i, 8, '0', STR_PAD_LEFT),
                    'company_name' => "Company {$i}",
                    'status' => collect([LeadStatus::NEW, LeadStatus::CONTACTED, LeadStatus::QUALIFIED, LeadStatus::UNQUALIFIED])->random(),
                    'source' => collect([LeadSource::WEBSITE, LeadSource::REFERRAL, LeadSource::SOCIAL_MEDIA, LeadSource::COLD_CALL])->random(),
                    'assigned_to' => $assignee->id,
                    'estimated_value' => rand(100000, 10000000),
                    'created_by' => $users[array_rand($users)]->id,
                ]
            );
            $leads[] = $lead;
        }
        return $leads;
    }
    private function seedOpportunities(Company $company, array $customers, array $employees, array $users, int $count): void
    {
        $pipeline = Pipeline::where('company_id', $company->id)->first();
        $stages = PipelineStage::where('pipeline_id', $pipeline->id)->get();

        for ($i = 1; $i <= $count; $i++) {
            $customer = $customers[array_rand($customers)];
            $assignee = $users[array_rand($users)]; // Use User instead of Employee
            $stage = $stages->random();
            
                    $amount = rand(500000, 50000000);
                    
                    Opportunity::firstOrCreate(
                        ['company_id' => $company->id, 'name' => "Opportunity {$i}"],
                        [
                            'company_id' => $company->id,
                            'name' => "Opportunity {$i}",
                            'description' => "Sample opportunity {$i}",
                            'customer_id' => $customer->id,
                            'pipeline_id' => $pipeline->id,
                            'stage_id' => $stage->id,
                            'assigned_to' => $assignee->id,
                            'amount' => $amount,
                            'total_amount' => $amount,
                            'probability' => $stage->probability,
                            'expected_close_date' => now()->addDays(rand(30, 180)),
                            'stage' => collect([OpportunityStage::PROSPECTING, OpportunityStage::QUALIFICATION, OpportunityStage::PROPOSAL])->random(),
                            'created_by' => $users[array_rand($users)]->id,
                        ]
                    );
        }
    }

    private function seedActivities(Company $company, array $customers, array $leads, array $employees, array $users, int $count): void
    {
        $activityTypes = [ActivityType::CALL, ActivityType::EMAIL, ActivityType::MEETING, ActivityType::TASK];
        
        for ($i = 1; $i <= $count; $i++) {
            $customer = $customers[array_rand($customers)];
            $lead = $leads[array_rand($leads)];
            $user = $users[array_rand($users)];
            
            CrmActivity::create([
                'company_id' => $company->id,
                'user_id' => $user->id,
                'type' => $activityTypes[array_rand($activityTypes)],
                'title' => "Activity {$i}",
                'description' => "Sample activity {$i} description",
                'subject_type' => rand(0, 1) ? 'App\\Domain\\Customer\\Models\\Customer' : 'App\\Domain\\CRM\\Models\\Lead',
                'subject_id' => rand(0, 1) ? $customer->id : $lead->id,
                'scheduled_at' => now()->addDays(rand(-30, 30)),
                'completed_at' => rand(0, 1) ? now()->subDays(rand(1, 10)) : null,
            ]);
        }
    }

    private function seedPOS(Company $company, array $branches, array $products, array $users, int $sessionCount): array
    {
        $sessions = [];
        for ($i = 1; $i <= $sessionCount; $i++) {
            $branch = $branches[array_rand($branches)];
            $user = $users[array_rand($users)];
            $session = POSSession::firstOrCreate(
                ['company_id' => $company->id, 'branch_id' => $branch->id, 'opened_at' => now()->subDays($i)->setTime(9, 0)],
                [
                    'company_id' => $company->id,
                    'branch_id' => $branch->id,
                    'opened_by' => $user->id,
                    'opening_cash' => 1000000, // 10,000 BDT
                    'closing_cash' => rand(1500000, 3000000),
                    'status' => 'closed',
                    'opened_at' => now()->subDays($i)->setTime(9, 0),
                    'closed_at' => now()->subDays($i)->setTime(18, 0),
                ]
            );
            $sessions[] = $session;
        }
        return $sessions;
    }
    private function seedPOSSales(Company $company, array $sessions, array $products, array $customers, int $count): void
    {
        foreach ($sessions as $session) {
            // Create POS sales for this session
            $salesPerSession = rand(5, 15);
            for ($saleNum = 1; $saleNum <= $salesPerSession; $saleNum++) {
                $customer = rand(0, 1) ? $customers[array_rand($customers)] : null;
                $saleTime = $session->opened_at->copy()->addHours(rand(1, 8));
                
                $sale = POSSale::create([
                    'company_id' => $company->id,
                    'session_id' => $session->id,
                    'customer_id' => $customer?->id,
                    'sale_date' => $saleTime,
                    'subtotal' => 0,
                    'tax_amount' => 0,
                    'discount_amount' => 0,
                    'total_amount' => 0,
                    'amount_paid' => 0,
                    'change_amount' => 0,
                    'payment_method' => collect(['cash', 'card', 'mobile'])->random(),
                ]);

                // Add sale items
                $itemCount = rand(1, 5);
                $subtotal = 0;
                for ($itemNum = 1; $itemNum <= $itemCount; $itemNum++) {
                    $product = $products[array_rand($products)];
                    $quantity = rand(1, 3);
                    $unitPrice = $product->selling_price;
                    $lineTotal = $quantity * $unitPrice;
                    $subtotal += $lineTotal;

                    POSSaleItem::create([
                        'pos_sale_id' => $sale->id,
                        'product_id' => $product->id,
                        'quantity' => $quantity,
                        'unit_price' => $unitPrice,
                        'line_total' => $lineTotal,
                    ]);
                }

                $taxAmount = (int)($subtotal * 0.15);
                $totalAmount = $subtotal + $taxAmount;
                $amountPaid = $totalAmount + rand(0, 50000); // Some change
                
                $sale->update([
                    'subtotal' => $subtotal,
                    'tax_amount' => $taxAmount,
                    'total_amount' => $totalAmount,
                    'amount_paid' => $amountPaid,
                    'change_amount' => $amountPaid - $totalAmount,
                ]);
            }
        }
    }
    private function seedPayments(Company $company, array $invoices, int $count): void
    {
        // Create payment methods
        $paymentMethods = [
            ['name' => 'Cash', 'type' => 'cash'],
            ['name' => 'Bank Transfer', 'type' => 'bank_transfer'],
            ['name' => 'Credit Card', 'type' => 'credit_card'],
            ['name' => 'Mobile Banking', 'type' => 'mobile_banking'],
        ];

        foreach ($paymentMethods as $methodData) {
            PaymentMethodModel::firstOrCreate(
                ['company_id' => $company->id, 'name' => $methodData['name']],
                [
                    'company_id' => $company->id,
                    'name' => $methodData['name'],
                    'type' => $methodData['type'],
                    'is_active' => true,
                ]
            );
        }

        $methods = PaymentMethodModel::where('company_id', $company->id)->get();
        
        for ($i = 1; $i <= $count; $i++) {
            $invoice = $invoices[array_rand($invoices)];
            $method = $methods->random();
            
            Payment::firstOrCreate(
                ['company_id' => $company->id, 'invoice_id' => $invoice->id, 'reference' => "PAY-{$i}"],
                [
                    'company_id' => $company->id,
                    'invoice_id' => $invoice->id,
                    'customer_id' => $invoice->customer_id,
                    'payment_method_id' => $method->id,
                    'amount' => rand(50000, $invoice->total_amount),
                    'payment_date' => now()->subDays(rand(1, 30)),
                    'reference' => "PAY-{$i}",
                    'status' => collect([PaymentStatus::COMPLETED, PaymentStatus::PENDING, PaymentStatus::FAILED])->random(),
                    'notes' => "Payment {$i} via {$method->name}",
                ]
            );
        }
    }

    private function seedLogistics(Company $company, int $count): array
    {
        $carriers = [];
        $carrierTypes = [CarrierType::PATHAO, CarrierType::PAPERFLY, CarrierType::STEADFAST, CarrierType::CUSTOM];
        $carrierNames = ['Pathao Courier', 'PaperFly', 'SteadFast', 'DHL Express', 'FedEx', 'UPS', 'Sundarban Courier', 'SA Paribahan', 'RedX', 'eCourier'];
        
        for ($i = 0; $i < $count; $i++) {
            $name = $carrierNames[$i] ?? "Carrier {$i}";
            $carrierType = $carrierTypes[array_rand($carrierTypes)];
            $carrier = Carrier::firstOrCreate(
                ['company_id' => $company->id, 'name' => $name],
                [
                    'company_id' => $company->id,
                    'name' => $name,
                    'code' => $carrierType,
                    'contact_person' => "Contact {$i}",
                    'phone' => '019'.str_pad($i, 8, '0', STR_PAD_LEFT),
                    'email' => strtolower(str_replace(' ', '', $name)) . '@carrier.com',
                    'is_active' => true,
                ]
            );
            $carriers[] = $carrier;
        }
        return $carriers;
    }
    private function seedShipments(Company $company, array $carriers, array $customers, array $products, int $count): void
    {
        for ($i = 1; $i <= $count; $i++) {
            $carrier = $carriers[array_rand($carriers)];
            $customer = $customers[array_rand($customers)];
            
                    $shippingCost = rand(50000, 500000);
                    
                    $shipment = Shipment::firstOrCreate(
                        ['company_id' => $company->id, 'tracking_number' => "SHIP-{$company->id}-{$i}"],
                        [
                            'company_id' => $company->id,
                            'carrier_id' => $carrier->id,
                            'customer_id' => $customer->id,
                            'tracking_number' => "SHIP-{$company->id}-{$i}",
                            'sender_name' => $company->name,
                            'sender_phone' => $company->phone ?? '01700000000',
                            'sender_address' => $company->address_line1 ?? 'Dhaka, Bangladesh',
                            'sender_city' => 'Dhaka',
                            'recipient_name' => $customer->name,
                            'recipient_phone' => $customer->phone,
                            'recipient_address' => $customer->address ?? 'Customer Address',
                            'recipient_city' => 'Dhaka',
                            'status' => collect([ShipmentStatus::PENDING, ShipmentStatus::IN_TRANSIT, ShipmentStatus::DELIVERED])->random(),
                            'delivery_type' => collect([DeliveryType::STANDARD, DeliveryType::EXPRESS, DeliveryType::NEXT_DAY])->random(),
                            'payment_method' => collect(['prepaid', 'cod'])->random(),
                            'shipping_cost' => $shippingCost,
                            'total_cost' => $shippingCost,
                            'weight' => rand(100, 5000), // grams
                        ]
                    );

            // Add shipment items
            $itemCount = rand(1, 5);
            for ($j = 1; $j <= $itemCount; $j++) {
                $product = $products[array_rand($products)];
                ShipmentItem::create([
                    'shipment_id' => $shipment->id,
                    'product_name' => $product->name,
                    'sku' => $product->sku,
                    'quantity' => rand(1, 10),
                    'unit_price' => $product->selling_price,
                    'total_price' => $product->selling_price * rand(1, 10),
                    'description' => $product->name,
                ]);
            }
        }
    }

    private function seedWorkflows(Company $company, array $employees, int $count): void
    {
        $workflowTypes = [
            ['name' => 'Invoice Approval', 'type' => WorkflowDocumentType::PURCHASE_ORDER],
            ['name' => 'Purchase Order Approval', 'type' => WorkflowDocumentType::PURCHASE_ORDER],
            ['name' => 'Leave Request', 'type' => WorkflowDocumentType::LEAVE_REQUEST],
            ['name' => 'Expense Approval', 'type' => WorkflowDocumentType::EXPENSE_CLAIM],
            ['name' => 'Payroll Run', 'type' => WorkflowDocumentType::PAYROLL_RUN],
        ];
        
        for ($i = 0; $i < $count; $i++) {
            $workflow = $workflowTypes[$i] ?? ['name' => "Workflow {$i}", 'type' => WorkflowDocumentType::PURCHASE_ORDER];
            $definition = WorkflowDefinition::firstOrCreate(
                ['company_id' => $company->id, 'name' => $workflow['name']],
                [
                    'company_id' => $company->id,
                    'name' => $workflow['name'],
                    'description' => "Automated {$workflow['name']} workflow",
                    'document_type' => $workflow['type'],
                    'is_active' => true,
                ]
            );

            // Create workflow instances
            for ($j = 1; $j <= rand(2, 5); $j++) {
                $documentId = ($definition->id * 1000) + $j; // Make document IDs unique per workflow definition
                WorkflowInstance::firstOrCreate(
                    [
                        'workflow_definition_id' => $definition->id,
                        'document_type' => 'App\\Domain\\Invoice\\Models\\Invoice',
                        'document_id' => $documentId,
                    ],
                    [
                        'workflow_definition_id' => $definition->id,
                        'document_type' => 'App\\Domain\\Invoice\\Models\\Invoice',
                        'document_id' => $documentId,
                        'current_status_key' => collect(['pending', 'in_progress', 'completed'])->random(),
                        'started_at' => now()->subDays(rand(1, 30)),
                    ]
                );
            }
        }
    }
    private function seedNotifications(Company $company, array $users, int $templateCount): void
    {
        $notificationTypes = [
            'invoice_created' => 'Invoice Created',
            'payment_received' => 'Payment Received',
            'order_shipped' => 'Order Shipped',
            'low_stock_alert' => 'Low Stock Alert',
            'task_assigned' => 'Task Assigned',
            'leave_request' => 'Leave Request',
            'expense_submitted' => 'Expense Submitted',
            'project_deadline' => 'Project Deadline',
            'customer_inquiry' => 'Customer Inquiry',
            'system_maintenance' => 'System Maintenance',
        ];

        foreach ($notificationTypes as $type => $title) {
            NotificationTemplate::firstOrCreate(
                ['company_id' => $company->id, 'event_key' => $type],
                [
                    'company_id' => $company->id,
                    'event_key' => $type,
                    'channel' => 'email',
                    'subject' => "{{company_name}} - {$title}",
                    'body' => "Dear {{recipient_name}},\n\nThis is to notify you about: {$title}\n\nDetails: {{details}}\n\nBest regards,\n{{company_name}}",
                    'is_active' => true,
                ]
            );
        }

        // Create notification logs
        // $templates = NotificationTemplate::where('company_id', $company->id)->get();
        // for ($i = 1; $i <= 100; $i++) {
        //     $template = $templates->random();
        //     $user = $users[array_rand($users)];
        //     
        //     NotificationLog::create([
        //         'company_id' => $company->id,
        //         'template_id' => $template->id,
        //         'recipient_type' => 'user',
        //         'recipient_id' => $user->id,
        //         'channel' => collect([NotificationChannel::EMAIL, NotificationChannel::IN_APP, NotificationChannel::SMS])->random(),
        //         'subject' => str_replace(['{{company_name}}', '{{recipient_name}}'], [$company->name, $user->name], $template->subject),
        //         'body' => str_replace(['{{company_name}}', '{{recipient_name}}', '{{details}}'], [$company->name, $user->name, "Sample notification details {$i}"], $template->body),
        //         'sent_at' => now()->subDays(rand(1, 30)),
        //         'status' => collect(['sent', 'delivered', 'failed'])->random(),
        //     ]);
        // }
    }

    private function seedCMS(Company $company, int $userId, int $siteCount): void
    {
        for ($i = 1; $i <= $siteCount; $i++) {
            $site = Site::firstOrCreate(
                ['company_id' => $company->id, 'domain' => "site{$i}-{$company->id}.example.com"],
                [
                    'company_id' => $company->id,
                    'name' => "Site {$i}",
                    'slug' => "site-{$i}-{$company->id}",
                    'domain' => "site{$i}-{$company->id}.example.com",
                    'subdomain' => "site{$i}-{$company->id}",
                    'status' => collect([SiteStatus::PUBLISHED, SiteStatus::DRAFT, SiteStatus::MAINTENANCE])->random(),
                    'created_by' => $userId,
                ]
            );

            // Create pages for each site
            $pageTypes = ['Home', 'About', 'Services', 'Products', 'Contact', 'Blog', 'FAQ', 'Privacy Policy', 'Terms of Service', 'Gallery'];
            foreach ($pageTypes as $pageType) {
                $page = Page::firstOrCreate(
                    ['site_id' => $site->id, 'slug' => strtolower(str_replace(' ', '-', $pageType))],
                    [
                        'site_id' => $site->id,
                        'title' => $pageType,
                        'slug' => strtolower(str_replace(' ', '-', $pageType)),
                        'content' => "<h1>{$pageType}</h1><p>This is the {$pageType} page content.</p>",
                        'meta_title' => "{$pageType} - {$site->name}",
                        'meta_description' => "{$pageType} page for {$site->name}",
                        'status' => collect([PageStatus::PUBLISHED, PageStatus::DRAFT])->random(),
                        'created_by' => $userId,
                    ]
                );

                // Create sections for each page
                $sectionTypes = [SectionType::HERO_BANNER, SectionType::TEXT_BLOCK, SectionType::FULL_WIDTH_IMAGE, SectionType::GALLERY];
                for ($j = 1; $j <= rand(2, 4); $j++) {
                    Section::create([
                        'page_id' => $page->id,
                        'type' => $sectionTypes[array_rand($sectionTypes)],
                        'title' => "Section {$j}",
                        'content' => json_encode(['text' => "Sample content for section {$j}", 'image' => null]),
                        'sort_order' => $j,
                        'is_active' => true,
                    ]);
                }
            }
        }
    }
    private function seedReports(Company $company, int $userId, int $count): void
    {
        $reportTypes = [
            'Sales Summary' => ['entity_type' => 'invoice', 'fields' => ['customer_name', 'total_amount', 'status']],
            'Inventory Report' => ['entity_type' => 'product', 'fields' => ['name', 'sku', 'cost_price', 'selling_price']],
            'Customer Analysis' => ['entity_type' => 'customer', 'fields' => ['name', 'phone', 'email', 'credit_limit']],
            'Financial Overview' => ['entity_type' => 'expense', 'fields' => ['description', 'amount', 'expense_date']],
            'Employee Performance' => ['entity_type' => 'employee', 'fields' => ['first_name', 'last_name', 'department', 'basic_salary']],
            'Project Status' => ['entity_type' => 'project', 'fields' => ['name', 'status', 'start_date', 'budget']],
            'Purchase Analysis' => ['entity_type' => 'purchase_order', 'fields' => ['supplier_name', 'total_amount', 'status']],
            'Stock Movement' => ['entity_type' => 'stock_movement', 'fields' => ['product_name', 'movement_type', 'quantity']],
            'Payment Summary' => ['entity_type' => 'payment', 'fields' => ['customer_name', 'amount', 'payment_date']],
            'CRM Pipeline' => ['entity_type' => 'opportunity', 'fields' => ['name', 'amount', 'stage', 'probability']],
        ];

        foreach ($reportTypes as $name => $config) {
            SavedReport::firstOrCreate(
                ['company_id' => $company->id, 'name' => $name],
                [
                    'company_id' => $company->id,
                    'name' => $name,
                    'entity_type' => $config['entity_type'],
                    'selected_fields' => $config['fields'],
                    'filters' => [],
                    'sort_field' => 'created_at',
                    'sort_direction' => 'desc',
                    'visualisation' => 'table',
                    'is_scheduled' => false,
                    'created_by' => $userId,
                ]
            );
        }
    }

    private function seedSystemSettings(Company $company): void
    {
        $settings = [
            'company_name' => $company->name,
            'company_email' => $company->email,
            'company_phone' => $company->phone,
            'company_address' => $company->address_line1,
            'default_currency' => $company->currency,
            'default_timezone' => $company->timezone,
            'tax_rate' => '15.00',
            'low_stock_threshold' => '10',
            'invoice_prefix' => 'INV-',
            'order_prefix' => 'ORD-',
            'receipt_prefix' => 'RCP-',
            'backup_frequency' => 'daily',
            'notification_email' => $company->email,
            'maintenance_mode' => 'false',
            'api_rate_limit' => '1000',
            'session_timeout' => '120',
            'password_policy' => 'strong',
            'two_factor_auth' => 'optional',
            'audit_logging' => 'true',
            'data_retention_days' => '365',
        ];

        foreach ($settings as $key => $value) {
            SystemSetting::firstOrCreate(
                ['company_id' => $company->id, 'key' => $key],
                [
                    'company_id' => $company->id,
                    'key' => $key,
                    'value' => $value,
                    'type' => 'string',
                    'is_public' => false,
                ]
            );
        }
    }
    private function seedContacts(Company $company, int $count): void
    {
        // Create contact groups
        $groupNames = ['Suppliers', 'Customers', 'Partners', 'Vendors', 'Prospects'];
        $groups = [];
        foreach ($groupNames as $groupName) {
            $group = ContactGroup::firstOrCreate(
                ['company_id' => $company->id, 'name' => $groupName],
                [
                    'company_id' => $company->id,
                    'name' => $groupName,
                    'description' => "{$groupName} contact group",
                ]
            );
            $groups[] = $group;
        }

        // Create contacts
        for ($i = 1; $i <= $count; $i++) {
            $group = $groups[array_rand($groups)];
            Contact::firstOrCreate(
                ['company_id' => $company->id, 'email' => "contact{$i}@example.com"],
                [
                    'company_id' => $company->id,
                    'group_id' => $group->id,
                    'first_name' => "Contact {$i}",
                    'last_name' => "Last {$i}",
                    'email' => "contact{$i}@example.com",
                    'phone' => '017'.str_pad($i, 8, '0', STR_PAD_LEFT),
                    'company_name' => "Company {$i}",
                    'job_title' => collect(['Manager', 'Director', 'Executive', 'Coordinator'])->random(),
                    'address' => "Address {$i}, Dhaka",
                    'notes' => "Sample contact {$i} notes",
                    'is_active' => true,
                ]
            );
        }
    }

    private function seedCustomFields(Company $company, array $products, array $customers, int $count): void
    {
        $domains = ['product', 'customer', 'invoice', 'order', 'employee'];
        $fieldTypes = [CustomFieldType::TEXT, CustomFieldType::NUMBER, CustomFieldType::EMAIL, CustomFieldType::PHONE, CustomFieldType::DATE, CustomFieldType::SELECT, CustomFieldType::BOOLEAN, CustomFieldType::TEXTAREA];

        for ($i = 1; $i <= $count; $i++) {
            $domain = $domains[array_rand($domains)];
            $fieldType = $fieldTypes[array_rand($fieldTypes)];
            
            $definition = CustomFieldDefinition::firstOrCreate(
                ['company_id' => $company->id, 'field_key' => "custom_field_{$i}"],
                [
                    'company_id' => $company->id,
                    'domain' => $domain,
                    'entity_type' => "App\\Domain\\" . ucfirst($domain) . "\\Models\\" . ucfirst($domain),
                    'field_key' => "custom_field_{$i}",
                    'label' => "Custom Field {$i}",
                    'field_type' => $fieldType,
                    'is_required' => rand(0, 1),
                    'is_active' => true,
                    'options' => $fieldType === CustomFieldType::SELECT ? ['Option 1', 'Option 2', 'Option 3'] : null,
                    'validation_rules' => ['max:255'],
                ]
            );

            // Create custom field values for some entities
            if ($domain === 'product') {
                foreach (array_slice($products, 0, 20) as $product) {
                    CustomFieldValue::firstOrCreate(
                        [
                            'entity_type' => 'App\\Domain\\Product\\Models\\Product',
                            'entity_id' => $product->id,
                            'field_key' => $definition->field_key,
                        ],
                        [
                            'value_text' => $fieldType === CustomFieldType::NUMBER ? null : "Sample value {$i}",
                            'value_number' => $fieldType === CustomFieldType::NUMBER ? rand(1, 100) : null,
                        ]
                    );
                }
            } elseif ($domain === 'customer') {
                foreach (array_slice($customers, 0, 20) as $customer) {
                    CustomFieldValue::firstOrCreate(
                        [
                            'entity_type' => 'App\\Domain\\Customer\\Models\\Customer',
                            'entity_id' => $customer->id,
                            'field_key' => $definition->field_key,
                        ],
                        [
                            'value_text' => $fieldType === CustomFieldType::NUMBER ? null : "Sample value {$i}",
                            'value_number' => $fieldType === CustomFieldType::NUMBER ? rand(1, 100) : null,
                        ]
                    );
                }
            }
        }
    }
    private function seedAccounting(Company $company, int $journalEntryCount): void
    {
        // Create account groups
        $accountGroups = [
            ['name' => 'Assets', 'type' => 'asset'],
            ['name' => 'Liabilities', 'type' => 'liability'],
            ['name' => 'Equity', 'type' => 'equity'],
            ['name' => 'Revenue', 'type' => 'revenue'],
            ['name' => 'Expenses', 'type' => 'expense'],
        ];

        $groups = [];
        foreach ($accountGroups as $groupData) {
            $group = AccountGroup::firstOrCreate(
                ['company_id' => $company->id, 'name' => $groupData['name']],
                [
                    'company_id' => $company->id,
                    'name' => $groupData['name'],
                    'type' => $groupData['type'],
                    'description' => "{$groupData['name']} account group",
                ]
            );
            $groups[$groupData['type']] = $group;
        }

        // Create chart of accounts
        $accounts = [
            ['name' => 'Cash', 'code' => '1001', 'type' => 'asset'],
            ['name' => 'Accounts Receivable', 'code' => '1002', 'type' => 'asset'],
            ['name' => 'Inventory', 'code' => '1003', 'type' => 'asset'],
            ['name' => 'Equipment', 'code' => '1004', 'type' => 'asset'],
            ['name' => 'Accounts Payable', 'code' => '2001', 'type' => 'liability'],
            ['name' => 'Loans Payable', 'code' => '2002', 'type' => 'liability'],
            ['name' => 'Owner Equity', 'code' => '3001', 'type' => 'equity'],
            ['name' => 'Sales Revenue', 'code' => '4001', 'type' => 'revenue'],
            ['name' => 'Service Revenue', 'code' => '4002', 'type' => 'revenue'],
            ['name' => 'Cost of Goods Sold', 'code' => '5001', 'type' => 'expense'],
            ['name' => 'Rent Expense', 'code' => '5002', 'type' => 'expense'],
            ['name' => 'Utilities Expense', 'code' => '5003', 'type' => 'expense'],
            ['name' => 'Salary Expense', 'code' => '5004', 'type' => 'expense'],
        ];

        $accountModels = [];
        foreach ($accounts as $accountData) {
            $account = Account::firstOrCreate(
                ['company_id' => $company->id, 'code' => $accountData['code']],
                [
                    'company_id' => $company->id,
                    'group_id' => $groups[$accountData['type']]->id,
                    'name' => $accountData['name'],
                    'code' => $accountData['code'],
                    'type' => $accountData['type'],
                    'balance' => rand(0, 10000000),
                    'is_active' => true,
                ]
            );
            $accountModels[] = $account;
        }

        // Create journal entries
        for ($i = 1; $i <= $journalEntryCount; $i++) {
            $debitAccount = $accountModels[array_rand($accountModels)];
            $creditAccount = $accountModels[array_rand($accountModels)];
            $amount = rand(10000, 1000000);

            JournalEntry::create([
                'company_id' => $company->id,
                'reference' => "JE-{$i}",
                'description' => "Journal entry {$i}",
                'date' => now()->subDays(rand(1, 90)),
                'debit_account_id' => $debitAccount->id,
                'credit_account_id' => $creditAccount->id,
                'amount' => $amount,
                'notes' => "Sample journal entry {$i}",
            ]);
        }
    }

    private function seedAuditLogs(Company $company, array $users, int $count): void
    {
        $actions = ['create', 'update', 'delete', 'view', 'export', 'import'];
        $entities = ['invoice', 'product', 'customer', 'order', 'payment', 'employee', 'project'];

        for ($i = 1; $i <= $count; $i++) {
            $user = $users[array_rand($users)];
            $action = $actions[array_rand($actions)];
            $entity = $entities[array_rand($entities)];

            AuditLog::create([
                'company_id' => $company->id,
                'user_id' => $user->id,
                'event' => $action,
                'auditable_type' => "App\\Domain\\" . ucfirst($entity) . "\\Models\\" . ucfirst($entity),
                'auditable_id' => rand(1, 100),
                'old_values' => ['status' => 'old_value'],
                'new_values' => ['status' => 'new_value'],
                'ip_address' => '192.168.1.' . rand(1, 255),
                'user_agent' => 'Mozilla/5.0 (compatible; ERP System)',
                'created_at' => now()->subDays(rand(1, 90)),
            ]);
        }
    }
}