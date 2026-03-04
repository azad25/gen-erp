<?php

namespace App\Domain\CMS\DTOs;

use App\Domain\CMS\Enums\PaymentMethod;

/**
 * Data Transfer Object for creating orders.
 */
readonly class CreateOrderData
{
    public function __construct(
        public int $siteId,
        public ?int $customerId,
        public string $customerEmail,
        public string $customerFirstName,
        public string $customerLastName,
        public ?string $customerPhone,
        public string $billingAddressLine1,
        public ?string $billingAddressLine2,
        public string $billingCity,
        public string $billingState,
        public string $billingPostalCode,
        public string $billingCountry,
        public string $shippingAddressLine1,
        public ?string $shippingAddressLine2,
        public string $shippingCity,
        public string $shippingState,
        public string $shippingPostalCode,
        public string $shippingCountry,
        public float $subtotal,
        public float $shippingCost,
        public float $taxAmount,
        public float $discountAmount,
        public float $totalAmount,
        public PaymentMethod $paymentMethod,
        public ?string $customerNotes,
    ) {}

    /**
     * Convert to array.
     */
    public function toArray(): array
    {
        return [
            'site_id' => $this->siteId,
            'customer_id' => $this->customerId,
            'customer_email' => $this->customerEmail,
            'customer_first_name' => $this->customerFirstName,
            'customer_last_name' => $this->customerLastName,
            'customer_phone' => $this->customerPhone,
            'billing_address_line_1' => $this->billingAddressLine1,
            'billing_address_line_2' => $this->billingAddressLine2,
            'billing_city' => $this->billingCity,
            'billing_state' => $this->billingState,
            'billing_postal_code' => $this->billingPostalCode,
            'billing_country' => $this->billingCountry,
            'shipping_address_line_1' => $this->shippingAddressLine1,
            'shipping_address_line_2' => $this->shippingAddressLine2,
            'shipping_city' => $this->shippingCity,
            'shipping_state' => $this->shippingState,
            'shipping_postal_code' => $this->shippingPostalCode,
            'shipping_country' => $this->shippingCountry,
            'subtotal' => $this->subtotal,
            'shipping_cost' => $this->shippingCost,
            'tax_amount' => $this->taxAmount,
            'discount_amount' => $this->discountAmount,
            'total_amount' => $this->totalAmount,
            'payment_method' => $this->paymentMethod->value,
            'customer_notes' => $this->customerNotes,
        ];
    }
}