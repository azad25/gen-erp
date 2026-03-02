<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\ProductCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Product Categories",
 *     description="Product category management"
 * )
 * REST API v1 controller for Product Category CRUD operations.
 */
class ProductCategoryController extends BaseApiController
{
    /**
     * @OA\Get(
     *     path="/product-categories",
     *     summary="List all product categories",
     *     tags={"Product Categories"},
     *     @OA\Parameter(name="search", in="query", description="Search term", @OA\Schema(type="string")),
     *     @OA\Parameter(name="per_page", in="query", description="Items per page", @OA\Schema(type="integer", default=15)),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", type="array", @OA\Items(allOf={@OA\Schema(ref="#/components/schemas/ProductCategory")})),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $categories = ProductCategory::query()
            ->where('company_id', activeCompany()->id)
            ->when($request->get('search'), fn ($q, $s) => $q->where('name', 'LIKE', "%{$s}%"))
            ->orderBy('name')
            ->paginate($request->integer('per_page', 15));

        return $this->paginated($categories);
    }

    /**
     * @OA\Get(
     *     path="/product-categories/{id}",
     *     summary="Get a specific product category",
     *     tags={"Product Categories"},
     *     @OA\Parameter(name="id", in="path", required=true, description="Product Category ID", @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", ref="#/components/schemas/ProductCategory")
     *         )
     *     )
     * )
     */
    public function show(ProductCategory $productCategory): JsonResponse
    {
        return $this->success($productCategory);
    }

    /**
     * @OA\Post(
     *     path="/product-categories",
     *     summary="Create a new product category",
     *     tags={"Product Categories"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="slug", type="string"),
     *             @OA\Property(property="description", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Product category created",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", ref="#/components/schemas/ProductCategory"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $validated['company_id'] = activeCompany()->id;
        $validated['slug'] = $validated['slug'] ?? str($validated['name'])->slug();

        $category = ProductCategory::create($validated);

        return $this->success($category, __('Product category created'), 201);
    }

    /**
     * @OA\Put(
     *     path="/product-categories/{id}",
     *     summary="Update a product category",
     *     tags={"Product Categories"},
     *     @OA\Parameter(name="id", in="path", required=true, description="Product Category ID", @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="slug", type="string"),
     *             @OA\Property(property="description", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Product category updated",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", ref="#/components/schemas/ProductCategory"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function update(Request $request, ProductCategory $productCategory): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $productCategory->update($validated);

        return $this->success($productCategory->fresh(), __('Product category updated'));
    }

    /**
     * @OA\Delete(
     *     path="/product-categories/{id}",
     *     summary="Delete a product category",
     *     tags={"Product Categories"},
     *     @OA\Parameter(name="id", in="path", required=true, description="Product Category ID", @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response=200,
     *         description="Product category deleted",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function destroy(ProductCategory $productCategory): JsonResponse
    {
        $productCategory->delete();

        return $this->success(null, __('Product category deleted'));
    }
}
