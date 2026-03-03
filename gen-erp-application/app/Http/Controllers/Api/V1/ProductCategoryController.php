<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Product\Contracts\ProductServiceInterface;
use App\Http\Resources\ProductCategoryResource;
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
    public function __construct(
        private readonly ProductServiceInterface $productService
    ) {}
    /**
     * @OA\Get(
     *     path="/api/v1/product-categories",
     *     summary="List all product categories",
     *     tags={"Product Categories"},
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
        $categories = $this->productService->getProductCategories(
            activeCompany()->id,
            $request->get('search'),
            $request->integer('per_page', 15)
        );

        return $this->paginated($categories, ProductCategoryResource::class);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/product-categories/{id}",
     *     summary="Get a specific product category",
     *     tags={"Product Categories"},
     *
     *     @OA\Parameter(name="id", in="path", required=true, description="Product Category ID", @OA\Schema(type="integer")),
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
        $productCategory = $this->productService->getProductCategory(activeCompany()->id, $id);

        return $this->success(new ProductCategoryResource($productCategory));
    }

    /**
     * @OA\Post(
     *     path="/api/v1/product-categories",
     *     summary="Create a new product category",
     *     tags={"Product Categories"},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="slug", type="string"),
     *             @OA\Property(property="description", type="string")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Product category created",
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
            'slug' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $category = $this->productService->createProductCategory(activeCompany()->id, $validated);

        return $this->success(new ProductCategoryResource($category), __('Product category created'), 201);
    }

    /**
     * @OA\Put(
     *     path="/api/v1/product-categories/{id}",
     *     summary="Update a product category",
     *     tags={"Product Categories"},
     *
     *     @OA\Parameter(name="id", in="path", required=true, description="Product Category ID", @OA\Schema(type="integer")),
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="slug", type="string"),
     *             @OA\Property(property="description", type="string")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Product category updated",
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
        $productCategory = $this->productService->getProductCategory(activeCompany()->id, $id);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $updatedCategory = $this->productService->updateProductCategory($productCategory, $validated);

        return $this->success(new ProductCategoryResource($updatedCategory), __('Product category updated'));
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/product-categories/{id}",
     *     summary="Delete a product category",
     *     tags={"Product Categories"},
     *
     *     @OA\Parameter(name="id", in="path", required=true, description="Product Category ID", @OA\Schema(type="integer")),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Product category deleted",
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
        $productCategory = $this->productService->getProductCategory(activeCompany()->id, $id);
        $this->productService->deleteProductCategory($productCategory);

        return $this->success(null, __('Product category deleted'));
    }
}
