<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\SalesOrder\Models\SalesOrder;
use App\Domain\SalesOrder\Services\SalesOrderService;
use App\Domain\SalesOrder\Actions\ConfirmSalesOrderAction;
use App\Domain\SalesOrder\Actions\CancelSalesOrderAction;
use App\Domain\Invoice\Actions\ConvertOrderToInvoiceAction;
use App\Http\Requests\SalesOrder\CreateSalesOrderRequest;
use App\Http\Requests\SalesOrder\UpdateSalesOrderRequest;
use App\Domain\SalesOrder\DTOs\CreateSalesOrderData;
use App\Http\Resources\SalesOrderResource;
use App\Http\Resources\InvoiceResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

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
        private readonly SalesOrderService $salesOrderService,
        private readonly ConfirmSalesOrderAction $confirmSalesOrderAction,
        private readonly CancelSalesOrderAction $cancelSalesOrderAction,
        private readonly ConvertOrderToInvoiceAction $convertOrderToInvoiceAction,
    ) {}

    /**
     * @OA\Get(
     *     path="/api/v1/sales-orders",
     *     summary="List all sales orders",
     *     tags={"Sales Orders"},
     *
     *     @OA\Parameter(name="search", in="query", description="Search term", @OA\Schema(type="string")),
     *     @OA\Parameter(name="status", in="query", description="Order status", @OA\Schema(type="string")),
     *     @OA\Parameter(name="customer_id", in="query", description="Customer ID", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="per_page", in="query", description="Items per page", @OA\Schema(type="integer", default=15)),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $orders = $this->salesOrderService->paginateOrders(
            activeCompany(),
            $request->only(['search', 'status', 'customer_id']),
            $request->integer('per_page', 15),
        );

        return $this->paginated($orders);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/sales-orders/{id}",
     *     summary="Get a specific sales order",
     *     tags={"Sales Orders"},
     *
     *     @OA\Parameter(name="id", in="path", required=true, description="Sales Order ID", @OA\Schema(type="integer")),
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
    public function show(SalesOrder $salesOrder): JsonResponse
    {
        $salesOrder->load(['customer', 'warehouse', 'items.product']);

        return $this->success($salesOrder);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/sales-orders",
     *     summary="Create a new sales order",
     *     tags={"Sales Orders"},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="customer_id", type="integer"),
     *             @OA\Property(property="warehouse_id", type="integer"),
     *             @OA\Property(property="order_date", type="string", format="date"),
     *             @OA\Property(property="items", type="array", @OA\Items(type="object"))
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Sales order created",
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
    public function store(CreateSalesOrderRequest $request): JsonResponse
    {
        $order = $this->salesOrderService->createOrder(
            activeCompany(),
            CreateSalesOrderData::fromRequest($request)->toArray(),
            $request->array('items'),
        );

        return $this->success($order->load(['customer', 'warehouse', 'items.product']), __('Sales order created'), 201);
    }

    /**
     * @OA\Put(
     *     path="/api/v1/sales-orders/{id}",
     *     summary="Update a sales order",
     *     tags={"Sales Orders"},
     *
     *     @OA\Parameter(name="id", in="path", required=true, description="Sales Order ID", @OA\Schema(type="integer")),
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="customer_id", type="integer"),
     *             @OA\Property(property="warehouse_id", type="integer"),
     *             @OA\Property(property="order_date", type="string", format="date"),
     *             @OA\Property(property="items", type="array", @OA\Items(type="object"))
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Sales order updated",
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
    public function update(UpdateSalesOrderRequest $request, SalesOrder $salesOrder): JsonResponse
    {
        $order = $this->salesOrderService->updateOrder(
            $salesOrder, 
            CreateSalesOrderData::fromRequest($request)->toArray(), 
            $request->array('items')
        );

        return $this->success($order->load(['customer', 'warehouse', 'items.product']), __('Sales order updated'));
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/sales-orders/{id}",
     *     summary="Delete a sales order",
     *     tags={"Sales Orders"},
     *
     *     @OA\Parameter(name="id", in="path", required=true, description="Sales Order ID", @OA\Schema(type="integer")),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Sales order deleted",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function destroy(SalesOrder $salesOrder): JsonResponse
    {
        try {
            $this->salesOrderService->deleteOrder($salesOrder);
        } catch (RuntimeException $e) {
            return $this->error($e->getMessage(), 422);
        }

        return $this->success(null, __('Sales order deleted'));
    }

    /**
     * @OA\Post(
     *     path="/api/v1/sales-orders/{salesOrder}/confirm",
     *     summary="Confirm a sales order",
     *     tags={"Sales Orders"},
     *
     *     @OA\Parameter(name="salesOrder", in="path", required=true, description="Sales Order ID", @OA\Schema(type="integer")),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Sales order confirmed",
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
    public function confirm(SalesOrder $salesOrder): JsonResponse
    {
        $this->authorize('confirm', $salesOrder);
        
        $this->confirmSalesOrderAction->execute($salesOrder);

        return $this->success(new SalesOrderResource($salesOrder->fresh()), __('Sales order confirmed'));
    }

    /**
     * @OA\Post(
     *     path="/api/v1/sales-orders/{salesOrder}/convert-to-invoice",
     *     summary="Convert sales order to invoice",
     *     tags={"Sales Orders"},
     *
     *     @OA\Parameter(name="salesOrder", in="path", required=true, description="Sales Order ID", @OA\Schema(type="integer")),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Invoice created from sales order",
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
    public function convertToInvoice(SalesOrder $salesOrder): JsonResponse
    {
        $this->authorize('convertToInvoice', $salesOrder);
        
        $invoice = $this->convertOrderToInvoiceAction->execute($salesOrder);

        return $this->success(new InvoiceResource($invoice->load(['customer', 'items.product'])), __('Invoice created from sales order'), 201);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/sales-orders/{salesOrder}/cancel",
     *     summary="Cancel a sales order",
     *     tags={"Sales Orders"},
     *
     *     @OA\Parameter(name="salesOrder", in="path", required=true, description="Sales Order ID", @OA\Schema(type="integer")),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Sales order cancelled",
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
    public function cancel(SalesOrder $salesOrder): JsonResponse
    {
        $this->authorize('cancel', $salesOrder);
        
        $this->cancelSalesOrderAction->execute($salesOrder);

        return $this->success(new SalesOrderResource($salesOrder->fresh()), __('Sales order cancelled'));
    }
}
