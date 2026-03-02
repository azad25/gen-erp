<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates customer creation input with tenant-scoped FK rules.
 */
class StoreCustomerRequest extends FormRequest
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
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:500'],
            'district' => ['nullable', 'string', 'max:100'],
            'credit_limit' => ['nullable', 'integer', 'min:0'],
            'credit_days' => ['nullable', 'integer', 'min:0'],
            'opening_balance' => ['nullable', 'integer'],
            'status' => ['nullable', 'string', 'in:active,inactive'],
            'contact_group_id' => ['nullable', Rule::exists('contact_groups', 'id')->where('company_id', activeCompany()?->id)],
            'custom_fields' => ['nullable', 'array'],
        ];
    }
}
