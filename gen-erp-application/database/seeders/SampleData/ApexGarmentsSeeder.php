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
 * Apex Garments Ltd — A manufacturing/RMG export company scenario.
 */
class ApexGarmentsSeeder
{
    protected ?\Illuminate\Console\Command $command = null;

    public function setCommand(\Illuminate\Console\Command $command): void
    {
        $this->command = $command;
    }

    public function run(Company $company, User $owner): void
    {
        CompanyContext::setActive($company);

        $this->command?->info('🏭 Seeding Apex Garments with MASSIVE data...');

        // ── Team (15 users) ────────────────────────────────────
        $this->seedUsers($company, 15);

        // ── Warehouses (3 warehouses) ───────────────────────────
        $warehouses = $this->seedWarehouses($company, 3);

        // ── Categories (8 categories) ──────────────────────────────
        $categories = $this->seedCategories($company, 8);

        // ── Products (150 items) ─────────────────────────
        $products = $this->seedProducts($company, $categories, 150);

        // ── Customers (30 international buyers) ───────────────────────────────
        $customers = $this->seedCustomers($company, 30);

        // ── Suppliers (15 suppliers) ─────────────────────────────
        $suppliers = $this->seedSuppliers($company, 15);

        // ── Sales Orders (25 export orders) ─────────────────────────────
        $this->seedSalesOrders($company, $customers, $products, $warehouses[0], 25);

        // ── Purchase Orders (20 orders) ───────────────────────────
        $this->seedPurchaseOrders($company, $suppliers, $products, $warehouses[0], 20);

        // ── Invoices (40 export invoices) ───────────────────────────────
        $this->seedInvoices($company, $customers, $products, $warehouses[0], 40);

        // ── Stock Movements (150 movements) ────────────────────
        $this->seedStockMovements($company, $products, $warehouses, 150);

        // ── Expenses (15 expenses) ───────────────────────────────
        $this->seedExpenses($company, 15, $owner->id);

        // ── Documents (40 files) ───────────────────────────────
        $this->seedDocuments($company, 40, $owner->id);

        // ── HR (20 employees) ────────────────────────────────
        $this->seedHR($company, 20);
    }

