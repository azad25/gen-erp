<?php

use App\Domain\CMS\Models\Site;
use App\Domain\CMS\Models\CustomerAccount;
use App\Domain\Auth\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->company = Company::factory()->create();
    $this->site = Site::factory()->create([
        'company_id' => $this->company->id,
        'subdomain' => 'teststore',
        'status' => 'published',
    ]);
});

describe('Customer Registration', function () {
    it('can register a new customer', function () {
        $response = $this->postJson("/api/public/{$this->site->subdomain}/register", [
            'email' => 'newcustomer@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'phone' => '+1234567890',
        ]);

        $response->assertStatus(201)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'id',
                        'email',
                        'first_name',
                        'last_name',
                        'phone',
                        'is_guest',
                        'is_verified',
                        'created_at',
                    ]
                ]);

        $this->assertDatabaseHas('cms_customer_accounts', [
            'site_id' => $this->site->id,
            'email' => 'newcustomer@example.com',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'is_guest' => false,
        ]);
    });

    it('validates required fields for registration', function () {
        $response = $this->postJson("/api/public/{$this->site->subdomain}/register", []);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['email', 'password', 'first_name', 'last_name']);
    });

    it('prevents duplicate email registration', function () {
        CustomerAccount::factory()->create([
            'site_id' => $this->site->id,
            'email' => 'existing@example.com',
        ]);

        $response = $this->postJson("/api/public/{$this->site->subdomain}/register", [
            'email' => 'existing@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);

        $response->assertStatus(422)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'errors'
                ]);
    });
});

describe('Customer Login', function () {
    it('can login with valid credentials', function () {
        $customer = CustomerAccount::factory()->create([
            'site_id' => $this->site->id,
            'email' => 'customer@example.com',
            'password' => Hash::make('password123'),
            'is_guest' => false,
        ]);

        $response = $this->postJson("/api/public/{$this->site->subdomain}/login", [
            'email' => 'customer@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'customer' => [
                            'id',
                            'email',
                            'first_name',
                            'last_name',
                        ],
                        'token'
                    ]
                ]);
    });

    it('rejects invalid credentials', function () {
        CustomerAccount::factory()->create([
            'site_id' => $this->site->id,
            'email' => 'customer@example.com',
            'password' => Hash::make('password123'),
            'is_guest' => false,
        ]);

        $response = $this->postJson("/api/public/{$this->site->subdomain}/login", [
            'email' => 'customer@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401)
                ->assertJson([
                    'success' => false,
                    'message' => 'Invalid credentials.',
                ]);
    });

    it('rejects guest customers', function () {
        CustomerAccount::factory()->guest()->create([
            'site_id' => $this->site->id,
            'email' => 'guest@example.com',
        ]);

        $response = $this->postJson("/api/public/{$this->site->subdomain}/login", [
            'email' => 'guest@example.com',
            'password' => 'anypassword',
        ]);

        $response->assertStatus(401)
                ->assertJson([
                    'success' => false,
                    'message' => 'Invalid credentials.',
                ]);
    });
});

describe('Customer Profile Management', function () {
    it('can get customer profile with valid token', function () {
        $customer = CustomerAccount::factory()->create([
            'site_id' => $this->site->id,
            'email' => 'customer@example.com',
        ]);

        $token = base64_encode($customer->id . ':' . $customer->email . ':' . now()->timestamp);

        $response = $this->getJson("/api/public/{$this->site->subdomain}/profile", [
            'Authorization' => "Bearer $token"
        ]);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'id',
                        'email',
                        'first_name',
                        'last_name',
                        'phone',
                        'is_guest',
                    ]
                ]);
    });

    it('rejects requests without token', function () {
        $response = $this->getJson("/api/public/{$this->site->subdomain}/profile");

        $response->assertStatus(401)
                ->assertJson([
                    'success' => false,
                    'message' => 'Unauthorized.',
                ]);
    });

    it('can update customer profile', function () {
        $customer = CustomerAccount::factory()->create([
            'site_id' => $this->site->id,
            'first_name' => 'Old',
            'last_name' => 'Name',
        ]);

        $token = base64_encode($customer->id . ':' . $customer->email . ':' . now()->timestamp);

        $response = $this->putJson("/api/public/{$this->site->subdomain}/profile", [
            'first_name' => 'New',
            'last_name' => 'Name',
            'phone' => '+9876543210',
        ], [
            'Authorization' => "Bearer $token"
        ]);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'id',
                        'first_name',
                        'last_name',
                        'phone',
                    ]
                ]);

        $this->assertDatabaseHas('cms_customer_accounts', [
            'id' => $customer->id,
            'first_name' => 'New',
            'phone' => '+9876543210',
        ]);
    });
});

