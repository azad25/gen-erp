<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Expense;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Expenses",
 *     description="Expense management"
 * )
 * REST API v1 controller for Expense operations.
 */
class ExpenseController extends BaseApiController
{
    /**
     * @OA\Get(
     *     path="/expenses",
     *     summary="List all expenses",
     *     tags={"Expenses"},
     *     @OA\Parameter(name="search", in="query", description="Search term", @OA\Schema(type="string")),
     *     @OA\Parameter(name="category", in="query", description="Category", @OA\Schema(type="string")),
     *     @OA\Parameter(name="status", in="query", description="Status", @OA\Schema(type="string")),
     *     @OA\Parameter(name="per_page", in="query", description="Items per page", @OA\Schema(type="integer", default=15)),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", type="array", @OA\Items(allOf={@OA\Schema(ref="#/components/schemas/Expense")})),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $expenses = Expense::query()
            ->when($request->get('search'), fn ($q, $s) => $q->where('description', 'LIKE', "%{$s}%"))
            ->when($request->get('category'), fn ($q, $s) => $q->where('category', $s))
            ->when($request->get('status'), fn ($q, $s) => $q->where('status', $s))
            ->with(['creator', 'account', 'paymentAccount'])
            ->orderBy('expense_date', 'desc')
            ->paginate($request->integer('per_page', 15));

        return $this->paginated($expenses);
    }

    /**
     * @OA\Get(
     *     path="/expenses/{id}",
     *     summary="Get a specific expense",
     *     tags={"Expenses"},
     *     @OA\Parameter(name="id", in="path", required=true, description="Expense ID", @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", ref="#/components/schemas/Expense")
     *         )
     *     )
     * )
     */
    public function show(Expense $expense): JsonResponse
    {
        $expense->load(['creator', 'account', 'paymentAccount']);

        return $this->success($expense);
    }

    /**
     * @OA\Post(
     *     path="/expenses",
     *     summary="Create a new expense",
     *     tags={"Expenses"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="expense_number", type="string"),
     *             @OA\Property(property="expense_date", type="string", format="date"),
     *             @OA\Property(property="category", type="string"),
     *             @OA\Property(property="description", type="string"),
     *             @OA\Property(property="amount", type="integer"),
     *             @OA\Property(property="tax_amount", type="integer"),
     *             @OA\Property(property="total_amount", type="integer"),
     *             @OA\Property(property="payment_method_id", type="integer"),
     *             @OA\Property(property="reference_number", type="string"),
     *             @OA\Property(property="receipt_url", type="string"),
     *             @OA\Property(property="status", type="string"),
     *             @OA\Property(property="custom_fields", type="array")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Expense created",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", ref="#/components/schemas/Expense"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'expense_number' => ['nullable', 'string', 'max:255'],
            'expense_date' => ['required', 'date'],
            'category' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:1000'],
            'amount' => ['required', 'integer', 'min:0'],
            'tax_amount' => ['nullable', 'integer', 'min:0'],
            'total_amount' => ['required', 'integer', 'min:0'],
            'payment_method_id' => ['nullable', 'exists:payment_methods,id'],
            'reference_number' => ['nullable', 'string', 'max:255'],
            'receipt_url' => ['nullable', 'url'],
            'status' => ['nullable', 'string'],
            'custom_fields' => ['nullable', 'array'],
        ]);

        $validated['company_id'] = activeCompany()?->id;
        $validated['created_by'] = auth()->id();
        $validated['status'] = $validated['status'] ?? 'pending';

        $expense = Expense::create($validated);

        return $this->success($expense->load(['creator', 'account', 'paymentAccount']), 'Expense created', 201);
    }

    public function update(Request $request, Expense $expense): JsonResponse
    {
        $validated = $request->validate([
            'expense_date' => ['sometimes', 'date'],
            'category' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'string', 'max:1000'],
            'amount' => ['sometimes', 'integer', 'min:0'],
            'tax_amount' => ['nullable', 'integer', 'min:0'],
            'total_amount' => ['sometimes', 'integer', 'min:0'],
            'payment_method_id' => ['nullable', 'exists:payment_methods,id'],
            'reference_number' => ['nullable', 'string', 'max:255'],
            'receipt_url' => ['nullable', 'url'],
            'status' => ['sometimes', 'string'],
            'custom_fields' => ['nullable', 'array'],
        ]);

        $expense->update($validated);

        return $this->success($expense->fresh(), 'Expense updated');
    }

    public function destroy(Expense $expense): JsonResponse
    {
        $expense->delete();

        return $this->success(null, 'Expense deleted');
    }
}
