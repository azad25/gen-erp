<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Accounting\Contracts\AccountingServiceInterface;
use App\Http\Resources\PaymentMethodResource;
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
    public function __construct(
        private readonly AccountingServiceInterface $accountingService
    ) {}
    /**
     * @OA\Get(
     *     path="/api/v1/payment-methods",
     *     summary="List all payment methods",
     *     tags={"Payment Methods"},
     *
     *     @OA\Parameter(name="search", in="query", description="Search term", @OA\Schema(type="string")),
     *     @OA\Parameter(name="is_active", in="query", description="Active status", @OA\Schema(type="boolean")),
     *     @OA\Parameter(name="per_page", in="query", description="Items per page", @OA\Schema(type="integer", default=15)),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *
     *                 @OA\Items(type="object")
     *             ),
     *
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $methods = $this->accountingService->getPaymentMethods(
            activeCompany()->id,
            $request->get('search'),
            $request->boolean('is_active'),
            $request->integer('per_page', 15)
        );

        return $this->paginated($methods, PaymentMethodResource::class);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/payment-methods/{id}",
     *     summary="Get a specific payment method",
     *     tags={"Payment Methods"},
     *
     *     @OA\Parameter(name="id", in="path", required=true, description="Payment Method ID", @OA\Schema(type="integer")),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     )
     * )
     */
    public function show(int $id): JsonResponse
    {
        $paymentMethod = $this->accountingService->getPaymentMethod(activeCompany()->id, $id);

        return $this->success(new PaymentMethodResource($paymentMethod));
    }

    /**
     * @OA\Post(
     *     path="/api/v1/payment-methods",
     *     summary="Create a new payment method",
     *     tags={"Payment Methods"},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="code", type="string"),
     *             @OA\Property(property="description", type="string"),
     *             @OA\Property(property="is_active", type="boolean")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Payment method created",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", type="object"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $method = $this->accountingService->createPaymentMethod(activeCompany()->id, $validated);

        return $this->success(new PaymentMethodResource($method), __('Payment method created'), 201);
    }

    /**
     * @OA\Put(
     *     path="/api/v1/payment-methods/{id}",
     *     summary="Update a payment method",
     *     tags={"Payment Methods"},
     *
     *     @OA\Parameter(name="id", in="path", required=true, description="Payment Method ID", @OA\Schema(type="integer")),
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="code", type="string"),
     *             @OA\Property(property="description", type="string"),
     *             @OA\Property(property="is_active", type="boolean")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Payment method updated",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", type="object"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $paymentMethod = $this->accountingService->getPaymentMethod(activeCompany()->id, $id);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'code' => ['sometimes', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $updatedMethod = $this->accountingService->updatePaymentMethod($paymentMethod, $validated);

        return $this->success(new PaymentMethodResource($updatedMethod), __('Payment method updated'));
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/payment-methods/{id}",
     *     summary="Delete a payment method",
     *     tags={"Payment Methods"},
     *
     *     @OA\Parameter(name="id", in="path", required=true, description="Payment Method ID", @OA\Schema(type="integer")),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Payment method deleted",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function destroy(int $id): JsonResponse
    {
        $paymentMethod = $this->accountingService->getPaymentMethod(activeCompany()->id, $id);
        $this->accountingService->deletePaymentMethod($paymentMethod);

        return $this->success(null, __('Payment method deleted'));
    }
}
