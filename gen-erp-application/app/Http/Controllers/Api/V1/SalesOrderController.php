<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\SalesOrder;
use App\Services\SalesService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Sales Orders",
 *     description="Sales order management"
 * )
 * REST API v1 controller for Sales Order CRUD operations.
 */
class SalesOrderController extends BaseApiController
{
    public function __construct(
        private SalesService $salesService
    ) {}

    /**
     * @OA\Get(
     *     path="/sales-orders",
     *     summary="List all sales orders",
     *     tags={"Sales Orders"},
     *     @OA\Parameter(name="search", in="query", description="Search term", @OA\Schema(type="string")),
     *     @OA\Parameter(name="status", in="query", description="Order status", @OA\Schema(type="string")),
     *     @OA\Parameter(name="customer_id", in="query", description="Customer ID", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="per_page", in="query", description="Items per page", @OA\Schema(type="integer", default=15)),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", type="array", @OA\Items(allOf={@OA\Schema(ref="#/components/schemas/SalesOrder")})),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $orders = SalesOrder::query()
            ->when($request->get('search'), fn ($q, $s) => $q->where('order_number', 'LIKE', "%{$s}%"))
            ->when($request->get('status'), fn ($q, $s) => $q->where('status', $s))
            ->when($request->get('customer_id'), fn ($q, $id) => $q->where('customer_id', $id))
            ->with(['customer', 'warehouse'])
            ->orderBy('order_date', 'desc')
            ->paginate($request->integer('per_page', 15));

        return $this->paginated($orders);
    }

    /**
     * @OA\Get(
     *     path="/sales-orders/{id}",
     *     summary="Get a specific sales order",
     *     tags={"Sales Orders"},
     *     @OA\Parameter(name="id", in="path", required=true, description="Sales Order ID", @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", ref="#/components/schemas/SalesOrder")
     *         )
     *     )
     * )
     */
    public function show(SalesOrder $salesOrder): JsonResponse
    {
        $salesOrder->load(['customer', 'warehouse', 'items.product']);

        return $this->success($salesOrder);
    }

    /**
     * @OA\Post(
     *     path="/sales-orders",
     *     summary="Create a new sales order",
     *     tags={"Sales Orders"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="customer_id", type="integer"),
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
     *         description="Sales order created",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", ref="#/components/schemas/SalesOrder"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customer_id' => ['required', 'exists:customers,id'],
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'order_date' => ['required', 'date'],
            'subtotal' => ['required', 'integer', 'min:0'],
            'discount_amount' => ['nullable', 'integer', 'min:0'],
            'tax_amount' => ['nullable', 'integer', 'min:0'],
            'total_amount' => ['required', 'integer', 'min:0'],
            'items' => ['required', 'array'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['required', 'integer', 'min:0'],
            'items.*.description' => ['nullable', 'string'],
            'items.*.unit' => ['nullable', 'string'],
        ]);

        $validated['company_id'] = activeCompany()?->id;
        $validated['status'] = 'draft';

        $order = $this->salesService->createOrder($validated);

        return $this->success($order->load(['customer', 'warehouse', 'items.product']), 'Sales order created', 201);
    }

    /**
     * @OA\Put(
     *     path="/sales-orders/{id}",
     *     summary="Update a sales order",
     *     tags={"Sales Orders"},
     *     @OA\Parameter(name="id", in="path", required=true, description="Sales Order ID", @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="customer_id", type="integer"),
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
     *         description="Sales order updated",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", ref="#/components/schemas/SalesOrder"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function update(Request $request, SalesOrder $salesOrder): JsonResponse
    {
        $validated = $request->validate([
            'customer_id' => ['sometimes', 'exists:customers,id'],
            'warehouse_id' => ['sometimes', 'exists:warehouses,id'],
            'order_date' => ['sometimes', 'date'],
            'subtotal' => ['sometimes', 'integer', 'min:0'],
            'discount_amount' => ['nullable', 'integer', 'min:0'],
            'tax_amount' => ['nullable', 'integer', 'min:0'],
            'total_amount' => ['sometimes', 'integer', 'min:0'],
        ]);

        $salesOrder->update($validated);

        return $this->success($salesOrder->fresh(), 'Sales order updated');
    }

    /**
     * @OA\Delete(
     *     path="/sales-orders/{id}",
     *     summary="Delete a sales order",
     *     tags={"Sales Orders"},
     *     @OA\Parameter(name="id", in="path", required=true, description="Sales Order ID", @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response=200,
     *         description="Sales order deleted",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function destroy(SalesOrder $salesOrder): JsonResponse
    {
        $salesOrder->delete();

        return $this->success(null, 'Sales order deleted');
    }

    /**
     * @OA\Post(
     *     path="/sales-orders/{salesOrder}/confirm",
     *     summary="Confirm a sales order",
     *     tags={"Sales Orders"},
     *     @OA\Parameter(name="salesOrder", in="path", required=true, description="Sales Order ID", @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response=200,
     *         description="Sales order confirmed",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", ref="#/components/schemas/SalesOrder"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function confirm(SalesOrder $salesOrder): JsonResponse
    {
        $this->salesService->confirmOrder($salesOrder);

        return $this->success($salesOrder->fresh(), 'Sales order confirmed');
    }

    /**
     * @OA\Post(
     *     path="/sales-orders/{salesOrder}/convert-to-invoice",
     *     summary="Convert sales order to invoice",
     *     tags={"Sales Orders"},
     *     @OA\Parameter(name="salesOrder", in="path", required=true, description="Sales Order ID", @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response=201,
     *         description="Invoice created from sales order",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", ref="#/components/schemas/Invoice"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function convertToInvoice(SalesOrder $salesOrder): JsonResponse
    {
        $invoice = $this->salesService->convertToInvoice($salesOrder);

        return $this->success($invoice->load(['customer', 'warehouse', 'items.product']), 'Invoice created from sales order', 201);
    }

    /**
     * @OA\Post(
     *     path="/sales-orders/{salesOrder}/cancel",
     *     summary="Cancel a sales order",
     *     tags={"Sales Orders"},
     *     @OA\Parameter(name="salesOrder", in="path", required=true, description="Sales Order ID", @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response=200,
     *         description="Sales order cancelled",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", ref="#/components/schemas/SalesOrder"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function cancel(SalesOrder $salesOrder): JsonResponse
    {
        $this->salesService->cancelOrder($salesOrder);

        return $this->success($salesOrder->fresh(), 'Sales order cancelled');
    }
}
