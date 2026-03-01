<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\PaymentMethod;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Payment Methods",
 *     description="Payment method management"
 * )
 * REST API v1 controller for Payment Method CRUD operations.
 */
class PaymentMethodController extends BaseApiController
{
    /**
     * @OA\Get(
     *     path="/payment-methods",
     *     summary="List all payment methods",
     *     tags={"Payment Methods"},
     *     @OA\Parameter(name="search", in="query", description="Search term", @OA\Schema(type="string")),
     *     @OA\Parameter(name="is_active", in="query", description="Active status", @OA\Schema(type="boolean")),
     *     @OA\Parameter(name="per_page", in="query", description="Items per page", @OA\Schema(type="integer", default=15)),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
                @OA\Property(property="success", type="boolean"
            ), @OA\Property(property="data", type="array", @OA\Items(allOf={@OA\Schema(ref="#/components/schemas/PaymentMethod")})), @OA\Property(property="message", type="string")))
     */
    public function index(Request $request): JsonResponse
    {
        $methods = PaymentMethod::query()
            ->when($request->get('search'), fn ($q, $s) => $q->where('name', 'LIKE', "%{$s}%"))
            ->when($request->get('is_active'), fn ($q, $s) => $q->where('is_active', $s))
            ->orderBy('name')
            ->paginate($request->integer('per_page', 15));

        return $this->paginated($methods);
    }

    /**
     * @OA\Get(
     *     path="/payment-methods/{id}",
     *     summary="Get a specific payment method",
     *     tags={"Payment Methods"},
     *     @OA\Parameter(name="id", in="path", required=true, description="Payment Method ID", @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
                @OA\Property(property="success", type="boolean"
            ), @OA\Property(property="data", ref="#/components/schemas/PaymentMethod")))
     */
    public function show(PaymentMethod $paymentMethod): JsonResponse
    {
        return $this->success($paymentMethod);
    }

    /**
     * @OA\Post(
     *     path="/payment-methods",
     *     summary="Create a new payment method",
     *     tags={"Payment Methods"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
                @OA\Property(property="name", type="string"
            ), @OA\Property(property="code", type="string"), @OA\Property(property="description", type="string"), @OA\Property(property="is_active", type="boolean"))),
     *     @OA\Response(
     *         response=201,
     *         description="Payment method created",
     *         @OA\JsonContent(
                @OA\Property(property="success", type="boolean"
            ), @OA\Property(property="data", ref="#/components/schemas/PaymentMethod"), @OA\Property(property="message", type="string")))
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $validated['company_id'] = activeCompany()?->id;

        $method = PaymentMethod::create($validated);

        return $this->success($method, 'Payment method created', 201);
    }

    /**
     * @OA\Put(
     *     path="/payment-methods/{id}",
     *     summary="Update a payment method",
     *     tags={"Payment Methods"},
     *     @OA\Parameter(name="id", in="path", required=true, description="Payment Method ID", @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
                @OA\Property(property="name", type="string"
            ), @OA\Property(property="code", type="string"), @OA\Property(property="description", type="string"), @OA\Property(property="is_active", type="boolean"))),
     *     @OA\Response(
     *         response=200,
     *         description="Payment method updated",
     *         @OA\JsonContent(
                @OA\Property(property="success", type="boolean"
            ), @OA\Property(property="data", ref="#/components/schemas/PaymentMethod"), @OA\Property(property="message", type="string")))
     */
    public function update(Request $request, PaymentMethod $paymentMethod): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'code' => ['sometimes', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $paymentMethod->update($validated);

        return $this->success($paymentMethod->fresh(), 'Payment method updated');
    }

    /**
     * @OA\Delete(
     *     path="/payment-methods/{id}",
     *     summary="Delete a payment method",
     *     tags={"Payment Methods"},
     *     @OA\Parameter(name="id", in="path", required=true, description="Payment Method ID", @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response=200,
     *         description="Payment method deleted",
     *         @OA\JsonContent(
                @OA\Property(property="success", type="boolean"
            ), @OA\Property(property="message", type="string")))
     */
    public function destroy(PaymentMethod $paymentMethod): JsonResponse
    {
        $paymentMethod->delete();

        return $this->success(null, 'Payment method deleted');
    }
}
