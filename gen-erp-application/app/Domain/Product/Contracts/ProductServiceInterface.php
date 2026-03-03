<?php

namespace App\Domain\Product\Contracts;

use App\Domain\Auth\Models\Company;
use App\Domain\Product\DTOs\CreateProductData;
use App\Domain\Product\Models\Product;
use App\Domain\Product\Models\TaxGroup;
use App\Domain\Product\Models\ProductCategory;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * Interface for product service operations.
 */
interface ProductServiceInterface
{
    /**
     * Create product with DTO.
     */
    public function create(CreateProductData $data): Product;

    /**
     * Update product with DTO.
     */
    public function update(Product $product, CreateProductData $data): Product;

    /**
     * Delete a product.
     */
    public function delete(Product $product): void;

    /**
     * Get a product with all relations eager loaded.
     */
    public function findWithRelations(int $id): Product;

    /**
     * Paginated product listing with filters.
     */
    public function paginate(Company $company, array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Search products by name, SKU, or barcode.
     */
    public function search(string $query, int $limit = 20): Collection;

    /**
     * Get paginated tax groups for a company.
     */
    public function getTaxGroups(int $companyId, ?string $search = null, int $perPage = 15): LengthAwarePaginator;

    /**
     * Get a specific tax group.
     */
    public function getTaxGroup(int $companyId, int $id): TaxGroup;

    /**
     * Create a tax group.
     */
    public function createTaxGroup(int $companyId, array $data): TaxGroup;

    /**
     * Update a tax group.
     */
    public function updateTaxGroup(TaxGroup $taxGroup, array $data): TaxGroup;

    /**
     * Delete a tax group.
     */
    public function deleteTaxGroup(TaxGroup $taxGroup): void;

    /**
     * Get paginated product categories for a company.
     */
    public function getProductCategories(int $companyId, ?string $search = null, int $perPage = 15): LengthAwarePaginator;

    /**
     * Get a specific product category.
     */
    public function getProductCategory(int $companyId, int $id): ProductCategory;

    /**
     * Create a product category.
     */
    public function createProductCategory(int $companyId, array $data): ProductCategory;

    /**
     * Update a product category.
     */
    public function updateProductCategory(ProductCategory $category, array $data): ProductCategory;

    /**
     * Delete a product category.
     */
    public function deleteProductCategory(ProductCategory $category): void;
}