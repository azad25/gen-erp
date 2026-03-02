<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\CreditNote;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Credit Notes",
 *     description="Credit note management"
 * )
 * REST API v1 controller for Credit Note operations.
 */
class CreditNoteController extends BaseApiController
{
    public function __construct(
        private PaymentService $paymentService
    ) {}

    /**
     * @OA\Get(
     *     path="/credit-notes",
     *     summary="List all credit notes",
     *     tags={"Credit Notes"},
     *     @OA\Parameter(name="search", in="query", description="Search term", @OA\Schema(type="string")),
     *     @OA\Parameter(name="status", in="query", description="Status", @OA\Schema(type="string")),
     *     @OA\Parameter(name="per_page", in="query", description="Items per page", @OA\Schema(type="integer", default=15)),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", type="array", @OA\Items(allOf={@OA\Schema(ref="#/components/schemas/CreditNote")})),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $creditNotes = CreditNote::query()
            ->where('company_id', activeCompany()->id)
            ->when($request->get('search'), fn ($q, $s) => $q->where('note_number', 'LIKE', "%{$s}%"))
            ->when($request->get('status'), fn ($q, $s) => $q->where('status', $s))
            ->with(['customer', 'invoice'])
            ->orderBy('note_date', 'desc')
            ->paginate($request->integer('per_page', 15));

        return $this->paginated($creditNotes);
    }

    /**
     * @OA\Get(
     *     path="/credit-notes/{id}",
     *     summary="Get a specific credit note",
     *     tags={"Credit Notes"},
     *     @OA\Parameter(name="id", in="path", required=true, description="Credit Note ID", @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", ref="#/components/schemas/CreditNote")
     *         )
     *     )
     * )
     */
    public function show(CreditNote $creditNote): JsonResponse
    {
        $creditNote->load(['customer', 'invoice', 'items']);

        return $this->success($creditNote);
    }

    /**
     * @OA\Post(
     *     path="/credit-notes",
     *     summary="Create a new credit note",
     *     tags={"Credit Notes"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="customer_id", type="integer"),
     *             @OA\Property(property="invoice_id", type="integer"),
     *             @OA\Property(property="note_date", type="string", format="date"),
     *             @OA\Property(property="subtotal", type="integer"),
     *             @OA\Property(property="discount_amount", type="integer"),
     *             @OA\Property(property="tax_amount", type="integer"),
     *             @OA\Property(property="total_amount", type="integer"),
     *             @OA\Property(property="reason", type="string"),
     *             @OA\Property(property="items", type="array")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Credit note created",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", ref="#/components/schemas/CreditNote"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customer_id' => ['required', 'exists:customers,id'],
            'invoice_id' => ['required', 'exists:invoices,id'],
            'note_date' => ['required', 'date'],
            'subtotal' => ['required', 'integer', 'min:0'],
            'discount_amount' => ['nullable', 'integer', 'min:0'],
            'tax_amount' => ['nullable', 'integer', 'min:0'],
            'total_amount' => ['required', 'integer', 'min:0'],
            'reason' => ['required', 'string', 'max:1000'],
            'items' => ['required', 'array'],
            'items.*.invoice_item_id' => ['required', 'exists:invoice_items,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['required', 'integer', 'min:0'],
        ]);

        $validated['company_id'] = activeCompany()->id;
        $validated['status'] = 'pending';

        $creditNote = $this->paymentService->issueCreditNote($validated);

        return $this->success($creditNote->load(['customer', 'invoice', 'items']), 'Credit note created', 201);
    }

    public function update(Request $request, CreditNote $creditNote): JsonResponse
    {
        $validated = $request->validate([
            'note_date' => ['sometimes', 'date'],
            'subtotal' => ['sometimes', 'integer', 'min:0'],
            'discount_amount' => ['nullable', 'integer', 'min:0'],
            'tax_amount' => ['nullable', 'integer', 'min:0'],
            'total_amount' => ['sometimes', 'integer', 'min:0'],
            'reason' => ['sometimes', 'string', 'max:1000'],
        ]);

        $creditNote->update($validated);

        return $this->success($creditNote->fresh(), 'Credit note updated');
    }

    public function destroy(CreditNote $creditNote): JsonResponse
    {
        $creditNote->delete();

        return $this->success(null, 'Credit note deleted');
    }
}
