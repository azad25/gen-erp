<?php

namespace App\Domain\Logistics\DTOs;

use App\Domain\Logistics\Enums\DeliveryType;
use App\Domain\Logistics\Enums\ShipmentStatus;

class ShipmentData
{
    public function __construct(
        public readonly int $companyId,
        public readonly int $carrierId,
        public readonly ?int $invoiceId,
        public readonly int $customerId,
        
        // Sender Info
        public readonly string $senderName,
        public readonly string $senderPhone,
        public readonly string $senderAddress,
        public readonly string $senderCity,
        public readonly ?string $senderArea = null,
        public readonly ?string $senderPostcode = null,
        
        // Recipient Info
        public readonly string $recipientName,
        public readonly string $recipientPhone,
        public readonly ?string $recipientEmail = null,
        public readonly string $recipientAddress,
        public readonly string $recipientCity,
        public readonly ?string $recipientArea = null,
        public readonly ?string $recipientPostcode = null,
        
        // Shipment Details
        public readonly ShipmentStatus $status = ShipmentStatus::PENDING,
        public readonly DeliveryType $deliveryType = DeliveryType::STANDARD,
        public readonly string $paymentMethod = 'prepaid',
        
        // Pricing
        public readonly float $codAmount = 0.0,
        public readonly float $shippingCost = 0.0,
        public readonly float $codCharge = 0.0,
        public readonly float $totalCost = 0.0,
        
        // Weight & Dimensions
        public readonly ?float $weight = null,
        public readonly ?float $length = null,
        public readonly ?float $width = null,
        public readonly ?float $height = null,
        
        // Additional Info
        public readonly ?string $specialInstructions = null,
        public readonly ?string $packageDescription = null,
        public readonly ?int $createdBy = null,
        
        // Items
        public readonly array $items = [],
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            companyId: $data['company_id'],
            carrierId: $data['carrier_id'],
            invoiceId: $data['invoice_id'] ?? null,
            customerId: $data['customer_id'],
            senderName: $data['sender_name'],
            senderPhone: $data['sender_phone'],
            senderAddress: $data['sender_address'],
            senderCity: $data['sender_city'],
            senderArea: $data['sender_area'] ?? null,
            senderPostcode: $data['sender_postcode'] ?? null,
            recipientName: $data['recipient_name'],
            recipientPhone: $data['recipient_phone'],
            recipientEmail: $data['recipient_email'] ?? null,
            recipientAddress: $data['recipient_address'],
            recipientCity: $data['recipient_city'],
            recipientArea: $data['recipient_area'] ?? null,
            recipientPostcode: $data['recipient_postcode'] ?? null,
            status: isset($data['status']) ? ShipmentStatus::from($data['status']) : ShipmentStatus::PENDING,
            deliveryType: isset($data['delivery_type']) ? DeliveryType::from($data['delivery_type']) : DeliveryType::STANDARD,
            paymentMethod: $data['payment_method'] ?? 'prepaid',
            codAmount: (float) ($data['cod_amount'] ?? 0.0),
            shippingCost: (float) ($data['shipping_cost'] ?? 0.0),
            codCharge: (float) ($data['cod_charge'] ?? 0.0),
            totalCost: (float) ($data['total_cost'] ?? 0.0),
            weight: isset($data['weight']) ? (float) $data['weight'] : null,
            length: isset($data['length']) ? (float) $data['length'] : null,
            width: isset($data['width']) ? (float) $data['width'] : null,
            height: isset($data['height']) ? (float) $data['height'] : null,
            specialInstructions: $data['special_instructions'] ?? null,
            packageDescription: $data['package_description'] ?? null,
            createdBy: $data['created_by'] ?? null,
            items: $data['items'] ?? [],
        );
    }

    public function toArray(): array
    {
        return [
            'company_id' => $this->companyId,
            'carrier_id' => $this->carrierId,
            'invoice_id' => $this->invoiceId,
            'customer_id' => $this->customerId,
            'sender_name' => $this->senderName,
            'sender_phone' => $this->senderPhone,
            'sender_address' => $this->senderAddress,
            'sender_city' => $this->senderCity,
            'sender_area' => $this->senderArea,
            'sender_postcode' => $this->senderPostcode,
            'recipient_name' => $this->recipientName,
            'recipient_phone' => $this->recipientPhone,
            'recipient_email' => $this->recipientEmail,
            'recipient_address' => $this->recipientAddress,
            'recipient_city' => $this->recipientCity,
            'recipient_area' => $this->recipientArea,
            'recipient_postcode' => $this->recipientPostcode,
            'status' => $this->status->value,
            'delivery_type' => $this->deliveryType->value,
            'payment_method' => $this->paymentMethod,
            'cod_amount' => $this->codAmount,
            'shipping_cost' => $this->shippingCost,
            'cod_charge' => $this->codCharge,
            'total_cost' => $this->totalCost,
            'weight' => $this->weight,
            'length' => $this->length,
            'width' => $this->width,
            'height' => $this->height,
            'special_instructions' => $this->specialInstructions,
            'package_description' => $this->packageDescription,
            'created_by' => $this->createdBy,
            'items' => $this->items,
        ];
    }

    public function isCOD(): bool
    {
        return $this->paymentMethod === 'cod';
    }

    public function getTotalWeight(): float
    {
        if ($this->weight) {
            return $this->weight;
        }

        // Calculate from items if no weight provided
        return array_reduce($this->items, function ($total, $item) {
            return $total + ($item['quantity'] * ($item['weight'] ?? 0.5));
        }, 0.0);
    }

    public function getTotalValue(): float
    {
        return array_reduce($this->items, function ($total, $item) {
            return $total + $item['total_price'];
        }, 0.0);
    }
}