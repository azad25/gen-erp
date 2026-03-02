<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\CustomerPayment;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

/**
 * @OA\Tag(
 *     name="Payments",
 *     description="Customer payment management"
 * )
 * REST API v1 controller for Customer Payment operations.
 */
class PaymentController extends BaseApiController
{
    public function __construct(
        private readonly PaymentService $paymentService
    ) {}

    /**
     * @OA\Get(
     *     path="/payments",
     *     summary="List all customer payments",
     *     tags={"Payments"},
     *     @OA\Parameter(name="search", in="query", description="Search term", @OA\Schema(type="string")),
     *     @OA\Parameter(name="customer_id", in="query", description="Customer ID", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="per_page", in="query", description="Items per page", @OA\Schema(type="integer", default=15)),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $companyId = activeCompany()->id;

        $payments = CustomerPayment::query()
            ->where('company_id', $companyId)
            ->when($request->get('search'), fn ($q, $s) => $q->where('receipt_number', 'LIKE', "%{$s}%"))
            ->when($request->get('customer_id'), fn ($q, $id) => $q->where('customer_id', $id))
            ->with(['customer'])
            ->orderBy('payment_date', 'desc')
            ->paginate($request->integer('per_page', 15));

        return $this->paginated($payments);
    }

    /**
     * @OA\Get(
     *     path="/payments/{id}",
     *     summary="Get a specific customer payment",
     *     tags={"Payments"},
     *     @OA\Parameter(name="id", in="path", required=true, description="Payment ID", @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     )
     * )
     */
    public function show(int $id): JsonResponse
    {
        $payment = CustomerPayment::query()
            ->where('company_id', activeCompany()->id)
            ->with(['customer', 'allocations.invoice'])
            ->findOrFail($id);

        return $this->success($payment);
    }

    /**
     * @OA\Post(
     *     path="/payments",
     *     summary="Receive a customer payment",
     *     tags={"Payments"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="customer_id", type="integer"),
     *             @OA\Property(property="payment_date", type="string", format="date"),
     *             @OA\Property(property="amount", type="integer"),
     *             @OA\Property(property="payment_method", type="string"),
     *             @OA\Property(property="reference", type="string"),
     *             @OA\Property(property="notes", type="string"),
     *             @OA\Property(property="allocations", type="array", @OA\Items(type="object"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Payment received",
     *         @OA\JsonContent(
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
            'customer_id' => ['required', Rule::exists('customers', 'id')->where('company_id', $companyId)],
            'payment_date' => ['required', 'date'],
            'amount' => ['required', 'integer', 'min:1'],
            'payment_method' => ['nullable', 'string', 'max:50'],
            'reference' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'allocations' => ['nullable', 'array'],
            'allocations.*.invoice_id' => ['required_with:allocations', Rule::exists('invoices', 'id')->where('company_id', $companyId)],
            'allocations.*.amount' => ['required_with:allocations', 'integer', 'min:1'],
        ]);

        $customer = \App\Models\Customer::where('company_id', $companyId)->findOrFail($validated['customer_id']);

        $data = collect($validated)->except(['customer_id', 'allocations'])->toArray();
        $allocations = $validated['allocations'] ?? [];

        try {
            $payment = $this->paymentService->receivePayment($customer, $data, $allocations);
        } catch (InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 422);
        }

        return $this->success($payment->load(['customer', 'allocations.invoice']), __('Payment received'), 201);
    }

    /**
     * Customer payments are financial records — update limited to notes/reference only.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $payment = CustomerPayment::query()
            ->where('company_id', activeCompany()->id)
            ->findOrFail($id);

        $validated = $request->validate([
            'notes' => ['nullable', 'string', 'max:2000'],
            'reference' => ['nullable', 'string', 'max:255'],
        ]);

        $payment->update($validated);

        return $this->success($payment->fresh(), __('Payment notes updated'));
    }

    /**
     * Customer payments are financial records and cannot be deleted.
     */
    public function destroy(int $id): JsonResponse
    {
        return $this->error(__('Payments are financial records and cannot be deleted. Use a credit note or reversal instead.'), 403);
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
    public function allocate(Request $request, int $id): JsonResponse
    {
        $companyId = activeCompany()->id;
        $payment = CustomerPayment::query()
            ->where('company_id', $companyId)
            ->findOrFail($id);

        $validated = $request->validate([
            'invoice_id' => ['required', Rule::exists('invoices', 'id')->where('company_id', $companyId)],
            'amount' => ['required', 'integer', 'min:1'],
        ]);

        $this->paymentService->allocatePayment($payment, $validated['invoice_id'], $validated['amount']);

        return $this->success($payment->fresh()->load(['allocations.invoice']), __('Payment allocated to invoice'));
    }
}
