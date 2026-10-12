<?php

declare(strict_types=1);

namespace App\Enums;

enum ServiceType: string
{
    case DOMAIN = 'domain';
    case HOSTING = 'hosting';
    case WEB_DEVELOPMENT = 'web_development';
    case DIGITAL_MARKETING = 'digital_marketing';

    public function label(): string
    {
        return match ($this) {
            self::DOMAIN => 'Domain',
            self::HOSTING => 'Hosting',
            self::WEB_DEVELOPMENT => 'Web Development',
            self::DIGITAL_MARKETING => 'Digital Marketing',
        };
    }

    public static function all(): array
    {
        return [
            self::DOMAIN,
            self::HOSTING,
            self::WEB_DEVELOPMENT,
            self::DIGITAL_MARKETING,
        ];
    }
}
