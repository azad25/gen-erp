<?php

namespace App\Domain\Product\DTOs;

use App\Http\Requests\Api\V1\StoreProductRequest;
use App\Http\Requests\Api\V1\UpdateProductRequest;
use App\Support\Enums\ProductType;

readonly class CreateProductData
{
    public function __construct(
        public int $companyId,
        public string $name,
        public string $sku,
        public ProductType|string $productType,
        public ?int $categoryId,
        public ?string $description,
        public int $sellingPrice,
        public int $purchasePrice,
        public ?int $taxGroupId,
        public string $unit,
        public bool $trackInventory,
        public bool $isActive,
        public ?int $reorderLevel,
        public array $customFields,
    ) {}

    /**
     * Create from array data.
     */
    public static function from(array $data): self
    {
        return new self(
            companyId: $data['company_id'],
            name: $data['name'],
            sku: $data['sku'] ?? '',
            productType: is_string($data['product_type']) ? $data['product_type'] : $data['product_type']->value,
            categoryId: $data['category_id'] ?? null,
            description: $data['description'] ?? null,
            sellingPrice: $data['selling_price'],
            purchasePrice: $data['purchase_price'] ?? $data['cost_price'] ?? 0,
            taxGroupId: $data['tax_group_id'] ?? null,
            unit: $data['unit'] ?? 'pcs',
            trackInventory: $data['track_inventory'] ?? true,
            isActive: $data['is_active'] ?? true,
            reorderLevel: $data['reorder_level'] ?? null,
            customFields: $data['custom_fields'] ?? [],
        );
    }

    /**
     * Create from Form Request.
     */
    public static function fromRequest(StoreProductRequest|UpdateProductRequest $request): self
    {
        $company = activeCompany();

        return new self(
            companyId: $company->id,
            name: $request->string('name'),
            sku: $request->string('sku'),
            productType: $request->string('product_type', 'product'),
            categoryId: $request->integer('category_id'),
            description: $request->string('description'),
            sellingPrice: $request->integer('selling_price'),
            purchasePrice: $request->integer('purchase_price'),
            taxGroupId: $request->integer('tax_group_id'),
            unit: $request->string('unit', 'pcs'),
            trackInventory: $request->boolean('track_inventory', true),
            isActive: $request->boolean('is_active', true),
            reorderLevel: $request->integer('reorder_level'),
            customFields: $request->array('custom_fields'),
        );
    }

    /**
     * Convert to array for model creation.
     */
    public function toArray(): array
    {
        return [
            'company_id' => $this->companyId,
            'name' => $this->name,
            'sku' => $this->sku,
            'product_type' => is_string($this->productType) ? $this->productType : $this->productType->value,
            'category_id' => $this->categoryId,
            'description' => $this->description,
            'selling_price' => $this->sellingPrice,
            'purchase_price' => $this->purchasePrice,
            'tax_group_id' => $this->taxGroupId,
            'unit' => $this->unit,
            'track_inventory' => $this->trackInventory,
            'is_active' => $this->isActive,
            'reorder_level' => $this->reorderLevel,
        ];
    }
}