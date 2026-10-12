<?php

declare(strict_types=1);

namespace App\Enums;

enum ExpenseCategory: string
{
    case DAILY = 'daily';
    case ADS = 'ads';
    case SERVER = 'server';
    case FAMILY = 'family';
    case OFFICE = 'office';
    case MARKETING = 'marketing';
    case SALARY = 'salary';
    case TOOLS = 'tools';
    case INVESTOR = 'investor';
    case CUSTOM = 'custom';

    public function label(): string
    {
        return match ($this) {
            self::DAILY => 'Daily Expenses',
            self::ADS => 'Advertisement',
            self::SERVER => 'Server & Hosting',
            self::FAMILY => 'Family',
            self::OFFICE => 'Office',
            self::MARKETING => 'Marketing',
            self::SALARY => 'Salary',
            self::TOOLS => 'Tools & Software',
            self::INVESTOR => 'Investor',
            self::CUSTOM => 'Custom',
        };
    }

    public static function defaultOptions(): array
    {
        return [
            self::DAILY,
            self::ADS,
            self::SERVER,
            self::FAMILY,
            self::OFFICE,
            self::MARKETING,
            self::SALARY,
            self::TOOLS,
            self::INVESTOR,
        ];
    }
}
