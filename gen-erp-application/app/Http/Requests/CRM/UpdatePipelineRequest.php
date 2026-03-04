<?php

namespace App\Http\Requests\CRM;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePipelineRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'color' => 'nullable|string|max:7',
            'is_active' => 'boolean',
            'settings' => 'nullable|array',
            'auto_move_stages' => 'boolean',
            'default_probability' => 'nullable|integer|min:0|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => __('crm.validation.title_required'),
            'color.max' => 'Color must be a valid hex color code',
            'default_probability.min' => __('crm.validation.score_min'),
            'default_probability.max' => __('crm.validation.score_max'),
        ];
    }
}