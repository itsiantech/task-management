<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\ServiceType;
use App\Models\Accounts\Service;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    public function run(): void
    {
        $services = [
            [
                'name' => '.com Domain Registration',
                'type' => ServiceType::DOMAIN->value,
                'default_price' => 1500.00,
                'description' => 'Standard .com domain registration for 1 year',
                'is_active' => true,
            ],
            [
                'name' => '.bd Domain Registration',
                'type' => ServiceType::DOMAIN->value,
                'default_price' => 2500.00,
                'description' => 'Bangladeshi .bd domain registration for 2 years',
                'is_active' => true,
            ],
            [
                'name' => 'Shared Hosting - Basic',
                'type' => ServiceType::HOSTING->value,
                'default_price' => 5000.00,
                'description' => '5GB storage, 50GB bandwidth, cPanel',
                'is_active' => true,
            ],
            [
                'name' => 'Shared Hosting - Business',
                'type' => ServiceType::HOSTING->value,
                'default_price' => 12000.00,
                'description' => '20GB storage, 200GB bandwidth, cPanel, SSL',
                'is_active' => true,
            ],
            [
                'name' => 'VPS Hosting',
                'type' => ServiceType::HOSTING->value,
                'default_price' => 25000.00,
                'description' => '4 CPU, 8GB RAM, 100GB SSD, Managed',
                'is_active' => true,
            ],
            [
                'name' => 'Corporate Website',
                'type' => ServiceType::WEB_DEVELOPMENT->value,
                'default_price' => 35000.00,
                'description' => 'Professional corporate website with CMS',
                'is_active' => true,
            ],
            [
                'name' => 'E-commerce Website',
                'type' => ServiceType::WEB_DEVELOPMENT->value,
                'default_price' => 75000.00,
                'description' => 'Full-featured e-commerce with payment gateway',
                'is_active' => true,
            ],
            [
                'name' => 'Portfolio Website',
                'type' => ServiceType::WEB_DEVELOPMENT->value,
                'default_price' => 20000.00,
                'description' => 'Personal or business portfolio website',
                'is_active' => true,
            ],
            [
                'name' => 'SEO Package - Basic',
                'type' => ServiceType::DIGITAL_MARKETING->value,
                'default_price' => 15000.00,
                'description' => 'Monthly SEO optimization, keyword research',
                'is_active' => true,
            ],
            [
                'name' => 'Social Media Marketing',
                'type' => ServiceType::DIGITAL_MARKETING->value,
                'default_price' => 25000.00,
                'description' => 'Facebook & Instagram management, content creation',
                'is_active' => true,
            ],
            [
                'name' => 'Google Ads Management',
                'type' => ServiceType::DIGITAL_MARKETING->value,
                'default_price' => 20000.00,
                'description' => 'PPC campaign setup and management',
                'is_active' => true,
            ],
        ];

        foreach ($services as $service) {
            Service::firstOrCreate(
                ['name' => $service['name']],
                $service
            );
        }

        $this->command->info('Services seeded successfully.');
    }
}
