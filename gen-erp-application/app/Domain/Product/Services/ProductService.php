<?php

namespace App\Domain\Product\Services;

use App\Domain\Product\Contracts\ProductServiceInterface;
use App\Support\Enums\ProductType;
use App\Domain\Auth\Models\Company;
use App\Domain\Product\Models\Product;
use App\Domain\Product\Models\TaxGroup;
use App\Domain\Product\Models\ProductCategory;
use App\Domain\Product\Repositories\Contracts\ProductRepositoryInterface;
use App\Domain\Product\Actions\CreateProductAction;
use App\Domain\Product\DTOs\CreateProductData;
use App\Domain\Shared\Services\CustomFieldService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Manages product creation, updating, deletion, and bulk import.
 */
class ProductService implements ProductServiceInterface
{
    public function __construct(
        private readonly CustomFieldService $customFieldService,
        private readonly ProductRepositoryInterface $productRepository,
        private readonly CreateProductAction $createProductAction,
    ) {}

    /**
     * Create product with DTO.
     */
    public function create(CreateProductData $data): Product
    {
        return $this->createProductAction->execute($data);
    }

    /**
     * Legacy method - Create product with raw arrays (for backward compatibility).
     */
    public function createLegacy(Company $company, array $data, array $customFields = []): Product
    {
        return DB::transaction(function () use ($company, $data, $customFields): Product {
            $data['company_id'] = $company->id;
            $data['slug'] = $this->uniqueSlug($company, $data['name'], $data['slug'] ?? null);

            // SERVICE and DIGITAL types never track inventory
            if (isset($data['product_type'])) {
                $type = $data['product_type'] instanceof ProductType
                    ? $data['product_type']
                    : ProductType::from($data['product_type']);

                if (! $type->tracksInventory()) {
                    $data['track_inventory'] = false;
                }
            }

            $product = $this->productRepository->create($data);

            if ($customFields !== []) {
                $this->customFieldService->saveValues('product', $product->id, $customFields);
            }

            return $product;
        });
    }

    /**
     * Update product with DTO.
     */
    public function update(Product $product, CreateProductData $data): Product
    {
        return DB::transaction(function () use ($product, $data): Product {
            $productData = $data->toArray();
            
            if (!isset($productData['slug'])) {
                $productData['slug'] = $this->uniqueSlug(
                    $product->company,
                    $data->name,
                    null,
                    $product->id
                );
            }

            // SERVICE/DIGITAL enforces track_inventory = false
            $type = ProductType::tryFrom($data->productType);
            if ($type && !$type->tracksInventory()) {
                $productData['track_inventory'] = false;
            }

            $product = $this->productRepository->update($product, $productData);

            if ($data->customFields) {
                $this->customFieldService->saveValues('product', $product->id, $data->customFields);
            }

            return $product;
        });
    }

    /**
     * Legacy method - Update product with raw arrays (for backward compatibility).
     */
    public function updateLegacy(Product $product, array $data, array $customFields = []): Product
    {
        return DB::transaction(function () use ($product, $data, $customFields): Product {
            if (isset($data['name']) && ! isset($data['slug'])) {
                $data['slug'] = $this->uniqueSlug(
                    $product->company,
                    $data['name'],
                    null,
                    $product->id
                );
            }

            // SERVICE/DIGITAL enforces track_inventory = false
            $type = isset($data['product_type'])
                ? (
                    $data['product_type'] instanceof ProductType
                    ? $data['product_type']
                    : ProductType::tryFrom($data['product_type'])
                )
                : $product->product_type;

            if ($type && ! $type->tracksInventory()) {
                $data['track_inventory'] = false;
            }

            $product = $this->productRepository->update($product, $data);

            if ($customFields !== []) {
                $this->customFieldService->saveValues('product', $product->id, $customFields);
            }

            return $product;
        });
    }

    /**
     * Soft delete a product — throws if used in any open order.
     *
     * @throws RuntimeException
     */
    public function delete(Product $product): void
    {
        if ($this->hasOpenOrders($product)) {
            throw new RuntimeException(
                __('Cannot delete a product that is part of an open order.')
            );
        }

        $this->productRepository->delete($product);
    }

    /**
     * Check if a product is referenced in open orders.
     */
    private function hasOpenOrders(Product $product): bool
    {
        // Check for open sales orders containing this product
        $hasOpenSalesOrders = $product->salesOrderItems()
            ->whereHas('salesOrder', fn ($q) => $q->where('status', '!=', 'fulfilled'))
            ->exists();

        // Check for open purchase orders if applicable
        $hasOpenPurchaseOrders = $product->purchaseOrderItems()
            ->whereHas('purchaseOrder', fn ($q) => $q->where('status', '!=', 'received'))
            ->exists();

        return $hasOpenSalesOrders || $hasOpenPurchaseOrders;
    }

    /**
     * Get a product with all relations eager loaded.
     */
    public function findWithRelations(int $id): Product
    {
        return Product::withoutGlobalScopes()
            ->with(['category', 'taxGroup', 'variants'])
            ->findOrFail($id);
    }

