<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\StockMovement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Stock Movements",
 *     description="Stock movement tracking"
 * )
 * REST API v1 controller for Stock Movement operations.
 */
class StockMovementController extends BaseApiController
{
    /**
     * @OA\Get(
     *     path="/stock-movements",
     *     summary="List all stock movements",
     *     tags={"Stock Movements"},
     *     @OA\Parameter(name="search", in="query", description="Search term", @OA\Schema(type="string")),
     *     @OA\Parameter(name="movement_type", in="query", description="Movement type", @OA\Schema(type="string")),
     *     @OA\Parameter(name="product_id", in="query", description="Product ID", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="warehouse_id", in="query", description="Warehouse ID", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="per_page", in="query", description="Items per page", @OA\Schema(type="integer", default=15)),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", type="array", @OA\Items(allOf={@OA\Schema(ref="#/components/schemas/StockMovement")})),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $movements = StockMovement::query()
            ->when($request->get('search'), fn ($q, $s) => $q->where('reference', 'LIKE', "%{$s}%"))
            ->when($request->get('movement_type'), fn ($q, $s) => $q->where('movement_type', $s))
            ->when($request->get('product_id'), fn ($q, $id) => $q->where('product_id', $id))
            ->when($request->get('warehouse_id'), fn ($q, $id) => $q->where('warehouse_id', $id))
            ->with(['product', 'warehouse'])
            ->orderBy('movement_date', 'desc')
            ->paginate($request->integer('per_page', 15));

        return $this->paginated($movements);
    }

    /**
     * @OA\Get(
     *     path="/stock-movements/{id}",
     *     summary="Get a specific stock movement",
     *     tags={"Stock Movements"},
     *     @OA\Parameter(name="id", in="path", required=true, description="Stock Movement ID", @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", ref="#/components/schemas/StockMovement")
     *         )
     *     )
     * )
     */
    public function show(StockMovement $stockMovement): JsonResponse
    {
        $stockMovement->load(['product', 'warehouse', 'branch']);

        return $this->success($stockMovement);
    }

    /**
     * @OA\Post(
     *     path="/stock-movements",
     *     summary="Create a new stock movement",
     *     tags={"Stock Movements"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="product_id", type="integer"),
     *             @OA\Property(property="warehouse_id", type="integer"),
     *             @OA\Property(property="branch_id", type="integer"),
     *             @OA\Property(property="movement_type", type="string"),
     *             @OA\Property(property="quantity", type="integer"),
     *             @OA\Property(property="quantity_before", type="integer"),
     *             @OA\Property(property="quantity_after", type="integer"),
     *             @OA\Property(property="unit_cost", type="integer"),
     *             @OA\Property(property="movement_date", type="string", format="date"),
     *             @OA\Property(property="reference", type="string"),
     *             @OA\Property(property="notes", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Stock movement created",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", ref="#/components/schemas/StockMovement"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'movement_type' => ['required', 'string'],
            'quantity' => ['required', 'integer'],
            'quantity_before' => ['required', 'integer'],
            'quantity_after' => ['required', 'integer'],
            'unit_cost' => ['nullable', 'integer'],
            'movement_date' => ['required', 'date'],
            'reference' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ]);

        $validated['company_id'] = activeCompany()?->id;

        $movement = StockMovement::create($validated);

        return $this->success($movement->load(['product', 'warehouse', 'branch']), 'Stock movement created', 201);
    }

    /**
     * @OA\Put(
     *     path="/stock-movements/{id}",
     *     summary="Update a stock movement",
     *     tags={"Stock Movements"},
     *     @OA\Parameter(name="id", in="path", required=true, description="Stock Movement ID", @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="quantity", type="integer"),
     *             @OA\Property(property="quantity_before", type="integer"),
     *             @OA\Property(property="quantity_after", type="integer"),
     *             @OA\Property(property="unit_cost", type="integer"),
     *             @OA\Property(property="movement_date", type="string", format="date"),
     *             @OA\Property(property="reference", type="string"),
     *             @OA\Property(property="notes", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Stock movement updated",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", ref="#/components/schemas/StockMovement"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function update(Request $request, StockMovement $stockMovement): JsonResponse
    {
        $validated = $request->validate([
            'quantity' => ['sometimes', 'integer'],
            'quantity_before' => ['sometimes', 'integer'],
            'quantity_after' => ['sometimes', 'integer'],
            'unit_cost' => ['nullable', 'integer'],
            'movement_date' => ['sometimes', 'date'],
            'reference' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ]);

        $stockMovement->update($validated);

        return $this->success($stockMovement->fresh(), 'Stock movement updated');
    }

    /**
     * @OA\Delete(
     *     path="/stock-movements/{id}",
     *     summary="Delete a stock movement",
     *     tags={"Stock Movements"},
     *     @OA\Parameter(name="id", in="path", required=true, description="Stock Movement ID", @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response=200,
     *         description="Stock movement deleted",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function destroy(StockMovement $stockMovement): JsonResponse
    {
        $stockMovement->delete();

        return $this->success(null, 'Stock movement deleted');
    }
}
