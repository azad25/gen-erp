<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Invoice\Models\Invoice;
use App\Domain\Invoice\Services\InvoiceService;
use App\Domain\Invoice\Actions\SendInvoiceAction;
use App\Domain\Invoice\Actions\CancelInvoiceAction;
use App\Http\Requests\Invoice\CreateInvoiceRequest;
use App\Http\Requests\Invoice\UpdateInvoiceRequest;
use App\Domain\Invoice\DTOs\CreateInvoiceData;
use App\Http\Resources\InvoiceResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

// CQRS Components
use App\Domain\Shared\Bus\CommandBus;
use App\Domain\Shared\Bus\QueryBus;
use App\Domain\Shared\Cache\CacheService;
use App\Domain\Invoice\Commands\CreateInvoiceCommand;
use App\Domain\Invoice\Commands\SendInvoiceCommand;
use App\Domain\Invoice\Queries\GetInvoiceQuery;
use App\Domain\Invoice\Queries\GetInvoicesQuery;

/**
 * @OA\Tag(
 *     name="Invoices",
 *     description="Invoice management"
 * )
 * REST API v1 controller for Invoice operations with CQRS pattern.
 */
class InvoiceController extends BaseApiController
{
    public function __construct(
        private readonly InvoiceService $invoiceService,
        private readonly SendInvoiceAction $sendInvoiceAction,
        private readonly CancelInvoiceAction $cancelInvoiceAction,
        private readonly CommandBus $commandBus,
        private readonly QueryBus $queryBus,
        private readonly CacheService $cache,
    ) {}

    /**
     * @OA\Get(
     *     path="/api/v1/invoices",
     *     summary="List all invoices",
     *     tags={"Invoices"},
     *
     *     @OA\Parameter(name="search", in="query", description="Search term", @OA\Schema(type="string")),
     *     @OA\Parameter(name="status", in="query", description="Invoice status", @OA\Schema(type="string")),
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
        $filters = $request->only(['search', 'status', 'customer_id']);
        $perPage = $request->integer('per_page', 15);
        
        // Use CQRS Query with caching
        $cacheKey = CacheService::invoiceListKey($filters + ['per_page' => $perPage]);
        
        $invoices = $this->cache->remember($cacheKey, function () use ($filters, $perPage) {
            $query = new GetInvoicesQuery(
                companyId: activeCompany()->id,
                filters: $filters,
                perPage: $perPage
            );
            
            return $this->queryBus->execute($query);
        }, 300); // 5 minutes cache

        return $this->paginated($invoices);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/invoices/{id}",
     *     summary="Get a specific invoice",
     *     tags={"Invoices"},
     *
     *     @OA\Parameter(name="id", in="path", required=true, description="Invoice ID", @OA\Schema(type="integer")),
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
    public function show(Invoice $invoice): JsonResponse
    {
        // Use CQRS Query with caching
        $cacheKey = CacheService::invoiceKey($invoice->id);
        
        $cachedInvoice = $this->cache->remember($cacheKey, function () use ($invoice) {
            $query = new GetInvoiceQuery(
                invoiceId: $invoice->id,
                companyId: $invoice->company_id
            );
            
            return $this->queryBus->execute($query);
        }, 600); // 10 minutes cache

        return $this->success(new InvoiceResource($cachedInvoice));
    }

    /**
     * @OA\Post(
     *     path="/api/v1/invoices",
     *     summary="Create a new direct invoice",
     *     tags={"Invoices"},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="customer_id", type="integer"),
     *             @OA\Property(property="warehouse_id", type="integer"),
     *             @OA\Property(property="invoice_date", type="string", format="date"),
     *             @OA\Property(property="items", type="array", @OA\Items(type="object"))
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Invoice created",
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
    public function store(CreateInvoiceRequest $request): JsonResponse
    {
        // Use CQRS Command
        $command = new CreateInvoiceCommand(
            companyId: activeCompany()->id,
            customerId: $request->integer('customer_id'),
            warehouseId: $request->integer('warehouse_id'),
            invoiceDate: $request->string('invoice_date', now()->toDateString()),
            dueDate: $request->string('due_date'),
            notes: $request->string('notes'),
            items: $request->array('items'),
            initiatedBy: auth()->id()
        );

        $invoice = $this->commandBus->execute($command);

        // Invalidate related caches
        $this->invalidateInvoiceCaches();

        return $this->success(new InvoiceResource($invoice->load(['customer', 'items.product'])), __('Invoice created'), 201);
    }

    /**
     * @OA\Put(
     *     path="/api/v1/invoices/{id}",
     *     summary="Update a draft invoice",
     *     tags={"Invoices"},
     *
     *     @OA\Parameter(name="id", in="path", required=true, description="Invoice ID", @OA\Schema(type="integer")),
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="customer_id", type="integer"),
     *             @OA\Property(property="items", type="array", @OA\Items(type="object"))
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Invoice updated",
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
    public function update(UpdateInvoiceRequest $request, Invoice $invoice): JsonResponse
    {
        // For now, use existing service (could be converted to CQRS command later)
        $updatedInvoice = $this->invoiceService->updateInvoice(
            $invoice, 
            CreateInvoiceData::fromRequest($request)->toArray(), 
            $request->array('items')
        );

        // Invalidate caches
        $this->cache->forget(CacheService::invoiceKey($invoice->id));
        $this->invalidateInvoiceCaches();

        return $this->success(new InvoiceResource($updatedInvoice->load(['customer', 'items.product'])), __('Invoice updated'));
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
     *     path="/api/v1/invoices/{invoice}/send",
     *     summary="Send an invoice",
     *     tags={"Invoices"},
     *
     *     @OA\Parameter(name="invoice", in="path", required=true, description="Invoice ID", @OA\Schema(type="integer")),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Invoice sent",
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
    public function send(Invoice $invoice): JsonResponse
    {
        $this->authorize('send', $invoice);
        
        // Use CQRS Command
        $command = new SendInvoiceCommand(
            invoiceId: $invoice->id,
            initiatedBy: auth()->id()
        );

        $this->commandBus->execute($command);

        // Invalidate caches
        $this->cache->forget(CacheService::invoiceKey($invoice->id));
        $this->invalidateInvoiceCaches();

        return $this->success(new InvoiceResource($invoice->fresh()->load(['customer', 'items.product'])), __('Invoice sent'));
    }

    /**
     * @OA\Post(
     *     path="/api/v1/invoices/{invoice}/cancel",
     *     summary="Cancel an invoice",
     *     tags={"Invoices"},
     *
     *     @OA\Parameter(name="invoice", in="path", required=true, description="Invoice ID", @OA\Schema(type="integer")),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Invoice cancelled",
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
    public function cancel(Invoice $invoice): JsonResponse
    {
        $this->authorize('cancel', $invoice);
        
        // Use existing Action (could be converted to CQRS command later)
        $this->cancelInvoiceAction->execute($invoice);

        // Invalidate caches
        $this->cache->forget(CacheService::invoiceKey($invoice->id));
        $this->invalidateInvoiceCaches();

        return $this->success(new InvoiceResource($invoice->fresh()), __('Invoice cancelled'));
    }

    /**
     * Invalidate invoice-related caches.
     */
    private function invalidateInvoiceCaches(): void
    {
        // Use cache tags if available, otherwise could implement pattern-based invalidation
        $this->cache->forgetByTags(['invoices', 'company:' . activeCompany()->id]);
    }
}