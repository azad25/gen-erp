<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\StockMovement;
use App\Services\InventoryService;
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
    public function __construct(
        private readonly InventoryService $inventoryService
    ) {}

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
        $movements = $this->inventoryService->paginateMovements(
            activeCompany(),
            $request->only(['search', 'movement_type', 'product_id', 'warehouse_id']),
            $request->integer('per_page', 15),
        );

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
     * Stock movements are created through InventoryService operations
     * (stockIn, stockOut, adjustments, transfers). Direct creation via API
     * is not permitted as it would bypass stock level tracking.
     */
    public function store(Request $request): JsonResponse
    {
        return $this->error(
            __('Stock movements cannot be created directly. Use stock adjustments or transfers instead.'),
            403
        );
    }

    /**
     * Stock movement metadata (notes only) can be updated.
     * Quantity and financial fields are immutable.
     */
    public function update(Request $request, StockMovement $stockMovement): JsonResponse
    {
        $validated = $request->validate([
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $stockMovement->update(['notes' => $validated['notes'] ?? $stockMovement->notes]);

        return $this->success($stockMovement->fresh(), __('Stock movement notes updated'));
    }

    /**
     * Stock movements are an immutable ledger and cannot be deleted.
     */
    public function destroy(StockMovement $stockMovement): JsonResponse
    {
        return $this->error(
            __('Stock movements are an immutable ledger and cannot be deleted.'),
            403
        );
    }
}