    private function seedUsers(Company $company, int $count = 15): array
    {
        $roles = [CompanyRole::SALES, CompanyRole::WAREHOUSE];
        $users = [];
        for ($i = 1; $i <= $count; $i++) {
            $role = $roles[array_rand($roles)];
            $user = User::firstOrCreate(
                ['email' => "apex{$i}@apex.test"],
                [
                    'name' => "Apex Staff {$i}",
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

    private function seedWarehouses(Company $company, int $count = 3): array
    {
        $names = ['Raw Materials Store', 'Finished Goods Warehouse', 'Packaging Store'];
        $codes = ['RAW-01', 'FIN-01', 'PKG-01'];
        $warehouses = [];
        for ($i = 0; $i < $count; $i++) {
            $warehouses[] = Warehouse::firstOrCreate(
                ['company_id' => $company->id, 'code' => $codes[$i]],
                [
                    'company_id' => $company->id,
                    'name' => $names[$i],
                    'code' => $codes[$i],
                    'address' => 'Ashulia, Savar',
                    'is_active' => true,
                ],
            );
        }
        return $warehouses;
    }

    private function seedCategories(Company $company, int $count = 8): array
    {
        $names = ['Raw Materials', 'Finished Garments', 'Accessories', 'Packaging', 'Fabric', 'Thread', 'Labels', 'Trimmings'];
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

    private function seedProducts(Company $company, array $categories, int $count = 150): array
    {
        $zeroRated = TaxGroup::where('company_id', $company->id)->where('name', 'Zero Rated')->first();
        $vatStd = TaxGroup::where('company_id', $company->id)->where('name', 'VAT 15%')->first();
        $categoryNames = array_keys($categories);

        $products = [];
        for ($i = 1; $i <= $count; $i++) {
            $catName = $categoryNames[array_rand($categoryNames)];
            $category = $categories[$catName];
            $isZeroRated = in_array($catName, ['Finished Garments', 'Raw Materials', 'Fabric']);
            $tax = $isZeroRated ? $zeroRated : $vatStd;
            $products[] = Product::firstOrCreate(
                ['company_id' => $company->id, 'sku' => "APX-".str_pad($i, 5, '0', STR_PAD_LEFT)],
                [
                    'company_id' => $company->id,
                    'name' => "Product {$i}",
                    'sku' => "APX-".str_pad($i, 5, '0', STR_PAD_LEFT),
                    'slug' => Str::slug("Product {$i}"),
                    'category_id' => $category->id,
                    'tax_group_id' => $tax?->id,
                    'product_type' => ProductType::PRODUCT,
                    'cost_price' => rand(10000, 5000000),
                    'selling_price' => rand(20000, 10000000),
                    'unit' => 'pcs',
                    'track_inventory' => true,
                    'low_stock_threshold' => rand(50, 200),
                    'is_active' => true,
                ],
            );
        }
        return $products;
    }

    private function seedCustomers(Company $company, int $count = 30): array
    {
        $customers = [];
        for ($i = 1; $i <= $count; $i++) {
            $customers[] = Customer::firstOrCreate(
                ['company_id' => $company->id, 'phone' => '+1'.str_pad($i, 10, '0', STR_PAD_LEFT)],
                [
                    'company_id' => $company->id,
                    'name' => "International Buyer {$i}",
                    'phone' => '+1'.str_pad($i, 10, '0', STR_PAD_LEFT),
                    'email' => "buyer{$i}@apex.test",
                    'address' => 'USA/Europe',
                    'credit_limit' => rand(50000000, 500000000),
                ],
            );
        }
        return $customers;
    }

    private function seedSuppliers(Company $company, int $count = 15): array
    {
        $suppliers = [];
        for ($i = 1; $i <= $count; $i++) {
            $suppliers[] = Supplier::firstOrCreate(
                ['company_id' => $company->id, 'name' => "Supplier {$i}"],
                [
                    'company_id' => $company->id,
                    'name' => "Supplier {$i}",
                    'phone' => '018'.str_pad($i, 8, '0', STR_PAD_LEFT),
                    'email' => "supplier{$i}@apex.test",
                    'address' => 'Dhaka, Bangladesh',
                    'vat_bin' => 'BIN'.str_pad($i, 11, '0', STR_PAD_LEFT),
                ],
            );
        }
        return $suppliers;
    }

    private function seedSalesOrders(Company $company, array $customers, array $products, Warehouse $warehouse, int $count = 25): void
    {
        for ($i = 1; $i <= $count; $i++) {
            $customer = $customers[array_rand($customers)];
            $status = collect([SalesOrderStatus::DRAFT, SalesOrderStatus::CONFIRMED, SalesOrderStatus::DELIVERED])->random();
            $orderDate = now()->subDays(rand(1, 60));

            $lineItems = [];
            $itemCount = rand(3, 6);
            $subtotal = 0;

            for ($j = 0; $j < $itemCount; $j++) {
                $product = $products[array_rand($products)];
                $qty = rand(100, 1000);
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
                $qty = rand(100, 500);
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

    private function seedInvoices(Company $company, array $customers, array $products, Warehouse $warehouse, int $count = 40): void
    {
        for ($i = 1; $i <= $count; $i++) {
            $customer = $customers[array_rand($customers)];
            $invoiceDate = now()->subDays(rand(1, 45));
            $status = collect([InvoiceStatus::PAID, InvoiceStatus::PAID, InvoiceStatus::SENT])->random();

            $lineItems = [];
            $itemCount = rand(3, 5);
            $subtotal = 0;

            for ($j = 0; $j < $itemCount; $j++) {
                $product = $products[array_rand($products)];
                $qty = rand(100, 500);
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

            $totalAmount = $subtotal;
            $amountPaid = $status === InvoiceStatus::PAID ? $totalAmount : 0;

            $invoice = Invoice::create([
                'company_id' => $company->id,
                'customer_id' => $customer->id,
                'warehouse_id' => $warehouse->id,
                'invoice_date' => $invoiceDate,
                'due_date' => $invoiceDate->copy()->addDays(30),
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

    private function seedStockMovements(Company $company, array $products, array $warehouses, int $count = 150): void
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
            $type = $movementTypes[array_rand($movementTypes)];
            $movementDate = now()->subDays(rand(1, 90));
            $quantity = rand(-200, 200);
            $quantityBefore = rand(0, 1000);
            $quantityAfter = $quantityBefore + $quantity;

            StockMovement::create([
                'company_id' => $company->id,
                'product_id' => $product->id,
                'warehouse_id' => $warehouse->id,
                'movement_type' => $type,
                'quantity' => $quantity,
                'quantity_before' => $quantityBefore,
                'quantity_after' => $quantityAfter,
                'unit_cost' => rand(10000, 5000000),
                'movement_date' => $movementDate,
                'created_at' => $movementDate,
            ]);
        }
    }

    private function seedExpenses(Company $company, int $count = 15, int $userId): void
    {
        $expenseTypes = ['Rent', 'Utilities', 'Salaries', 'Transport', 'Marketing', 'Supplies', 'Maintenance'];
        for ($i = 1; $i <= $count; $i++) {
            $type = $expenseTypes[array_rand($expenseTypes)];
            $amount = rand(100000, 10000000);
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

    private function seedDocuments(Company $company, int $count = 40, int $userId): void
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

    private function seedHR(Company $company, int $count = 20): void
    {
        $departments = ['Production', 'Quality Control', 'Administration', 'Finance'];
        $designations = ['Manager', 'Supervisor', 'Operator', 'Staff'];

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
                    'email' => "employee{$i}@apex.test",
                    'phone' => '017'.rand(10000000, 99999999),
                    'department_id' => $dept->id,
                    'designation_id' => $desig->id,
                    'joining_date' => now()->subMonths(rand(6, 48)),
                    'basic_salary' => rand(1200000, 6000000),
                    'status' => 'active',
                ],
            );
        }

        // ── Expenses (manufacturing overhead) ───────────────
        $expenses = [
            ['description' => 'Factory Rent - Feb 2026', 'amount' => 15000000, 'expense_date' => now()->subDays(5)],
            ['description' => 'Electricity Bill (Industrial)', 'amount' => 3500000, 'expense_date' => now()->subDays(10)],
            ['description' => 'Generator Diesel', 'amount' => 1200000, 'expense_date' => now()->subDays(7)],
            ['description' => 'Worker Transport', 'amount' => 800000, 'expense_date' => now()->subDays(3)],
            ['description' => 'Compliance Audit Fee', 'amount' => 500000, 'expense_date' => now()->subDays(30)],
            ['description' => 'Fire Safety Equipment', 'amount' => 350000, 'expense_date' => now()->subDays(45)],
            ['description' => 'Machine Maintenance', 'amount' => 900000, 'expense_date' => now()->subDays(20)],
        ];

        foreach ($expenses as $data) {
            Expense::firstOrCreate(
                ['company_id' => $company->id, 'description' => $data['description']],
                array_merge($data, [
                    'company_id' => $company->id,
                    'total_amount' => $data['amount'],
                    'status' => 'approved',
                ]),
            );
        }
    }
}
