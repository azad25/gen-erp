<?php

namespace Tests\Feature\Domain\Logistics;

use App\Domain\Auth\Models\User;
use App\Domain\Logistics\Models\Carrier;
use App\Domain\Logistics\Models\Shipment;
use App\Domain\Logistics\Models\TrackingEvent;
use App\Domain\Logistics\Enums\ShipmentStatus;
use App\Domain\Auth\Models\Company;
use App\Domain\Customer\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrackingApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Company $company;
    private Customer $customer;
    private Carrier $carrier;
    private Shipment $shipment;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['preferred_language' => 'en']);
        $this->company = Company::factory()->create();
        $this->user->companies()->attach($this->company, ['role' => 'owner']);
        $this->user->update(['last_active_company_id' => $this->company->id]);

        $this->customer = Customer::factory()->create(['company_id' => $this->company->id]);
        $this->carrier = Carrier::factory()->create(['company_id' => $this->company->id]);
        $this->shipment = Shipment::factory()->create([
            'company_id' => $this->company->id,
            'carrier_id' => $this->carrier->id,
            'customer_id' => $this->customer->id,
            'created_by' => $this->user->id,
        ]);

        $this->actingAs($this->user, 'sanctum');
    }

    /** @test */
    public function it_can_track_shipment_by_tracking_number()
    {
        // Create some tracking events
        TrackingEvent::factory()->count(3)->create([
            'shipment_id' => $this->shipment->id,
        ]);

        $response = $this->getJson("/api/v1/logistics/tracking/{$this->shipment->tracking_number}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'shipment' => [
                        'id',
                        'tracking_number',
                        'status',
                        'carrier',
                        'recipient',
                    ],
                    'tracking_events' => [
                        '*' => [
                            'id',
                            'status',
                            'location',
                            'description',
                            'event_time',
                        ],
                    ],
                    'estimated_delivery',
                ],
            ]);
    }

    /** @test */
    public function it_returns_404_for_invalid_tracking_number()
    {
        // Ensure we're using English locale for this test
        app()->setLocale('en');
        
        $response = $this->getJson('/api/v1/logistics/tracking/INVALID123');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Tracking information not found',
            ]);
    }

    /** @test */
    public function it_can_get_tracking_history_for_shipment()
    {
        TrackingEvent::factory()->count(5)->create([
            'shipment_id' => $this->shipment->id,
        ]);

        $response = $this->getJson("/api/v1/logistics/shipments/{$this->shipment->id}/tracking");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'status',
                        'location',
                        'description',
                        'event_time',
                    ],
                ],
            ]);

        $this->assertCount(5, $response->json('data'));
    }

    /** @test */
    public function it_can_update_shipment_status()
    {
        $statusData = [
            'status' => 'in_transit',
            'location' => 'Dhaka Hub',
            'description' => 'Package is in transit to destination',
        ];

        $response = $this->postJson("/api/v1/logistics/shipments/{$this->shipment->id}/tracking/update", $statusData);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'status',
                    'location',
                    'description',
                    'event_time',
                ],
            ]);

        $this->assertDatabaseHas('tracking_events', [
            'shipment_id' => $this->shipment->id,
            'status' => 'in_transit',
            'location' => 'Dhaka Hub',
            'description' => 'Package is in transit to destination',
        ]);

        $this->assertDatabaseHas('shipments', [
            'id' => $this->shipment->id,
            'status' => 'in_transit',
        ]);
    }

    /** @test */
    public function it_validates_status_update_data()
    {
        $response = $this->postJson("/api/v1/logistics/shipments/{$this->shipment->id}/tracking/update", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }

    /** @test */
    public function it_can_sync_with_carrier()
    {
        $response = $this->postJson("/api/v1/logistics/shipments/{$this->shipment->id}/tracking/sync");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
            ]);
    }

    /** @test */
    public function it_can_bulk_sync_tracking()
    {
        $shipment2 = Shipment::factory()->create([
            'company_id' => $this->company->id,
            'carrier_id' => $this->carrier->id,
            'customer_id' => $this->customer->id,
            'created_by' => $this->user->id,
        ]);

        $syncData = [
            'shipment_ids' => [$this->shipment->id, $shipment2->id],
        ];

        $response = $this->postJson('/api/v1/logistics/tracking/bulk-sync', $syncData);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'success',
                    'failed',
                    'errors',
                ],
            ]);
    }

    /** @test */
    public function it_can_get_delivery_statistics()
    {
        // Create some delivered shipments
        Shipment::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'carrier_id' => $this->carrier->id,
            'customer_id' => $this->customer->id,
            'created_by' => $this->user->id,
            'status' => ShipmentStatus::DELIVERED,
            'actual_delivery_date' => now()->subDays(1),
        ]);

        $response = $this->getJson('/api/v1/logistics/tracking/statistics');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'total_shipments',
                    'delivered_shipments',
                    'delivery_rate',
                    'on_time_deliveries',
                    'on_time_rate',
                    'avg_delivery_days',
                ],
            ]);
    }

    /** @test */
    public function it_can_filter_statistics_by_date_range()
    {
        $filters = [
            'date_from' => now()->subWeek()->format('Y-m-d'),
            'date_to' => now()->format('Y-m-d'),
        ];

        $response = $this->getJson('/api/v1/logistics/tracking/statistics?' . http_build_query($filters));

        $response->assertStatus(200);
    }

    /** @test */
    public function public_tracking_works_without_authentication()
    {
        // Don't authenticate for this test
        $this->withoutMiddleware();

        TrackingEvent::factory()->count(2)->create([
            'shipment_id' => $this->shipment->id,
        ]);

        $response = $this->getJson("/api/public/test-tenant/track/{$this->shipment->tracking_number}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'tracking_number',
                    'status',
                    'recipient_name',
                    'recipient_city',
                    'estimated_delivery',
                    'tracking_events' => [
                        '*' => [
                            'status',
                            'location',
                            'description',
                            'event_time',
                        ],
                    ],
                ],
            ]);

        // Should not include sensitive information
        $responseData = $response->json('data');
        $this->assertArrayNotHasKey('recipient_phone', $responseData);
        $this->assertArrayNotHasKey('recipient_address', $responseData);
    }

    /** @test */
    public function public_tracking_returns_404_for_invalid_number()
    {
        $this->withoutMiddleware();

        $response = $this->getJson('/api/public/test-tenant/track/INVALID123');

        $response->assertStatus(404);
    }

    /** @test */
    public function it_sets_delivery_date_when_status_is_delivered()
    {
        $statusData = [
            'status' => 'delivered',
            'location' => 'Customer Address',
            'description' => 'Package delivered successfully',
        ];

        $response = $this->postJson("/api/v1/logistics/shipments/{$this->shipment->id}/tracking/update", $statusData);

        $response->assertStatus(200);

        $this->assertDatabaseHas('shipments', [
            'id' => $this->shipment->id,
            'status' => 'delivered',
        ]);

        $this->shipment->refresh();
        $this->assertNotNull($this->shipment->actual_delivery_date);
    }

    /** @test */
    public function it_requires_authentication_for_private_endpoints()
    {
        // Remove authentication for this test
        $this->app['auth']->forgetGuards();
        
        $response = $this->getJson("/api/v1/logistics/tracking/{$this->shipment->tracking_number}");
        $response->assertStatus(401);

        $response = $this->getJson("/api/v1/logistics/shipments/{$this->shipment->id}/tracking");
        $response->assertStatus(401);

        $response = $this->postJson("/api/v1/logistics/shipments/{$this->shipment->id}/tracking/update", [
            'status' => 'in_transit',
        ]);
        $response->assertStatus(401);
    }

    /** @test */
    public function it_only_shows_company_shipments_in_tracking()
    {
        // Create shipment for different company
        $otherCompany = Company::factory()->create();
        $otherCarrier = Carrier::factory()->create(['company_id' => $otherCompany->id]);
        $otherCustomer = Customer::factory()->create(['company_id' => $otherCompany->id]);
        $otherUser = User::factory()->create(['preferred_language' => 'en']);
        $otherUser->companies()->attach($otherCompany, ['role' => 'owner']);
        $otherShipment = Shipment::factory()->create([
            'company_id' => $otherCompany->id,
            'carrier_id' => $otherCarrier->id,
            'customer_id' => $otherCustomer->id,
            'created_by' => $otherUser->id,
        ]);

        // Try to access other company's shipment tracking
        $response = $this->getJson("/api/v1/logistics/tracking/{$otherShipment->tracking_number}");

        $response->assertStatus(404); // Should not find it due to company scoping
    }
}