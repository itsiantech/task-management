<?php

declare(strict_types=1);

namespace App\Enums;

enum InvoiceStatus: string
{
    case DRAFT = 'draft';
    case UNPAID = 'unpaid';
    case PARTIAL = 'partial';
    case PAID = 'paid';
    case DUE = 'due';
    case HOLD = 'hold';
    case VOID = 'void';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::UNPAID => 'Unpaid',
            self::PARTIAL => 'Partial',
            self::PAID => 'Paid',
            self::DUE => 'Due (Overdue)',
            self::HOLD => 'On Hold',
            self::VOID => 'Void',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::DRAFT => 'bg-slate-100 text-slate-700',
            self::UNPAID => 'bg-amber-100 text-amber-700',
            self::PARTIAL => 'bg-blue-100 text-blue-700',
            self::PAID => 'bg-emerald-100 text-emerald-700',
            self::DUE => 'bg-rose-100 text-rose-700',
            self::HOLD => 'bg-orange-100 text-orange-700',
            self::VOID => 'bg-gray-100 text-gray-700',
        };
    }

    public function isEditable(): bool
    {
        return in_array($this, [self::DRAFT, self::UNPAID, self::PARTIAL, self::HOLD]);
    }

    public function isLocked(): bool
    {
        return $this === self::PAID;
    }

    public function canAddPayment(): bool
    {
        return in_array($this, [self::UNPAID, self::PARTIAL, self::DUE]);
    }
}
