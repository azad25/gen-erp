<?php

namespace App\Domain\Logistics\DTOs;

use App\Domain\Logistics\Enums\DeliveryType;

class CarrierRateData
{
    public function __construct(
        public readonly int $carrierId,
        public readonly string $carrierName,
        public readonly DeliveryType $deliveryType,
        public readonly float $baseRate,
        public readonly float $weightRate,
        public readonly float $codCharge,
        public readonly float $totalRate,
        public readonly int $estimatedDays,
        public readonly bool $available = true,
        public readonly ?string $error = null,
        public readonly array $additionalCharges = [],
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            carrierId: $data['carrier_id'],
            carrierName: $data['carrier_name'],
            deliveryType: DeliveryType::from($data['delivery_type']),
            baseRate: (float) $data['base_rate'],
            weightRate: (float) $data['weight_rate'],
            codCharge: (float) ($data['cod_charge'] ?? 0.0),
            totalRate: (float) $data['total_rate'],
            estimatedDays: (int) $data['estimated_days'],
            available: (bool) ($data['available'] ?? true),
            error: $data['error'] ?? null,
            additionalCharges: $data['additional_charges'] ?? [],
        );
    }

    public static function unavailable(int $carrierId, string $carrierName, string $error): self
    {
        return new self(
            carrierId: $carrierId,
            carrierName: $carrierName,
            deliveryType: DeliveryType::STANDARD,
            baseRate: 0.0,
            weightRate: 0.0,
            codCharge: 0.0,
            totalRate: 0.0,
            estimatedDays: 0,
            available: false,
            error: $error,
        );
    }

    public function toArray(): array
    {
        return [
            'carrier_id' => $this->carrierId,
            'carrier_name' => $this->carrierName,
            'delivery_type' => $this->deliveryType->value,
            'delivery_type_label' => $this->deliveryType->label(),
            'base_rate' => $this->baseRate,
            'weight_rate' => $this->weightRate,
            'cod_charge' => $this->codCharge,
            'total_rate' => $this->totalRate,
            'estimated_days' => $this->estimatedDays,
            'available' => $this->available,
            'error' => $this->error,
            'additional_charges' => $this->additionalCharges,
            'formatted_rate' => $this->getFormattedRate(),
            'estimated_delivery' => $this->getEstimatedDeliveryDate(),
        ];
    }

    public function getFormattedRate(): string
    {
        return '৳' . number_format($this->totalRate, 2);
    }

    public function getEstimatedDeliveryDate(): string
    {
        if (!$this->available) {
            return 'N/A';
        }

        $date = now()->addDays($this->estimatedDays);
        return $date->format('M d, Y');
    }

    public function isAvailable(): bool
    {
        return $this->available && $this->error === null;
    }

    public function isCheapest(array $rates): bool
    {
        if (!$this->available) {
            return false;
        }

        $availableRates = array_filter($rates, fn($rate) => $rate->available);
        $minRate = min(array_map(fn($rate) => $rate->totalRate, $availableRates));
        
        return $this->totalRate === $minRate;
    }

    public function isFastest(array $rates): bool
    {
        if (!$this->available) {
            return false;
        }

        $availableRates = array_filter($rates, fn($rate) => $rate->available);
        $minDays = min(array_map(fn($rate) => $rate->estimatedDays, $availableRates));
        
        return $this->estimatedDays === $minDays;
    }

    public function getBreakdown(): array
    {
        $breakdown = [
            'Base Rate' => $this->baseRate,
            'Weight Charge' => $this->weightRate,
        ];

        if ($this->codCharge > 0) {
            $breakdown['COD Charge'] = $this->codCharge;
        }

        foreach ($this->additionalCharges as $name => $amount) {
            $breakdown[$name] = $amount;
        }

        return $breakdown;
    }
}