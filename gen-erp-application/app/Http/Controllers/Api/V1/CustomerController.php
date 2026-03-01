<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Customers",
 *     description="Customer management"
 * )
 * REST API v1 controller for Customer CRUD operations.
 */
class CustomerController extends BaseApiController
{
    /**
     * @OA\Get(
     *     path="/customers",
     *     summary="List all customers",
     *     tags={"Customers"},
     *     @OA\Parameter(name="search", in="query", description="Search term", @OA\Schema(type="string")),
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
        $customers = Customer::query()
            ->when($request->get('search'), fn ($q, $s) => $q->where('name', 'LIKE', "%{$s}%"))
            ->orderBy('name')
            ->paginate($request->integer('per_page', 15));

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
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:500'],
            'district' => ['nullable', 'string', 'max:100'],
            'credit_limit' => ['nullable', 'integer', 'min:0'],
        ]);

        $validated['company_id'] = activeCompany()?->id;
        $customer = Customer::create($validated);

        return $this->success($customer, 'Customer created', 201);
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
    public function update(Request $request, Customer $customer): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:500'],
            'district' => ['nullable', 'string', 'max:100'],
            'credit_limit' => ['nullable', 'integer', 'min:0'],
        ]);

        $customer->update($validated);

        return $this->success($customer->fresh(), 'Customer updated');
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
        $customer->delete();

        return $this->success(null, 'Customer deleted');
    }
}
