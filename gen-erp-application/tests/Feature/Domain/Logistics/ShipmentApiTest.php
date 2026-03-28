<?php

namespace Tests\Feature\Domain\Logistics;

use App\Domain\Auth\Models\User;
use App\Domain\Logistics\Models\Carrier;
use App\Domain\Logistics\Models\Shipment;
use App\Domain\Auth\Models\Company;
use App\Domain\Customer\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ShipmentApiTest extends TestCase
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
        $this->carrier = Carrier::factory()->create(['company_id' => $this->company->id]);

        $this->actingAs($this->user, 'sanctum');
    }

    /** @test */
    public function it_can_create_a_shipment()
    {
        $shipmentData = [
            'carrier_id' => $this->carrier->id,
            'customer_id' => $this->customer->id,
            'recipient_name' => 'John Doe',
            'recipient_phone' => '+8801712345678',
            'recipient_email' => 'john@example.com',
            'recipient_address' => '123 Main Street',
            'recipient_city' => 'Dhaka',
            'recipient_country' => 'Bangladesh',
            'sender_name' => 'Company Store',
            'sender_phone' => '+8801987654321',
            'sender_address' => '456 Business Ave',
            'sender_city' => 'Dhaka',
            'sender_country' => 'Bangladesh',
            'delivery_type' => 'standard',
            'payment_method' => 'prepaid',
            'weight' => 2.5,
            'declared_value' => 1000.00,
            'items' => [
                [
                    'name' => 'Test Product',
                    'quantity' => 2,
                    'weight' => 1.25,
                    'value' => 500.00,
                    'description' => 'Test product description',
                ],
            ],
        ];

        $response = $this->postJson('/api/v1/logistics/shipments', $shipmentData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'tracking_number',
                    'status',
                    'carrier',
                    'customer',
                    'recipient',
                    'sender',
                    'delivery_type',
                    'payment_method',
                    'weight',
                    'declared_value',
                    'items',
                    'created_at',
                ],
            ]);

        $this->assertDatabaseHas('shipments', [
            'carrier_id' => $this->carrier->id,
            'customer_id' => $this->customer->id,
            'recipient_name' => 'John Doe',
            'payment_method' => 'prepaid',
        ]);

        $this->assertDatabaseHas('shipment_items', [
            'product_name' => 'Test Product',
            'quantity' => 2,
            'weight' => 1.25,
        ]);
    }

    /** @test */
    public function it_can_create_cod_shipment()
    {
        $shipmentData = [
            'carrier_id' => $this->carrier->id,
            'customer_id' => $this->customer->id,
            'recipient_name' => 'Jane Doe',
            'recipient_phone' => '+8801712345678',
            'recipient_address' => '123 Main Street',
            'recipient_city' => 'Dhaka',
            'recipient_country' => 'Bangladesh',
            'sender_name' => 'Company Store',
            'sender_phone' => '+8801987654321',
            'sender_address' => '456 Business Ave',
            'sender_city' => 'Dhaka',
            'sender_country' => 'Bangladesh',
            'delivery_type' => 'standard',
            'payment_method' => 'cod',
            'cod_amount' => 1500.00,
            'weight' => 2.5,
            'items' => [
                [
                    'name' => 'COD Product',
                    'quantity' => 1,
                    'weight' => 2.5,
                    'value' => 1500.00,
                ],
            ],
        ];

        $response = $this->postJson('/api/v1/logistics/shipments', $shipmentData);

        $response->assertStatus(201);

        $this->assertDatabaseHas('shipments', [
            'payment_method' => 'cod',
            'cod_amount' => 1500.00,
        ]);
    }

    /** @test */
    public function it_validates_shipment_creation_data()
    {
        $response = $this->postJson('/api/v1/logistics/shipments', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'carrier_id',
                'customer_id',
                'recipient_name',
                'recipient_phone',
                'recipient_address',
                'recipient_city',
                'sender_name',
                'sender_phone',
                'sender_address',
                'sender_city',
                'delivery_type',
                'payment_method',
                'weight',
                'items',
            ]);
    }

    /** @test */
    public function it_requires_cod_amount_for_cod_shipments()
    {
        $shipmentData = [
            'carrier_id' => $this->carrier->id,
            'customer_id' => $this->customer->id,
            'recipient_name' => 'John Doe',
            'recipient_phone' => '+8801712345678',
            'recipient_address' => '123 Main Street',
            'recipient_city' => 'Dhaka',
            'recipient_country' => 'Bangladesh',
            'sender_name' => 'Company Store',
            'sender_phone' => '+8801987654321',
            'sender_address' => '456 Business Ave',
            'sender_city' => 'Dhaka',
            'sender_country' => 'Bangladesh',
            'delivery_type' => 'standard',
            'payment_method' => 'cod', // COD without cod_amount
            'weight' => 2.5,
            'items' => [
                [
                    'name' => 'Test Product',
                    'quantity' => 1,
                    'weight' => 2.5,
                ],
            ],
        ];

        $response = $this->postJson('/api/v1/logistics/shipments', $shipmentData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['cod_amount']);
    }

    /** @test */
    public function it_can_list_shipments()
    {
        Shipment::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'carrier_id' => $this->carrier->id,
            'customer_id' => $this->customer->id,
            'created_by' => $this->user->id,
        ]);

        $response = $this->getJson('/api/v1/logistics/shipments');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'tracking_number',
                        'status',
                        'carrier',
                        'customer',
                        'recipient',
                        'created_at',
                    ],
                ],
                'meta' => [
                    'current_page',
                    'last_page',
                    'per_page',
                    'total',
                ],
            ]);
    }

    /** @test */
    public function it_can_show_specific_shipment()
    {
        $shipment = Shipment::factory()->create([
            'company_id' => $this->company->id,
            'carrier_id' => $this->carrier->id,
            'customer_id' => $this->customer->id,
            'created_by' => $this->user->id,
        ]);

        $response = $this->getJson("/api/v1/logistics/shipments/{$shipment->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'tracking_number',
                    'status',
                    'carrier',
                    'customer',
                    'recipient',
                    'sender',
                    'delivery_type',
                    'payment_method',
                    'items',
                    'tracking_events',
                ],
            ]);
    }

    /** @test */
    public function it_can_update_shipment()
    {
        $shipment = Shipment::factory()->pending()->create([
            'company_id' => $this->company->id,
            'carrier_id' => $this->carrier->id,
            'customer_id' => $this->customer->id,
            'created_by' => $this->user->id,
        ]);

        $updateData = [
            'recipient_name' => 'Updated Name',
            'recipient_phone' => '+8801999999999',
            'special_instructions' => 'Handle with care',
        ];

        $response = $this->putJson("/api/v1/logistics/shipments/{$shipment->id}", $updateData);

        $response->assertStatus(200);

        $this->assertDatabaseHas('shipments', [
            'id' => $shipment->id,
            'recipient_name' => 'Updated Name',
            'recipient_phone' => '+8801999999999',
            'special_instructions' => 'Handle with care',
        ]);
    }

    /** @test */
    public function it_can_cancel_shipment()
    {
        $shipment = Shipment::factory()->create([
            'company_id' => $this->company->id,
            'carrier_id' => $this->carrier->id,
            'customer_id' => $this->customer->id,
            'created_by' => $this->user->id, // Use authenticated user with company associations
            'status' => 'pending',
        ]);

        $response = $this->deleteJson("/api/v1/logistics/shipments/{$shipment->id}");

        $response->assertStatus(200);

        $this->assertDatabaseHas('shipments', [
            'id' => $shipment->id,
            'status' => 'cancelled',
        ]);
    }

    /** @test */
    public function it_can_create_bulk_shipments()
    {
        $bulkData = [
            'shipments' => [
                [
                    'carrier_id' => $this->carrier->id,
                    'customer_id' => $this->customer->id,
                    'recipient_name' => 'Bulk Customer 1',
                    'recipient_phone' => '+8801712345678',
                    'recipient_address' => '123 Street',
                    'recipient_city' => 'Dhaka',
                    'sender_name' => 'Store',
                    'sender_phone' => '+8801987654321',
                    'sender_address' => '456 Ave',
                    'sender_city' => 'Dhaka',
                    'delivery_type' => 'standard',
                    'payment_method' => 'prepaid',
                    'weight' => 1.0,
                    'items' => [
                        [
                            'name' => 'Product 1',
                            'quantity' => 1,
                            'weight' => 1.0,
                        ],
                    ],
                ],
                [
                    'carrier_id' => $this->carrier->id,
                    'customer_id' => $this->customer->id,
                    'recipient_name' => 'Bulk Customer 2',
                    'recipient_phone' => '+8801712345679',
                    'recipient_address' => '124 Street',
                    'recipient_city' => 'Dhaka',
                    'sender_name' => 'Store',
                    'sender_phone' => '+8801987654321',
                    'sender_address' => '456 Ave',
                    'sender_city' => 'Dhaka',
                    'delivery_type' => 'express',
                    'payment_method' => 'cod',
                    'cod_amount' => 500.00,
                    'weight' => 2.0,
                    'items' => [
                        [
                            'name' => 'Product 2',
                            'quantity' => 1,
                            'weight' => 2.0,
                        ],
                    ],
                ],
            ],
        ];

        $response = $this->postJson('/api/v1/logistics/shipments/bulk', $bulkData);

        $response->assertStatus(200);

        $this->assertDatabaseHas('shipments', [
            'recipient_name' => 'Bulk Customer 1',
            'payment_method' => 'prepaid',
        ]);

        $this->assertDatabaseHas('shipments', [
            'recipient_name' => 'Bulk Customer 2',
            'payment_method' => 'cod',
            'cod_amount' => 500.00,
        ]);
    }

    /** @test */
    public function it_can_schedule_pickup()
    {
        $shipment = Shipment::factory()->create([
            'company_id' => $this->company->id,
            'carrier_id' => $this->carrier->id,
            'customer_id' => $this->customer->id,
            'created_by' => $this->user->id,
            'status' => 'pending',
        ]);

        $pickupData = [
            'pickup_date' => now()->addDay()->format('Y-m-d'),
            'pickup_time_slot' => '10:00-12:00',
            'special_instructions' => 'Call before pickup',
        ];

        $response = $this->postJson("/api/v1/logistics/shipments/{$shipment->id}/schedule-pickup", $pickupData);

        $response->assertStatus(200);
    }

    /** @test */
    public function it_requires_authentication()
    {
        // Test without authentication by creating a fresh request
        $this->app['auth']->forgetGuards();
        
        $response = $this->getJson('/api/v1/logistics/shipments');
        
        $response->assertStatus(401);
    }

    /** @test */
    public function it_filters_shipments_by_company()
    {
        // Create shipment for current company
        $ownShipment = Shipment::factory()->create([
            'company_id' => $this->company->id,
            'carrier_id' => $this->carrier->id,
            'customer_id' => $this->customer->id,
            'created_by' => $this->user->id,
        ]);

        // Create shipment for different company
        $otherCompany = Company::factory()->create();
        $otherCarrier = Carrier::factory()->create(['company_id' => $otherCompany->id]);
        $otherCustomer = Customer::factory()->create(['company_id' => $otherCompany->id]);
        $otherUser = User::factory()->create(['preferred_language' => 'en']);
        $otherUser->companies()->attach($otherCompany, ['role' => 'owner']);
        
        Shipment::factory()->create([
            'company_id' => $otherCompany->id,
            'carrier_id' => $otherCarrier->id,
            'customer_id' => $otherCustomer->id,
            'created_by' => $otherUser->id,
        ]);

        $response = $this->getJson('/api/v1/logistics/shipments');

        $response->assertStatus(200);
        
        $shipmentIds = collect($response->json('data'))->pluck('id')->toArray();
        
        $this->assertContains($ownShipment->id, $shipmentIds);
        $this->assertCount(1, $shipmentIds); // Should only see own company's shipments
    }
}