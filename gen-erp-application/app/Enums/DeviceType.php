<?php

namespace App\Enums;

enum DeviceType: string
{
    case BIOMETRIC = 'biometric';
    case POS_PRINTER = 'pos_printer';
    case BARCODE_SCANNER = 'barcode_scanner';
    case CASH_DRAWER = 'cash_drawer';
    case CARD_TERMINAL = 'card_terminal';
    case WEIGHT_SCALE = 'weight_scale';
    case TEMPERATURE_SENSOR = 'temperature_sensor';
    case LABEL_PRINTER = 'label_printer';
    case KIOSK = 'kiosk';
    case CUSTOM = 'custom';

    public function label(): string
    {
        return match ($this) {
            self::BIOMETRIC => __('Biometric Terminal'),
            self::POS_PRINTER => __('POS Printer'),
            self::BARCODE_SCANNER => __('Barcode Scanner'),
            self::CASH_DRAWER => __('Cash Drawer'),
            self::CARD_TERMINAL => __('Card Terminal'),
            self::WEIGHT_SCALE => __('Weight Scale'),
            self::TEMPERATURE_SENSOR => __('Temperature Sensor'),
            self::LABEL_PRINTER => __('Label Printer'),
            self::KIOSK => __('Kiosk'),
            self::CUSTOM => __('Custom Device'),
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $c) => [$c->value => $c->label()])->toArray();
    }
}
