<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\PurchaseOrder;
use App\Services\PurchaseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Purchase Orders",
 *     description="Purchase order management"
 * )
 * REST API v1 controller for Purchase Order CRUD operations.
 */
class PurchaseOrderController extends BaseApiController
{
    public function __construct(
        private PurchaseService $purchaseService
    ) {}

    /**
     * @OA\Get(
     *     path="/purchase-orders",
     *     summary="List all purchase orders",
     *     tags={"Purchase Orders"},
     *     @OA\Parameter(name="search", in="query", description="Search term", @OA\Schema(type="string")),
     *     @OA\Parameter(name="status", in="query", description="Order status", @OA\Schema(type="string")),
     *     @OA\Parameter(name="supplier_id", in="query", description="Supplier ID", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="per_page", in="query", description="Items per page", @OA\Schema(type="integer", default=15)),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/PurchaseOrder")),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $orders = PurchaseOrder::query()
            ->when($request->get('search'), fn ($q, $s) => $q->where('order_number', 'LIKE', "%{$s}%"))
            ->when($request->get('status'), fn ($q, $s) => $q->where('status', $s))
            ->when($request->get('supplier_id'), fn ($q, $id) => $q->where('supplier_id', $id))
            ->with(['supplier', 'warehouse'])
            ->orderBy('order_date', 'desc')
            ->paginate($request->integer('per_page', 15));

        return $this->paginated($orders);
    }

    /**
     * @OA\Get(
     *     path="/purchase-orders/{id}",
     *     summary="Get a specific purchase order",
     *     tags={"Purchase Orders"},
     *     @OA\Parameter(name="id", in="path", required=true, description="Purchase Order ID", @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", ref="#/components/schemas/PurchaseOrder")
     *         )
     *     )
     * )
     */
    public function show(PurchaseOrder $purchaseOrder): JsonResponse
    {
        $purchaseOrder->load(['supplier', 'warehouse', 'items.product']);

        return $this->success($purchaseOrder);
    }

