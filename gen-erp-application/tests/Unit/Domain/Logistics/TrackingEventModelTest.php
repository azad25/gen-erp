<?php

namespace Tests\Unit\Domain\Logistics;

use App\Domain\Logistics\Enums\ShipmentStatus;
use App\Domain\Logistics\Models\Shipment;
use App\Domain\Logistics\Models\TrackingEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrackingEventModelTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_create_a_tracking_event()
    {
        $shipment = Shipment::factory()->create();
        
        $event = TrackingEvent::create([
            'shipment_id' => $shipment->id,
            'status' => ShipmentStatus::PICKED_UP,
            'location' => 'Dhaka, Bangladesh',
            'description' => 'Package picked up',
            'event_time' => now(),
        ]);

        $this->assertInstanceOf(TrackingEvent::class, $event);
        $this->assertEquals(ShipmentStatus::PICKED_UP, $event->status);
        $this->assertEquals('Dhaka, Bangladesh', $event->location);
        $this->assertEquals('Package picked up', $event->description);
    }

    /** @test */
    public function it_belongs_to_a_shipment()
    {
        $shipment = Shipment::factory()->create();
        $event = TrackingEvent::factory()->create([
            'shipment_id' => $shipment->id,
        ]);

        $this->assertInstanceOf(Shipment::class, $event->shipment);
        $this->assertEquals($shipment->id, $event->shipment->id);
    }

    /** @test */
    public function it_automatically_sets_created_at_on_creation()
    {
        $event = TrackingEvent::factory()->create();
        
        $this->assertNotNull($event->created_at);
        $this->assertInstanceOf(\Carbon\Carbon::class, $event->created_at);
    }

    /** @test */
    public function it_casts_status_to_enum()
    {
        $event = TrackingEvent::factory()->create([
            'status' => 'delivered',
        ]);

        $this->assertInstanceOf(ShipmentStatus::class, $event->status);
        $this->assertEquals(ShipmentStatus::DELIVERED, $event->status);
    }

    /** @test */
    public function it_casts_event_time_to_datetime()
    {
        $event = TrackingEvent::factory()->create([
            'event_time' => '2026-03-04 10:30:00',
        ]);

        $this->assertInstanceOf(\Carbon\Carbon::class, $event->event_time);
    }

    /** @test */
    public function it_can_format_time()
    {
        $event = TrackingEvent::factory()->create([
            'event_time' => '2026-03-04 14:30:00',
        ]);

        $formattedTime = $event->getFormattedTime();
        $this->assertEquals('Mar 04, 2026 02:30 PM', $formattedTime);
    }

    /** @test */
    public function it_can_check_if_delivered()
    {
        $deliveredEvent = TrackingEvent::factory()->delivered()->create();
        $pendingEvent = TrackingEvent::factory()->pending()->create();

        $this->assertTrue($deliveredEvent->isDelivered());
        $this->assertFalse($pendingEvent->isDelivered());
    }

    /** @test */
    public function it_can_check_if_failed()
    {
        $failedEvent = TrackingEvent::factory()->failed()->create();
        $deliveredEvent = TrackingEvent::factory()->delivered()->create();

        $this->assertTrue($failedEvent->isFailed());
        $this->assertFalse($deliveredEvent->isFailed());
    }

    /** @test */
    public function it_has_latest_scope()
    {
        $shipment = Shipment::factory()->create();
        
        $firstEvent = TrackingEvent::factory()->create([
            'shipment_id' => $shipment->id,
            'event_time' => now()->subHours(2),
        ]);
        
        $latestEvent = TrackingEvent::factory()->create([
            'shipment_id' => $shipment->id,
            'event_time' => now()->subHour(),
        ]);

        $events = TrackingEvent::where('shipment_id', $shipment->id)->latest('event_time')->get();
        $this->assertEquals($latestEvent->id, $events->first()->id);
    }

    /** @test */
    public function it_has_by_status_scope()
    {
        TrackingEvent::factory()->delivered()->create();
        TrackingEvent::factory()->pending()->create();

        $deliveredEvents = TrackingEvent::byStatus(ShipmentStatus::DELIVERED)->get();
        $this->assertCount(1, $deliveredEvents);
        $this->assertEquals(ShipmentStatus::DELIVERED, $deliveredEvents->first()->status);
    }
}