describe('Guest Customer Conversion', function () {
    it('can convert guest to registered customer', function () {
        $guestCustomer = CustomerAccount::factory()->guest()->create([
            'site_id' => $this->site->id,
        ]);

        $token = base64_encode($guestCustomer->id . ':' . $guestCustomer->email . ':' . now()->timestamp);

        $response = $this->postJson("/api/public/{$this->site->subdomain}/convert-guest", [
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ], [
            'Authorization' => "Bearer $token"
        ]);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'id',
                        'is_guest',
                    ]
                ]);

        $this->assertDatabaseHas('cms_customer_accounts', [
            'id' => $guestCustomer->id,
            'is_guest' => false,
        ]);

        $updatedCustomer = CustomerAccount::find($guestCustomer->id);
        expect(Hash::check('newpassword123', $updatedCustomer->password))->toBeTrue();
    });

    it('cannot convert already registered customer', function () {
        $registeredCustomer = CustomerAccount::factory()->create([
            'site_id' => $this->site->id,
            'is_guest' => false,
        ]);

        $token = base64_encode($registeredCustomer->id . ':' . $registeredCustomer->email . ':' . now()->timestamp);

        $response = $this->postJson("/api/public/{$this->site->subdomain}/convert-guest", [
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ], [
            'Authorization' => "Bearer $token"
        ]);

        $response->assertStatus(400)
                ->assertJson([
                    'success' => false,
                ]);
    });
});

describe('Customer Orders', function () {
    it('can get customer order history', function () {
        $customer = CustomerAccount::factory()->create([
            'site_id' => $this->site->id,
        ]);

        // Create some orders for the customer
        \App\Domain\CMS\Models\PublicOrder::factory()->count(3)->create([
            'site_id' => $this->site->id,
            'customer_id' => $customer->id,
        ]);

        $token = base64_encode($customer->id . ':' . $customer->email . ':' . now()->timestamp);

        $response = $this->getJson("/api/public/{$this->site->subdomain}/orders", [
            'Authorization' => "Bearer $token"
        ]);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        '*' => [
                            'id',
                            'order_number',
                            'totals' => [
                                'total_amount'
                            ],
                            'status',
                            'timestamps' => [
                                'placed_at'
                            ],
                        ]
                    ]
                ]);

        expect($response->json('data'))->toHaveCount(3);
    });

    it('returns empty array for customer with no orders', function () {
        $customer = CustomerAccount::factory()->create([
            'site_id' => $this->site->id,
        ]);

        $token = base64_encode($customer->id . ':' . $customer->email . ':' . now()->timestamp);

        $response = $this->getJson("/api/public/{$this->site->subdomain}/orders", [
            'Authorization' => "Bearer $token"
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'data' => []
                ]);
    });
});

describe('Site Validation', function () {
    it('returns 404 for non-existent site', function () {
        $response = $this->postJson('/api/public/nonexistent/register', [
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);

        $response->assertStatus(404);
    });

    it('returns 404 for unpublished site', function () {
        $unpublishedSite = Site::factory()->create([
            'company_id' => $this->company->id,
            'subdomain' => 'unpublished',
            'status' => 'draft',
        ]);

        $response = $this->postJson("/api/public/{$unpublishedSite->subdomain}/register", [
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);

        $response->assertStatus(404);
    });
});