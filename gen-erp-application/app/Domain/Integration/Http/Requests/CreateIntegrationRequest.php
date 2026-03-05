<?php

namespace App\Domain\Integration\Http\Requests;

use App\Support\Enums\IntegrationCategory;
use App\Support\Enums\IntegrationTier;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateIntegrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // TODO: Implement proper authorization when permission system is ready
        // return $this->user()->can('create-integrations');
    }

    public function rules(): array
    {
        return [
            'slug' => ['required', 'string', 'max:100', 'unique:integrations,slug'],
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', Rule::enum(IntegrationCategory::class)],
            'description' => ['nullable', 'string', 'max:1000'],
            'logo_path' => ['nullable', 'string', 'max:500'],
            'tier' => ['required', Rule::enum(IntegrationTier::class)],
            'min_plan' => ['required', 'string', Rule::in(['free', 'pro', 'enterprise'])],
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
