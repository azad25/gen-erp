<?php

namespace App\Domain\Product\Actions;

use App\Domain\Product\Models\Product;
use App\Domain\Product\Events\ProductCreated;
use App\Domain\Product\DTOs\CreateProductData;
use App\Domain\Product\Repositories\Contracts\ProductRepositoryInterface;
use App\Domain\Shared\Services\CustomFieldService;
use App\Support\Enums\ProductType;
use App\Domain\Auth\Models\Company;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Create a new product with validation and business rules.
 */
class CreateProductAction
{
    public function __construct(
        private readonly ProductRepositoryInterface $productRepository,
        private readonly CustomFieldService $customFieldService,
    ) {}

    public function execute(CreateProductData $data): Product
    {
        return DB::transaction(function () use ($data): Product {
            $productData = $data->toArray();
            $productData['slug'] = $this->uniqueSlug(
                Company::find($data->companyId), 
                $data->name, 
                null
            );

            // SERVICE and DIGITAL types never track inventory
            $type = ProductType::tryFrom($data->productType);
            if ($type && !$type->tracksInventory()) {
                $productData['track_inventory'] = false;
            }

            $product = $this->productRepository->create($productData);

            if ($data->customFields) {
                $this->customFieldService->saveValues('product', $product->id, $data->customFields);
            }

            // Fire domain event
            ProductCreated::dispatch($product);

            return $product;
        });
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