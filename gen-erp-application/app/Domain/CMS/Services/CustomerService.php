<?php

namespace App\Domain\CMS\Services;

use App\Domain\CMS\Contracts\CustomerServiceInterface;
use App\Domain\CMS\Models\CustomerAccount;
use App\Domain\CMS\Models\Site;
use App\Domain\CMS\DTOs\CreateCustomerData;
use App\Domain\CMS\DTOs\UpdateCustomerData;
use App\Domain\CMS\Events\CustomerRegistered;
use App\Domain\CMS\Events\CustomerLoggedIn;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

/**
 * Service for managing CMS customer accounts.
 */
class CustomerService implements CustomerServiceInterface
{
    /**
     * Register a new customer account.
     */
    public function register(int $siteId, CreateCustomerData $data): CustomerAccount
    {
        // Check if email already exists for this site
        $existingCustomer = CustomerAccount::where('site_id', $siteId)
            ->where('email', $data->email)
            ->first();

        if ($existingCustomer) {
            throw ValidationException::withMessages([
                'email' => ['A customer with this email already exists.']
            ]);
        }

        $customer = CustomerAccount::create([
            'site_id' => $siteId,
            'email' => $data->email,
            'password' => $data->password ? Hash::make($data->password) : null,
            'first_name' => $data->firstName,
            'last_name' => $data->lastName,
            'phone' => $data->phone,
            'is_guest' => $data->isGuest,
        ]);

        event(new CustomerRegistered($customer));

        return $customer;
    }

    /**
     * Create a guest customer account.
     */
    public function createGuest(int $siteId, CreateCustomerData $data): CustomerAccount
    {
        $guestData = new CreateCustomerData(
            email: $data->email,
            firstName: $data->firstName,
            lastName: $data->lastName,
            phone: $data->phone,
            password: null,
            isGuest: true
        );

        return $this->register($siteId, $guestData);
    }

    /**
     * Authenticate a customer.
     */
    public function login(int $siteId, string $email, string $password): ?CustomerAccount
    {
        $customer = CustomerAccount::where('site_id', $siteId)
            ->where('email', $email)
            ->where('is_guest', false)
            ->first();

        if (!$customer || !Hash::check($password, $customer->password)) {
            return null;
        }

        event(new CustomerLoggedIn($customer));

        return $customer;
    }

    /**
     * Find customer by email for a specific site.
     */
    public function findByEmail(int $siteId, string $email): ?CustomerAccount
    {
        return CustomerAccount::where('site_id', $siteId)
            ->where('email', $email)
            ->first();
    }

    /**
     * Find customer by ID.
     */
    public function findById(int $customerId): ?CustomerAccount
    {
        return CustomerAccount::find($customerId);
    }

    /**
     * Find or create a guest customer.
     */
    public function findOrCreateGuest(int $siteId, string $email, string $firstName = '', string $lastName = ''): CustomerAccount
    {
        $customer = $this->findByEmail($siteId, $email);

        if (!$customer) {
            $data = new CreateCustomerData(
                email: $email,
                firstName: $firstName,
                lastName: $lastName,
                phone: null,
                password: null,
                isGuest: true
            );

            $customer = $this->createGuest($siteId, $data);
        }

        return $customer;
    }

    /**
     * Update customer information.
     */
    public function updateCustomer(int $customerId, UpdateCustomerData $data): CustomerAccount
    {
        $customer = CustomerAccount::findOrFail($customerId);

        $updateData = array_filter([
            'first_name' => $data->firstName,
            'last_name' => $data->lastName,
            'phone' => $data->phone,
        ], fn($value) => $value !== null);

        if ($data->password) {
            $updateData['password'] = Hash::make($data->password);
        }

        $customer->update($updateData);

        return $customer->fresh();
    }

    /**
     * Get customers for a site.
     */
    public function getCustomersForSite(int $siteId, bool $includeGuests = true): Collection
    {
        $query = CustomerAccount::where('site_id', $siteId);

        if (!$includeGuests) {
            $query->registered();
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * Get customer order history.
     */
    public function getCustomerOrders(int $customerId): Collection
    {
        $customer = CustomerAccount::findOrFail($customerId);
        return $customer->orders()->with('items')->orderBy('created_at', 'desc')->get();
    }

    /**
     * Convert guest to registered customer.
     */
    public function convertGuestToRegistered(int $customerId, string $password): CustomerAccount
    {
        $customer = CustomerAccount::findOrFail($customerId);

        if (!$customer->is_guest) {
            throw new \InvalidArgumentException('Customer is already registered.');
        }

        $customer->update([
            'password' => Hash::make($password),
            'is_guest' => false,
        ]);

        return $customer->fresh();
    }

    /**
     * Verify customer email.
     */
    public function verifyEmail(int $customerId): CustomerAccount
    {
        $customer = CustomerAccount::findOrFail($customerId);

        $customer->update([
            'email_verified_at' => now(),
        ]);

        return $customer->fresh();
    }

    /**
     * Get customer statistics for a site.
     */
    public function getCustomerStatistics(int $siteId): array
    {
        $site = Site::findOrFail($siteId);

        return [
            'total_customers' => $site->customerAccounts()->count(),
            'registered_customers' => $site->customerAccounts()->registered()->count(),
            'guest_customers' => $site->customerAccounts()->guests()->count(),
            'verified_customers' => $site->customerAccounts()->verified()->count(),
            'customers_with_orders' => $site->customerAccounts()
                ->whereHas('orders')
                ->count(),
            'new_customers_this_month' => $site->customerAccounts()
                ->where('created_at', '>=', now()->startOfMonth())
                ->count(),
        ];
    }

    /**
     * Delete customer account.
     */
    public function deleteCustomer(int $customerId): bool
    {
        $customer = CustomerAccount::findOrFail($customerId);
        return $customer->delete();
    }
}