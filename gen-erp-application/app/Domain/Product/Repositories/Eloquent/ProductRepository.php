<?php

namespace App\Domain\Product\Repositories\Eloquent;

use App\Domain\Product\Models\Product;
use App\Domain\Auth\Models\Company;
use App\Domain\Product\Repositories\Contracts\ProductRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class ProductRepository implements ProductRepositoryInterface
{
    /**
     * Find product by ID for the given company.
     */
    public function findByIdForCompany(int $id, Company $company): ?Product
    {
        return Product::where('company_id', $company->id)
            ->with(['category', 'taxGroup', 'variants'])
            ->find($id);
    }

    /**
     * Get paginated products for a company with filters.
     */
    public function paginateForCompany(Company $company, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return Product::query()
            ->where('company_id', $company->id)
            ->when($filters['search'] ?? null, fn($q, $s) => $this->applySearchFilter($q, $s))
            ->when($filters['product_type'] ?? null, fn($q, $type) => $q->where('product_type', $type))
            ->when($filters['category_id'] ?? null, fn($q, $id) => $q->where('category_id', $id))
            ->when($filters['is_active'] ?? null, fn($q, $active) => $q->where('is_active', $active))
            ->with(['category', 'taxGroup']) // Always eager load relationships
            ->orderBy('name')
            ->paginate($perPage);
    }

    /**
     * Create a new product.
     */
    public function create(array $data): Product
    {
        return Product::withoutGlobalScopes()->create($data);
    }

    /**
     * Update an existing product.
     */
    public function update(Product $product, array $data): Product
    {
        $product->update($data);
        return $product->fresh(['category', 'taxGroup', 'variants']);
    }

    /**
     * Delete a product.
     */
    public function delete(Product $product): bool
    {
        return $product->delete();
    }

    /**
     * Find active products for a company.
     */
    public function findActiveForCompany(Company $company): Collection
    {
        return Product::where('company_id', $company->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'sku', 'selling_price']);
    }

    /**
     * Search products by name or SKU.
     */
    public function searchByName(string $query, Company $company, int $limit = 20): Collection
    {
        $term = mb_strtolower(trim($query));
        
        return Product::where('company_id', $company->id)
            ->where('is_active', true)
            ->where(function ($q) use ($term) {
                $q->whereRaw('LOWER(name) LIKE ?', ["%{$term}%"])
                  ->orWhere('sku', 'like', "%{$term}%");
            })
            ->limit($limit)
            ->get(['id', 'name', 'sku', 'selling_price', 'cost_price']);
    }

    /**
     * Find products by category.
     */
    public function findByCategoryForCompany(int $categoryId, Company $company): Collection
    {
        return Product::where('company_id', $company->id)
            ->where('category_id', $categoryId)
            ->where('is_active', true)
            ->with(['category'])
            ->orderBy('name')
            ->get();
    }

    /**
     * Find low stock products.
     */
    public function findLowStockForCompany(Company $company): Collection
    {
        return Product::where('company_id', $company->id)
            ->where('is_active', true)
            ->where('track_inventory', true)
            ->whereHas('stockLevels', function ($query) {
                $query->whereRaw('quantity <= reorder_level');
            })
            ->with(['category', 'stockLevels'])
            ->orderBy('name')
            ->get();
    }

    /**
     * Apply search filter to query.
     */
    private function applySearchFilter(Builder $query, string $search): Builder
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'LIKE', "%{$search}%")
              ->orWhere('sku', 'LIKE', "%{$search}%")
              ->orWhere('description', 'LIKE', "%{$search}%");
        });
    }
}