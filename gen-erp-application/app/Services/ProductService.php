<?php

namespace App\Services;

use App\Enums\ProductType;
use App\Models\Company;
use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Manages product creation, updating, deletion, and bulk import.
 */
class ProductService
{
    public function __construct(
        private readonly CustomFieldService $customFieldService,
    ) {}

    /**
     * Create a product with custom fields in one transaction.
     *
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $customFields
     */
    public function create(Company $company, array $data, array $customFields = []): Product
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

            $product = Product::withoutGlobalScopes()->create($data);

            if ($customFields !== []) {
                $this->customFieldService->saveValues('product', $product->id, $customFields);
            }

            return $product;
        });
    }

    /**
     * Update a product and its custom fields atomically.
     *
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $customFields
     */
    public function update(Product $product, array $data, array $customFields = []): Product
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

            $product->update($data);

            if ($customFields !== []) {
                $this->customFieldService->saveValues('product', $product->id, $customFields);
            }

            return $product->fresh();
        });
    }

    /**
     * Soft delete a product — throws if used in any open order.
     *
     * @throws RuntimeException
     */
    public function delete(Product $product): void
    {
        // TODO: Phase 3B — check $product->salesOrderItems()->open()->exists()
        // For now, guard against future integration by checking a stub
        if ($this->hasOpenOrders($product)) {
            throw new RuntimeException(
                __('Cannot delete a product that is part of an open order.')
            );
        }

        $product->delete();
    }

    /**
     * Check if a product is referenced in open orders.
     * Returns false until order modules exist (Phase 3B+).
     */
    private function hasOpenOrders(Product $product): bool
    {
        // TODO: Phase 3B — implement real check
        return false;
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
     * Search products by name, SKU, or barcode, scoped to the active company.
     *
     * @return Collection<int, Product>
     */
    public function search(string $query, int $limit = 20): Collection
    {
        $term = mb_strtolower(trim($query));

        return Product::active()
            ->where(function ($q) use ($term): void {
                $q->whereRaw('LOWER(name) LIKE ?', ["%{$term}%"])
                    ->orWhereRaw('LOWER(sku) LIKE ?', ["%{$term}%"])
                    ->orWhere('barcode', 'like', "%{$term}%");
            })
            ->limit($limit)
            ->get();
    }

    /**
     * Bulk-import products from a parsed array.
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @return array{created: int, failed: int, errors: array<int, array{row: int, error: string}>}
     */
    public function bulkCreate(Company $company, array $rows): array
    {
        $created = 0;
        $failed = 0;
        $errors = [];

        foreach ($rows as $index => $row) {
            try {
                $this->create($company, $row);
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
}
