<?php

namespace App\Domain\POS\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateSaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'session_id' => 'required|exists:pos_sessions,id',
            'customer_id' => 'nullable|exists:customers,id',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'nullable|exists:products,id',
            'items.*.variant_id' => 'nullable|exists:product_variants,id',
            'items.*.description' => 'required|string|max:255',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|integer|min:0',
            'items.*.discount_amount' => 'nullable|integer|min:0',
            'items.*.tax_amount' => 'nullable|integer|min:0',
            'amount_tendered' => 'required|integer|min:0',
            'payment_method_id' => 'nullable|exists:payment_methods,id',
        ];
    }
}
