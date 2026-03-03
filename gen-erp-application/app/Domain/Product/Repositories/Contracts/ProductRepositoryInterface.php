<?php

namespace App\Domain\Product\Repositories\Contracts;

use App\Domain\Product\Models\Product;
use App\Domain\Auth\Models\Company;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface ProductRepositoryInterface
{
    /**
     * Find product by ID for the given company.
     */
    public function findByIdForCompany(int $id, Company $company): ?Product;

    /**
     * Get paginated products for a company with filters.
     */
    public function paginateForCompany(Company $company, array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Create a new product.
     */
    public function create(array $data): Product;

    /**
     * Update an existing product.
     */
    public function update(Product $product, array $data): Product;

    /**
     * Delete a product.
     */
    public function delete(Product $product): bool;

    /**
     * Find active products for a company.
     */
    public function findActiveForCompany(Company $company): Collection;

    /**
     * Search products by name or SKU.
     */
    public function searchByName(string $query, Company $company, int $limit = 20): Collection;

    /**
     * Find products by category.
     */
    public function findByCategoryForCompany(int $categoryId, Company $company): Collection;

    /**
     * Find low stock products.
     */
    public function findLowStockForCompany(Company $company): Collection;
}