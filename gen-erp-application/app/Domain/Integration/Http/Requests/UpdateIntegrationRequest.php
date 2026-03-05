<?php

namespace App\Domain\Integration\Http\Requests;

use App\Support\Enums\IntegrationCategory;
use App\Support\Enums\IntegrationTier;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateIntegrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // TODO: Implement proper authorization when permission system is ready
        // return $this->user()->can('update-integrations');
    }

    public function rules(): array
    {
        $integrationId = $this->route('integration');

        return [
            'slug' => ['sometimes', 'string', 'max:100', Rule::unique('integrations', 'slug')->ignore($integrationId)],
            'name' => ['sometimes', 'string', 'max:255'],
            'category' => ['sometimes', Rule::enum(IntegrationCategory::class)],
            'description' => ['nullable', 'string', 'max:1000'],
            'logo_path' => ['nullable', 'string', 'max:500'],
            'tier' => ['sometimes', Rule::enum(IntegrationTier::class)],
            'min_plan' => ['sometimes', 'string', Rule::in(['free', 'pro', 'enterprise'])],
            'config_schema' => ['nullable', 'array'],
            'capabilities' => ['nullable', 'array'],
            'is_active' => ['boolean'],
            'is_official' => ['boolean'],
            'version' => ['nullable', 'string', 'max:50'],
            'author' => ['nullable', 'string', 'max:255'],
            'author_url' => ['nullable', 'url', 'max:500'],
        ];
    }
}
