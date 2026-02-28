<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * REST API v1 controller for Product CRUD operations.
 */
class ProductController extends BaseApiController
{
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

    public function show(Product $product): JsonResponse
    {
        return $this->success($product->load(['category', 'taxGroup']));
    }

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

    public function destroy(Product $product): JsonResponse
    {
        $product->delete();

        return $this->success(null, 'Product deleted');
    }
}
