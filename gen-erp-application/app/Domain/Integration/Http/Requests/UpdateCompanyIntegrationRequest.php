<?php

namespace App\Domain\Integration\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCompanyIntegrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // TODO: Implement proper authorization when permission system is ready
        // return $this->user()->can('update-integrations');
    }

    public function rules(): array
    {
        return [
            'config' => ['nullable', 'array'],
            'field_maps' => ['nullable', 'array'],
        ];
    }
}
