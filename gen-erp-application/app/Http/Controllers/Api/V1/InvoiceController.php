<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Invoice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Invoices",
 *     description="Invoice management (read-only)"
 * )
 * REST API v1 controller for Invoice operations.
 */
class InvoiceController extends BaseApiController
{
    /**
     * @OA\Get(
     *     path="/invoices",
     *     summary="List all invoices",
     *     tags={"Invoices"},
     *     @OA\Parameter(name="status", in="query", description="Invoice status", @OA\Schema(type="string")),
     *     @OA\Parameter(name="customer_id", in="query", description="Customer ID", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="per_page", in="query", description="Items per page", @OA\Schema(type="integer", default=15)),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", type="array", @OA\Items(allOf={@OA\Schema(ref="#/components/schemas/Invoice")})),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $invoices = Invoice::query()
            ->with('customer')
            ->when($request->get('status'), fn ($q, $s) => $q->where('status', $s))
            ->when($request->get('customer_id'), fn ($q, $c) => $q->where('customer_id', $c))
            ->orderByDesc('invoice_date')
            ->paginate($request->integer('per_page', 15));

        return $this->paginated($invoices);
    }

    /**
     * @OA\Get(
     *     path="/invoices/{id}",
     *     summary="Get a specific invoice",
     *     tags={"Invoices"},
     *     @OA\Parameter(name="id", in="path", required=true, description="Invoice ID", @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", ref="#/components/schemas/Invoice")
     *         )
     *     )
     * )
     */
    public function show(Invoice $invoice): JsonResponse
    {
        return $this->success($invoice->load(['customer', 'items']));
    }

    /**
     * @OA\Post(
     *     path="/invoices",
     *     summary="Create a new invoice",
     *     tags={"Invoices"},
     *     @OA\Response(
     *         response=501,
     *         description="Not implemented",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function store(Request $request): JsonResponse
    {
        return $this->error('Invoice creation via API is not yet supported. Use the web interface.', 501);
    }

    /**
     * @OA\Put(
     *     path="/invoices/{id}",
     *     summary="Update an invoice",
     *     tags={"Invoices"},
     *     @OA\Parameter(name="id", in="path", required=true, description="Invoice ID", @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response=501,
     *         description="Not implemented",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function update(Request $request, Invoice $invoice): JsonResponse
    {
        return $this->error('Invoice updates via API are not yet supported.', 501);
    }

    /**
     * @OA\Delete(
     *     path="/invoices/{id}",
     *     summary="Delete an invoice",
     *     tags={"Invoices"},
     *     @OA\Parameter(name="id", in="path", required=true, description="Invoice ID", @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response=403,
     *         description="Not allowed",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function destroy(Invoice $invoice): JsonResponse
    {
        return $this->error('Invoice deletion via API is not supported.', 403);
    }
}
