<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Accounts\Payment;
use App\Models\Accounts\PaymentEdit;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<PaymentEdit>
 */
class PaymentEditFactory extends Factory
{
    protected $model = PaymentEdit::class;

    public function definition(): array
    {
        return [
            'payment_id' => Payment::factory(),
            'edited_by' => User::factory(),
            'edited_at' => fake()->dateTimeBetween('-1 month', 'now'),
            'old_values' => [
                'amount' => fake()->randomFloat(2, 1000, 10000),
                'payment_date' => fake()->date(),
                'note' => fake()->sentence(),
            ],
            'new_values' => [
                'amount' => fake()->randomFloat(2, 1000, 10000),
                'payment_date' => fake()->date(),
                'note' => fake()->sentence(),
            ],
            'reason' => fake()->randomElement(['Correction', 'Update', 'Adjustment']) . ': ' . fake()->sentence(),
        ];
    }
}
