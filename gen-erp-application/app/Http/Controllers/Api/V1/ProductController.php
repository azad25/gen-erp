<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\StoreProductRequest;
use App\Http\Requests\Api\V1\UpdateProductRequest;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * @OA\Tag(
 *     name="Products",
 *     description="Product management"
 * )
 * REST API v1 controller for Product CRUD operations.
 */
class ProductController extends BaseApiController
{
    public function __construct(
        private readonly ProductService $productService
    ) {}

    /**
     * @OA\Get(
     *     path="/products",
     *     summary="List all products",
     *     tags={"Products"},
     *     @OA\Parameter(name="search", in="query", description="Search term", @OA\Schema(type="string")),
     *     @OA\Parameter(name="product_type", in="query", description="Product type", @OA\Schema(type="string")),
     *     @OA\Parameter(name="category_id", in="query", description="Category ID", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="per_page", in="query", description="Items per page", @OA\Schema(type="integer", default=15)),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", type="array", @OA\Items(allOf={@OA\Schema(ref="#/components/schemas/Product")})),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $products = $this->productService->paginate(
            activeCompany(),
            $request->only(['search', 'product_type', 'category_id', 'is_active']),
            $request->integer('per_page', 15),
        );

        return $this->paginated($products);
    }

    /**
     * @OA\Get(
     *     path="/products/{id}",
     *     summary="Get a specific product",
     *     tags={"Products"},
     *     @OA\Parameter(name="id", in="path", required=true, description="Product ID", @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", ref="#/components/schemas/Product")
     *         )
     *     )
     * )
     */
    public function show(Product $product): JsonResponse
    {
        $product->load(['category', 'taxGroup', 'variants']);

        return $this->success($product);
    }

    /**
     * @OA\Post(
     *     path="/products",
     *     summary="Create a new product",
     *     tags={"Products"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="sku", type="string"),
     *             @OA\Property(property="product_type", type="string"),
     *             @OA\Property(property="category_id", type="integer"),
     *             @OA\Property(property="selling_price", type="integer"),
     *             @OA\Property(property="purchase_price", type="integer")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Product created",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", ref="#/components/schemas/Product"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function store(StoreProductRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $customFields = $validated['custom_fields'] ?? [];
        unset($validated['custom_fields']);

        $product = $this->productService->create(
            activeCompany(),
            $validated,
            $customFields,
        );

        return $this->success($product->load(['category', 'taxGroup']), __('Product created'), 201);
    }

    /**
     * @OA\Put(
     *     path="/products/{id}",
     *     summary="Update a product",
     *     tags={"Products"},
     *     @OA\Parameter(name="id", in="path", required=true, description="Product ID", @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="sku", type="string"),
     *             @OA\Property(property="selling_price", type="integer"),
     *             @OA\Property(property="purchase_price", type="integer")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Product updated",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", ref="#/components/schemas/Product"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function update(UpdateProductRequest $request, Product $product): JsonResponse
    {
        $validated = $request->validated();
        $customFields = $validated['custom_fields'] ?? [];
        unset($validated['custom_fields']);

        $product = $this->productService->update(
            $product,
            $validated,
            $customFields,
        );

        return $this->success($product->load(['category', 'taxGroup']), __('Product updated'));
    }

    /**
     * @OA\Delete(
     *     path="/products/{id}",
     *     summary="Delete a product",
     *     tags={"Products"},
     *     @OA\Parameter(name="id", in="path", required=true, description="Product ID", @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response=200,
     *         description="Product deleted",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function destroy(Product $product): JsonResponse
    {
        try {
            $this->productService->delete($product);
        } catch (RuntimeException $e) {
            return $this->error($e->getMessage(), 422);
        }

        return $this->success(null, __('Product deleted'));
    }
}
