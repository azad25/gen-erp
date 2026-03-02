<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\StoreCustomerRequest;
use App\Http\Requests\Api\V1\UpdateCustomerRequest;
use App\Models\Customer;
use App\Services\ContactService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * @OA\Tag(
 *     name="Customers",
 *     description="Customer management"
 * )
 * REST API v1 controller for Customer CRUD operations.
 */
class CustomerController extends BaseApiController
{
    public function __construct(
        private readonly ContactService $contactService
    ) {}

    /**
     * @OA\Get(
     *     path="/customers",
     *     summary="List all customers",
     *     tags={"Customers"},
     *     @OA\Parameter(name="search", in="query", description="Search term", @OA\Schema(type="string")),
     *     @OA\Parameter(name="status", in="query", description="Status filter", @OA\Schema(type="string")),
     *     @OA\Parameter(name="contact_group_id", in="query", description="Contact Group ID", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="per_page", in="query", description="Items per page", @OA\Schema(type="integer", default=15)),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", type="array", @OA\Items(allOf={@OA\Schema(ref="#/components/schemas/Customer")})),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $customers = $this->contactService->paginateCustomers(
            activeCompany(),
            $request->only(['search', 'status', 'contact_group_id']),
            $request->integer('per_page', 15),
        );

        return $this->paginated($customers);
    }

    /**
     * @OA\Get(
     *     path="/customers/{id}",
     *     summary="Get a specific customer",
     *     tags={"Customers"},
     *     @OA\Parameter(name="id", in="path", required=true, description="Customer ID", @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", ref="#/components/schemas/Customer")
     *         )
     *     )
     * )
     */
    public function show(Customer $customer): JsonResponse
    {
        $customer->load(['contactGroup']);

        return $this->success($customer);
    }

    /**
     * @OA\Post(
     *     path="/customers",
     *     summary="Create a new customer",
     *     tags={"Customers"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="email", type="string"),
     *             @OA\Property(property="phone", type="string"),
     *             @OA\Property(property="address", type="string"),
     *             @OA\Property(property="district", type="string"),
     *             @OA\Property(property="credit_limit", type="integer")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Customer created",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", ref="#/components/schemas/Customer"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function store(StoreCustomerRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $customFields = $validated['custom_fields'] ?? [];
        unset($validated['custom_fields']);

        $customer = $this->contactService->createCustomer(
            activeCompany(),
            $validated,
            $customFields,
        );

        return $this->success($customer, __('Customer created'), 201);
    }

    /**
     * @OA\Put(
     *     path="/customers/{id}",
     *     summary="Update a customer",
     *     tags={"Customers"},
     *     @OA\Parameter(name="id", in="path", required=true, description="Customer ID", @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="email", type="string"),
     *             @OA\Property(property="phone", type="string"),
     *             @OA\Property(property="address", type="string"),
     *             @OA\Property(property="district", type="string"),
     *             @OA\Property(property="credit_limit", type="integer")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Customer updated",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", ref="#/components/schemas/Customer"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function update(UpdateCustomerRequest $request, Customer $customer): JsonResponse
    {
        $validated = $request->validated();
        $customFields = $validated['custom_fields'] ?? [];
        unset($validated['custom_fields']);

        $customer = $this->contactService->updateCustomer(
            $customer,
            $validated,
            $customFields,
        );

        return $this->success($customer, __('Customer updated'));
    }

    /**
     * @OA\Delete(
     *     path="/customers/{id}",
     *     summary="Delete a customer",
     *     tags={"Customers"},
     *     @OA\Parameter(name="id", in="path", required=true, description="Customer ID", @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response=200,
     *         description="Customer deleted",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function destroy(Customer $customer): JsonResponse
    {
        try {
            $this->contactService->deleteCustomer($customer);
        } catch (RuntimeException $e) {
            return $this->error($e->getMessage(), 422);
        }

        return $this->success(null, __('Customer deleted'));
    }
}
