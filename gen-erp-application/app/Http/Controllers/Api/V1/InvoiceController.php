<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Invoice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * REST API v1 controller for Invoice operations.
 */
class InvoiceController extends BaseApiController
{
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

    public function show(Invoice $invoice): JsonResponse
    {
        return $this->success($invoice->load(['customer', 'items']));
    }

    public function store(Request $request): JsonResponse
    {
        return $this->error('Invoice creation via API is not yet supported. Use the web interface.', 501);
    }

    public function update(Request $request, Invoice $invoice): JsonResponse
    {
        return $this->error('Invoice updates via API are not yet supported.', 501);
    }

    public function destroy(Invoice $invoice): JsonResponse
    {
        return $this->error('Invoice deletion via API is not supported.', 403);
    }
}