    /**
     * Paginated product listing with filters.
     *
     * @param  array<string, mixed>  $filters
     */
    public function paginate(Company $company, array $filters = [], int $perPage = 15): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return $this->productRepository->paginateForCompany($company, $filters, $perPage);
    }

    /**
     * Search products by name, SKU, or barcode, scoped to the active company.
     *
     * @return Collection<int, Product>
     */
    public function search(string $query, int $limit = 20): Collection
    {
        return $this->productRepository->searchByName($query, activeCompany(), $limit);
    }

    /**
     * Bulk-import products from a parsed array.
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @return array{created: int, failed: int, errors: array<int, array{row: int, error: string}>}
     */
    public function bulkCreateLegacy(Company $company, array $rows): array
    {
        $created = 0;
        $failed = 0;
        $errors = [];

        foreach ($rows as $index => $row) {
            try {
                $this->createLegacy($company, $row);
                $created++;
            } catch (\Throwable $e) {
                $failed++;
                $errors[] = ['row' => $index + 1, 'error' => $e->getMessage()];
            }
        }

        return compact('created', 'failed', 'errors');
    }

    /**
     * Bulk create products from DTO array.
     *
     * @param  array<int, CreateProductData>  $dataArray
     * @return array{created: int, failed: int, errors: array<int, array{row: int, error: string}>}
     */
    public function bulkCreate(array $dataArray): array
    {
        $created = 0;
        $failed = 0;
        $errors = [];

        foreach ($dataArray as $index => $data) {
            try {
                $this->create($data);
                $created++;
            } catch (\Throwable $e) {
                $failed++;
                $errors[] = ['row' => $index + 1, 'error' => $e->getMessage()];
            }
        }

        return compact('created', 'failed', 'errors');
    }

    /**
     * Generate a unique slug for a product within a company.
     */
    private function uniqueSlug(Company $company, string $name, ?string $slug, ?int $excludeId = null): string
    {
        $base = $slug ? Str::slug($slug) : Str::slug($name);
        $candidate = $base;
        $counter = 2;

        while (true) {
            $exists = Product::withoutGlobalScopes()
                ->where('company_id', $company->id)
                ->where('slug', $candidate)
                ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
                ->exists();

            if (! $exists) {
                return $candidate;
            }

            $candidate = "{$base}-{$counter}";
            $counter++;
        }
    }

    // ═══════════════════════════════════════════════
    // Tax Group Management
    // ═══════════════════════════════════════════════

    /**
     * Get paginated tax groups for a company.
     */
    public function getTaxGroups(int $companyId, ?string $search = null, int $perPage = 15): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return TaxGroup::query()
            ->where('company_id', $companyId)
            ->when($search, fn ($q, $s) => $q->where('name', 'LIKE', "%{$s}%"))
            ->orderBy('name')
            ->paginate($perPage);
    }

    /**
     * Get a specific tax group.
     */
    public function getTaxGroup(int $companyId, int $id): TaxGroup
    {
        return TaxGroup::where('company_id', $companyId)->findOrFail($id);
    }

    /**
     * Create a tax group.
     */
    public function createTaxGroup(int $companyId, array $data): TaxGroup
    {
        $data['company_id'] = $companyId;
        return TaxGroup::create($data);
    }

    /**
     * Update a tax group.
     */
    public function updateTaxGroup(TaxGroup $taxGroup, array $data): TaxGroup
    {
        $taxGroup->update($data);
        return $taxGroup->fresh();
    }

    /**
     * Delete a tax group.
     */
    public function deleteTaxGroup(TaxGroup $taxGroup): void
    {
        $taxGroup->delete();
    }

    // ═══════════════════════════════════════════════
    // Product Category Management
    // ═══════════════════════════════════════════════

    /**
     * Get paginated product categories for a company.
     */
    public function getProductCategories(int $companyId, ?string $search = null, int $perPage = 15): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return ProductCategory::query()
            ->where('company_id', $companyId)
            ->when($search, fn ($q, $s) => $q->where('name', 'LIKE', "%{$s}%"))
            ->orderBy('name')
            ->paginate($perPage);
    }

    /**
     * Get a specific product category.
     */
    public function getProductCategory(int $companyId, int $id): ProductCategory
    {
        return ProductCategory::where('company_id', $companyId)->findOrFail($id);
    }

    /**
     * Create a product category.
     */
    public function createProductCategory(int $companyId, array $data): ProductCategory
    {
        $data['company_id'] = $companyId;
        
        // Generate slug if not provided
        if (!isset($data['slug']) && isset($data['name'])) {
            $data['slug'] = str($data['name'])->slug();
        }
        
        return ProductCategory::create($data);
    }

    /**
     * Update a product category.
     */
    public function updateProductCategory(ProductCategory $category, array $data): ProductCategory
    {
        $category->update($data);
        return $category->fresh();
    }

    /**
     * Delete a product category.
     */
    public function deleteProductCategory(ProductCategory $category): void
    {
        $category->delete();
    }
}
