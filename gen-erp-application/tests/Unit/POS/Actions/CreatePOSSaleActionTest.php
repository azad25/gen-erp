<?php

namespace Tests\Unit\POS\Actions;

use App\Domain\Auth\Models\Branch;
use App\Domain\Auth\Models\Company;
use App\Domain\Auth\Models\User;
use App\Domain\Customer\Models\Customer;
use App\Domain\Accounting\Models\PaymentMethod;
use App\Domain\POS\Actions\CreatePOSSaleAction;
use App\Domain\POS\DTOs\CreatePOSSaleData;
use App\Domain\POS\DTOs\POSSaleItemData;
use App\Domain\POS\Events\POSSaleCreated;
use App\Domain\POS\Exceptions\SessionClosedException;
use App\Domain\POS\Models\POSSale;
use App\Domain\POS\Models\POSSession;
use App\Domain\Product\Models\Product;
use App\Services\CompanyContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class CreatePOSSaleActionTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected Branch $branch;
    protected User $user;
    protected POSSession $session;
    protected Product $product;
    protected Customer $customer;
    protected PaymentMethod $paymentMethod;
    protected CreatePOSSaleAction $action;

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
        $this->action = new CreatePOSSaleAction();
    }

    public function test_can_create_sale_with_items(): void
    {
        Event::fake();

        $items = [
            new POSSaleItemData(
                productId: $this->product->id,
                variantId: null,
                description: $this->product->name,
                quantity: 2,
                unitPrice: 500000,
            ),
        ];

        $data = new CreatePOSSaleData(
            sessionId: $this->session->id,
            customerId: $this->customer->id,
            items: $items,
            amountTendered: 1500000,
            paymentMethodId: $this->paymentMethod->id,
        );

        $sale = $this->action->execute($data);

        $this->assertInstanceOf(POSSale::class, $sale);
        $this->assertEquals($this->company->id, $sale->company_id);
        $this->assertEquals($this->session->id, $sale->pos_session_id);
        $this->assertEquals(1000000, $sale->total_amount); // 2 * 500000
        $this->assertEquals(1500000, $sale->amount_tendered);
        $this->assertEquals(500000, $sale->change_amount);
        $this->assertEquals('completed', $sale->status);
        $this->assertCount(1, $sale->items);

        Event::assertDispatched(POSSaleCreated::class);
    }

    public function test_throws_exception_when_session_closed(): void
    {
        $closedSession = POSSession::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'opened_by' => $this->user->id,
            'opening_cash' => 1000000,
            'status' => 'closed',
            'opened_at' => now()->subDay(),
            'closed_at' => now()->subHour(),
            'closed_by' => $this->user->id,
        ]);

        $items = [
            new POSSaleItemData(
                productId: $this->product->id,
                variantId: null,
                description: $this->product->name,
                quantity: 1,
                unitPrice: 500000,
            ),
        ];

        $data = new CreatePOSSaleData(
            sessionId: $closedSession->id,
            customerId: null,
            items: $items,
            amountTendered: 500000,
            paymentMethodId: $this->paymentMethod->id,
        );

        $this->expectException(SessionClosedException::class);

        $this->action->execute($data);
    }

    public function test_calculates_totals_correctly(): void
    {
        $items = [
            new POSSaleItemData(
                productId: $this->product->id,
                variantId: null,
                description: 'Product 1',
                quantity: 2,
                unitPrice: 500000,
                discountAmount: 50000,
                taxAmount: 100000,
            ),
        ];

        $data = new CreatePOSSaleData(
            sessionId: $this->session->id,
            customerId: null,
            items: $items,
            amountTendered: 1500000,
            paymentMethodId: $this->paymentMethod->id,
        );

        $sale = $this->action->execute($data);

        // subtotal = 2 * 500000 = 1000000
        // discount = 50000
        // tax = 100000
        // total = 1000000 - 50000 + 100000 = 1050000
        $this->assertEquals(1000000, $sale->subtotal);
        $this->assertEquals(50000, $sale->discount_amount);
        $this->assertEquals(100000, $sale->tax_amount);
        $this->assertEquals(1050000, $sale->total_amount);
    }

    public function test_generates_unique_sale_number(): void
    {
        $items = [
            new POSSaleItemData(
                productId: $this->product->id,
                variantId: null,
                description: $this->product->name,
                quantity: 1,
                unitPrice: 500000,
            ),
        ];

        $data = new CreatePOSSaleData(
            sessionId: $this->session->id,
            customerId: null,
            items: $items,
            amountTendered: 500000,
            paymentMethodId: $this->paymentMethod->id,
        );

        $sale1 = $this->action->execute($data);
        $sale2 = $this->action->execute($data);

        $this->assertNotEquals($sale1->sale_number, $sale2->sale_number);
        $this->assertStringStartsWith('POS-', $sale1->sale_number);
        $this->assertStringStartsWith('POS-', $sale2->sale_number);
    }
}
