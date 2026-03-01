<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Payment;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Payments",
 *     description="Payment management"
 * )
 * REST API v1 controller for Payment operations.
 */
class PaymentController extends BaseApiController
{
    public function __construct(
        private PaymentService $paymentService
    ) {}

    /**
     * @OA\Get(
     *     path="/payments",
     *     summary="List all payments",
     *     tags={"Payments"},
     *     @OA\Parameter(name="search", in="query", description="Search term", @OA\Schema(type="string")),
     *     @OA\Parameter(name="payment_type", in="query", description="Payment type", @OA\Schema(type="string")),
     *     @OA\Parameter(name="status", in="query", description="Payment status", @OA\Schema(type="string")),
     *     @OA\Parameter(name="per_page", in="query", description="Items per page", @OA\Schema(type="integer", default=15)),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", type="array", @OA\Items(allOf={@OA\Schema(ref="#/components/schemas/Payment")})),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $payments = Payment::query()
            ->when($request->get('search'), fn ($q, $s) => $q->where('reference', 'LIKE', "%{$s}%"))
            ->when($request->get('payment_type'), fn ($q, $s) => $q->where('payment_type', $s))
            ->when($request->get('status'), fn ($q, $s) => $q->where('status', $s))
            ->with(['customer', 'supplier', 'paymentMethod'])
            ->orderBy('payment_date', 'desc')
            ->paginate($request->integer('per_page', 15));

        return $this->paginated($payments);
    }

    /**
     * @OA\Get(
     *     path="/payments/{id}",
     *     summary="Get a specific payment",
     *     tags={"Payments"},
     *     @OA\Parameter(name="id", in="path", required=true, description="Payment ID", @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", ref="#/components/schemas/Payment")
     *         )
     *     )
     * )
     */
    public function show(Payment $payment): JsonResponse
    {
        $payment->load(['customer', 'supplier', 'paymentMethod', 'allocations.invoice']);

        return $this->success($payment);
    }

    /**
     * @OA\Post(
     *     path="/payments",
     *     summary="Create a new payment",
     *     tags={"Payments"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="payment_type", type="string"),
     *             @OA\Property(property="customer_id", type="integer"),
     *             @OA\Property(property="supplier_id", type="integer"),
     *             @OA\Property(property="payment_method_id", type="integer"),
     *             @OA\Property(property="payment_date", type="string", format="date"),
     *             @OA\Property(property="amount", type="integer"),
     *             @OA\Property(property="reference", type="string"),
     *             @OA\Property(property="notes", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Payment created",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", ref="#/components/schemas/Payment"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'payment_type' => ['required', 'string'],
            'customer_id' => ['nullable', 'exists:customers,id'],
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
            'payment_method_id' => ['required', 'exists:payment_methods,id'],
            'payment_date' => ['required', 'date'],
            'amount' => ['required', 'integer', 'min:0'],
            'reference' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ]);

        $validated['company_id'] = activeCompany()?->id;
        $validated['status'] = 'pending';

        $payment = $this->paymentService->receivePayment($validated);

        return $this->success($payment->load(['customer', 'supplier', 'paymentMethod']), 'Payment created', 201);
    }

    /**
     * @OA\Put(
     *     path="/payments/{id}",
     *     summary="Update a payment",
     *     tags={"Payments"},
     *     @OA\Parameter(name="id", in="path", required=true, description="Payment ID", @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="payment_date", type="string", format="date"),
     *             @OA\Property(property="amount", type="integer"),
     *             @OA\Property(property="reference", type="string"),
     *             @OA\Property(property="notes", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Payment updated",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", ref="#/components/schemas/Payment"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function update(Request $request, Payment $payment): JsonResponse
    {
        $validated = $request->validate([
            'payment_date' => ['sometimes', 'date'],
            'amount' => ['sometimes', 'integer', 'min:0'],
            'reference' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ]);

        $payment->update($validated);

        return $this->success($payment->fresh(), 'Payment updated');
    }

    /**
     * @OA\Delete(
     *     path="/payments/{id}",
     *     summary="Delete a payment",
     *     tags={"Payments"},
     *     @OA\Parameter(name="id", in="path", required=true, description="Payment ID", @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response=200,
     *         description="Payment deleted",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function destroy(Payment $payment): JsonResponse
    {
        $payment->delete();

        return $this->success(null, 'Payment deleted');
    }

    /**
     * @OA\Post(
     *     path="/payments/{payment}/allocate",
     *     summary="Allocate payment to invoice",
     *     tags={"Payments"},
     *     @OA\Parameter(name="payment", in="path", required=true, description="Payment ID", @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="invoice_id", type="integer"),
     *             @OA\Property(property="amount", type="integer")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Payment allocated to invoice",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", type="object"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function allocate(Request $request, Payment $payment): JsonResponse
    {
        $validated = $request->validate([
            'invoice_id' => ['required', 'exists:invoices,id'],
            'amount' => ['required', 'integer', 'min:0'],
        ]);

        $allocation = $this->paymentService->allocateToInvoice($payment, $validated['invoice_id'], $validated['amount']);

        return $this->success($allocation, 'Payment allocated to invoice');
    }
}
