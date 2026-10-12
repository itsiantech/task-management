<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PaymentAccountType;
use App\Models\Accounts\PaymentAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<PaymentAccount>
 */
class PaymentAccountFactory extends Factory
{
    protected $model = PaymentAccount::class;

    public function definition(): array
    {
        $type = fake()->randomElement(PaymentAccountType::cases());

        return [
            'name' => match ($type) {
                PaymentAccountType::BKASH => 'Personal bKash',
                PaymentAccountType::NAGAD => 'Business Nagad',
                PaymentAccountType::BANK => fake()->randomElement(['Dutch-Bangla', 'Brac Bank', 'City Bank']) . ' Account',
                PaymentAccountType::CASH => 'Office Cash',
            },
            'type' => $type->value,
            'account_number' => match ($type) {
                PaymentAccountType::BKASH => fake()->numerify('01#########'),
                PaymentAccountType::NAGAD => fake()->numerify('01#########'),
                PaymentAccountType::BANK => fake()->bankAccountNumber(),
                PaymentAccountType::CASH => null,
            },
            'balance' => fake()->randomFloat(2, 0, 100000),
            'notes' => fake()->optional()->sentence(),
            'is_active' => true,
        ];
    }

    public function bkash(): self
    {
        return $this->state(fn (array $attributes) => [
            'type' => PaymentAccountType::BKASH->value,
            'name' => 'Personal bKash',
            'account_number' => fake()->numerify('01#########'),
        ]);
    }

    public function nagad(): self
    {
        return $this->state(fn (array $attributes) => [
            'type' => PaymentAccountType::NAGAD->value,
            'name' => 'Business Nagad',
            'account_number' => fake()->numerify('01#########'),
        ]);
    }

    public function bank(): self
    {
        return $this->state(fn (array $attributes) => [
            'type' => PaymentAccountType::BANK->value,
            'name' => fake()->randomElement(['Dutch-Bangla', 'Brac Bank', 'City Bank']) . ' Account',
            'account_number' => fake()->bankAccountNumber(),
        ]);
    }

    public function cash(): self
    {
        return $this->state(fn (array $attributes) => [
            'type' => PaymentAccountType::CASH->value,
            'name' => 'Office Cash',
            'account_number' => null,
        ]);
    }

    public function inactive(): self
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
