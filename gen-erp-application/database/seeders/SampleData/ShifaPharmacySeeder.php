<?php

namespace Database\Seeders\SampleData;

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
 * Shifa Pharmacy — A pharmacy/healthcare business with medicines, health products.
 */
class ShifaPharmacySeeder
{
    protected ?\Illuminate\Console\Command $command = null;

    public function setCommand(\Illuminate\Console\Command $command): void
    {
        $this->command = $command;
    }

    public function run(Company $company, User $owner): void
    {
        CompanyContext::setActive($company);

        $this->command?->info('💊 Seeding Shifa Pharmacy with MASSIVE data...');

        // ── Team (10 users) ────────────────────────────────────
        $this->seedUsers($company, 10);

        // ── Warehouses (2 warehouses) ───────────────────────────
        $warehouses = $this->seedWarehouses($company, 2);

        // ── Categories (10 categories) ──────────────────────────────
        $categories = $this->seedCategories($company, 10);

        // ── Products (100 medicines) ─────────────────────────
        $products = $this->seedProducts($company, $categories, 100);

        // ── Customers (50 customers) ───────────────────────────────
        $customers = $this->seedCustomers($company, 50);

        // ── Suppliers (10 suppliers) ─────────────────────────────
        $suppliers = $this->seedSuppliers($company, 10);

        // ── Sales Orders (30 orders) ─────────────────────────────
        $this->seedSalesOrders($company, $customers, $products, $warehouses[0], 30);

        // ── Purchase Orders (20 orders) ───────────────────────────
        $this->seedPurchaseOrders($company, $suppliers, $products, $warehouses[0], 20);

        // ── Invoices (60 invoices) ───────────────────────────────
        $this->seedInvoices($company, $customers, $products, $warehouses[0], 60);

        // ── Stock Movements (200 movements) ────────────────────
        $this->seedStockMovements($company, $products, $warehouses, 200);

        // ── Expenses (20 expenses) ───────────────────────────────
        $this->seedExpenses($company, 20, $owner->id);

        // ── Documents (50 files) ───────────────────────────────
        $this->seedDocuments($company, 50, $owner->id);

        // ── HR (15 employees) ────────────────────────────────
        $this->seedHR($company, 15);
    }

