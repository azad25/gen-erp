<?php

namespace App\Domain\Logistics\Http\Requests;

use App\Domain\Logistics\Enums\DeliveryType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateShipmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'carrier_id' => 'required|integer|exists:carriers,id',
            'customer_id' => 'required|integer|exists:customers,id',
            'invoice_id' => 'nullable|integer|exists:invoices,id',
            
            // Recipient information
            'recipient_name' => 'required|string|max:255',
            'recipient_phone' => 'required|string|max:20',
            'recipient_email' => 'nullable|email|max:255',
            'recipient_address' => 'required|string|max:500',
            'recipient_city' => 'required|string|max:100',
            'recipient_state' => 'nullable|string|max:100',
            'recipient_postal_code' => 'nullable|string|max:20',
            'recipient_country' => 'required|string|max:100',
            
            // Sender information
            'sender_name' => 'required|string|max:255',
            'sender_phone' => 'required|string|max:20',
            'sender_email' => 'nullable|email|max:255',
            'sender_address' => 'required|string|max:500',
            'sender_city' => 'required|string|max:100',
            'sender_state' => 'nullable|string|max:100',
            'sender_postal_code' => 'nullable|string|max:20',
            'sender_country' => 'required|string|max:100',
            
            // Shipment details
            'delivery_type' => ['required', Rule::enum(DeliveryType::class)],
            'payment_method' => 'required|string|in:prepaid,cod',
            'cod_amount' => 'required_if:payment_method,cod|nullable|numeric|min:0',
            'declared_value' => 'nullable|numeric|min:0',
            'weight' => 'required|numeric|min:0.1',
            'dimensions' => 'nullable|array',
            'dimensions.length' => 'nullable|numeric|min:0',
            'dimensions.width' => 'nullable|numeric|min:0',
            'dimensions.height' => 'nullable|numeric|min:0',
            'special_instructions' => 'nullable|string|max:500',
            'expected_delivery_date' => 'nullable|date|after:today',
            
            // Items
            'items' => 'required|array|min:1',
            'items.*.name' => 'required|string|max:255',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.weight' => 'required|numeric|min:0.1',
            'items.*.value' => 'nullable|numeric|min:0',
            'items.*.description' => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'carrier_id.required' => __('logistics.validation.carrier_required'),
            'customer_id.required' => __('logistics.validation.customer_required'),
            'recipient_name.required' => __('logistics.validation.recipient_name_required'),
            'recipient_phone.required' => __('logistics.validation.recipient_phone_required'),
            'recipient_address.required' => __('logistics.validation.recipient_address_required'),
            'recipient_city.required' => __('logistics.validation.recipient_city_required'),
            'sender_name.required' => __('logistics.validation.sender_name_required'),
            'sender_phone.required' => __('logistics.validation.sender_phone_required'),
            'sender_address.required' => __('logistics.validation.sender_address_required'),
            'sender_city.required' => __('logistics.validation.sender_city_required'),
            'delivery_type.required' => __('logistics.validation.delivery_type_required'),
            'payment_method.required' => __('logistics.validation.payment_method_required'),
            'cod_amount.required_if' => __('logistics.validation.cod_amount_required'),
            'weight.required' => __('logistics.validation.weight_required'),
            'items.required' => __('logistics.validation.items_required'),
            'items.*.name.required' => __('logistics.validation.item_name_required'),
            'items.*.quantity.required' => __('logistics.validation.item_quantity_required'),
            'items.*.weight.required' => __('logistics.validation.item_weight_required'),
        ];
    }
}