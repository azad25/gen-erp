<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case BKASH = 'bkash';
    case NAGAD = 'nagad';
    case ROCKET = 'rocket';
    case BANK = 'bank';

    public function label(): string
    {
        return match ($this) {
            self::BKASH => 'bKash',
            self::NAGAD => 'Nagad',
            self::ROCKET => 'Rocket',
            self::BANK => 'Bank Transfer',
        };
    }

    /** The payment number customers should send to. */
    public function recipientNumber(): string
    {
        return match ($this) {
            self::BKASH => config('erp.payment.bkash_number', '01XXXXXXXXX'),
            self::NAGAD => config('erp.payment.nagad_number', '01XXXXXXXXX'),
            self::ROCKET => config('erp.payment.rocket_number', '01XXXXXXXXX'),
            self::BANK => '',
        };
    }
}
