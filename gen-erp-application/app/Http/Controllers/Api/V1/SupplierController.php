<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\StoreSupplierRequest;
use App\Http\Requests\Api\V1\UpdateSupplierRequest;
use App\Models\Supplier;
use App\Services\ContactService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * @OA\Tag(
 *     name="Suppliers",
 *     description="Supplier management"
 * )
 * REST API v1 controller for Supplier CRUD operations.
 */
class SupplierController extends BaseApiController
{
    public function __construct(
        private readonly ContactService $contactService
    ) {}

    /**
     * @OA\Get(
     *     path="/suppliers",
     *     summary="List all suppliers",
     *     tags={"Suppliers"},
     *     @OA\Parameter(name="search", in="query", description="Search term", @OA\Schema(type="string")),
     *     @OA\Parameter(name="status", in="query", description="Status filter", @OA\Schema(type="string")),
     *     @OA\Parameter(name="contact_group_id", in="query", description="Contact Group ID", @OA\Schema(type="integer")),
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
        $suppliers = $this->contactService->paginateSuppliers(
            activeCompany(),
            $request->only(['search', 'status', 'contact_group_id']),
            $request->integer('per_page', 15),
        );

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
        $supplier->load(['contactGroup']);

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
    public function store(StoreSupplierRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $customFields = $validated['custom_fields'] ?? [];
        unset($validated['custom_fields']);

        $supplier = $this->contactService->createSupplier(
            activeCompany(),
            $validated,
            $customFields,
        );

        return $this->success($supplier, __('Supplier created'), 201);
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
    public function update(UpdateSupplierRequest $request, Supplier $supplier): JsonResponse
    {
        $validated = $request->validated();
        $customFields = $validated['custom_fields'] ?? [];
        unset($validated['custom_fields']);

        $supplier = $this->contactService->updateSupplier(
            $supplier,
            $validated,
            $customFields,
        );

        return $this->success($supplier, __('Supplier updated'));
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
        try {
            $this->contactService->deleteSupplier($supplier);
        } catch (RuntimeException $e) {
            return $this->error($e->getMessage(), 422);
        }

        return $this->success(null, __('Supplier deleted'));
    }
}
