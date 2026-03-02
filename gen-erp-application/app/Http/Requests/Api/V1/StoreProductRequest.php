<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates product creation input with tenant-scoped FK rules.
 */
class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && activeCompany() !== null;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['nullable', 'string', 'max:100'],
            'barcode' => ['nullable', 'string', 'max:100'],
            'slug' => ['nullable', 'string', 'max:255'],
            'product_type' => ['required', 'string'],
            'category_id' => ['nullable', Rule::exists('product_categories', 'id')->where('company_id', activeCompany()?->id)],
            'tax_group_id' => ['nullable', Rule::exists('tax_groups', 'id')->where('company_id', activeCompany()?->id)],
            'unit' => ['nullable', 'string', 'max:50'],
            'purchase_price' => ['nullable', 'integer', 'min:0'],
            'selling_price' => ['nullable', 'integer', 'min:0'],
            'track_inventory' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'description' => ['nullable', 'string', 'max:2000'],
            'custom_fields' => ['nullable', 'array'],
        ];
    }
}
