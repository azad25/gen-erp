<?php

namespace Database\Seeders\SampleData;

use App\Enums\CompanyRole;
use App\Enums\InvoiceStatus;
use App\Enums\ProductType;
use App\Models\Branch;
use App\Models\Company;
use App\Models\CompanyUser;
use App\Models\Customer;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Product;
use App\Models\ProductCategory;
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
    public function run(Company $company, User $owner): void
    {
        CompanyContext::setActive($company);

        // ── Team Members ────────────────────────────────────
        $users = $this->seedUsers($company);

        // ── Branches ────────────────────────────────────────
        $branches = $this->seedBranches($company);

        // ── Warehouses ──────────────────────────────────────
        $warehouse = Warehouse::firstOrCreate(
            ['company_id' => $company->id, 'code' => 'WH-01'],
            ['company_id' => $company->id, 'name' => 'Main Warehouse', 'code' => 'WH-01', 'address' => 'Mirpur, Dhaka', 'is_active' => true],
        );

        // ── Product Categories ──────────────────────────────
        $categories = $this->seedCategories($company);

        // ── Products (30 items) ─────────────────────────────
        $products = $this->seedProducts($company, $categories);

        // ── Customers (20) ──────────────────────────────────
        $customers = $this->seedCustomers($company);

        // ── Suppliers (8) ───────────────────────────────────
        $suppliers = $this->seedSuppliers($company);

        // ── Invoices (15) ───────────────────────────────────
        $this->seedInvoices($company, $customers, $products, $warehouse);

        // ── Expenses (10) ───────────────────────────────────
        $this->seedExpenses($company);

        // ── Departments & Employees ─────────────────────────
        $this->seedHR($company);
    }

    /**
     * @return array<int, User>
     */
    private function seedUsers(Company $company): array
    {
        $teamMembers = [
            ['name' => 'Rahim Sales', 'email' => 'rahim.sales@ruposhi.test', 'role' => CompanyRole::SALES],
            ['name' => 'Karim Warehouse', 'email' => 'karim.wh@ruposhi.test', 'role' => CompanyRole::WAREHOUSE],
            ['name' => 'Fatema HR', 'email' => 'fatema.hr@ruposhi.test', 'role' => CompanyRole::HR_MANAGER],
            ['name' => 'Sumon Accountant', 'email' => 'sumon.acc@ruposhi.test', 'role' => CompanyRole::ACCOUNTANT],
        ];

        $users = [];
        foreach ($teamMembers as $member) {
            $user = User::firstOrCreate(
                ['email' => $member['email']],
                ['name' => $member['name'], 'password' => Hash::make('Password@123'), 'email_verified_at' => now()],
            );
            CompanyUser::firstOrCreate(
                ['company_id' => $company->id, 'user_id' => $user->id],
                ['role' => $member['role']->value, 'is_active' => true, 'joined_at' => now()],
            );
            $users[] = $user;
        }

        return $users;
    }

    /**
     * @return array<int, Branch>
     */
    private function seedBranches(Company $company): array
    {
        $branches = [
            ['name' => 'Mirpur Main Branch', 'code' => 'MIR-01', 'is_headquarters' => true, 'address' => 'Mirpur-10, Dhaka'],
            ['name' => 'Uttara Branch', 'code' => 'UTT-01', 'is_headquarters' => false, 'address' => 'Sector 7, Uttara, Dhaka'],
        ];

        $created = [];
        foreach ($branches as $data) {
            $created[] = Branch::firstOrCreate(
                ['company_id' => $company->id, 'code' => $data['code']],
                array_merge($data, ['company_id' => $company->id, 'is_active' => true]),
            );
        }

        return $created;
    }

    /**
     * @return array<string, ProductCategory>
     */
    private function seedCategories(Company $company): array
    {
        $names = ['Electronics', 'Groceries', 'Clothing', 'Household', 'Stationery', 'Personal Care'];
        $categories = [];
        foreach ($names as $name) {
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
    private function seedProducts(Company $company, array $categories): array
    {
        $vatGroup = TaxGroup::where('company_id', $company->id)->where('name', 'VAT 15%')->first();

        $items = [
            ['name' => 'Samsung Galaxy A15', 'sku' => 'ELEC-001', 'cat' => 'Electronics', 'cost' => 1500000, 'sell' => 1800000],
            ['name' => 'Xiaomi Redmi 13C', 'sku' => 'ELEC-002', 'cat' => 'Electronics', 'cost' => 1200000, 'sell' => 1450000],
            ['name' => 'Wireless Earbuds Pro', 'sku' => 'ELEC-003', 'cat' => 'Electronics', 'cost' => 80000, 'sell' => 120000],
            ['name' => 'USB-C Cable 1m', 'sku' => 'ELEC-004', 'cat' => 'Electronics', 'cost' => 5000, 'sell' => 10000],
            ['name' => 'Power Bank 10000mAh', 'sku' => 'ELEC-005', 'cat' => 'Electronics', 'cost' => 60000, 'sell' => 95000],
            ['name' => 'Miniket Rice 5kg', 'sku' => 'GRC-001', 'cat' => 'Groceries', 'cost' => 35000, 'sell' => 42000],
            ['name' => 'Soybean Oil 5L', 'sku' => 'GRC-002', 'cat' => 'Groceries', 'cost' => 70000, 'sell' => 82000],
            ['name' => 'Pran Chanachur 200g', 'sku' => 'GRC-003', 'cat' => 'Groceries', 'cost' => 4000, 'sell' => 6000],
            ['name' => 'Fresh Mango Juice 1L', 'sku' => 'GRC-004', 'cat' => 'Groceries', 'cost' => 6000, 'sell' => 8500],
            ['name' => 'Nescafe Coffee 200g', 'sku' => 'GRC-005', 'cat' => 'Groceries', 'cost' => 30000, 'sell' => 38000],
            ['name' => 'Cotton T-Shirt (L)', 'sku' => 'CLT-001', 'cat' => 'Clothing', 'cost' => 20000, 'sell' => 35000],
            ['name' => 'Denim Jeans (32)', 'sku' => 'CLT-002', 'cat' => 'Clothing', 'cost' => 55000, 'sell' => 85000],
            ['name' => 'Polo Shirt (M)', 'sku' => 'CLT-003', 'cat' => 'Clothing', 'cost' => 30000, 'sell' => 50000],
            ['name' => 'Lungi (Standard)', 'sku' => 'CLT-004', 'cat' => 'Clothing', 'cost' => 15000, 'sell' => 25000],
            ['name' => 'LED Bulb 9W', 'sku' => 'HOU-001', 'cat' => 'Household', 'cost' => 8000, 'sell' => 15000],
            ['name' => 'Broom (Jharu)', 'sku' => 'HOU-002', 'cat' => 'Household', 'cost' => 5000, 'sell' => 8000],
            ['name' => 'Plastic Chair', 'sku' => 'HOU-003', 'cat' => 'Household', 'cost' => 40000, 'sell' => 60000],
            ['name' => 'Wall Clock', 'sku' => 'HOU-004', 'cat' => 'Household', 'cost' => 15000, 'sell' => 25000],
            ['name' => 'A4 Paper (500 sheets)', 'sku' => 'STA-001', 'cat' => 'Stationery', 'cost' => 25000, 'sell' => 35000],
            ['name' => 'Ball Pen (10 pack)', 'sku' => 'STA-002', 'cat' => 'Stationery', 'cost' => 5000, 'sell' => 8000],
            ['name' => 'Notebook 200pg', 'sku' => 'STA-003', 'cat' => 'Stationery', 'cost' => 6000, 'sell' => 10000],
            ['name' => 'Dove Soap 100g', 'sku' => 'PC-001', 'cat' => 'Personal Care', 'cost' => 6000, 'sell' => 8500],
            ['name' => 'Colgate Toothpaste 150g', 'sku' => 'PC-002', 'cat' => 'Personal Care', 'cost' => 8000, 'sell' => 11000],
            ['name' => 'Sunsilk Shampoo 340ml', 'sku' => 'PC-003', 'cat' => 'Personal Care', 'cost' => 18000, 'sell' => 25000],
            ['name' => 'Dettol Hand Wash 200ml', 'sku' => 'PC-004', 'cat' => 'Personal Care', 'cost' => 10000, 'sell' => 14000],
        ];

        $products = [];
        foreach ($items as $item) {
            $category = $categories[$item['cat']] ?? null;
            $products[] = Product::firstOrCreate(
                ['company_id' => $company->id, 'sku' => $item['sku']],
                [
                    'company_id' => $company->id,
                    'name' => $item['name'],
                    'sku' => $item['sku'],
                    'slug' => Str::slug($item['name']),
                    'category_id' => $category?->id,
                    'tax_group_id' => $vatGroup?->id,
                    'product_type' => ProductType::PRODUCT,
                    'cost_price' => $item['cost'],
                    'selling_price' => $item['sell'],
                    'unit' => 'pcs',
                    'track_inventory' => true,
                    'low_stock_threshold' => 5,
                    'is_active' => true,
                ],
            );
        }

        return $products;
    }

    /**
     * @return array<int, Customer>
     */
    private function seedCustomers(Company $company): array
    {
        $customerData = [
            ['name' => 'Shafiq Islam', 'phone' => '01711111111', 'district' => 'Dhaka'],
            ['name' => 'Nasreen Akter', 'phone' => '01722222222', 'district' => 'Dhaka'],
            ['name' => 'Md. Rafiqul Haque', 'phone' => '01733333333', 'district' => 'Gazipur'],
            ['name' => 'Taslima Begum', 'phone' => '01744444444', 'district' => 'Narayanganj'],
            ['name' => 'Abdul Karim', 'phone' => '01755555555', 'district' => 'Dhaka'],
            ['name' => 'Rahima Khatun', 'phone' => '01766666666', 'district' => 'Manikganj'],
            ['name' => 'Jahangir Alam', 'phone' => '01777777777', 'district' => 'Tangail'],
            ['name' => 'Mst. Salma', 'phone' => '01788888888', 'district' => 'Dhaka'],
            ['name' => 'Kamal Hossain', 'phone' => '01799999999', 'district' => 'Mymensingh'],
            ['name' => 'Fatema Noor', 'phone' => '01811111111', 'district' => 'Comilla'],
            ['name' => 'Shahidul Islam', 'phone' => '01822222222', 'district' => 'Chittagong'],
            ['name' => 'Roksana Parvin', 'phone' => '01833333333', 'district' => 'Rajshahi'],
            ['name' => 'Anisur Rahman', 'phone' => '01844444444', 'district' => 'Khulna'],
            ['name' => 'Monira Akhter', 'phone' => '01855555555', 'district' => 'Sylhet'],
            ['name' => 'Belal Ahmed', 'phone' => '01866666666', 'district' => 'Barisal'],
        ];

        $customers = [];
        foreach ($customerData as $data) {
            $customers[] = Customer::firstOrCreate(
                ['company_id' => $company->id, 'phone' => $data['phone']],
                array_merge($data, [
                    'company_id' => $company->id,
                    'email' => strtolower(Str::slug($data['name'], '.')).'@example.com',
                    'credit_limit' => 5000000,
                ]),
            );
        }

        return $customers;
    }

    /**
     * @return array<int, Supplier>
     */
    private function seedSuppliers(Company $company): array
    {
        $supplierData = [
            ['name' => 'Samsung Bangladesh', 'phone' => '01911111111', 'vat_bin' => '111222333444'],
            ['name' => 'Pran-RFL Group', 'phone' => '01922222222', 'vat_bin' => '222333444555'],
            ['name' => 'Unilever BD', 'phone' => '01933333333', 'vat_bin' => '333444555666'],
            ['name' => 'ACI Limited', 'phone' => '01944444444', 'vat_bin' => '444555666777'],
            ['name' => 'City Group', 'phone' => '01955555555', 'vat_bin' => '555666777888'],
            ['name' => 'Bashundhara Group', 'phone' => '01966666666', 'vat_bin' => '666777888999'],
        ];

        $suppliers = [];
        foreach ($supplierData as $data) {
            $suppliers[] = Supplier::firstOrCreate(
                ['company_id' => $company->id, 'name' => $data['name']],
                array_merge($data, [
                    'company_id' => $company->id,
                    'email' => strtolower(Str::slug($data['name'], '.')).'@supplier.test',
                    'address' => 'Dhaka, Bangladesh',
                ]),
            );
        }

        return $suppliers;
    }

    private function seedInvoices(Company $company, array $customers, array $products, Warehouse $warehouse): void
    {
        for ($i = 0; $i < 15; $i++) {
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

    private function seedExpenses(Company $company): void
    {
        $expenses = [
            ['description' => 'Shop Rent - February 2026', 'amount' => 3500000, 'expense_date' => now()->subDays(5)],
            ['description' => 'Electricity Bill', 'amount' => 450000, 'expense_date' => now()->subDays(10)],
            ['description' => 'Internet & Phone', 'amount' => 250000, 'expense_date' => now()->subDays(12)],
            ['description' => 'Staff Tea/Snacks', 'amount' => 15000, 'expense_date' => now()->subDays(3)],
            ['description' => 'Transport (goods delivery)', 'amount' => 80000, 'expense_date' => now()->subDays(7)],
            ['description' => 'WASA Water Bill', 'amount' => 120000, 'expense_date' => now()->subDays(15)],
            ['description' => 'Printer Cartridge', 'amount' => 180000, 'expense_date' => now()->subDays(20)],
            ['description' => 'Signboard Maintenance', 'amount' => 500000, 'expense_date' => now()->subDays(25)],
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

    private function seedHR(Company $company): void
    {
        $sales = Department::firstOrCreate(
            ['company_id' => $company->id, 'name' => 'Sales'],
            ['company_id' => $company->id, 'name' => 'Sales'],
        );
        $warehouse = Department::firstOrCreate(
            ['company_id' => $company->id, 'name' => 'Warehouse'],
            ['company_id' => $company->id, 'name' => 'Warehouse'],
        );
        $admin = Department::firstOrCreate(
            ['company_id' => $company->id, 'name' => 'Administration'],
            ['company_id' => $company->id, 'name' => 'Administration'],
        );

        $manager = Designation::firstOrCreate(
            ['company_id' => $company->id, 'name' => 'Manager'],
            ['company_id' => $company->id, 'name' => 'Manager'],
        );
        $executive = Designation::firstOrCreate(
            ['company_id' => $company->id, 'name' => 'Executive'],
            ['company_id' => $company->id, 'name' => 'Executive'],
        );

        $employees = [
            ['first_name' => 'Rahim', 'last_name' => 'Uddin', 'dept' => $sales, 'desig' => $manager, 'basic' => 2500000],
            ['first_name' => 'Selim', 'last_name' => 'Khan', 'dept' => $sales, 'desig' => $executive, 'basic' => 1800000],
            ['first_name' => 'Jabbar', 'last_name' => 'Ali', 'dept' => $warehouse, 'desig' => $manager, 'basic' => 2200000],
            ['first_name' => 'Hasina', 'last_name' => 'Akter', 'dept' => $admin, 'desig' => $executive, 'basic' => 2000000],
            ['first_name' => 'Noor', 'last_name' => 'Islam', 'dept' => $sales, 'desig' => $executive, 'basic' => 1600000],
        ];

        foreach ($employees as $emp) {
            Employee::firstOrCreate(
                ['company_id' => $company->id, 'first_name' => $emp['first_name'], 'last_name' => $emp['last_name']],
                [
                    'company_id' => $company->id,
                    'first_name' => $emp['first_name'],
                    'last_name' => $emp['last_name'],
                    'email' => strtolower($emp['first_name']).'@ruposhi.test',
                    'phone' => '017'.rand(10000000, 99999999),
                    'department_id' => $emp['dept']->id,
                    'designation_id' => $emp['desig']->id,
                    'joining_date' => now()->subMonths(rand(3, 24)),
                    'basic_salary' => $emp['basic'],
                    'status' => 'active',
                ],
            );
        }
    }
}
