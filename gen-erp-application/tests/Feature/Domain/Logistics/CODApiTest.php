<?php

namespace Tests\Feature\Domain\Logistics;

use App\Domain\Auth\Models\User;
use App\Domain\Logistics\Models\Carrier;
use App\Domain\Logistics\Models\Shipment;
use App\Domain\Logistics\Enums\ShipmentStatus;
use App\Domain\Auth\Models\Company;
use App\Domain\Customer\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CODApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Company $company;
    private Customer $customer;
    private Carrier $carrier;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['preferred_language' => 'en']);
        $this->company = Company::factory()->create();
        $this->user->companies()->attach($this->company, ['role' => 'owner']);
        $this->user->update(['last_active_company_id' => $this->company->id]);

        $this->customer = Customer::factory()->create(['company_id' => $this->company->id]);
        $this->carrier = Carrier::factory()->create([
            'company_id' => $this->company->id,
            'supports_cod' => true,
            'cod_charge_percentage' => 2.5,
        ]);

        $this->actingAs($this->user, 'sanctum');
    }

    /** @test */
    public function it_can_calculate_cod_charge()
    {
        $chargeData = [
            'cod_amount' => 1000.00,
            'carrier_id' => $this->carrier->id,
        ];

        $response = $this->postJson('/api/v1/logistics/cod/calculate-charge', $chargeData);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'cod_amount',
                    'cod_charge',
                    'net_amount',
                ],
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'cod_amount' => 1000.00,
                    'cod_charge' => 25.00, // 2.5% of 1000
                    'net_amount' => 975.00,
                ],
            ]);
    }

    /** @test */
    public function it_validates_cod_charge_calculation_data()
    {
        $response = $this->postJson('/api/v1/logistics/cod/calculate-charge', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['cod_amount', 'carrier_id']);
    }

    /** @test */
    public function it_can_mark_cod_as_collected()
    {
        $shipment = Shipment::factory()->create([
            'company_id' => $this->company->id,
            'carrier_id' => $this->carrier->id,
            'customer_id' => $this->customer->id,
            'created_by' => $this->user->id,
            'payment_method' => 'cod',
            'cod_amount' => 1500.00,
            'status' => ShipmentStatus::DELIVERED,
        ]);

        $collectionData = [
            'collected_amount' => 1500.00,
            'collected_at' => now()->format('Y-m-d H:i:s'),
        ];

        $response = $this->postJson("/api/v1/logistics/shipments/{$shipment->id}/cod/mark-collected", $collectionData);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'tracking_number',
                    'cod',
                ],
            ]);

        $this->assertDatabaseHas('shipments', [
            'id' => $shipment->id,
            'cod_collected_amount' => 1500.00,
            'cod_status' => 'collected',
        ]);
    }

    /** @test */
    public function it_validates_cod_collection_data()
    {
        $shipment = Shipment::factory()->create([
            'company_id' => $this->company->id,
            'carrier_id' => $this->carrier->id,
            'customer_id' => $this->customer->id,
            'created_by' => $this->user->id,
            'payment_method' => 'cod',
            'status' => ShipmentStatus::DELIVERED,
        ]);

        $response = $this->postJson("/api/v1/logistics/shipments/{$shipment->id}/cod/mark-collected", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['collected_amount']);
    }

    /** @test */
    public function it_can_settle_cod_with_carrier()
    {
        // Create multiple COD shipments that are collected
        $shipments = Shipment::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'carrier_id' => $this->carrier->id,
            'customer_id' => $this->customer->id,
            'created_by' => $this->user->id,
            'payment_method' => 'cod',
            'cod_amount' => 1000.00,
            'cod_collected_amount' => 1000.00,
            'cod_charge' => 25.00,
            'cod_status' => 'collected',
            'status' => ShipmentStatus::DELIVERED,
        ]);

        $settlementData = [
            'shipment_ids' => $shipments->pluck('id')->toArray(),
        ];

        $response = $this->postJson("/api/v1/logistics/carriers/{$this->carrier->id}/cod/settle", $settlementData);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'success',
                    'message',
                    'shipments_count',
                    'total_cod_amount',
                    'total_charges',
                    'net_amount',
                    'settlement_date',
                ],
            ]);

        // Check that shipments are marked as settled
        foreach ($shipments as $shipment) {
            $this->assertDatabaseHas('shipments', [
                'id' => $shipment->id,
                'cod_status' => 'settled',
            ]);
        }
    }

    /** @test */
    public function it_can_get_cod_summary_for_carrier()
    {
        // Create various COD shipments with different statuses
        Shipment::factory()->create([
            'company_id' => $this->company->id,
            'carrier_id' => $this->carrier->id,
            'customer_id' => $this->customer->id,
            'created_by' => $this->user->id,
            'payment_method' => 'cod',
            'cod_amount' => 1000.00,
            'status' => ShipmentStatus::DELIVERED,
        ]);

        Shipment::factory()->create([
            'company_id' => $this->company->id,
            'carrier_id' => $this->carrier->id,
            'customer_id' => $this->customer->id,
            'created_by' => $this->user->id,
            'payment_method' => 'cod',
            'cod_amount' => 1500.00,
            'cod_collected_amount' => 1500.00,
            'cod_status' => 'collected',
            'status' => ShipmentStatus::DELIVERED,
        ]);

        $response = $this->getJson("/api/v1/logistics/carriers/{$this->carrier->id}/cod/summary");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'total_cod_shipments',
                    'delivered_cod_shipments',
                    'collected_cod_shipments',
                    'settled_cod_shipments',
                    'collection_rate',
                    'settlement_rate',
                    'total_cod_amount',
                    'collected_amount',
                    'settled_amount',
                    'total_charges',
                    'pending_collection',
                    'pending_settlement',
                    'net_settled_amount',
                ],
            ]);
    }

    /** @test */
    public function it_can_get_pending_cod_collections()
    {
        // Create delivered COD shipment that hasn't been collected
        $pendingShipment = Shipment::factory()->create([
            'company_id' => $this->company->id,
            'carrier_id' => $this->carrier->id,
            'customer_id' => $this->customer->id,
            'created_by' => $this->user->id,
            'payment_method' => 'cod',
            'cod_amount' => 1000.00,
            'status' => ShipmentStatus::DELIVERED,
        ]);

        // Create collected COD shipment (should not appear in pending)
        Shipment::factory()->create([
            'company_id' => $this->company->id,
            'carrier_id' => $this->carrier->id,
            'customer_id' => $this->customer->id,
            'created_by' => $this->user->id,
            'payment_method' => 'cod',
            'cod_amount' => 1500.00,
            'cod_collected_amount' => 1500.00,
            'cod_status' => 'collected',
            'status' => ShipmentStatus::DELIVERED,
        ]);

        $response = $this->getJson("/api/v1/logistics/carriers/{$this->carrier->id}/cod/pending-collection");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'tracking_number',
                        'cod',
                        'customer',
                    ],
                ],
            ]);

        $shipmentIds = collect($response->json('data'))->pluck('id')->toArray();
        $this->assertContains($pendingShipment->id, $shipmentIds);
        $this->assertCount(1, $shipmentIds);
    }

    /** @test */
    public function it_can_get_pending_cod_settlements()
    {
        // Create collected but not settled COD shipment
        $unsettledShipment = Shipment::factory()->create([
            'company_id' => $this->company->id,
            'carrier_id' => $this->carrier->id,
            'customer_id' => $this->customer->id,
            'created_by' => $this->user->id,
            'payment_method' => 'cod',
            'cod_amount' => 1000.00,
            'cod_collected_amount' => 1000.00,
            'cod_status' => 'collected',
            'status' => ShipmentStatus::DELIVERED,
        ]);

        // Create settled COD shipment (should not appear in pending)
        Shipment::factory()->create([
            'company_id' => $this->company->id,
            'carrier_id' => $this->carrier->id,
            'customer_id' => $this->customer->id,
            'created_by' => $this->user->id,
            'payment_method' => 'cod',
            'cod_amount' => 1500.00,
            'cod_collected_amount' => 1500.00,
            'cod_status' => 'settled',
            'cod_settled_at' => now(),
            'status' => ShipmentStatus::DELIVERED,
        ]);

        $response = $this->getJson("/api/v1/logistics/carriers/{$this->carrier->id}/cod/pending-settlement");

        $response->assertStatus(200);

        $shipmentIds = collect($response->json('data'))->pluck('id')->toArray();
        $this->assertContains($unsettledShipment->id, $shipmentIds);
        $this->assertCount(1, $shipmentIds);
    }

    /** @test */
    public function it_can_generate_cod_report()
    {
        // Create various COD shipments
        Shipment::factory()->count(2)->create([
            'company_id' => $this->company->id,
            'carrier_id' => $this->carrier->id,
            'customer_id' => $this->customer->id,
            'created_by' => $this->user->id,
            'payment_method' => 'cod',
            'cod_amount' => 1000.00,
            'status' => ShipmentStatus::DELIVERED,
        ]);

        $response = $this->getJson("/api/v1/logistics/carriers/{$this->carrier->id}/cod/report");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'summary',
                    'pending_collection',
                    'pending_settlement',
                    'generated_at',
                ],
            ]);
    }

    /** @test */
    public function it_can_sync_cod_status_with_carrier()
    {
        // Create pending COD shipment
        Shipment::factory()->create([
            'company_id' => $this->company->id,
            'carrier_id' => $this->carrier->id,
            'customer_id' => $this->customer->id,
            'created_by' => $this->user->id,
            'payment_method' => 'cod',
            'cod_amount' => 1000.00,
            'status' => ShipmentStatus::DELIVERED,
            'carrier_tracking_number' => 'CARRIER123',
        ]);

        $response = $this->postJson("/api/v1/logistics/carriers/{$this->carrier->id}/cod/sync-status");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'success',
                    'message',
                    'synced_count',
                ],
            ]);
    }

    /** @test */
    public function it_can_filter_cod_summary_by_date_range()
    {
        $filters = [
            'date_from' => now()->subWeek()->format('Y-m-d'),
            'date_to' => now()->format('Y-m-d'),
        ];

        $response = $this->getJson("/api/v1/logistics/carriers/{$this->carrier->id}/cod/summary?" . http_build_query($filters));

        $response->assertStatus(200);
    }

    /** @test */
    /** @test */
    /** @test */
    /** @test */
    /** @test */
    /** @test */
    /** @test */
    /** @test */
    public function it_requires_authentication()
    {
        // Remove authentication for this test
        $this->app['auth']->forgetGuards();
        
        $response = $this->getJson("/api/v1/logistics/carriers/{$this->carrier->id}/cod/summary");
        $response->assertStatus(401);

        // Test POST endpoint without authentication
        $response = $this->postJson('/api/v1/logistics/cod/calculate-charge', [
            'cod_amount' => 1000,
            'carrier_id' => $this->carrier->id,
        ]);
        $response->assertStatus(401);
    }

    /** @test */
    public function it_only_works_with_company_carriers()
    {
        // Create carrier for different company
        $otherCompany = Company::factory()->create();
        $otherCarrier = Carrier::factory()->create([
            'company_id' => $otherCompany->id,
            'supports_cod' => true,
        ]);

        $response = $this->getJson("/api/v1/logistics/carriers/{$otherCarrier->id}/cod/summary");

        $response->assertStatus(404); // Should not find due to company scoping
    }

    /** @test */
    public function it_prevents_cod_collection_for_non_cod_shipments()
    {
        $shipment = Shipment::factory()->create([
            'company_id' => $this->company->id,
            'carrier_id' => $this->carrier->id,
            'customer_id' => $this->customer->id,
            'created_by' => $this->user->id,
            'payment_method' => 'prepaid', // Not COD
            'status' => ShipmentStatus::DELIVERED,
        ]);

        $collectionData = [
            'collected_amount' => 1000.00,
        ];

        $response = $this->postJson("/api/v1/logistics/shipments/{$shipment->id}/cod/mark-collected", $collectionData);

        $response->assertStatus(422); // Should fail validation or business logic
    }

    /** @test */
    public function it_prevents_cod_collection_for_non_delivered_shipments()
    {
        $shipment = Shipment::factory()->create([
            'company_id' => $this->company->id,
            'carrier_id' => $this->carrier->id,
            'customer_id' => $this->customer->id,
            'created_by' => $this->user->id,
            'payment_method' => 'cod',
            'cod_amount' => 1000.00,
            'status' => ShipmentStatus::IN_TRANSIT, // Not delivered yet
        ]);

        $collectionData = [
            'collected_amount' => 1000.00,
        ];

        $response = $this->postJson("/api/v1/logistics/shipments/{$shipment->id}/cod/mark-collected", $collectionData);

        $response->assertStatus(422); // Should fail business logic validation
    }
}