<?php

namespace Tests\Unit\Domain\Logistics;

use App\Domain\Auth\Models\Company;
use App\Domain\Auth\Models\User;
use App\Domain\Logistics\Enums\ReturnReason;
use App\Domain\Logistics\Models\Carrier;
use App\Domain\Logistics\Models\Shipment;
use App\Domain\Logistics\Models\ShipmentReturn;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShipmentReturnModelTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->company = Company::factory()->create();
        $this->user = User::factory()->create();
    }

    /** @test */
    public function it_can_create_a_shipment_return()
    {
        $shipment = Shipment::factory()->create();
        
        $return = ShipmentReturn::create([
            'company_id' => $this->company->id,
            'shipment_id' => $shipment->id,
            'reason' => ReturnReason::DAMAGED,
            'reason_details' => 'Product was damaged',
            'status' => 'requested',
            'requested_by' => $this->user->id,
        ]);

        $this->assertInstanceOf(ShipmentReturn::class, $return);
        $this->assertEquals(ReturnReason::DAMAGED, $return->reason);
        $this->assertEquals('Product was damaged', $return->reason_details);
        $this->assertEquals('requested', $return->status);
    }

    /** @test */
    public function it_automatically_generates_uuid_and_return_number()
    {
        $return = ShipmentReturn::factory()->create();
        
        $this->assertNotNull($return->uuid);
        $this->assertNotNull($return->return_number);
        $this->assertStringStartsWith('RET-', $return->return_number);
    }

    /** @test */
    public function it_automatically_sets_requested_at()
    {
        $return = ShipmentReturn::factory()->create();
        
        $this->assertNotNull($return->requested_at);
        $this->assertInstanceOf(\Carbon\Carbon::class, $return->requested_at);
    }

    /** @test */
    public function it_belongs_to_company_and_shipment()
    {
        $shipment = Shipment::factory()->create(['company_id' => $this->company->id]);
        $return = ShipmentReturn::factory()->create([
            'company_id' => $this->company->id,
            'shipment_id' => $shipment->id,
        ]);

        $this->assertInstanceOf(Company::class, $return->company);
        $this->assertInstanceOf(Shipment::class, $return->shipment);
        $this->assertEquals($this->company->id, $return->company->id);
        $this->assertEquals($shipment->id, $return->shipment->id);
    }

    /** @test */
    public function it_can_be_approved()
    {
        $return = ShipmentReturn::factory()->create(['status' => 'requested']);
        
        $return->approve($this->user);
        
        $this->assertEquals('approved', $return->status);
        $this->assertEquals($this->user->id, $return->approved_by);
        $this->assertNotNull($return->approved_at);
    }

    /** @test */
    public function it_can_be_rejected()
    {
        $return = ShipmentReturn::factory()->create(['status' => 'requested']);
        
        $return->reject($this->user);
        
        $this->assertEquals('rejected', $return->status);
        $this->assertEquals($this->user->id, $return->approved_by);
        $this->assertNotNull($return->approved_at);
    }

    /** @test */
    public function it_can_process_refund()
    {
        $return = ShipmentReturn::factory()->approved()->create();
        
        $return->processRefund(500.00, 'bank_transfer');
        
        $this->assertEquals('refunded', $return->status);
        $this->assertEquals(500.00, $return->refund_amount);
        $this->assertEquals('bank_transfer', $return->refund_method);
        $this->assertNotNull($return->refunded_at);
    }

    /** @test */
    public function it_can_check_status_states()
    {
        $pendingReturn = ShipmentReturn::factory()->create(['status' => 'requested']);
        $approvedReturn = ShipmentReturn::factory()->approved()->create();
        $completedReturn = ShipmentReturn::factory()->completed()->create();

        $this->assertTrue($pendingReturn->isPending());
        $this->assertFalse($pendingReturn->isApproved());
        $this->assertFalse($pendingReturn->isCompleted());

        $this->assertFalse($approvedReturn->isPending());
        $this->assertTrue($approvedReturn->isApproved());
        $this->assertFalse($approvedReturn->isCompleted());

        $this->assertFalse($completedReturn->isPending());
        $this->assertFalse($completedReturn->isApproved());
        $this->assertTrue($completedReturn->isCompleted());
    }

    /** @test */
    public function it_has_company_scope()
    {
        $otherCompany = Company::factory()->create();
        
        ShipmentReturn::factory()->create(['company_id' => $this->company->id]);
        ShipmentReturn::factory()->create(['company_id' => $otherCompany->id]);

        $companyReturns = ShipmentReturn::forCompany($this->company->id)->get();
        $this->assertCount(1, $companyReturns);
        $this->assertEquals($this->company->id, $companyReturns->first()->company_id);
    }

    /** @test */
    public function it_has_status_scopes()
    {
        ShipmentReturn::factory()->create(['status' => 'requested']);
        ShipmentReturn::factory()->approved()->create();
        ShipmentReturn::factory()->completed()->create();

        $this->assertCount(1, ShipmentReturn::pending()->get());
        $this->assertCount(1, ShipmentReturn::approved()->get());
        $this->assertCount(1, ShipmentReturn::completed()->get());
    }

    /** @test */
    public function it_casts_reason_to_enum()
    {
        $return = ShipmentReturn::factory()->create([
            'reason' => 'damaged',
        ]);

        $this->assertInstanceOf(ReturnReason::class, $return->reason);
        $this->assertEquals(ReturnReason::DAMAGED, $return->reason);
    }

    /** @test */
    public function it_casts_images_to_array()
    {
        $images = ['image1.jpg', 'image2.jpg'];
        $return = ShipmentReturn::factory()->create([
            'images' => $images,
        ]);

        $this->assertIsArray($return->images);
        $this->assertEquals($images, $return->images);
    }
}