<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Products",
 *     description="Product management"
 * )
 * REST API v1 controller for Product CRUD operations.
 */
class ProductController extends BaseApiController
{
    /**
     * @OA\Get(
     *     path="/products",
     *     summary="List all products",
     *     tags={"Products"},
     *     @OA\Parameter(name="search", in="query", description="Search term", @OA\Schema(type="string")),
     *     @OA\Parameter(name="category_id", in="query", description="Category ID", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="active_only", in="query", description="Active only", @OA\Schema(type="boolean")),
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
        $products = Product::query()
            ->when($request->get('search'), fn ($q, $s) => $q->where('name', 'LIKE', "%{$s}%"))
            ->when($request->get('category_id'), fn ($q, $c) => $q->where('category_id', $c))
            ->when($request->boolean('active_only'), fn ($q) => $q->where('is_active', true))
            ->orderBy('name')
            ->paginate($request->integer('per_page', 15));

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
        return $this->success($product->load(['category', 'taxGroup']));
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
     *             @OA\Property(property="cost_price", type="integer"),
     *             @OA\Property(property="selling_price", type="integer"),
     *             @OA\Property(property="category_id", type="integer"),
     *             @OA\Property(property="tax_group_id", type="integer"),
     *             @OA\Property(property="product_type", type="string"),
     *             @OA\Property(property="unit", type="string"),
     *             @OA\Property(property="is_active", type="boolean")
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
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['nullable', 'string', 'max:100'],
            'cost_price' => ['required', 'integer', 'min:0'],
            'selling_price' => ['required', 'integer', 'min:0'],
            'category_id' => ['nullable', 'integer', 'exists:product_categories,id'],
            'tax_group_id' => ['nullable', 'integer', 'exists:tax_groups,id'],
            'product_type' => ['nullable', 'string'],
            'unit' => ['nullable', 'string', 'max:50'],
            'is_active' => ['boolean'],
        ]);

        $validated['company_id'] = activeCompany()?->id;
        $product = Product::create($validated);

        return $this->success($product, 'Product created', 201);
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
     *             @OA\Property(property="cost_price", type="integer"),
     *             @OA\Property(property="selling_price", type="integer"),
     *             @OA\Property(property="category_id", type="integer"),
     *             @OA\Property(property="tax_group_id", type="integer"),
     *             @OA\Property(property="is_active", type="boolean")
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
    public function update(Request $request, Product $product): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'sku' => ['nullable', 'string', 'max:100'],
            'cost_price' => ['sometimes', 'integer', 'min:0'],
            'selling_price' => ['sometimes', 'integer', 'min:0'],
            'category_id' => ['nullable', 'integer', 'exists:product_categories,id'],
            'tax_group_id' => ['nullable', 'integer', 'exists:tax_groups,id'],
            'is_active' => ['boolean'],
        ]);

        $product->update($validated);

        return $this->success($product->fresh(), 'Product updated');
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
        $product->delete();

        return $this->success(null, 'Product deleted');
    }
}
