<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates supplier update input with tenant-scoped FK rules.
 */
class UpdateSupplierRequest extends FormRequest
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
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:500'],
            'district' => ['nullable', 'string', 'max:100'],
            'tin_number' => ['nullable', 'string', 'max:50'],
            'bin_number' => ['nullable', 'string', 'max:50'],
            'tds_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'vds_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'opening_balance' => ['nullable', 'integer'],
            'status' => ['nullable', 'string', 'in:active,inactive'],
            'contact_group_id' => ['nullable', Rule::exists('contact_groups', 'id')->where('company_id', activeCompany()?->id)],
            'custom_fields' => ['nullable', 'array'],
        ];
    }
}
