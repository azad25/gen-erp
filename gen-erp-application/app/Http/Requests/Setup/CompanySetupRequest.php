<?php

namespace App\Http\Requests\Setup;

use App\Enums\BusinessType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates the company setup wizard input across all steps.
 */
class CompanySetupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            // Step 1 — Basics
            'name' => ['required', 'string', 'max:255'],
            'business_type' => ['required', 'string', Rule::enum(BusinessType::class)],
            'phone' => ['nullable', 'string', 'max:20', 'regex:/^01[3-9]\d{8}$/'],
            'email' => ['nullable', 'string', 'email', 'max:255'],

            // Step 2 — Location
            'address_line1' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'district' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'vat_registered' => ['nullable', 'boolean'],
            'vat_bin' => ['nullable', 'required_if:vat_registered,true', 'string', 'max:20'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'phone.regex' => __('Please enter a valid Bangladeshi mobile number (e.g. 01712345678).'),
            'vat_bin.required_if' => __('VAT BIN is required when VAT registered.'),
        ];
    }
}
