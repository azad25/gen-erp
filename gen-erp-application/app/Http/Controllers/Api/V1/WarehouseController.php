<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Inventory\Contracts\InventoryServiceInterface;
use App\Domain\Inventory\DTOs\CreateWarehouseData;
use App\Domain\Inventory\DTOs\UpdateWarehouseData;
use App\Domain\Inventory\Models\Warehouse;
use App\Http\Resources\WarehouseResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Warehouses",
 *     description="Warehouse management"
 * )
 * REST API v1 controller for Warehouse CRUD operations.
 */
class WarehouseController extends BaseApiController
{
    public function __construct(
        private InventoryServiceInterface $inventoryService
    ) {}
    /**
     * @OA\Get(
     *     path="/api/v1/warehouses",
     *     summary="List all warehouses",
     *     tags={"Warehouses"},
     *
     *     @OA\Parameter(name="search", in="query", description="Search term", @OA\Schema(type="string")),
     *     @OA\Parameter(name="is_active", in="query", description="Active status", @OA\Schema(type="boolean")),
     *     @OA\Parameter(name="per_page", in="query", description="Items per page", @OA\Schema(type="integer", default=15)),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *
     *                 @OA\Items(type="object")
     *             ),
     *
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $warehouses = $this->inventoryService
            ->getWarehouses(
                activeCompany(),
                $request->get('search'),
                $request->get('is_active')
            )
            ->paginate($request->integer('per_page', 15));

        return $this->paginated(WarehouseResource::collection($warehouses));
    }

    /**
     * @OA\Get(
     *     path="/api/v1/warehouses/{id}",
     *     summary="Get a specific warehouse",
     *     tags={"Warehouses"},
     *
     *     @OA\Parameter(name="id", in="path", required=true, description="Warehouse ID", @OA\Schema(type="integer")),
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
    public function show(Warehouse $warehouse): JsonResponse
    {
        return $this->success(new WarehouseResource($warehouse));
    }

    /**
     * @OA\Post(
     *     path="/api/v1/warehouses",
     *     summary="Create a new warehouse",
     *     tags={"Warehouses"},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="code", type="string"),
     *             @OA\Property(property="address", type="string"),
     *             @OA\Property(property="is_active", type="boolean")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Warehouse created",
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
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:500'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $warehouseData = new CreateWarehouseData(
            company_id: activeCompany()->id,
            name: $validated['name'],
            code: $validated['code'],
            address: $validated['address'] ?? null,
            is_active: $validated['is_active'] ?? true,
        );

        $warehouse = $this->inventoryService->createWarehouse($warehouseData);

        return $this->success(new WarehouseResource($warehouse), __('Warehouse created'), 201);
    }

    /**
     * @OA\Put(
     *     path="/api/v1/warehouses/{id}",
     *     summary="Update a warehouse",
     *     tags={"Warehouses"},
     *
     *     @OA\Parameter(name="id", in="path", required=true, description="Warehouse ID", @OA\Schema(type="integer")),
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="code", type="string"),
     *             @OA\Property(property="address", type="string"),
     *             @OA\Property(property="is_active", type="boolean")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Warehouse updated",
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
    public function update(Request $request, Warehouse $warehouse): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'code' => ['sometimes', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:500'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $updateData = new UpdateWarehouseData(
            name: $validated['name'] ?? null,
            code: $validated['code'] ?? null,
            address: $validated['address'] ?? null,
            is_active: $validated['is_active'] ?? null,
        );

        $warehouse = $this->inventoryService->updateWarehouse($warehouse, $updateData);

        return $this->success(new WarehouseResource($warehouse), __('Warehouse updated'));
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/warehouses/{id}",
     *     summary="Delete a warehouse",
     *     tags={"Warehouses"},
     *
     *     @OA\Parameter(name="id", in="path", required=true, description="Warehouse ID", @OA\Schema(type="integer")),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Warehouse deleted",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function destroy(Warehouse $warehouse): JsonResponse
    {
        $this->inventoryService->deleteWarehouse($warehouse);

        return $this->success(null, __('Warehouse deleted'));
    }
}
