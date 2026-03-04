<?php

namespace App\Http\Controllers\Api\Public;

use App\Domain\CMS\Services\CustomerService;
use App\Domain\CMS\Models\Site;
use App\Domain\CMS\DTOs\CreateCustomerData;
use App\Domain\CMS\DTOs\UpdateCustomerData;
use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Resources\CustomerAccountResource;
use App\Http\Resources\PublicOrderResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

/**
 * Public API controller for customer account management.
 */
class CustomerController extends BaseApiController
{
    public function __construct(
        private readonly CustomerService $customerService
    ) {}

    /**
     * Register a new customer account.
     */
    public function register(Request $request, string $tenant): JsonResponse
    {
        $site = Site::where('subdomain', $tenant)
            ->orWhere('domain', $tenant)
            ->published()
            ->firstOrFail();

        $validated = $request->validate([
            'email' => 'required|email|max:255',
            'password' => 'required|string|min:8|confirmed',
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'phone' => 'nullable|string|max:20',
        ]);

        try {
            $data = new CreateCustomerData(
                email: $validated['email'],
                firstName: $validated['first_name'],
                lastName: $validated['last_name'],
                phone: $validated['phone'] ?? null,
                password: $validated['password'],
                isGuest: false
            );

            $customer = $this->customerService->register($site->id, $data);

            return $this->success(
                new CustomerAccountResource($customer),
                'Account created successfully.',
                201
            );
        } catch (ValidationException $e) {
            return $this->error('Registration failed.', 422, $e->errors());
        }
    }

    /**
     * Login customer.
     */
    public function login(Request $request, string $tenant): JsonResponse
    {
        $site = Site::where('subdomain', $tenant)
            ->orWhere('domain', $tenant)
            ->published()
            ->firstOrFail();

        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $customer = $this->customerService->login(
            $site->id,
            $validated['email'],
            $validated['password']
        );

        if (!$customer) {
            return $this->error('Invalid credentials.', 401);
        }

        // Create a simple token (you might want to use Laravel Sanctum for production)
        $token = base64_encode($customer->id . ':' . $customer->email . ':' . now()->timestamp);

        return $this->success([
            'customer' => new CustomerAccountResource($customer),
            'token' => $token,
        ], 'Login successful.');
    }

    /**
     * Get customer profile.
     */
    public function profile(Request $request, string $tenant): JsonResponse
    {
        $customerId = $this->getCustomerIdFromToken($request);
        
        if (!$customerId) {
            return $this->error('Unauthorized.', 401);
        }

        $customer = $this->customerService->findById($customerId);

        if (!$customer) {
            return $this->error('Customer not found.', 404);
        }

        return $this->success(new CustomerAccountResource($customer));
    }

    /**
     * Update customer profile.
     */
    public function updateProfile(Request $request, string $tenant): JsonResponse
    {
        $customerId = $this->getCustomerIdFromToken($request);
        
        if (!$customerId) {
            return $this->error('Unauthorized.', 401);
        }

        $validated = $request->validate([
            'first_name' => 'sometimes|string|max:100',
            'last_name' => 'sometimes|string|max:100',
            'phone' => 'nullable|string|max:20',
            'password' => 'nullable|string|min:8|confirmed',
        ]);

        $data = new UpdateCustomerData(
            firstName: $validated['first_name'] ?? null,
            lastName: $validated['last_name'] ?? null,
            phone: $validated['phone'] ?? null,
            password: $validated['password'] ?? null,
        );

        $customer = $this->customerService->updateCustomer($customerId, $data);

        return $this->success(
            new CustomerAccountResource($customer),
            'Profile updated successfully.'
        );
    }

    /**
     * Get customer order history.
     */
    public function orders(Request $request, string $tenant): JsonResponse
    {
        $customerId = $this->getCustomerIdFromToken($request);
        
        if (!$customerId) {
            return $this->error('Unauthorized.', 401);
        }

        $orders = $this->customerService->getCustomerOrders($customerId);

        return $this->success(PublicOrderResource::collection($orders));
    }

    /**
     * Convert guest to registered customer.
     */
    public function convertGuest(Request $request, string $tenant): JsonResponse
    {
        $customerId = $this->getCustomerIdFromToken($request);
        
        if (!$customerId) {
            return $this->error('Unauthorized.', 401);
        }

        $validated = $request->validate([
            'password' => 'required|string|min:8|confirmed',
        ]);

        try {
            $customer = $this->customerService->convertGuestToRegistered(
                $customerId,
                $validated['password']
            );

            return $this->success(
                new CustomerAccountResource($customer),
                'Account upgraded successfully.'
            );
        } catch (\InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * Extract customer ID from simple token.
     * Note: This is a basic implementation. Use Laravel Sanctum for production.
     */
    private function getCustomerIdFromToken(Request $request): ?int
    {
        $token = $request->bearerToken();
        
        if (!$token) {
            return null;
        }

        try {
            $decoded = base64_decode($token);
            $parts = explode(':', $decoded);
            
            if (count($parts) >= 3) {
                return (int) $parts[0];
            }
        } catch (\Exception $e) {
            // Invalid token
        }

        return null;
    }
}