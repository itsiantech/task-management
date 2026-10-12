<?php

declare(strict_types=1);

namespace App\Enums;

enum PaymentAccountType: string
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
            self::BANK => 'Bank Account',
            self::CASH => 'Cash in Hand',
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
