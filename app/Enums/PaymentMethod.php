<?php

declare(strict_types=1);

namespace App\Enums;

enum PaymentMethod: string
{
    case BKASH = 'bkash';
    case NAGAD = 'nagad';
    case BANK = 'bank';
    case CASH = 'cash';

    public function label(): string
    {
        return match ($this) {
            self::BKASH => 'bKash',
            self::NAGAD => 'Nagad',
            self::BANK => 'Bank',
            self::CASH => 'Cash',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::BKASH => '📱',
            self::NAGAD => '📱',
            self::BANK => '🏦',
            self::CASH => '💵',
        };
    }
}
