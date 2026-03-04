<?php

namespace App\Http\Requests\CRM;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLeadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'first_name' => 'sometimes|required|string|max:255',
            'last_name' => 'sometimes|required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'company_name' => 'nullable|string|max:255',
            'job_title' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:2',
            'postal_code' => 'nullable|string|max:20',
            'status' => 'nullable|string|in:new,contacted,qualified,unqualified,converted',
            'source' => 'nullable|string|in:website,referral,social_media,advertisement,email_campaign,cold_call,trade_show,partner,organic_search,paid_search,direct,other',
            'score' => 'nullable|integer|min:0|max:100',
            'estimated_value' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|max:3',
            'expected_close_date' => 'nullable|date',
            'assigned_to' => 'nullable|integer|exists:users,id',
            'custom_fields' => 'nullable|array',
            'notes' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'first_name.required' => __('crm.validation.first_name_required'),
            'last_name.required' => __('crm.validation.last_name_required'),
            'email.email' => __('crm.validation.email_invalid'),
            'score.min' => __('crm.validation.score_min'),
            'score.max' => __('crm.validation.score_max'),
            'estimated_value.min' => __('crm.validation.estimated_value_min'),
        ];
    }
}