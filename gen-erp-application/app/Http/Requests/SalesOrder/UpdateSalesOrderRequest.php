<?php

namespace App\Http\Requests\SalesOrder;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSalesOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $salesOrder = $this->route('sales_order');
        return $this->user()->can('update', $salesOrder);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $companyId = $this->user()->activeCompany()->id;

        return [
            'customer_id' => [
                'sometimes',
                'required',
                'integer',
                Rule::exists('customers', 'id')->where('company_id', $companyId)
            ],
            'warehouse_id' => [
                'nullable',
                'integer',
                Rule::exists('warehouses', 'id')->where('company_id', $companyId)
            ],
            'order_date' => ['sometimes', 'required', 'date'],
            'delivery_date' => ['nullable', 'date', 'after_or_equal:order_date'],
            'reference' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'terms_conditions' => ['nullable', 'string', 'max:5000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => [
                'nullable',
                'integer',
                Rule::exists('products', 'id')->where('company_id', $companyId)
            ],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.unit_price' => ['required', 'integer', 'min:0'],
            'items.*.description' => ['nullable', 'string', 'max:500'],
            'items.*.unit' => ['nullable', 'string', 'max:50'],
            'items.*.discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'items.*.tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'items.*.tax_group_id' => [
                'nullable',
                'integer',
                Rule::exists('tax_groups', 'id')->where('company_id', $companyId)
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'customer_id.required' => 'Please select a customer for this sales order.',
            'customer_id.exists' => 'The selected customer is not valid.',
            'items.required' => 'At least one line item is required.',
            'items.min' => 'At least one line item is required.',
            'items.*.quantity.min' => 'Quantity must be greater than zero.',
            'items.*.unit_price.min' => 'Unit price must be zero or greater.',
            'items.*.discount_percent.max' => 'Discount cannot exceed 100%.',
            'items.*.tax_rate.max' => 'Tax rate cannot exceed 100%.',
        ];
    }
}