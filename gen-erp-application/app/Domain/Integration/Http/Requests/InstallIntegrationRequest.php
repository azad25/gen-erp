<?php

namespace App\Domain\Integration\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InstallIntegrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // TODO: Implement proper authorization when permission system is ready
        // return $this->user()->can('install-integrations');
    }

    public function rules(): array
    {
        return [
            'integration_id' => ['required', 'integer', Rule::exists('integrations', 'id')->where('is_active', true)],
            'config' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'integration_id.exists' => 'The selected integration is not available or inactive.',
        ];
    }
}
