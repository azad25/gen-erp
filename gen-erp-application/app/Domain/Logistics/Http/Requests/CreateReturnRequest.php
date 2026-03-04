<?php

namespace App\Domain\Logistics\Http\Requests;

use App\Domain\Logistics\Enums\ReturnReason;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateReturnRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'shipment_id' => 'required|integer|exists:shipments,id',
            'reason' => ['required', Rule::enum(ReturnReason::class)],
            'reason_details' => 'required|string|max:1000',
            'images' => 'nullable|array|max:5',
            'images.*' => 'image|mimes:jpeg,png,jpg|max:2048',
        ];
    }

    public function messages(): array
    {
        return [
            'shipment_id.required' => __('logistics.validation.shipment_required'),
            'shipment_id.exists' => __('logistics.validation.shipment_not_found'),
            'reason.required' => __('logistics.validation.return_reason_required'),
            'reason_details.required' => __('logistics.validation.return_reason_details_required'),
            'reason_details.max' => __('logistics.validation.return_reason_details_max'),
            'images.max' => __('logistics.validation.return_images_max'),
            'images.*.image' => __('logistics.validation.return_image_format'),
            'images.*.mimes' => __('logistics.validation.return_image_types'),
            'images.*.max' => __('logistics.validation.return_image_size'),
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'requested_by' => auth()->id(),
        ]);
    }
}