    /**
     * @OA\Post(
     *     path="/purchase-orders",
     *     summary="Create a new purchase order",
     *     tags={"Purchase Orders"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="supplier_id", type="integer"),
     *             @OA\Property(property="warehouse_id", type="integer"),
     *             @OA\Property(property="order_date", type="string", format="date"),
     *             @OA\Property(property="subtotal", type="integer"),
     *             @OA\Property(property="discount_amount", type="integer"),
     *             @OA\Property(property="tax_amount", type="integer"),
     *             @OA\Property(property="total_amount", type="integer"),
     *             @OA\Property(property="items", type="array", @OA\Items(type="object"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Purchase order created",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", ref="#/components/schemas/PurchaseOrder"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'order_date' => ['required', 'date'],
            'subtotal' => ['required', 'integer', 'min:0'],
            'discount_amount' => ['nullable', 'integer', 'min:0'],
            'tax_amount' => ['nullable', 'integer', 'min:0'],
            'total_amount' => ['required', 'integer', 'min:0'],
            'items' => ['required', 'array'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity_ordered' => ['required', 'integer', 'min:1'],
            'items.*.unit_cost' => ['required', 'integer', 'min:0'],
            'items.*.description' => ['nullable', 'string'],
            'items.*.unit' => ['nullable', 'string'],
        ]);

        $validated['company_id'] = activeCompany()?->id;
        $validated['status'] = 'draft';

        $order = $this->purchaseService->createOrder($validated);

        return $this->success($order->load(['supplier', 'warehouse', 'items.product']), 'Purchase order created', 201);
    }

    /**
     * @OA\Put(
     *     path="/purchase-orders/{id}",
     *     summary="Update a purchase order",
     *     tags={"Purchase Orders"},
     *     @OA\Parameter(name="id", in="path", required=true, description="Purchase Order ID", @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="supplier_id", type="integer"),
     *             @OA\Property(property="warehouse_id", type="integer"),
     *             @OA\Property(property="order_date", type="string", format="date"),
     *             @OA\Property(property="subtotal", type="integer"),
     *             @OA\Property(property="discount_amount", type="integer"),
     *             @OA\Property(property="tax_amount", type="integer"),
     *             @OA\Property(property="total_amount", type="integer")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Purchase order updated",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", ref="#/components/schemas/PurchaseOrder"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function update(Request $request, PurchaseOrder $purchaseOrder): JsonResponse
    {
        $validated = $request->validate([
            'supplier_id' => ['sometimes', 'exists:suppliers,id'],
            'warehouse_id' => ['sometimes', 'exists:warehouses,id'],
            'order_date' => ['sometimes', 'date'],
            'subtotal' => ['sometimes', 'integer', 'min:0'],
            'discount_amount' => ['nullable', 'integer', 'min:0'],
            'tax_amount' => ['nullable', 'integer', 'min:0'],
            'total_amount' => ['sometimes', 'integer', 'min:0'],
        ]);

        $purchaseOrder->update($validated);

        return $this->success($purchaseOrder->fresh(), 'Purchase order updated');
    }

    /**
     * @OA\Delete(
     *     path="/purchase-orders/{id}",
     *     summary="Delete a purchase order",
     *     tags={"Purchase Orders"},
     *     @OA\Parameter(name="id", in="path", required=true, description="Purchase Order ID", @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response=200,
     *         description="Purchase order deleted",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function destroy(PurchaseOrder $purchaseOrder): JsonResponse
    {
        $purchaseOrder->delete();

        return $this->success(null, 'Purchase order deleted');
    }

    /**
     * @OA\Post(
     *     path="/purchase-orders/{purchaseOrder}/confirm",
     *     summary="Confirm a purchase order",
     *     tags={"Purchase Orders"},
     *     @OA\Parameter(name="purchaseOrder", in="path", required=true, description="Purchase Order ID", @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response=200,
     *         description="Purchase order confirmed",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", ref="#/components/schemas/PurchaseOrder"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function confirm(PurchaseOrder $purchaseOrder): JsonResponse
    {
        $this->purchaseService->confirmOrder($purchaseOrder);

        return $this->success($purchaseOrder->fresh(), 'Purchase order confirmed');
    }

    /**
     * @OA\Post(
     *     path="/purchase-orders/{purchaseOrder}/receive",
     *     summary="Receive goods from purchase order",
     *     tags={"Purchase Orders"},
     *     @OA\Parameter(name="purchaseOrder", in="path", required=true, description="Purchase Order ID", @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
                
     *             @OA\Property(property="items",
                type="array",
                @OA\Items(type="object"
            )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Goods receipt created",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", ref="#/components/schemas/GoodsReceipt"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function receive(Request $request, PurchaseOrder $purchaseOrder): JsonResponse
    {
        $validated = $request->validate([
            'items' => ['required', 'array'],
            'items.*.purchase_order_item_id' => ['required', 'exists:purchase_order_items,id'],
            'items.*.quantity_received' => ['required', 'integer', 'min:1'],
        ]);

        $receipt = $this->purchaseService->createReceipt($purchaseOrder, $validated['items']);

        return $this->success($receipt->load(['supplier', 'warehouse', 'items.product']), 'Goods receipt created', 201);
    }

    /**
     * @OA\Post(
     *     path="/purchase-orders/{purchaseOrder}/cancel",
     *     summary="Cancel a purchase order",
     *     tags={"Purchase Orders"},
     *     @OA\Parameter(name="purchaseOrder", in="path", required=true, description="Purchase Order ID", @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response=200,
     *         description="Purchase order cancelled",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", ref="#/components/schemas/PurchaseOrder"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function cancel(PurchaseOrder $purchaseOrder): JsonResponse
    {
        $this->purchaseService->cancelOrder($purchaseOrder);

        return $this->success($purchaseOrder->fresh(), 'Purchase order cancelled');
    }
}
