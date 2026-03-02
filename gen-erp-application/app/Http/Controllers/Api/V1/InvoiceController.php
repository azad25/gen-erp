<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Invoice;
use App\Services\SalesService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * @OA\Tag(
 *     name="Invoices",
 *     description="Invoice management"
 * )
 * REST API v1 controller for Invoice operations.
 */
class InvoiceController extends BaseApiController
{
    public function __construct(
        private readonly SalesService $salesService
    ) {}

    /**
     * @OA\Get(
     *     path="/invoices",
     *     summary="List all invoices",
     *     tags={"Invoices"},
     *     @OA\Parameter(name="search", in="query", description="Search term", @OA\Schema(type="string")),
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
        $invoices = $this->salesService->paginateInvoices(
            activeCompany(),
            $request->only(['search', 'status', 'customer_id']),
            $request->integer('per_page', 15),
        );

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
        return $this->success($invoice->load(['customer', 'items.product']));
    }

    /**
     * @OA\Post(
     *     path="/invoices",
     *     summary="Create a new direct invoice",
     *     tags={"Invoices"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="customer_id", type="integer"),
     *             @OA\Property(property="warehouse_id", type="integer"),
     *             @OA\Property(property="invoice_date", type="string", format="date"),
     *             @OA\Property(property="items", type="array", @OA\Items(type="object"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Invoice created",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", ref="#/components/schemas/Invoice"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function store(Request $request): JsonResponse
    {
        $companyId = activeCompany()->id;

        $validated = $request->validate([
            'customer_id' => ['required', Rule::exists('customers', 'id')->where('company_id', $companyId)],
            'warehouse_id' => ['nullable', Rule::exists('warehouses', 'id')->where('company_id', $companyId)],
            'invoice_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:invoice_date'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'terms_conditions' => ['nullable', 'string', 'max:5000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['nullable', Rule::exists('products', 'id')->where('company_id', $companyId)],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.unit_price' => ['required', 'integer', 'min:0'],
            'items.*.description' => ['nullable', 'string'],
            'items.*.unit' => ['nullable', 'string'],
            'items.*.discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'items.*.tax_rate' => ['nullable', 'numeric', 'min:0'],
            'items.*.tax_group_id' => ['nullable', Rule::exists('tax_groups', 'id')->where('company_id', $companyId)],
        ]);

        $items = $validated['items'];
        unset($validated['items']);

        $invoice = $this->salesService->createInvoice(
            activeCompany(),
            $validated,
            $items,
        );

        return $this->success($invoice->load(['customer', 'items.product']), __('Invoice created'), 201);
    }

    /**
     * @OA\Put(
     *     path="/invoices/{id}",
     *     summary="Update a draft invoice",
     *     tags={"Invoices"},
     *     @OA\Parameter(name="id", in="path", required=true, description="Invoice ID", @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="customer_id", type="integer"),
     *             @OA\Property(property="items", type="array", @OA\Items(type="object"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Invoice updated",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", ref="#/components/schemas/Invoice"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function update(Request $request, Invoice $invoice): JsonResponse
    {
        if ($invoice->status->value !== 'draft') {
            return $this->error(__('Only draft invoices can be updated.'), 422);
        }

        $companyId = activeCompany()->id;

        $validated = $request->validate([
            'customer_id' => ['sometimes', Rule::exists('customers', 'id')->where('company_id', $companyId)],
            'warehouse_id' => ['nullable', Rule::exists('warehouses', 'id')->where('company_id', $companyId)],
            'invoice_date' => ['sometimes', 'date'],
            'due_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'terms_conditions' => ['nullable', 'string', 'max:5000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['nullable', Rule::exists('products', 'id')->where('company_id', $companyId)],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.unit_price' => ['required', 'integer', 'min:0'],
            'items.*.description' => ['nullable', 'string'],
            'items.*.unit' => ['nullable', 'string'],
            'items.*.discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'items.*.tax_rate' => ['nullable', 'numeric', 'min:0'],
            'items.*.tax_group_id' => ['nullable', Rule::exists('tax_groups', 'id')->where('company_id', $companyId)],
        ]);

        $items = $validated['items'];
        unset($validated['items']);

        $invoice = $this->salesService->updateInvoice($invoice, $validated, $items);

        return $this->success($invoice->load(['customer', 'items.product']), __('Invoice updated'));
    }

    /**
     * Invoices are financial records and cannot be deleted.
     * Use the cancel endpoint instead.
     */
    public function destroy(Invoice $invoice): JsonResponse
    {
        return $this->error(__('Invoices cannot be deleted. Use cancel instead.'), 403);
    }

    /**
     * @OA\Post(
     *     path="/invoices/{invoice}/send",
     *     summary="Send an invoice",
     *     tags={"Invoices"},
     *     @OA\Parameter(name="invoice", in="path", required=true, description="Invoice ID", @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response=200,
     *         description="Invoice sent",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", ref="#/components/schemas/Invoice"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function send(Invoice $invoice): JsonResponse
    {
        $this->salesService->sendInvoice($invoice);

        return $this->success($invoice->fresh()->load(['customer', 'items.product']), __('Invoice sent'));
    }

    /**
     * @OA\Post(
     *     path="/invoices/{invoice}/cancel",
     *     summary="Cancel an invoice",
     *     tags={"Invoices"},
     *     @OA\Parameter(name="invoice", in="path", required=true, description="Invoice ID", @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response=200,
     *         description="Invoice cancelled",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", ref="#/components/schemas/Invoice"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function cancel(Invoice $invoice): JsonResponse
    {
        $this->salesService->cancelInvoice($invoice);

        return $this->success($invoice->fresh(), __('Invoice cancelled'));
    }
}
