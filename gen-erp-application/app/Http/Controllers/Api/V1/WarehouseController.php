<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Warehouse;
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
    /**
     * @OA\Get(
     *     path="/warehouses",
     *     summary="List all warehouses",
     *     tags={"Warehouses"},
     *     @OA\Parameter(name="search", in="query", description="Search term", @OA\Schema(type="string")),
     *     @OA\Parameter(name="is_active", in="query", description="Active status", @OA\Schema(type="boolean")),
     *     @OA\Parameter(name="per_page", in="query", description="Items per page", @OA\Schema(type="integer", default=15)),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/Warehouse")
     *             ),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $warehouses = Warehouse::query()
            ->where('company_id', activeCompany()->id)
            ->when($request->get('search'), fn ($q, $s) => $q->where('name', 'LIKE', "%{$s}%"))
            ->when($request->get('is_active'), fn ($q, $s) => $q->where('is_active', $s))
            ->orderBy('name')
            ->paginate($request->integer('per_page', 15));

        return $this->paginated($warehouses);
    }

    /**
     * @OA\Get(
     *     path="/warehouses/{id}",
     *     summary="Get a specific warehouse",
     *     tags={"Warehouses"},
     *     @OA\Parameter(name="id", in="path", required=true, description="Warehouse ID", @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", ref="#/components/schemas/Warehouse")
     *         )
     *     )
     * )
     */
    public function show(Warehouse $warehouse): JsonResponse
    {
        return $this->success($warehouse);
    }

    /**
     * @OA\Post(
     *     path="/warehouses",
     *     summary="Create a new warehouse",
     *     tags={"Warehouses"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="code", type="string"),
     *             @OA\Property(property="address", type="string"),
     *             @OA\Property(property="is_active", type="boolean")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Warehouse created",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", ref="#/components/schemas/Warehouse"),
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

        $validated['company_id'] = activeCompany()->id;

        $warehouse = Warehouse::create($validated);

        return $this->success($warehouse, __('Warehouse created'), 201);
    }

    /**
     * @OA\Put(
     *     path="/warehouses/{id}",
     *     summary="Update a warehouse",
     *     tags={"Warehouses"},
     *     @OA\Parameter(name="id", in="path", required=true, description="Warehouse ID", @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="code", type="string"),
     *             @OA\Property(property="address", type="string"),
     *             @OA\Property(property="is_active", type="boolean")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Warehouse updated",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", ref="#/components/schemas/Warehouse"),
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

        $warehouse->update($validated);

        return $this->success($warehouse->fresh(), __('Warehouse updated'));
    }

    /**
     * @OA\Delete(
     *     path="/warehouses/{id}",
     *     summary="Delete a warehouse",
     *     tags={"Warehouses"},
     *     @OA\Parameter(name="id", in="path", required=true, description="Warehouse ID", @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response=200,
     *         description="Warehouse deleted",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function destroy(Warehouse $warehouse): JsonResponse
    {
        $warehouse->delete();

        return $this->success(null, __('Warehouse deleted'));
    }
}
