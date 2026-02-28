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
 * Apex Garments Ltd — A manufacturing/RMG export company scenario.
 */
class ApexGarmentsSeeder
{
    public function run(Company $company, User $owner): void
    {
        CompanyContext::setActive($company);

        // ── Team Members ────────────────────────────────────
        $productionMgr = User::firstOrCreate(
            ['email' => 'production@apex.test'],
            ['name' => 'Rafiq Production Manager', 'password' => Hash::make('Password@123'), 'email_verified_at' => now()],
        );
        CompanyUser::firstOrCreate(
            ['company_id' => $company->id, 'user_id' => $productionMgr->id],
            ['role' => CompanyRole::WAREHOUSE->value, 'is_active' => true, 'joined_at' => now()],
        );

        $exportMgr = User::firstOrCreate(
            ['email' => 'export@apex.test'],
            ['name' => 'Sultana Export Manager', 'password' => Hash::make('Password@123'), 'email_verified_at' => now()],
        );
        CompanyUser::firstOrCreate(
            ['company_id' => $company->id, 'user_id' => $exportMgr->id],
            ['role' => CompanyRole::SALES->value, 'is_active' => true, 'joined_at' => now()],
        );

        // ── Warehouses ──────────────────────────────────────
        $rawWh = Warehouse::firstOrCreate(
            ['company_id' => $company->id, 'code' => 'RAW-01'],
            ['company_id' => $company->id, 'name' => 'Raw Materials Store', 'code' => 'RAW-01', 'address' => 'Ashulia, Savar', 'is_active' => true],
        );
        $finishedWh = Warehouse::firstOrCreate(
            ['company_id' => $company->id, 'code' => 'FIN-01'],
            ['company_id' => $company->id, 'name' => 'Finished Goods Warehouse', 'code' => 'FIN-01', 'address' => 'Ashulia, Savar', 'is_active' => true],
        );

        // ── Categories ──────────────────────────────────────
        $categories = [];
        foreach (['Raw Materials', 'Finished Garments', 'Accessories', 'Packaging'] as $name) {
            $categories[$name] = ProductCategory::firstOrCreate(
                ['company_id' => $company->id, 'name' => $name],
                ['company_id' => $company->id, 'name' => $name, 'slug' => Str::slug($name)],
            );
        }

        // ── Products ────────────────────────────────────────
        $zeroRated = TaxGroup::where('company_id', $company->id)->where('name', 'Zero Rated')->first();
        $vatStd = TaxGroup::where('company_id', $company->id)->where('name', 'VAT 15%')->first();

        $productItems = [
            // Raw materials
            ['name' => 'Cotton Fabric 60" (per yard)', 'sku' => 'RAW-001', 'cat' => 'Raw Materials', 'cost' => 25000, 'sell' => 30000, 'tax' => 'zero'],
            ['name' => 'Polyester Fabric 58" (per yard)', 'sku' => 'RAW-002', 'cat' => 'Raw Materials', 'cost' => 18000, 'sell' => 22000, 'tax' => 'zero'],
            ['name' => 'Denim Fabric 62" (per yard)', 'sku' => 'RAW-003', 'cat' => 'Raw Materials', 'cost' => 35000, 'sell' => 42000, 'tax' => 'zero'],
            ['name' => 'Sewing Thread (5000m spool)', 'sku' => 'RAW-004', 'cat' => 'Raw Materials', 'cost' => 4000, 'sell' => 6000, 'tax' => 'zero'],
            ['name' => 'Buttons (1000 pcs)', 'sku' => 'ACC-001', 'cat' => 'Accessories', 'cost' => 5000, 'sell' => 7500, 'tax' => 'zero'],
            ['name' => 'Zippers 7" (100 pcs)', 'sku' => 'ACC-002', 'cat' => 'Accessories', 'cost' => 15000, 'sell' => 20000, 'tax' => 'zero'],
            ['name' => 'Labels (1000 pcs)', 'sku' => 'ACC-003', 'cat' => 'Accessories', 'cost' => 8000, 'sell' => 12000, 'tax' => 'zero'],
            ['name' => 'Poly Bags (500 pcs)', 'sku' => 'PKG-001', 'cat' => 'Packaging', 'cost' => 3000, 'sell' => 5000, 'tax' => 'std'],
            ['name' => 'Carton Box (25 pcs)', 'sku' => 'PKG-002', 'cat' => 'Packaging', 'cost' => 12500, 'sell' => 17500, 'tax' => 'std'],

            // Finished goods (export — zero rated)
            ['name' => 'Men\'s Basic T-Shirt (FOB)', 'sku' => 'FIN-001', 'cat' => 'Finished Garments', 'cost' => 15000, 'sell' => 25000, 'tax' => 'zero'],
            ['name' => 'Men\'s Polo Shirt (FOB)', 'sku' => 'FIN-002', 'cat' => 'Finished Garments', 'cost' => 22000, 'sell' => 38000, 'tax' => 'zero'],
            ['name' => 'Women\'s Denim Jeans (FOB)', 'sku' => 'FIN-003', 'cat' => 'Finished Garments', 'cost' => 35000, 'sell' => 55000, 'tax' => 'zero'],
            ['name' => 'Kids Hoodie (FOB)', 'sku' => 'FIN-004', 'cat' => 'Finished Garments', 'cost' => 20000, 'sell' => 32000, 'tax' => 'zero'],
            ['name' => 'Men\'s Formal Shirt (FOB)', 'sku' => 'FIN-005', 'cat' => 'Finished Garments', 'cost' => 28000, 'sell' => 45000, 'tax' => 'zero'],
        ];

        $products = [];
        foreach ($productItems as $item) {
            $category = $categories[$item['cat']] ?? null;
            $tax = $item['tax'] === 'zero' ? $zeroRated : $vatStd;
            $products[] = Product::firstOrCreate(
                ['company_id' => $company->id, 'sku' => $item['sku']],
                [
                    'company_id' => $company->id,
                    'name' => $item['name'],
                    'sku' => $item['sku'],
                    'slug' => Str::slug($item['name']),
                    'category_id' => $category?->id,
                    'tax_group_id' => $tax?->id,
                    'product_type' => ProductType::PRODUCT,
                    'cost_price' => $item['cost'],
                    'selling_price' => $item['sell'],
                    'unit' => str_contains($item['sku'], 'RAW') ? 'yard' : 'pcs',
                    'track_inventory' => true,
                    'low_stock_threshold' => 100,
                    'is_active' => true,
                ],
            );
        }

        // ── Customers (international buyers) ────────────────
        $customerData = [
            ['name' => 'H&M Bulk Procurement', 'phone' => '01711000001', 'district' => 'Dhaka'],
            ['name' => 'Primark Sourcing', 'phone' => '01711000002', 'district' => 'Dhaka'],
            ['name' => 'Walmart Asia Pacific', 'phone' => '01711000003', 'district' => 'Dhaka'],
            ['name' => 'Zara Inditex BD', 'phone' => '01711000004', 'district' => 'Dhaka'],
            ['name' => 'Target Corp BD Office', 'phone' => '01711000005', 'district' => 'Dhaka'],
            ['name' => 'Next PLC Bangladesh', 'phone' => '01711000006', 'district' => 'Dhaka'],
            ['name' => 'Decathlon BD Liaison', 'phone' => '01711000007', 'district' => 'Dhaka'],
        ];

        $customers = [];
        foreach ($customerData as $data) {
            $customers[] = Customer::firstOrCreate(
                ['company_id' => $company->id, 'phone' => $data['phone']],
                array_merge($data, ['company_id' => $company->id, 'credit_limit' => 50000000]),
            );
        }

        // ── Suppliers ───────────────────────────────────────
        $supplierData = [
            ['name' => 'Noman Group Textiles', 'phone' => '01711100001', 'vat_bin' => 'NOM123456789'],
            ['name' => 'DBL Textiles', 'phone' => '01711100002', 'vat_bin' => 'DBL123456789'],
            ['name' => 'Epyllion Group', 'phone' => '01711100003', 'vat_bin' => 'EPY123456789'],
            ['name' => 'SQ Textiles', 'phone' => '01711100004', 'vat_bin' => 'SQT123456789'],
        ];

        foreach ($supplierData as $data) {
            Supplier::firstOrCreate(
                ['company_id' => $company->id, 'name' => $data['name']],
                array_merge($data, ['company_id' => $company->id, 'address' => 'Gazipur/Narayanganj, Bangladesh']),
            );
        }

        // ── Export Invoices (8 large orders) ────────────────
        $finishedProducts = array_values(array_filter($products, fn ($p) => str_starts_with($p->sku, 'FIN-')));

        for ($i = 0; $i < 8; $i++) {
            $customer = $customers[array_rand($customers)];
            $invoiceDate = now()->subDays(rand(5, 90));
            $status = collect([InvoiceStatus::PAID, InvoiceStatus::SENT, InvoiceStatus::SENT])->random();

            $lineItems = [];
            $subtotal = 0;
            $itemCount = rand(1, 3);

            for ($j = 0; $j < $itemCount; $j++) {
                $product = $finishedProducts[array_rand($finishedProducts)];
                $qty = rand(500, 5000);
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
            $amountPaid = $status === InvoiceStatus::PAID ? $totalAmount : (int) ($totalAmount * 0.3);

            $invoice = Invoice::create([
                'company_id' => $company->id,
                'customer_id' => $customer->id,
                'warehouse_id' => $finishedWh->id,
                'invoice_date' => $invoiceDate,
                'due_date' => $invoiceDate->copy()->addDays(60),
                'status' => $status,
                'subtotal' => $subtotal,
                'tax_amount' => 0,
                'total_amount' => $totalAmount,
                'amount_paid' => $amountPaid,
                'notes' => 'Export Order — FOB Chittagong',
            ]);

            foreach ($lineItems as $item) {
                InvoiceItem::create(array_merge($item, ['invoice_id' => $invoice->id]));
            }
        }

        // ── HR (Manufacturing workforce) ────────────────────
        $production = Department::firstOrCreate(
            ['company_id' => $company->id, 'name' => 'Production'],
            ['company_id' => $company->id, 'name' => 'Production'],
        );
        $quality = Department::firstOrCreate(
            ['company_id' => $company->id, 'name' => 'Quality Control'],
            ['company_id' => $company->id, 'name' => 'Quality Control'],
        );
        $export = Department::firstOrCreate(
            ['company_id' => $company->id, 'name' => 'Export & Merchandising'],
            ['company_id' => $company->id, 'name' => 'Export & Merchandising'],
        );
        $compliance = Department::firstOrCreate(
            ['company_id' => $company->id, 'name' => 'Compliance'],
            ['company_id' => $company->id, 'name' => 'Compliance'],
        );

        $gm = Designation::firstOrCreate(
            ['company_id' => $company->id, 'name' => 'General Manager'],
            ['company_id' => $company->id, 'name' => 'General Manager'],
        );
        $agm = Designation::firstOrCreate(
            ['company_id' => $company->id, 'name' => 'AGM'],
            ['company_id' => $company->id, 'name' => 'AGM'],
        );
        $operator = Designation::firstOrCreate(
            ['company_id' => $company->id, 'name' => 'Machine Operator'],
            ['company_id' => $company->id, 'name' => 'Machine Operator'],
        );
        $inspector = Designation::firstOrCreate(
            ['company_id' => $company->id, 'name' => 'QC Inspector'],
            ['company_id' => $company->id, 'name' => 'QC Inspector'],
        );

        $employees = [
            ['first_name' => 'Rafiq', 'last_name' => 'Ahmed', 'dept' => $production, 'desig' => $gm, 'basic' => 6000000],
            ['first_name' => 'Sultana', 'last_name' => 'Razia', 'dept' => $export, 'desig' => $agm, 'basic' => 5000000],
            ['first_name' => 'Momin', 'last_name' => 'Khan', 'dept' => $production, 'desig' => $operator, 'basic' => 1800000],
            ['first_name' => 'Bilkis', 'last_name' => 'Begum', 'dept' => $production, 'desig' => $operator, 'basic' => 1800000],
            ['first_name' => 'Jalal', 'last_name' => 'Uddin', 'dept' => $quality, 'desig' => $inspector, 'basic' => 2200000],
            ['first_name' => 'Nargis', 'last_name' => 'Akter', 'dept' => $quality, 'desig' => $inspector, 'basic' => 2100000],
            ['first_name' => 'Aziz', 'last_name' => 'Rahman', 'dept' => $compliance, 'desig' => $agm, 'basic' => 4500000],
            ['first_name' => 'Kamrul', 'last_name' => 'Hasan', 'dept' => $export, 'desig' => $agm, 'basic' => 4000000],
        ];

        foreach ($employees as $emp) {
            Employee::firstOrCreate(
                ['company_id' => $company->id, 'first_name' => $emp['first_name'], 'last_name' => $emp['last_name']],
                [
                    'company_id' => $company->id,
                    'first_name' => $emp['first_name'],
                    'last_name' => $emp['last_name'],
                    'email' => strtolower($emp['first_name']).'@apex.test',
                    'phone' => '017'.rand(10000000, 99999999),
                    'department_id' => $emp['dept']->id,
                    'designation_id' => $emp['desig']->id,
                    'joining_date' => now()->subMonths(rand(6, 48)),
                    'basic_salary' => $emp['basic'],
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
