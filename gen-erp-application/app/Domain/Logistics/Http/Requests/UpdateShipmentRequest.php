<?php

namespace App\Domain\Logistics\Http\Requests;

use App\Domain\Logistics\Enums\DeliveryType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateShipmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Recipient information (can be updated)
            'recipient_name' => 'sometimes|string|max:255',
            'recipient_phone' => 'sometimes|string|max:20',
            'recipient_email' => 'sometimes|nullable|email|max:255',
            'recipient_address' => 'sometimes|string|max:500',
            'recipient_city' => 'sometimes|string|max:100',
            'recipient_state' => 'sometimes|nullable|string|max:100',
            'recipient_postal_code' => 'sometimes|nullable|string|max:20',
            
            // Shipment details (limited updates)
            'delivery_type' => ['sometimes', Rule::enum(DeliveryType::class)],
            'cod_amount' => 'sometimes|nullable|numeric|min:0',
            'declared_value' => 'sometimes|nullable|numeric|min:0',
            'special_instructions' => 'sometimes|nullable|string|max:500',
            'expected_delivery_date' => 'sometimes|nullable|date|after:today',
        ];
    }

    public function messages(): array
    {
        return [
            'recipient_name.string' => __('logistics.validation.recipient_name_string'),
            'recipient_phone.string' => __('logistics.validation.recipient_phone_string'),
            'recipient_email.email' => __('logistics.validation.recipient_email_format'),
            'recipient_address.string' => __('logistics.validation.recipient_address_string'),
            'cod_amount.numeric' => __('logistics.validation.cod_amount_numeric'),
            'declared_value.numeric' => __('logistics.validation.declared_value_numeric'),
            'expected_delivery_date.date' => __('logistics.validation.expected_delivery_date_format'),
            'expected_delivery_date.after' => __('logistics.validation.expected_delivery_date_future'),
        ];
    }
}