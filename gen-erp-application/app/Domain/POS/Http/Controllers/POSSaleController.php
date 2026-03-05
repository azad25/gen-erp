<?php

namespace App\Domain\POS\Http\Controllers;

use App\Domain\POS\Contracts\POSServiceInterface;
use App\Domain\POS\DTOs\CreatePOSSaleData;
use App\Domain\POS\DTOs\POSSaleItemData;
use App\Domain\POS\Exceptions\InvalidPOSSaleException;
use App\Domain\POS\Http\Requests\CreateSaleRequest;
use App\Domain\POS\Http\Resources\POSSaleResource;
use App\Domain\POS\Models\POSSale;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class POSSaleController extends Controller
{
    public function __construct(
        private readonly POSServiceInterface $posService
    ) {}

    public function index(Request $request, int $sessionId): JsonResponse
    {
        $sales = $this->posService->getSales($sessionId);

        return response()->json([
            'success' => true,
            'data' => POSSaleResource::collection($sales),
            'meta' => [
                'current_page' => $sales->currentPage(),
                'total' => $sales->total(),
                'per_page' => $sales->perPage(),
            ],
        ]);
    }

    public function store(CreateSaleRequest $request): JsonResponse
    {
        $items = array_map(
            fn($item) => new POSSaleItemData(
                productId: $item['product_id'] ?? null,
                variantId: $item['variant_id'] ?? null,
                description: $item['description'],
                quantity: $item['quantity'],
                unitPrice: $item['unit_price'],
                discountAmount: $item['discount_amount'] ?? 0,
                taxAmount: $item['tax_amount'] ?? 0,
            ),
            $request->input('items')
        );

        $data = new CreatePOSSaleData(
            sessionId: $request->input('session_id'),
            customerId: $request->input('customer_id'),
            items: $items,
            amountTendered: $request->input('amount_tendered'),
            paymentMethodId: $request->input('payment_method_id'),
        );

        $sale = $this->posService->createSale($data);

        return response()->json([
            'success' => true,
            'message' => 'Sale created successfully.',
            'data' => new POSSaleResource($sale),
        ], 201);
    }

    public function show(POSSale $sale): JsonResponse
    {
        $sale->load(['items.product', 'customer', 'session']);

        return response()->json([
            'success' => true,
            'data' => new POSSaleResource($sale),
        ]);
    }

    public function void(POSSale $sale): JsonResponse
    {
        try {
            $voidedSale = $this->posService->voidSale($sale);

            return response()->json([
                'success' => true,
                'message' => 'Sale voided successfully.',
                'data' => new POSSaleResource($voidedSale),
            ]);
        } catch (InvalidPOSSaleException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
