<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Supplier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Suppliers",
 *     description="Supplier management"
 * )
 * REST API v1 controller for Supplier CRUD operations.
 */
class SupplierController extends BaseApiController
{
    /**
     * @OA\Get(
     *     path="/suppliers",
     *     summary="List all suppliers",
     *     tags={"Suppliers"},
     *     @OA\Parameter(name="search", in="query", description="Search term", @OA\Schema(type="string")),
     *     @OA\Parameter(name="per_page", in="query", description="Items per page", @OA\Schema(type="integer", default=15)),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", type="array", @OA\Items(allOf={@OA\Schema(ref="#/components/schemas/Supplier")})),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $suppliers = Supplier::query()
            ->when($request->get('search'), fn ($q, $s) => $q->where('name', 'LIKE', "%{$s}%"))
            ->orderBy('name')
            ->paginate($request->integer('per_page', 15));

        return $this->paginated($suppliers);
    }

    /**
     * @OA\Get(
     *     path="/suppliers/{id}",
     *     summary="Get a specific supplier",
     *     tags={"Suppliers"},
     *     @OA\Parameter(name="id", in="path", required=true, description="Supplier ID", @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", ref="#/components/schemas/Supplier")
     *         )
     *     )
     * )
     */
    public function show(Supplier $supplier): JsonResponse
    {
        return $this->success($supplier);
    }

    /**
     * @OA\Post(
     *     path="/suppliers",
     *     summary="Create a new supplier",
     *     tags={"Suppliers"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="email", type="string"),
     *             @OA\Property(property="phone", type="string"),
     *             @OA\Property(property="address", type="string"),
     *             @OA\Property(property="vat_bin", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Supplier created",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", ref="#/components/schemas/Supplier"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:500'],
            'vat_bin' => ['nullable', 'string', 'max:50'],
        ]);

        $validated['company_id'] = activeCompany()?->id;
        $supplier = Supplier::create($validated);

        return $this->success($supplier, 'Supplier created', 201);
    }

    /**
     * @OA\Put(
     *     path="/suppliers/{id}",
     *     summary="Update a supplier",
     *     tags={"Suppliers"},
     *     @OA\Parameter(name="id", in="path", required=true, description="Supplier ID", @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="email", type="string"),
     *             @OA\Property(property="phone", type="string"),
     *             @OA\Property(property="address", type="string"),
     *             @OA\Property(property="vat_bin", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Supplier updated",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", ref="#/components/schemas/Supplier"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function update(Request $request, Supplier $supplier): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:500'],
            'vat_bin' => ['nullable', 'string', 'max:50'],
        ]);

        $supplier->update($validated);

        return $this->success($supplier->fresh(), 'Supplier updated');
    }

    /**
     * @OA\Delete(
     *     path="/suppliers/{id}",
     *     summary="Delete a supplier",
     *     tags={"Suppliers"},
     *     @OA\Parameter(name="id", in="path", required=true, description="Supplier ID", @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response=200,
     *         description="Supplier deleted",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function destroy(Supplier $supplier): JsonResponse
    {
        $supplier->delete();

        return $this->success(null, 'Supplier deleted');
    }
}
