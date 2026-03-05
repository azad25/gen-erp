<?php

namespace Tests\Feature\POS;

use App\Domain\Auth\Models\Branch;
use App\Domain\Auth\Models\Company;
use App\Domain\Auth\Models\User;
use App\Domain\Customer\Models\Customer;
use App\Domain\Accounting\Models\PaymentMethod;
use App\Domain\POS\Models\POSSale;
use App\Domain\POS\Models\POSSession;
use App\Domain\Product\Models\Product;
use App\Services\CompanyContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class POSSaleTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected Branch $branch;
    protected User $user;
    protected POSSession $session;
    protected Product $product;
    protected Customer $customer;
    protected PaymentMethod $paymentMethod;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        $this->branch = Branch::create([
            'company_id' => $this->company->id,
            'name' => 'Main Branch',
            'code' => 'MAIN',
            'is_active' => true,
        ]);
        $this->user = User::factory()->create();
        
        $this->company->users()->attach($this->user->id, [
            'role' => 'admin',
            'is_owner' => true,
            'is_active' => true,
        ]);

        $this->session = POSSession::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'opened_by' => $this->user->id,
            'opening_cash' => 1000000,
            'status' => 'open',
            'opened_at' => now(),
        ]);

        $this->product = Product::create([
            'company_id' => $this->company->id,
            'name' => 'Test Product',
            'slug' => 'test-product',
            'sku' => 'TEST-001',
            'type' => 'simple',
            'unit_price' => 500000,
            'is_active' => true,
        ]);

        $this->customer = Customer::create([
            'company_id' => $this->company->id,
            'name' => 'Test Customer',
            'email' => 'customer@test.com',
            'phone' => '01700000000',
        ]);

        $this->paymentMethod = PaymentMethod::create([
            'company_id' => $this->company->id,
            'name' => 'Cash',
            'code' => 'CASH',
            'is_active' => true,
        ]);

        CompanyContext::setActive($this->company);
        $this->actingAs($this->user);
    }

    public function test_can_create_pos_sale(): void
    {
        $response = $this->postJson('/api/v1/pos/sales', [
            'session_id' => $this->session->id,
            'customer_id' => $this->customer->id,
            'payment_method_id' => $this->paymentMethod->id,
            'amount_tendered' => 1500000, // 15,000 BDT
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'description' => $this->product->name,
                    'quantity' => 2,
                    'unit_price' => 500000, // 5,000 BDT
                ],
            ],
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'sale_number',
                    'total_amount',
                    'status',
                    'items',
                ],
            ]);

        $this->assertDatabaseHas('pos_sales', [
            'company_id' => $this->company->id,
            'pos_session_id' => $this->session->id,
            'customer_id' => $this->customer->id,
            'status' => 'completed',
        ]);

        $this->assertDatabaseHas('pos_sale_items', [
            'product_id' => $this->product->id,
            'quantity' => 2,
            'unit_price' => 500000,
        ]);
    }

    public function test_can_void_pos_sale(): void
    {
        $sale = POSSale::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'pos_session_id' => $this->session->id,
            'sale_number' => 'POS-TEST-001',
            'sale_date' => now(),
            'subtotal' => 1000000,
            'total_amount' => 1000000,
            'amount_tendered' => 1000000,
            'change_amount' => 0,
            'status' => 'completed',
        ]);

        $response = $this->postJson("/api/v1/pos/sales/{$sale->id}/void");

        $response->assertStatus(200);

        $this->assertDatabaseHas('pos_sales', [
            'id' => $sale->id,
            'status' => 'voided',
        ]);
    }

    public function test_cannot_void_already_voided_sale(): void
    {
        $sale = POSSale::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'pos_session_id' => $this->session->id,
            'sale_number' => 'POS-TEST-002',
            'sale_date' => now(),
            'subtotal' => 1000000,
            'total_amount' => 1000000,
            'amount_tendered' => 1000000,
            'change_amount' => 0,
            'status' => 'voided',
        ]);

        $response = $this->postJson("/api/v1/pos/sales/{$sale->id}/void");

        $response->assertStatus(422);
    }

    public function test_can_get_session_sales(): void
    {
        for ($i = 1; $i <= 3; $i++) {
            POSSale::create([
                'company_id' => $this->company->id,
                'branch_id' => $this->branch->id,
                'pos_session_id' => $this->session->id,
                'sale_number' => "POS-TEST-00{$i}",
                'sale_date' => now(),
                'subtotal' => 1000000,
                'total_amount' => 1000000,
                'amount_tendered' => 1000000,
                'change_amount' => 0,
                'status' => 'completed',
            ]);
        }

        $response = $this->getJson("/api/v1/pos/sessions/{$this->session->id}/sales");

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_sale_calculates_change_correctly(): void
    {
        $response = $this->postJson('/api/v1/pos/sales', [
            'session_id' => $this->session->id,
            'payment_method_id' => $this->paymentMethod->id,
            'amount_tendered' => 1500000, // 15,000 BDT
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'description' => $this->product->name,
                    'quantity' => 2,
                    'unit_price' => 500000, // 5,000 BDT each = 10,000 total
                ],
            ],
        ]);

        $response->assertStatus(201);

        $sale = POSSale::latest()->first();
        $this->assertEquals(500000, $sale->change_amount); // 5,000 BDT change
    }
}
