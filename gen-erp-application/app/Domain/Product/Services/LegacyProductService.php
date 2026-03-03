<?php

namespace App\Services;

use App\Models\Company;
use App\Domain\Product\Models\Product;
use App\Domain\Product\Services\ProductService as DomainProductService;
use App\Domain\Product\DTOs\CreateProductData;

/**
 * Legacy ProductService - delegates to domain service for backward compatibility.
 * @deprecated Use App\Domain\Product\Services\ProductService directly
 */
class ProductService
{
    public function __construct(
        private readonly DomainProductService $domainProductService,
    ) {}

    /**
     * Create product with DTO.
     */
    public function create(CreateProductData $data): Product
    {
        return $this->domainProductService->create($data);
    }

    /**
     * Legacy method - Create product with raw arrays (for backward compatibility).
     */
    public function createLegacy(Company $company, array $data, array $customFields = []): Product
    {
        return $this->domainProductService->createLegacy($company, $data, $customFields);
    }

    /**
     * Update product with DTO.
     */
    public function update(Product $product, CreateProductData $data): Product
    {
        return $this->domainProductService->update($product, $data);
    }

    /**
     * Legacy method - Update product with raw arrays (for backward compatibility).
     */
    public function updateLegacy(Product $product, array $data, array $customFields = []): Product
    {
        return $this->domainProductService->updateLegacy($product, $data, $customFields);
    }

    /**
     * Soft delete a product — throws if used in any open order.
     *
     * @throws \RuntimeException
     */
    public function delete(Product $product): void
    {
        $this->domainProductService->delete($product);
    }

    /**
     * Get a product with all relations eager loaded.
     */
    public function findWithRelations(int $id): Product
    {
        return $this->domainProductService->findWithRelations($id);
    }

    /**
     * Paginated product listing with filters.
     *
     * @param  array<string, mixed>  $filters
     */
    public function paginate(Company $company, array $filters = [], int $perPage = 15): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return $this->domainProductService->paginate($company, $filters, $perPage);
    }

    /**
     * Search products by name, SKU, or barcode, scoped to the active company.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function search(string $query, int $limit = 20): \Illuminate\Database\Eloquent\Collection
    {
        return $this->domainProductService->search($query, $limit);
    }

    /**
     * Bulk-import products from a parsed array.
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @return array{created: int, failed: int, errors: array<int, array{row: int, error: string}>}
     */
    public function bulkCreate(Company $company, array $rows): array
    {
        return $this->domainProductService->bulkCreate($company, $rows);
    }
}