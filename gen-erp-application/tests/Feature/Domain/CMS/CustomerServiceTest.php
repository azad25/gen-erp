<?php

use App\Domain\CMS\Services\CustomerService;
use App\Domain\CMS\Models\Site;
use App\Domain\CMS\Models\CustomerAccount;
use App\Domain\CMS\DTOs\CreateCustomerData;
use App\Domain\CMS\DTOs\UpdateCustomerData;
use App\Domain\CMS\Events\CustomerRegistered;
use App\Domain\CMS\Events\CustomerLoggedIn;
use App\Domain\Auth\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->customerService = app(CustomerService::class);
    $this->company = Company::factory()->create();
    $this->site = Site::factory()->create(['company_id' => $this->company->id]);
});

describe('Customer Registration', function () {
    it('can register a new customer', function () {
        Event::fake();

        $data = new CreateCustomerData(
            email: 'test@example.com',
            firstName: 'John',
            lastName: 'Doe',
            phone: '+1234567890',
            password: 'password123',
            isGuest: false
        );

        $customer = $this->customerService->register($this->site->id, $data);

        expect($customer)->toBeInstanceOf(CustomerAccount::class);
        expect($customer->email)->toBe('test@example.com');
        expect($customer->first_name)->toBe('John');
        expect($customer->last_name)->toBe('Doe');
        expect($customer->phone)->toBe('+1234567890');
        expect($customer->is_guest)->toBeFalse();
        expect($customer->site_id)->toBe($this->site->id);

        Event::assertDispatched(CustomerRegistered::class);
    });

    it('can create a guest customer', function () {
        Event::fake();

        $data = new CreateCustomerData(
            email: 'guest@example.com',
            firstName: 'Guest',
            lastName: 'User',
            phone: null,
            password: null,
            isGuest: true
        );

        $customer = $this->customerService->createGuest($this->site->id, $data);

        expect($customer->email)->toBe('guest@example.com');
        expect($customer->is_guest)->toBeTrue();
        expect($customer->password)->toBeNull();

        Event::assertDispatched(CustomerRegistered::class);
    });

    it('prevents duplicate email registration for same site', function () {
        CustomerAccount::factory()->create([
            'site_id' => $this->site->id,
            'email' => 'existing@example.com',
        ]);

        $data = new CreateCustomerData(
            email: 'existing@example.com',
            firstName: 'John',
            lastName: 'Doe',
            password: 'password123'
        );

        expect(fn() => $this->customerService->register($this->site->id, $data))
            ->toThrow(ValidationException::class);
    });

    it('allows same email for different sites', function () {
        $otherSite = Site::factory()->create(['company_id' => $this->company->id]);
        
        CustomerAccount::factory()->create([
            'site_id' => $otherSite->id,
            'email' => 'test@example.com',
        ]);

        $data = new CreateCustomerData(
            email: 'test@example.com',
            firstName: 'John',
            lastName: 'Doe',
            password: 'password123'
        );

        $customer = $this->customerService->register($this->site->id, $data);

        expect($customer->email)->toBe('test@example.com');
        expect($customer->site_id)->toBe($this->site->id);
    });
});

describe('Customer Authentication', function () {
    it('can authenticate a customer with valid credentials', function () {
        Event::fake();

        $customer = CustomerAccount::factory()->create([
            'site_id' => $this->site->id,
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'is_guest' => false,
        ]);

        $authenticatedCustomer = $this->customerService->login(
            $this->site->id,
            'test@example.com',
            'password123'
        );

        expect($authenticatedCustomer)->not->toBeNull();
        expect($authenticatedCustomer->id)->toBe($customer->id);

        Event::assertDispatched(CustomerLoggedIn::class);
    });

    it('returns null for invalid credentials', function () {
        CustomerAccount::factory()->create([
            'site_id' => $this->site->id,
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'is_guest' => false,
        ]);

        $authenticatedCustomer = $this->customerService->login(
            $this->site->id,
            'test@example.com',
            'wrongpassword'
        );

        expect($authenticatedCustomer)->toBeNull();
    });

    it('returns null for guest customers', function () {
        CustomerAccount::factory()->guest()->create([
            'site_id' => $this->site->id,
            'email' => 'guest@example.com',
        ]);

        $authenticatedCustomer = $this->customerService->login(
            $this->site->id,
            'guest@example.com',
            'anypassword'
        );

        expect($authenticatedCustomer)->toBeNull();
    });
});

