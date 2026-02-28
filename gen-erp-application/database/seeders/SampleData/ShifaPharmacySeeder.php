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
 * Shifa Pharmacy — A pharmacy/healthcare business with medicines, health products.
 */
class ShifaPharmacySeeder
{
    public function run(Company $company, User $owner): void
    {
        CompanyContext::setActive($company);

        // ── Team ────────────────────────────────────────────
        $pharmacist = User::firstOrCreate(
            ['email' => 'pharmacist@shifa.test'],
            ['name' => 'Dr. Aminul Islam', 'password' => Hash::make('Password@123'), 'email_verified_at' => now()],
        );
        CompanyUser::firstOrCreate(
            ['company_id' => $company->id, 'user_id' => $pharmacist->id],
            ['role' => CompanyRole::SALES->value, 'is_active' => true, 'joined_at' => now()],
        );

        // ── Warehouse ───────────────────────────────────────
        $warehouse = Warehouse::firstOrCreate(
            ['company_id' => $company->id, 'code' => 'MED-01'],
            ['company_id' => $company->id, 'name' => 'Medicine Store', 'code' => 'MED-01', 'address' => 'Dhanmondi, Dhaka', 'is_active' => true],
        );

        // ── Categories ──────────────────────────────────────
        $categories = [];
        foreach (['Medicines', 'OTC Drugs', 'Health Supplements', 'Personal Care', 'Medical Devices', 'Baby Care'] as $name) {
            $categories[$name] = ProductCategory::firstOrCreate(
                ['company_id' => $company->id, 'name' => $name],
                ['company_id' => $company->id, 'name' => $name, 'slug' => Str::slug($name)],
            );
        }

        // ── Products (20 medicines) ─────────────────────────
        $vatGroup = TaxGroup::where('company_id', $company->id)->where('name', 'Zero Rated')->first();
        $vatStd = TaxGroup::where('company_id', $company->id)->where('name', 'VAT 15%')->first();

        $medicines = [
            ['name' => 'Napa Extra 500mg (10 tab)', 'sku' => 'MED-001', 'cat' => 'Medicines', 'cost' => 1800, 'sell' => 2500, 'tax' => 'zero'],
            ['name' => 'Seclo 20mg (10 cap)', 'sku' => 'MED-002', 'cat' => 'Medicines', 'cost' => 5000, 'sell' => 6500, 'tax' => 'zero'],
            ['name' => 'Amoxycillin 500mg (10 cap)', 'sku' => 'MED-003', 'cat' => 'Medicines', 'cost' => 4000, 'sell' => 5500, 'tax' => 'zero'],
            ['name' => 'Losectil 20mg (14 cap)', 'sku' => 'MED-004', 'cat' => 'Medicines', 'cost' => 8000, 'sell' => 10500, 'tax' => 'zero'],
            ['name' => 'Montelukast 10mg (30 tab)', 'sku' => 'MED-005', 'cat' => 'Medicines', 'cost' => 15000, 'sell' => 20000, 'tax' => 'zero'],
            ['name' => 'Panadol Cold & Flu (12 tab)', 'sku' => 'OTC-001', 'cat' => 'OTC Drugs', 'cost' => 3000, 'sell' => 4500, 'tax' => 'zero'],
            ['name' => 'Savlon Antiseptic 100ml', 'sku' => 'OTC-002', 'cat' => 'OTC Drugs', 'cost' => 7000, 'sell' => 9500, 'tax' => 'zero'],
            ['name' => 'Oral Saline (25 sachets)', 'sku' => 'OTC-003', 'cat' => 'OTC Drugs', 'cost' => 5000, 'sell' => 6250, 'tax' => 'zero'],
            ['name' => 'Vitamin D3 1000IU (30 cap)', 'sku' => 'SUP-001', 'cat' => 'Health Supplements', 'cost' => 20000, 'sell' => 28000, 'tax' => 'std'],
            ['name' => 'Calcium + Vit D (60 tab)', 'sku' => 'SUP-002', 'cat' => 'Health Supplements', 'cost' => 30000, 'sell' => 40000, 'tax' => 'std'],
            ['name' => 'Iron + Folic Acid (30 tab)', 'sku' => 'SUP-003', 'cat' => 'Health Supplements', 'cost' => 12000, 'sell' => 16000, 'tax' => 'std'],
            ['name' => 'Digital Thermometer', 'sku' => 'DEV-001', 'cat' => 'Medical Devices', 'cost' => 15000, 'sell' => 25000, 'tax' => 'std'],
            ['name' => 'Blood Pressure Monitor', 'sku' => 'DEV-002', 'cat' => 'Medical Devices', 'cost' => 150000, 'sell' => 200000, 'tax' => 'std'],
            ['name' => 'Surgical Mask (50 pcs)', 'sku' => 'DEV-003', 'cat' => 'Medical Devices', 'cost' => 15000, 'sell' => 20000, 'tax' => 'std'],
            ['name' => 'Hand Sanitizer 500ml', 'sku' => 'PC-001', 'cat' => 'Personal Care', 'cost' => 10000, 'sell' => 14000, 'tax' => 'std'],
            ['name' => 'Baby Diaper (Medium, 30 pcs)', 'sku' => 'BAB-001', 'cat' => 'Baby Care', 'cost' => 40000, 'sell' => 55000, 'tax' => 'std'],
            ['name' => 'Baby Lotion 200ml', 'sku' => 'BAB-002', 'cat' => 'Baby Care', 'cost' => 15000, 'sell' => 22000, 'tax' => 'std'],
        ];

        $products = [];
        foreach ($medicines as $item) {
            $category = $categories[$item['cat']] ?? null;
            $tax = $item['tax'] === 'zero' ? $vatGroup : $vatStd;
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
                    'unit' => 'pcs',
                    'track_inventory' => true,
                    'low_stock_threshold' => 10,
                    'is_active' => true,
                ],
            );
        }

        // ── Customers ───────────────────────────────────────
        $customerData = [
            ['name' => 'Dr. Mahmudul Hasan', 'phone' => '01611111111', 'district' => 'Dhaka'],
            ['name' => 'Rupa Begum', 'phone' => '01622222222', 'district' => 'Dhaka'],
            ['name' => 'Ziaul Haque', 'phone' => '01633333333', 'district' => 'Gazipur'],
            ['name' => 'Nasreen Pharmacy', 'phone' => '01644444444', 'district' => 'Narsingdi'],
            ['name' => 'Community Health Centre', 'phone' => '01655555555', 'district' => 'Dhaka'],
            ['name' => 'Rashed Kabir', 'phone' => '01666666666', 'district' => 'Munshiganj'],
            ['name' => 'Marzia Akter', 'phone' => '01677777777', 'district' => 'Dhaka'],
            ['name' => 'Habibur Rahman', 'phone' => '01688888888', 'district' => 'Chandpur'],
            ['name' => 'Shamima Khatun', 'phone' => '01699999999', 'district' => 'Dhaka'],
            ['name' => 'Faruk Ahmed', 'phone' => '01311111111', 'district' => 'Kishoreganj'],
        ];

        $customers = [];
        foreach ($customerData as $data) {
            $customers[] = Customer::firstOrCreate(
                ['company_id' => $company->id, 'phone' => $data['phone']],
                array_merge($data, ['company_id' => $company->id, 'credit_limit' => 2000000]),
            );
        }

        // ── Suppliers ───────────────────────────────────────
        $supplierData = [
            ['name' => 'Beximco Pharmaceuticals', 'phone' => '01511111111', 'vat_bin' => 'BEX123456789'],
            ['name' => 'Square Pharmaceuticals', 'phone' => '01522222222', 'vat_bin' => 'SQR123456789'],
            ['name' => 'Incepta Pharmaceuticals', 'phone' => '01533333333', 'vat_bin' => 'INC123456789'],
            ['name' => 'Renata Limited', 'phone' => '01544444444', 'vat_bin' => 'REN123456789'],
            ['name' => 'Opsonin Pharma', 'phone' => '01555555555', 'vat_bin' => 'OPS123456789'],
        ];

        foreach ($supplierData as $data) {
            Supplier::firstOrCreate(
                ['company_id' => $company->id, 'name' => $data['name']],
                array_merge($data, ['company_id' => $company->id, 'address' => 'Dhaka, Bangladesh']),
            );
        }

        // ── Invoices (12 prescriptions) ─────────────────────
        for ($i = 0; $i < 12; $i++) {
            $customer = $customers[array_rand($customers)];
            $invoiceDate = now()->subDays(rand(1, 45));
            $status = collect([InvoiceStatus::PAID, InvoiceStatus::PAID, InvoiceStatus::SENT])->random();

            $lineItems = [];
            $subtotal = 0;
            $itemCount = rand(2, 4);

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

        // ── HR ──────────────────────────────────────────────
        $dispensary = Department::firstOrCreate(
            ['company_id' => $company->id, 'name' => 'Dispensary'],
            ['company_id' => $company->id, 'name' => 'Dispensary'],
        );
        $pharmaDesig = Designation::firstOrCreate(
            ['company_id' => $company->id, 'name' => 'Pharmacist'],
            ['company_id' => $company->id, 'name' => 'Pharmacist'],
        );
        $assistDesig = Designation::firstOrCreate(
            ['company_id' => $company->id, 'name' => 'Sales Assistant'],
            ['company_id' => $company->id, 'name' => 'Sales Assistant'],
        );

        $employees = [
            ['first_name' => 'Aminul', 'last_name' => 'Islam', 'desig' => $pharmaDesig, 'basic' => 3500000],
            ['first_name' => 'Shirin', 'last_name' => 'Akter', 'desig' => $assistDesig, 'basic' => 1500000],
            ['first_name' => 'Nasir', 'last_name' => 'Uddin', 'desig' => $assistDesig, 'basic' => 1400000],
        ];

        foreach ($employees as $emp) {
            Employee::firstOrCreate(
                ['company_id' => $company->id, 'first_name' => $emp['first_name'], 'last_name' => $emp['last_name']],
                [
                    'company_id' => $company->id,
                    'first_name' => $emp['first_name'],
                    'last_name' => $emp['last_name'],
                    'email' => strtolower($emp['first_name']).'@shifa.test',
                    'phone' => '016'.rand(10000000, 99999999),
                    'department_id' => $dispensary->id,
                    'designation_id' => $emp['desig']->id,
                    'joining_date' => now()->subMonths(rand(6, 36)),
                    'basic_salary' => $emp['basic'],
                    'status' => 'active',
                ],
            );
        }

        // ── Expenses ────────────────────────────────────────
        $expenses = [
            ['description' => 'Shop Rent - Feb 2026', 'amount' => 5000000, 'expense_date' => now()->subDays(5)],
            ['description' => 'Electricity Bill', 'amount' => 600000, 'expense_date' => now()->subDays(10)],
            ['description' => 'Drug License Renewal', 'amount' => 250000, 'expense_date' => now()->subDays(30)],
            ['description' => 'Refrigerator Maintenance', 'amount' => 150000, 'expense_date' => now()->subDays(15)],
            ['description' => 'DGDA Registration Fee', 'amount' => 100000, 'expense_date' => now()->subDays(60)],
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