    private function seedUsers(Company $company, int $count = 10): array
    {
        $roles = [CompanyRole::SALES, CompanyRole::WAREHOUSE];
        $users = [];
        for ($i = 1; $i <= $count; $i++) {
            $role = $roles[array_rand($roles)];
            $user = User::firstOrCreate(
                ['email' => "pharma{$i}@shifa.test"],
                [
                    'name' => "Pharmacist {$i}",
                    'password' => Hash::make('Password@123'),
                    'email_verified_at' => now(),
                    'phone' => '016'.str_pad($i, 8, '0', STR_PAD_LEFT),
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

    private function seedWarehouses(Company $company, int $count = 2): array
    {
        $warehouses = [];
        for ($i = 0; $i < $count; $i++) {
            $warehouses[] = Warehouse::firstOrCreate(
                ['company_id' => $company->id, 'code' => 'MED-'.str_pad($i + 1, 2, '0', STR_PAD_LEFT)],
                [
                    'company_id' => $company->id,
                    'name' => "Medicine Store ".($i + 1),
                    'code' => 'MED-'.str_pad($i + 1, 2, '0', STR_PAD_LEFT),
                    'address' => 'Dhanmondi, Dhaka',
                    'is_active' => true,
                ],
            );
        }
        return $warehouses;
    }

    private function seedCategories(Company $company, int $count = 10): array
    {
        $names = ['Medicines', 'OTC Drugs', 'Health Supplements', 'Personal Care', 'Medical Devices', 'Baby Care', 'Equipment', 'Lab Supplies', 'Ayurvedic', 'Homeopathy'];
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

    private function seedProducts(Company $company, array $categories, int $count = 100): array
    {
        $vatGroup = TaxGroup::where('company_id', $company->id)->where('name', 'Zero Rated')->first();
        $vatStd = TaxGroup::where('company_id', $company->id)->where('name', 'VAT 15%')->first();
        $categoryNames = array_keys($categories);

        $products = [];
        for ($i = 1; $i <= $count; $i++) {
            $catName = $categoryNames[array_rand($categoryNames)];
            $category = $categories[$catName];
            $isZeroRated = rand(0, 1) === 0;
            $tax = $isZeroRated ? $vatGroup : $vatStd;
            $products[] = Product::firstOrCreate(
                ['company_id' => $company->id, 'sku' => "MED-".str_pad($i, 5, '0', STR_PAD_LEFT)],
                [
                    'company_id' => $company->id,
                    'name' => "Medicine {$i}",
                    'sku' => "MED-".str_pad($i, 5, '0', STR_PAD_LEFT),
                    'slug' => Str::slug("Medicine {$i}"),
                    'category_id' => $category->id,
                    'tax_group_id' => $tax?->id,
                    'product_type' => ProductType::PRODUCT,
                    'cost_price' => rand(1000, 5000000),
                    'selling_price' => rand(2000, 10000000),
                    'unit' => 'pcs',
                    'track_inventory' => true,
                    'low_stock_threshold' => rand(5, 20),
                    'is_active' => true,
                ],
            );
        }
        return $products;
    }

    private function seedCustomers(Company $company, int $count = 50): array
    {
        $customers = [];
        for ($i = 1; $i <= $count; $i++) {
            $customers[] = Customer::firstOrCreate(
                ['company_id' => $company->id, 'phone' => '016'.str_pad($i, 8, '0', STR_PAD_LEFT)],
                [
                    'company_id' => $company->id,
                    'name' => "Customer {$i}",
                    'phone' => '016'.str_pad($i, 8, '0', STR_PAD_LEFT),
                    'email' => "customer{$i}@shifa.test",
                    'credit_limit' => rand(500000, 20000000),
                ],
            );
        }
        return $customers;
    }

    private function seedSuppliers(Company $company, int $count = 10): array
    {
        $suppliers = [];
        for ($i = 1; $i <= $count; $i++) {
            $suppliers[] = Supplier::firstOrCreate(
                ['company_id' => $company->id, 'name' => "Supplier {$i}"],
                [
                    'company_id' => $company->id,
                    'name' => "Supplier {$i}",
                    'phone' => '015'.str_pad($i, 8, '0', STR_PAD_LEFT),
                    'email' => "supplier{$i}@shifa.test",
                    'address' => 'Dhaka, Bangladesh',
                    'vat_bin' => 'BIN'.str_pad($i, 11, '0', STR_PAD_LEFT),
                ],
            );
        }
        return $suppliers;
    }

    private function seedSalesOrders(Company $company, array $customers, array $products, Warehouse $warehouse, int $count = 30): void
    {
        for ($i = 1; $i <= $count; $i++) {
            $customer = $customers[array_rand($customers)];
            $status = collect([SalesOrderStatus::DRAFT, SalesOrderStatus::CONFIRMED, SalesOrderStatus::DELIVERED])->random();
            $orderDate = now()->subDays(rand(1, 60));

            $lineItems = [];
            $itemCount = rand(2, 4);
            $subtotal = 0;

            for ($j = 0; $j < $itemCount; $j++) {
                $product = $products[array_rand($products)];
                $qty = rand(1, 5);
                $price = $product->selling_price;
                $lineTotal = $qty * $price;
                $subtotal += $lineTotal;

                $lineItems[] = [
                    'description' => $product->name,
                    'quantity' => $qty,
                    'unit' => $product->unit ?? 'pcs',
                    'unit_price' => $price,
                    'discount_amount' => 0,
                    'tax_rate' => 0,
                    'tax_amount' => 0,
                    'line_total' => $lineTotal,
                ];
            }

            $salesOrder = SalesOrder::create([
                'company_id' => $company->id,
                'customer_id' => $customer->id,
                'warehouse_id' => $warehouse->id,
                'status' => $status,
                'order_date' => $orderDate,
                'subtotal' => $subtotal,
                'discount_amount' => 0,
                'tax_amount' => 0,
                'total_amount' => $subtotal,
            ]);

            foreach ($lineItems as $item) {
                SalesOrderItem::create(array_merge($item, ['sales_order_id' => $salesOrder->id, 'product_id' => $products[array_rand($products)]->id]));
            }
        }
    }

    private function seedPurchaseOrders(Company $company, array $suppliers, array $products, Warehouse $warehouse, int $count = 20): void
    {
        for ($i = 1; $i <= $count; $i++) {
            $supplier = $suppliers[array_rand($suppliers)];
            $status = collect([PurchaseOrderStatus::DRAFT, PurchaseOrderStatus::SENT, PurchaseOrderStatus::RECEIVED])->random();
            $orderDate = now()->subDays(rand(1, 60));

            $lineItems = [];
            $itemCount = rand(2, 4);
            $subtotal = 0;

            for ($j = 0; $j < $itemCount; $j++) {
                $product = $products[array_rand($products)];
                $qty = rand(10, 50);
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
                    'tax_rate' => 0,
                    'tax_amount' => 0,
                    'line_total' => $lineTotal,
                ];
            }

            $purchaseOrder = PurchaseOrder::create([
                'company_id' => $company->id,
                'supplier_id' => $supplier->id,
                'warehouse_id' => $warehouse->id,
                'status' => $status,
                'order_date' => $orderDate,
                'subtotal' => $subtotal,
                'discount_amount' => 0,
                'tax_amount' => 0,
                'total_amount' => $subtotal,
            ]);

            foreach ($lineItems as $item) {
                PurchaseOrderItem::create(array_merge($item, ['purchase_order_id' => $purchaseOrder->id, 'product_id' => $products[array_rand($products)]->id]));
            }
        }
    }

    private function seedInvoices(Company $company, array $customers, array $products, Warehouse $warehouse, int $count = 60): void
    {
        for ($i = 1; $i <= $count; $i++) {
            $customer = $customers[array_rand($customers)];
            $invoiceDate = now()->subDays(rand(1, 45));
            $status = collect([InvoiceStatus::PAID, InvoiceStatus::PAID, InvoiceStatus::SENT])->random();

            $lineItems = [];
            $itemCount = rand(2, 4);
            $subtotal = 0;

            for ($j = 0; $j < $itemCount; $j++) {
                $product = $products[array_rand($products)];
                $qty = rand(1, 3);
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
                    'tax_rate' => 0,
                    'tax_amount' => 0,
                    'line_total' => $lineTotal,
                ];
            }

            $totalAmount = $subtotal;
            $amountPaid = $status === InvoiceStatus::PAID ? $totalAmount : 0;

            $invoice = Invoice::create([
                'company_id' => $company->id,
                'customer_id' => $customer->id,
                'warehouse_id' => $warehouse->id,
                'invoice_date' => $invoiceDate,
                'due_date' => $invoiceDate->copy()->addDays(7),
                'status' => $status,
                'subtotal' => $subtotal,
                'tax_amount' => 0,
                'total_amount' => $totalAmount,
                'amount_paid' => $amountPaid,
            ]);

            foreach ($lineItems as $item) {
                InvoiceItem::create(array_merge($item, ['invoice_id' => $invoice->id]));
            }
        }
    }

    private function seedStockMovements(Company $company, array $products, array $warehouses, int $count = 200): void
    {
        $movementTypes = [
            StockMovementType::PURCHASE_RECEIPT,
            StockMovementType::SALE,
            StockMovementType::ADJUSTMENT_IN,
            StockMovementType::ADJUSTMENT_OUT,
        ];
        for ($i = 1; $i <= $count; $i++) {
            $product = $products[array_rand($products)];
            $warehouse = $warehouses[array_rand($warehouses)];
            $type = $movementTypes[array_rand($movementTypes)];
            $movementDate = now()->subDays(rand(1, 90));
            $quantity = rand(-50, 50);
            $quantityBefore = rand(0, 500);
            $quantityAfter = $quantityBefore + $quantity;

            StockMovement::create([
                'company_id' => $company->id,
                'product_id' => $product->id,
                'warehouse_id' => $warehouse->id,
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

    private function seedExpenses(Company $company, int $count = 20, int $userId): void
    {
        $expenseTypes = ['Rent', 'Utilities', 'Salaries', 'Transport', 'Marketing', 'Supplies', 'Maintenance'];
        for ($i = 1; $i <= $count; $i++) {
            $type = $expenseTypes[array_rand($expenseTypes)];
            $amount = rand(50000, 5000000);
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

    private function seedDocuments(Company $company, int $count = 50, int $userId): void
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

    private function seedHR(Company $company, int $count = 15): void
    {
        $departments = ['Dispensary', 'Administration', 'Finance'];
        $designations = ['Pharmacist', 'Manager', 'Assistant'];

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
                    'email' => "employee{$i}@shifa.test",
                    'phone' => '016'.rand(10000000, 99999999),
                    'department_id' => $dept->id,
                    'designation_id' => $desig->id,
                    'joining_date' => now()->subMonths(rand(6, 36)),
                    'basic_salary' => rand(1500000, 5000000),
                    'status' => 'active',
                ],
            );
        }
    }
}
