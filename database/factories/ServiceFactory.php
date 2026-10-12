<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ServiceType;
use App\Models\Accounts\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<Service>
 */
class ServiceFactory extends Factory
{
    protected $model = Service::class;

    public function definition(): array
    {
        $type = fake()->randomElement(ServiceType::all());

        return [
            'name' => match ($type) {
                ServiceType::DOMAIN => fake()->domainName() . ' Domain',
                ServiceType::HOSTING => fake()->company() . ' Hosting',
                ServiceType::WEB_DEVELOPMENT => fake()->randomElement(['E-commerce', 'Corporate', 'Portfolio']) . ' Website',
                ServiceType::DIGITAL_MARKETING => fake()->randomElement(['SEO', 'Social Media', 'Google Ads']) . ' Package',
            },
            'type' => $type->value,
            'default_price' => fake()->randomFloat(2, 5000, 50000),
            'description' => fake()->sentence(10),
            'is_active' => true,
        ];
    }

    public function domain(): self
    {
        return $this->state(fn (array $attributes) => [
            'type' => ServiceType::DOMAIN->value,
            'name' => fake()->domainName() . ' Domain',
            'default_price' => fake()->randomFloat(2, 1200, 2500),
        ]);
    }

    public function hosting(): self
    {
        return $this->state(fn (array $attributes) => [
            'type' => ServiceType::HOSTING->value,
            'name' => fake()->company() . ' Hosting',
            'default_price' => fake()->randomFloat(2, 5000, 15000),
        ]);
    }

    public function webDevelopment(): self
    {
        return $this->state(fn (array $attributes) => [
            'type' => ServiceType::WEB_DEVELOPMENT->value,
            'name' => fake()->randomElement(['E-commerce', 'Corporate', 'Portfolio']) . ' Website',
            'default_price' => fake()->randomFloat(2, 20000, 100000),
        ]);
    }

    public function digitalMarketing(): self
    {
        return $this->state(fn (array $attributes) => [
            'type' => ServiceType::DIGITAL_MARKETING->value,
            'name' => fake()->randomElement(['SEO', 'Social Media', 'Google Ads']) . ' Package',
            'default_price' => fake()->randomFloat(2, 10000, 50000),
        ]);
    }

    public function inactive(): self
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
