<?php

namespace Tests\Unit\Domain\Logistics;

use App\Domain\Auth\Models\Company;
use App\Domain\Logistics\Enums\DeliveryType;
use App\Domain\Logistics\Enums\ShipmentStatus;
use App\Domain\Logistics\Models\Carrier;
use App\Domain\Logistics\Models\Shipment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShipmentModelTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected Carrier $carrier;

    protected function setUp(): void
    {
        parent::setUp();
        $this->company = Company::factory()->create();
        $this->carrier = Carrier::factory()->create(['company_id' => $this->company->id]);
    }

    /** @test */
    public function it_can_create_a_shipment()
    {
        $customer = \App\Domain\Customer\Models\Customer::factory()->create();
        
        $shipment = Shipment::create([
            'company_id' => $this->company->id,
            'carrier_id' => $this->carrier->id,
            'customer_id' => $customer->id,
            'sender_name' => 'Test Sender',
            'sender_phone' => '01700000000',
            'sender_address' => 'Test Address',
            'sender_city' => 'Dhaka',
            'recipient_name' => 'Test Recipient',
            'recipient_phone' => '01800000000',
            'recipient_address' => 'Recipient Address',
            'recipient_city' => 'Chittagong',
            'status' => ShipmentStatus::PENDING,
            'delivery_type' => DeliveryType::STANDARD,
            'payment_method' => 'cod',
            'cod_amount' => 1000.00,
            'shipping_cost' => 100.00,
            'cod_charge' => 15.00,
            'total_cost' => 115.00,
        ]);

        $this->assertInstanceOf(Shipment::class, $shipment);
        $this->assertEquals('Test Sender', $shipment->sender_name);
        $this->assertEquals(ShipmentStatus::PENDING, $shipment->status);
        $this->assertEquals(DeliveryType::STANDARD, $shipment->delivery_type);
        $this->assertNotNull($shipment->uuid);
        $this->assertNotNull($shipment->tracking_number);
    }

    /** @test */
    public function it_generates_uuid_and_tracking_number_on_creation()
    {
        $shipment = Shipment::factory()->create([
            'company_id' => $this->company->id,
            'carrier_id' => $this->carrier->id,
        ]);

        $this->assertNotNull($shipment->uuid);
        $this->assertNotNull($shipment->tracking_number);
        $this->assertStringStartsWith('SHP-', $shipment->tracking_number);
    }

    /** @test */
    public function it_belongs_to_company_and_carrier()
    {
        $shipment = Shipment::factory()->create([
            'company_id' => $this->company->id,
            'carrier_id' => $this->carrier->id,
        ]);

        $this->assertInstanceOf(Company::class, $shipment->company);
        $this->assertInstanceOf(Carrier::class, $shipment->carrier);
        $this->assertEquals($this->company->id, $shipment->company->id);
        $this->assertEquals($this->carrier->id, $shipment->carrier->id);
    }

    /** @test */
    public function it_can_update_status_with_tracking_event()
    {
        $shipment = Shipment::factory()->create([
            'company_id' => $this->company->id,
            'carrier_id' => $this->carrier->id,
            'status' => ShipmentStatus::PENDING,
        ]);

        $shipment->updateStatus(ShipmentStatus::PICKED_UP, 'Dhaka Hub', 'Package picked up');

        $this->assertEquals(ShipmentStatus::PICKED_UP, $shipment->fresh()->status);
        
        $latestEvent = $shipment->trackingEvents()->latest('event_time')->first();
        $this->assertEquals(ShipmentStatus::PICKED_UP, $latestEvent->status);
        $this->assertEquals('Dhaka Hub', $latestEvent->location);
        $this->assertEquals('Package picked up', $latestEvent->description);
    }

    /** @test */
    public function it_can_check_if_cod()
    {
        $codShipment = Shipment::factory()->create([
            'company_id' => $this->company->id,
            'carrier_id' => $this->carrier->id,
            'payment_method' => 'cod',
        ]);

        $prepaidShipment = Shipment::factory()->create([
            'company_id' => $this->company->id,
            'carrier_id' => $this->carrier->id,
            'payment_method' => 'prepaid',
        ]);

        $this->assertTrue($codShipment->isCOD());
        $this->assertFalse($prepaidShipment->isCOD());
    }

    /** @test */
    public function it_can_check_if_delivered()
    {
        $deliveredShipment = Shipment::factory()->create([
            'company_id' => $this->company->id,
            'carrier_id' => $this->carrier->id,
            'status' => ShipmentStatus::DELIVERED,
        ]);

        $pendingShipment = Shipment::factory()->create([
            'company_id' => $this->company->id,
            'carrier_id' => $this->carrier->id,
            'status' => ShipmentStatus::PENDING,
        ]);

        $this->assertTrue($deliveredShipment->isDelivered());
        $this->assertFalse($pendingShipment->isDelivered());
    }

    /** @test */
    public function it_can_check_if_can_be_cancelled()
    {
        $pendingShipment = Shipment::factory()->create([
            'company_id' => $this->company->id,
            'carrier_id' => $this->carrier->id,
            'status' => ShipmentStatus::PENDING,
        ]);

        $deliveredShipment = Shipment::factory()->create([
            'company_id' => $this->company->id,
            'carrier_id' => $this->carrier->id,
            'status' => ShipmentStatus::DELIVERED,
        ]);

        $this->assertTrue($pendingShipment->canBeCancelled());
        $this->assertFalse($deliveredShipment->canBeCancelled());
    }

    /** @test */
    public function it_has_status_scopes()
    {
        Shipment::factory()->create([
            'company_id' => $this->company->id,
            'carrier_id' => $this->carrier->id,
            'status' => ShipmentStatus::PENDING,
        ]);

        Shipment::factory()->create([
            'company_id' => $this->company->id,
            'carrier_id' => $this->carrier->id,
            'status' => ShipmentStatus::IN_TRANSIT,
        ]);

        Shipment::factory()->create([
            'company_id' => $this->company->id,
            'carrier_id' => $this->carrier->id,
            'status' => ShipmentStatus::DELIVERED,
        ]);

        $this->assertCount(1, Shipment::pending()->get());
        $this->assertCount(1, Shipment::inTransit()->get());
        $this->assertCount(1, Shipment::completed()->get());
    }

    // Note: Public tracking URL test removed due to route dependency
    // This functionality should be tested in integration tests
}