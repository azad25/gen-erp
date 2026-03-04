<?php

namespace App\Http\Requests\CRM;

use Illuminate\Foundation\Http\FormRequest;

class CreateOpportunityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'pipeline_id' => 'required|integer|exists:pipelines,id',
            'stage_id' => 'required|integer|exists:pipeline_stages,id',
            'expected_close_date' => 'nullable|date|after:today',
            'description' => 'nullable|string',
            'currency' => 'nullable|string|max:3',
            'probability' => 'nullable|integer|min:0|max:100',
            'lead_id' => 'nullable|integer|exists:leads,id',
            'customer_id' => 'nullable|integer|exists:customers,id',
            'assigned_to' => 'nullable|integer|exists:users,id',
            'source' => 'nullable|string|max:255',
            'campaign' => 'nullable|string|max:255',
            'products' => 'nullable|array',
            'products.*.id' => 'required_with:products|integer|exists:products,id',
            'products.*.quantity' => 'required_with:products|integer|min:1',
            'products.*.price' => 'required_with:products|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'tax_amount' => 'nullable|numeric|min:0',
            'custom_fields' => 'nullable|array',
            'notes' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => __('crm.validation.title_required'),
            'amount.required' => __('crm.validation.amount_required'),
            'amount.min' => __('crm.validation.amount_min'),
            'pipeline_id.required' => __('crm.validation.pipeline_required'),
            'stage_id.required' => __('crm.validation.stage_required'),
            'expected_close_date.after' => __('crm.validation.expected_close_date_future'),
            'probability.min' => __('crm.validation.score_min'),
            'probability.max' => __('crm.validation.score_max'),
            'discount_amount.min' => __('crm.validation.estimated_value_min'),
            'tax_amount.min' => __('crm.validation.estimated_value_min'),
        ];
    }
}