describe('Customer Management', function () {
    it('can find customer by email', function () {
        $customer = CustomerAccount::factory()->create([
            'site_id' => $this->site->id,
            'email' => 'find@example.com',
        ]);

        $foundCustomer = $this->customerService->findByEmail($this->site->id, 'find@example.com');

        expect($foundCustomer)->not->toBeNull();
        expect($foundCustomer->id)->toBe($customer->id);
    });

    it('can find or create guest customer', function () {
        // First call should create
        $customer1 = $this->customerService->findOrCreateGuest(
            $this->site->id,
            'newguest@example.com',
            'New',
            'Guest'
        );

        expect($customer1->email)->toBe('newguest@example.com');
        expect($customer1->is_guest)->toBeTrue();

        // Second call should find existing
        $customer2 = $this->customerService->findOrCreateGuest(
            $this->site->id,
            'newguest@example.com',
            'Different',
            'Name'
        );

        expect($customer2->id)->toBe($customer1->id);
        expect($customer2->first_name)->toBe('New'); // Should keep original name
    });

    it('can update customer information', function () {
        $customer = CustomerAccount::factory()->create([
            'site_id' => $this->site->id,
            'first_name' => 'Old',
            'last_name' => 'Name',
        ]);

        $data = new UpdateCustomerData(
            firstName: 'New',
            lastName: 'Name',
            phone: '+9876543210'
        );

        $updatedCustomer = $this->customerService->updateCustomer($customer->id, $data);

        expect($updatedCustomer->first_name)->toBe('New');
        expect($updatedCustomer->phone)->toBe('+9876543210');
    });

    it('can convert guest to registered customer', function () {
        $guestCustomer = CustomerAccount::factory()->guest()->create([
            'site_id' => $this->site->id,
        ]);

        $registeredCustomer = $this->customerService->convertGuestToRegistered(
            $guestCustomer->id,
            'newpassword123'
        );

        expect($registeredCustomer->is_guest)->toBeFalse();
        expect(Hash::check('newpassword123', $registeredCustomer->password))->toBeTrue();
    });

    it('cannot convert already registered customer', function () {
        $registeredCustomer = CustomerAccount::factory()->create([
            'site_id' => $this->site->id,
            'is_guest' => false,
        ]);

        expect(fn() => $this->customerService->convertGuestToRegistered($registeredCustomer->id, 'password'))
            ->toThrow(\InvalidArgumentException::class);
    });

    it('can verify customer email', function () {
        $customer = CustomerAccount::factory()->unverified()->create([
            'site_id' => $this->site->id,
        ]);

        expect($customer->email_verified_at)->toBeNull();

        $verifiedCustomer = $this->customerService->verifyEmail($customer->id);

        expect($verifiedCustomer->email_verified_at)->not->toBeNull();
    });
});

describe('Customer Statistics', function () {
    it('can get customer statistics for a site', function () {
        // Create various types of customers
        CustomerAccount::factory()->count(3)->create(['site_id' => $this->site->id]);
        CustomerAccount::factory()->guest()->count(2)->create(['site_id' => $this->site->id]);
        CustomerAccount::factory()->verified()->count(4)->create(['site_id' => $this->site->id]);
        
        // Create customers with orders
        $customerWithOrders = CustomerAccount::factory()->create(['site_id' => $this->site->id]);
        \App\Domain\CMS\Models\PublicOrder::factory()->count(2)->create([
            'site_id' => $this->site->id,
            'customer_id' => $customerWithOrders->id,
        ]);

        $stats = $this->customerService->getCustomerStatistics($this->site->id);

        expect($stats)->toHaveKeys([
            'total_customers',
            'registered_customers', 
            'guest_customers',
            'verified_customers',
            'customers_with_orders',
            'new_customers_this_month'
        ]);

        expect($stats['total_customers'])->toBeGreaterThan(0);
        expect($stats['guest_customers'])->toBe(2);
        expect($stats['customers_with_orders'])->toBe(1);
    });
});

describe('Customer Queries', function () {
    it('can get customers for a site', function () {
        CustomerAccount::factory()->count(3)->create(['site_id' => $this->site->id]);
        CustomerAccount::factory()->guest()->count(2)->create(['site_id' => $this->site->id]);
        
        // Create customers for different site
        $otherSite = Site::factory()->create(['company_id' => $this->company->id]);
        CustomerAccount::factory()->count(2)->create(['site_id' => $otherSite->id]);

        $customers = $this->customerService->getCustomersForSite($this->site->id);

        expect($customers)->toHaveCount(5);
        expect($customers->every(fn($customer) => $customer->site_id === $this->site->id))->toBeTrue();
    });

    it('can get customers excluding guests', function () {
        CustomerAccount::factory()->count(3)->create(['site_id' => $this->site->id]);
        CustomerAccount::factory()->guest()->count(2)->create(['site_id' => $this->site->id]);

        $customers = $this->customerService->getCustomersForSite($this->site->id, false);

        expect($customers)->toHaveCount(3);
        expect($customers->every(fn($customer) => !$customer->is_guest))->toBeTrue();
    });

    it('can get customer order history', function () {
        $customer = CustomerAccount::factory()->create(['site_id' => $this->site->id]);
        
        \App\Domain\CMS\Models\PublicOrder::factory()->count(3)->create([
            'site_id' => $this->site->id,
            'customer_id' => $customer->id,
        ]);

        $orders = $this->customerService->getCustomerOrders($customer->id);

        expect($orders)->toHaveCount(3);
        expect($orders->every(fn($order) => $order->customer_id === $customer->id))->toBeTrue();
    });
});