<?php

namespace App\Domain\CMS\Contracts;

use App\Domain\CMS\Models\CustomerAccount;
use App\Domain\CMS\DTOs\CreateCustomerData;
use App\Domain\CMS\DTOs\UpdateCustomerData;
use Illuminate\Database\Eloquent\Collection;

/**
 * Contract for customer management service.
 */
interface CustomerServiceInterface
{
    /**
     * Register a new customer account.
     */
    public function register(int $siteId, CreateCustomerData $data): CustomerAccount;

    /**
     * Create a guest customer account.
     */
    public function createGuest(int $siteId, CreateCustomerData $data): CustomerAccount;

    /**
     * Authenticate a customer.
     */
    public function login(int $siteId, string $email, string $password): ?CustomerAccount;

    /**
     * Find customer by email for a specific site.
     */
    public function findByEmail(int $siteId, string $email): ?CustomerAccount;

    /**
     * Find or create a guest customer.
     */
    public function findOrCreateGuest(int $siteId, string $email, string $firstName = '', string $lastName = ''): CustomerAccount;

    /**
     * Update customer information.
     */
    public function updateCustomer(int $customerId, UpdateCustomerData $data): CustomerAccount;

    /**
     * Get customers for a site.
     */
    public function getCustomersForSite(int $siteId, bool $includeGuests = true): Collection;

    /**
     * Get customer order history.
     */
    public function getCustomerOrders(int $customerId): Collection;

    /**
     * Convert guest to registered customer.
     */
    public function convertGuestToRegistered(int $customerId, string $password): CustomerAccount;

    /**
     * Verify customer email.
     */
    public function verifyEmail(int $customerId): CustomerAccount;

    /**
     * Get customer statistics for a site.
     */
    public function getCustomerStatistics(int $siteId): array;

    /**
     * Delete customer account.
     */
    public function deleteCustomer(int $customerId): bool;
}