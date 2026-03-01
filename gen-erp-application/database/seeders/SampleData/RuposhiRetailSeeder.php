<?php

namespace Database\Seeders\SampleData;

use App\Enums\BusinessType;
use App\Enums\CompanyRole;
use App\Enums\InvoiceStatus;
use App\Enums\ProductType;
use App\Enums\SalesOrderStatus;
use App\Enums\PurchaseOrderStatus;
use App\Enums\StockMovementType;
use App\Models\Branch;
use App\Models\Company;
use App\Models\CompanyUser;
use App\Models\Customer;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Document;
use App\Models\DocumentFolder;
use App\Models\Employee;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\TaxGroup;
use App\Models\User;
use App\Models\Warehouse;
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

        $this->command?->info('🏪 Seeding Ruposhi Retail with MASSIVE data...');

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
        $this->seedInvoices($company, $customers, $products, $warehouses[0], 100);

        // ── Stock Movements (500 movements) ───────────────────
        $this->seedStockMovements($company, $products, $warehouses, $branches, 500);

        // ── Expenses (50 expenses) ────────────────────────────
        $this->seedExpenses($company, 50, $owner->id);

        // ── Documents (100 files) ────────────────────────────
        $this->seedDocuments($company, 100, $owner->id);

        // ── Departments & Employees (50 employees) ───────────
        $this->seedHR($company, 50);
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
                    'name' => "Warehouse ".($i + 1),
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
                ['company_id' => $company->id, 'sku' => "SKU-".str_pad($i, 5, '0', STR_PAD_LEFT)],
                [
                    'company_id' => $company->id,
                    'name' => "Product {$i}",
                    'sku' => "SKU-".str_pad($i, 5, '0', STR_PAD_LEFT),
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

    private function seedInvoices(Company $company, array $customers, array $products, Warehouse $warehouse, int $count = 100): void
    {
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
        }
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

    private function seedExpenses(Company $company, int $count = 50, int $userId): void
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

    private function seedDocuments(Company $company, int $count = 100, int $userId): void
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

    private function seedHR(Company $company, int $count = 50): void
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

        for ($i = 1; $i <= $count; $i++) {
            $dept = Department::where('company_id', $company->id)->inRandomOrder()->first();
            $desig = Designation::where('company_id', $company->id)->inRandomOrder()->first();
            Employee::firstOrCreate(
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
                    'status' => 'active',
                ],
            );
        }
    }
}
