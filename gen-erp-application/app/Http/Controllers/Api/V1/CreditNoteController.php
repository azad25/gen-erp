<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Customer\Contracts\PaymentServiceInterface;
use App\Domain\Customer\Models\CreditNote;
use App\Domain\Invoice\Models\Invoice;
use App\Http\Resources\CreditNoteResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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
        private readonly PaymentServiceInterface $paymentService
    ) {}

    /**
     * @OA\Get(
     *     path="/api/v1/credit-notes",
     *     summary="List all credit notes",
     *     tags={"Credit Notes"},
     *
     *     @OA\Parameter(name="search", in="query", description="Search term", @OA\Schema(type="string")),
     *     @OA\Parameter(name="status", in="query", description="Status", @OA\Schema(type="string")),
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
        $creditNotes = CreditNote::query()
            ->where('company_id', activeCompany()->id)
            ->when($request->get('search'), fn ($q, $s) => $q->where('note_number', 'LIKE', "%{$s}%"))
            ->when($request->get('status'), fn ($q, $s) => $q->where('status', $s))
            ->with(['customer', 'invoice'])
            ->orderBy('note_date', 'desc')
            ->paginate($request->integer('per_page', 15));

        return $this->paginated($creditNotes, CreditNoteResource::class);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/credit-notes/{id}",
     *     summary="Get a specific credit note",
     *     tags={"Credit Notes"},
     *
     *     @OA\Parameter(name="id", in="path", required=true, description="Credit Note ID", @OA\Schema(type="integer")),
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
    public function show(CreditNote $creditNote): JsonResponse
    {
        $creditNote->load(['customer', 'invoice', 'items']);

        return $this->success(new CreditNoteResource($creditNote));
    }

    /**
     * @OA\Post(
     *     path="/api/v1/credit-notes",
     *     summary="Create a new credit note",
     *     tags={"Credit Notes"},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *
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
     *
     *     @OA\Response(
     *         response=201,
     *         description="Credit note created",
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
        $companyId = activeCompany()->id;

        $validated = $request->validate([
            'invoice_id' => ['required', Rule::exists('invoices', 'id')->where('company_id', $companyId)],
            'note_date' => ['required', 'date'],
            'reason' => ['required', 'string', 'max:1000'],
            'items' => ['required', 'array'],
            'items.*.product_id' => ['nullable', 'integer'],
            'items.*.description' => ['required', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.unit_price' => ['required', 'integer', 'min:0'],
            'items.*.tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        $invoice = Invoice::where('company_id', $companyId)->findOrFail($validated['invoice_id']);
        
        $data = [
            'note_date' => $validated['note_date'],
            'reason' => $validated['reason'],
        ];

        $creditNote = $this->paymentService->issueCreditNote($invoice, $data, $validated['items']);

        return $this->success(new CreditNoteResource($creditNote->load(['customer', 'invoice', 'items'])), 'Credit note created', 201);
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

        return $this->success(new CreditNoteResource($creditNote->fresh()), 'Credit note updated');
    }

    public function destroy(CreditNote $creditNote): JsonResponse
    {
        $creditNote->delete();

        return $this->success(null, 'Credit note deleted');
    }
}
