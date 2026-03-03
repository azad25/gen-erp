<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Product\Contracts\ProductServiceInterface;
use App\Http\Resources\TaxGroupResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Tax Groups",
 *     description="Tax group management"
 * )
 * REST API v1 controller for Tax Group CRUD operations.
 */
class TaxGroupController extends BaseApiController
{
    public function __construct(
        private readonly ProductServiceInterface $productService
    ) {}
    /**
     * @OA\Get(
     *     path="/api/v1/tax-groups",
     *     summary="List all tax groups",
     *     tags={"Tax Groups"},
     *
     *     @OA\Parameter(name="search", in="query", description="Search term", @OA\Schema(type="string")),
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
        $taxGroups = $this->productService->getTaxGroups(
            activeCompany()->id,
            $request->get('search'),
            $request->integer('per_page', 15)
        );

        return $this->paginated($taxGroups, TaxGroupResource::class);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/tax-groups/{id}",
     *     summary="Get a specific tax group",
     *     tags={"Tax Groups"},
     *
     *     @OA\Parameter(name="id", in="path", required=true, description="Tax Group ID", @OA\Schema(type="integer")),
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
    public function show(int $id): JsonResponse
    {
        $taxGroup = $this->productService->getTaxGroup(activeCompany()->id, $id);

        return $this->success(new TaxGroupResource($taxGroup));
    }

    /**
     * @OA\Post(
     *     path="/api/v1/tax-groups",
     *     summary="Create a new tax group",
     *     tags={"Tax Groups"},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="tax_rate", type="number"),
     *             @OA\Property(property="description", type="string")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Tax group created",
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
            'rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
            'is_default' => ['boolean'],
        ]);

        $taxGroup = $this->productService->createTaxGroup(activeCompany()->id, $validated);

        return $this->success(new TaxGroupResource($taxGroup), __('Tax group created'), 201);
    }

    /**
     * @OA\Put(
     *     path="/api/v1/tax-groups/{id}",
     *     summary="Update a tax group",
     *     tags={"Tax Groups"},
     *
     *     @OA\Parameter(name="id", in="path", required=true, description="Tax Group ID", @OA\Schema(type="integer")),
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="tax_rate", type="number"),
     *             @OA\Property(property="description", type="string")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Tax group updated",
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
    public function update(Request $request, int $id): JsonResponse
    {
        $taxGroup = $this->productService->getTaxGroup(activeCompany()->id, $id);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'rate' => ['sometimes', 'numeric', 'min:0', 'max:100'],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
            'is_default' => ['boolean'],
        ]);

        $updatedTaxGroup = $this->productService->updateTaxGroup($taxGroup, $validated);

        return $this->success(new TaxGroupResource($updatedTaxGroup), __('Tax group updated'));
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/tax-groups/{id}",
     *     summary="Delete a tax group",
     *     tags={"Tax Groups"},
     *
     *     @OA\Parameter(name="id", in="path", required=true, description="Tax Group ID", @OA\Schema(type="integer")),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Tax group deleted",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function destroy(int $id): JsonResponse
    {
        $taxGroup = $this->productService->getTaxGroup(activeCompany()->id, $id);
        $this->productService->deleteTaxGroup($taxGroup);

        return $this->success(null, __('Tax group deleted'));
    }
}
