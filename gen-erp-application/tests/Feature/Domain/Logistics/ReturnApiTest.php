<?php

namespace Tests\Feature\Domain\Logistics;

use App\Domain\Auth\Models\User;
use App\Domain\Logistics\Models\Carrier;
use App\Domain\Logistics\Models\Shipment;
use App\Domain\Logistics\Models\ShipmentReturn;
use App\Domain\Logistics\Enums\ShipmentStatus;
use App\Domain\Logistics\Enums\ReturnReason;
use App\Domain\Auth\Models\Company;
use App\Domain\Customer\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ReturnApiTest extends TestCase
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

        // Ensure we're using English locale for tests
        app()->setLocale('en');

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
            'status' => ShipmentStatus::DELIVERED,
        ]);

        $this->actingAs($this->user, 'sanctum');
    }

    /** @test */
    /** @test */
    public function it_can_create_return_request()
    {
        $returnData = [
            'shipment_id' => $this->shipment->id,
            'reason' => 'not_needed', // This reason doesn't auto-approve
            'reason_details' => 'Customer no longer needs the item',
        ];

        $response = $this->postJson('/api/v1/logistics/returns', $returnData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'return_number',
                    'status',
                    'reason',
                    'reason_details',
                    'shipment',
                    'requested_by',
                    'requested_at',
                ],
            ]);

        $this->assertDatabaseHas('shipment_returns', [
            'shipment_id' => $this->shipment->id,
            'reason' => 'not_needed',
            'reason_details' => 'Customer no longer needs the item',
            'status' => 'requested',
            'requested_by' => $this->user->id,
        ]);
    }

    /** @test */
    public function it_validates_return_request_data()
    {
        $response = $this->postJson('/api/v1/logistics/returns', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'shipment_id',
                'reason',
                'reason_details',
            ]);
    }

    /** @test */
    public function it_can_create_return_with_images()
    {
        Storage::fake('public');

        $returnData = [
            'shipment_id' => $this->shipment->id,
            'reason' => 'damaged',
            'reason_details' => 'Package arrived damaged',
            'images' => [
                UploadedFile::fake()->image('damage1.jpg'),
                UploadedFile::fake()->image('damage2.jpg'),
            ],
        ];

        $response = $this->postJson('/api/v1/logistics/returns', $returnData);

        $response->assertStatus(201);

        $return = ShipmentReturn::latest()->first();
        $this->assertNotNull($return->images);
        $this->assertCount(2, $return->images);
    }

    /** @test */
    public function it_can_list_returns()
    {
        ShipmentReturn::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'shipment_id' => $this->shipment->id,
            'requested_by' => $this->user->id,
        ]);

        $response = $this->getJson('/api/v1/logistics/returns');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'return_number',
                        'status',
                        'reason',
                        'shipment',
                        'requested_by',
                        'requested_at',
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
    public function it_can_show_specific_return()
    {
        $return = ShipmentReturn::factory()->create([
            'company_id' => $this->company->id,
            'shipment_id' => $this->shipment->id,
            'requested_by' => $this->user->id,
        ]);

        $response = $this->getJson("/api/v1/logistics/returns/{$return->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'return_number',
                    'status',
                    'reason',
                    'reason_details',
                    'shipment',
                    'requested_by',
                    'requested_at',
                ],
            ]);
    }

    /** @test */
    public function it_can_approve_return_request()
    {
        $return = ShipmentReturn::factory()->create([
            'company_id' => $this->company->id,
            'shipment_id' => $this->shipment->id,
            'requested_by' => $this->user->id,
            'status' => 'requested',
        ]);

        $response = $this->postJson("/api/v1/logistics/returns/{$return->id}/approve");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Return request approved successfully',
            ]);

        $this->assertDatabaseHas('shipment_returns', [
            'id' => $return->id,
            'status' => 'approved',
            'approved_by' => $this->user->id,
        ]);
    }

    /** @test */
    public function it_can_reject_return_request()
    {
        $return = ShipmentReturn::factory()->create([
            'company_id' => $this->company->id,
            'shipment_id' => $this->shipment->id,
            'requested_by' => $this->user->id,
            'status' => 'requested',
        ]);

        $rejectionData = [
            'reason' => 'Return period expired',
        ];

        $response = $this->postJson("/api/v1/logistics/returns/{$return->id}/reject", $rejectionData);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Return request rejected',
            ]);

        $this->assertDatabaseHas('shipment_returns', [
            'id' => $return->id,
            'status' => 'rejected',
            'approved_by' => $this->user->id,
        ]);
    }

    /** @test */
    public function it_can_mark_return_as_received()
    {
        $return = ShipmentReturn::factory()->create([
            'company_id' => $this->company->id,
            'shipment_id' => $this->shipment->id,
            'requested_by' => $this->user->id,
            'status' => 'approved',
        ]);

        $response = $this->postJson("/api/v1/logistics/returns/{$return->id}/mark-received");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Return marked as received',
            ]);

        $this->assertDatabaseHas('shipment_returns', [
            'id' => $return->id,
            'status' => 'received',
        ]);
    }

    /** @test */
    public function it_can_process_refund()
    {
        $return = ShipmentReturn::factory()->create([
            'company_id' => $this->company->id,
            'shipment_id' => $this->shipment->id,
            'requested_by' => $this->user->id,
            'status' => 'received',
        ]);

        $refundData = [
            'amount' => 500.00,
            'method' => 'bank_transfer',
        ];

        $response = $this->postJson("/api/v1/logistics/returns/{$return->id}/process-refund", $refundData);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Refund processed successfully',
            ]);

        $this->assertDatabaseHas('shipment_returns', [
            'id' => $return->id,
            'status' => 'refunded',
            'refund_amount' => 500.00,
            'refund_method' => 'bank_transfer',
        ]);
    }

    /** @test */
    public function it_validates_refund_data()
    {
        $return = ShipmentReturn::factory()->create([
            'company_id' => $this->company->id,
            'shipment_id' => $this->shipment->id,
            'requested_by' => $this->user->id,
            'status' => 'received',
        ]);

        $response = $this->postJson("/api/v1/logistics/returns/{$return->id}/process-refund", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['amount', 'method']);
    }

    /** @test */
    public function it_can_upload_return_images()
    {
        Storage::fake('public');

        $return = ShipmentReturn::factory()->create([
            'company_id' => $this->company->id,
            'shipment_id' => $this->shipment->id,
            'requested_by' => $this->user->id,
            'images' => null, // Start with no images
        ]);

        $imageData = [
            'images' => [
                UploadedFile::fake()->image('additional1.jpg'),
                UploadedFile::fake()->image('additional2.jpg'),
            ],
        ];

        $response = $this->postJson("/api/v1/logistics/returns/{$return->id}/upload-images", $imageData);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'images',
                ],
            ]);

        $return->refresh();
        $this->assertNotNull($return->images);
        $this->assertCount(2, $return->images);
    }

    /** @test */
    public function it_validates_image_uploads()
    {
        $return = ShipmentReturn::factory()->create([
            'company_id' => $this->company->id,
            'shipment_id' => $this->shipment->id,
            'requested_by' => $this->user->id,
        ]);

        // Test with non-image file
        $invalidData = [
            'images' => [
                UploadedFile::fake()->create('document.pdf', 1000),
            ],
        ];

        $response = $this->postJson("/api/v1/logistics/returns/{$return->id}/upload-images", $invalidData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['images.0']);
    }

    /** @test */
    public function it_can_get_return_statistics()
    {
        // Create various returns with different statuses
        ShipmentReturn::factory()->create([
            'company_id' => $this->company->id,
            'shipment_id' => $this->shipment->id,
            'requested_by' => $this->user->id,
            'status' => 'approved',
        ]);

        ShipmentReturn::factory()->create([
            'company_id' => $this->company->id,
            'shipment_id' => $this->shipment->id,
            'requested_by' => $this->user->id,
            'status' => 'rejected',
        ]);

        $response = $this->getJson('/api/v1/logistics/returns/statistics');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'total_returns',
                    'approved_returns',
                    'rejected_returns',
                    'completed_returns',
                    'approval_rate',
                    'completion_rate',
                    'avg_processing_hours',
                    'reason_breakdown',
                ],
            ]);
    }

    /** @test */
    public function it_can_filter_returns()
    {
        ShipmentReturn::factory()->create([
            'company_id' => $this->company->id,
            'shipment_id' => $this->shipment->id,
            'requested_by' => $this->user->id,
            'status' => 'approved',
            'reason' => ReturnReason::DAMAGED,
        ]);

        ShipmentReturn::factory()->create([
            'company_id' => $this->company->id,
            'shipment_id' => $this->shipment->id,
            'requested_by' => $this->user->id,
            'status' => 'rejected',
            'reason' => ReturnReason::WRONG_ITEM,
        ]);

        // Filter by status
        $response = $this->getJson('/api/v1/logistics/returns?status=approved');
        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));

        // Filter by reason
        $response = $this->getJson('/api/v1/logistics/returns?reason=damaged');
        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
    }

    /** @test */
    public function it_requires_authentication()
    {
        // Clear authentication by creating a fresh unauthenticated request
        auth()->guard('sanctum')->forgetUser();
        $this->app['auth']->forgetGuards();
        
        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->getJson('/api/v1/logistics/returns');
        
        $response->assertStatus(401);
    }

    /** @test */
    public function it_only_shows_company_returns()
    {
        // Create return for current company
        $ownReturn = ShipmentReturn::factory()->create([
            'company_id' => $this->company->id,
            'shipment_id' => $this->shipment->id,
            'requested_by' => $this->user->id,
        ]);

        // Create return for different company
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
        
        ShipmentReturn::factory()->create([
            'company_id' => $otherCompany->id,
            'shipment_id' => $otherShipment->id,
            'requested_by' => $this->user->id,
        ]);

        $response = $this->getJson('/api/v1/logistics/returns');

        $response->assertStatus(200);
        
        $returnIds = collect($response->json('data'))->pluck('id')->toArray();
        
        $this->assertContains($ownReturn->id, $returnIds);
        $this->assertCount(1, $returnIds); // Should only see own company's returns
    }
}