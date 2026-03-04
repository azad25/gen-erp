<?php

namespace Tests\Unit\Domain\Logistics;

use App\Domain\Logistics\Models\Shipment;
use App\Domain\Logistics\Models\ShipmentItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShipmentItemModelTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_create_a_shipment_item()
    {
        $shipment = Shipment::factory()->create();
        
        $item = ShipmentItem::create([
            'shipment_id' => $shipment->id,
            'product_name' => 'Test Product',
            'sku' => 'TEST123',
            'quantity' => 2,
            'unit_price' => 100.00,
            'total_price' => 200.00,
        ]);

        $this->assertInstanceOf(ShipmentItem::class, $item);
        $this->assertEquals('Test Product', $item->product_name);
        $this->assertEquals('TEST123', $item->sku);
        $this->assertEquals(2, $item->quantity);
        $this->assertEquals(100.00, $item->unit_price);
        $this->assertEquals(200.00, $item->total_price);
    }

    /** @test */
    public function it_belongs_to_a_shipment()
    {
        $shipment = Shipment::factory()->create();
        $item = ShipmentItem::factory()->create([
            'shipment_id' => $shipment->id,
        ]);

        $this->assertInstanceOf(Shipment::class, $item->shipment);
        $this->assertEquals($shipment->id, $item->shipment->id);
    }

    /** @test */
    public function it_can_calculate_total_weight()
    {
        $item = ShipmentItem::factory()->create([
            'quantity' => 3,
        ]);

        // Should use default weight of 0.5kg per item
        $totalWeight = $item->getTotalWeight();
        $this->assertEquals(1.5, $totalWeight); // 3 * 0.5
    }

    /** @test */
    public function it_can_get_display_name()
    {
        $item = ShipmentItem::factory()->create([
            'product_name' => 'Test Product',
            'sku' => 'TEST123',
        ]);

        $displayName = $item->getDisplayName();
        $this->assertEquals('Test Product (TEST123)', $displayName);
    }

    /** @test */
    public function it_can_get_display_name_without_sku()
    {
        $item = ShipmentItem::factory()->create([
            'product_name' => 'Test Product',
            'sku' => null,
        ]);

        $displayName = $item->getDisplayName();
        $this->assertEquals('Test Product', $displayName);
    }

    /** @test */
    public function it_casts_numeric_fields_correctly()
    {
        $item = ShipmentItem::factory()->create([
            'quantity' => '5',
            'unit_price' => '99.99',
            'total_price' => '499.95',
        ]);

        $this->assertIsInt($item->quantity);
        $this->assertIsString($item->unit_price); // Laravel casts decimal to string
        $this->assertIsString($item->total_price); // Laravel casts decimal to string
        $this->assertEquals(5, $item->quantity);
        $this->assertEquals('99.99', $item->unit_price);
        $this->assertEquals('499.95', $item->total_price);
    }
}