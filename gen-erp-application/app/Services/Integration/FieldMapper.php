<?php

namespace App\Services\Integration;

/**
 * Maps GenERP BD fields to/from external system fields with optional transforms.
 */
class FieldMapper
{
    /**
     * Map source data using field mappings.
     *
     * @param  array  $sourceData  Flat or nested associative array of source data
     * @param  array  $fieldMaps   Array of mapping definitions: [{generpbd_field, external_field, transform}]
     * @return array Mapped data for the external system
     */
    public function mapOutbound(array $sourceData, array $fieldMaps): array
    {
        $result = [];

        foreach ($fieldMaps as $map) {
            $value = data_get($sourceData, $map['generpbd_field']);
            $value = $this->applyTransform($value, $map['transform'] ?? null);
            data_set($result, $map['external_field'], $value);
        }

        return $result;
    }

    /**
     * Map external data back into GenERP BD fields (inbound).
     *
     * @param  array  $externalData  Data from external system
     * @param  array  $fieldMaps     Array of mapping definitions
     * @return array Mapped data for GenERP BD
     */
    public function mapInbound(array $externalData, array $fieldMaps): array
    {
        $result = [];

        foreach ($fieldMaps as $map) {
            $value = data_get($externalData, $map['external_field']);
            $value = $this->applyReverseTransform($value, $map['transform'] ?? null);
            data_set($result, $map['generpbd_field'], $value);
        }

        return $result;
    }

    /** Apply a named transform to a value. */
    private function applyTransform(mixed $value, ?string $transform): mixed
    {
        if ($transform === null || $value === null) {
            return $value;
        }

        return match ($transform) {
            'divide_by_100' => is_numeric($value) ? $value / 100 : $value,
            'multiply_by_100' => is_numeric($value) ? (int) ($value * 100) : $value,
            'to_string' => (string) $value,
            'to_integer' => (int) $value,
            'to_float' => (float) $value,
            'upper' => is_string($value) ? strtoupper($value) : $value,
            'lower' => is_string($value) ? strtolower($value) : $value,
            'trim' => is_string($value) ? trim($value) : $value,
            'date_ymd' => $value instanceof \DateTimeInterface ? $value->format('Y-m-d') : $value,
            'date_dmy' => $value instanceof \DateTimeInterface ? $value->format('d-m-Y') : $value,
            'boolean' => (bool) $value,
            'json_encode' => json_encode($value),
            'json_decode' => is_string($value) ? json_decode($value, true) : $value,
            default => $value, // Unknown transform — pass through
        };
    }

    /** Apply reverse transform for inbound mapping. */
    private function applyReverseTransform(mixed $value, ?string $transform): mixed
    {
        if ($transform === null || $value === null) {
            return $value;
        }

        // Reverse the transform direction
        return match ($transform) {
            'divide_by_100' => is_numeric($value) ? (int) ($value * 100) : $value,
            'multiply_by_100' => is_numeric($value) ? $value / 100 : $value,
            default => $this->applyTransform($value, $transform),
        };
    }

    /**
     * Get list of all available transforms for UI display.
     *
     * @return array<string, string>
     */
    public static function availableTransforms(): array
    {
        return [
            'divide_by_100' => 'Divide by 100 (paise → BDT)',
            'multiply_by_100' => 'Multiply by 100 (BDT → paise)',
            'to_string' => 'Convert to text',
            'to_integer' => 'Convert to integer',
            'to_float' => 'Convert to decimal',
            'upper' => 'UPPERCASE',
            'lower' => 'lowercase',
            'trim' => 'Trim whitespace',
            'date_ymd' => 'Date (YYYY-MM-DD)',
            'date_dmy' => 'Date (DD-MM-YYYY)',
            'boolean' => 'Convert to boolean',
            'json_encode' => 'JSON encode',
            'json_decode' => 'JSON decode',
        ];
    }
}
