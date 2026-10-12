<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PaymentMethod;
use App\Models\Accounts\Payment;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<Payment>
 */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        return [
            'invoice_id' => Invoice::factory(),
            'client_id' => Client::factory(),
            'order_id' => Task::factory(),
            'amount' => fake()->randomFloat(2, 1000, 50000),
            'payment_date' => fake()->dateTimeBetween('-30 days', 'now'),
            'transaction_date' => fake()->dateTimeBetween('-30 days', 'now'),
            'gateway' => fake()->randomElement(PaymentMethod::cases())->value,
            'method' => fake()->randomElement(PaymentMethod::cases())->value,
            'receiving_account' => fake()->randomElement(['Personal bKash', 'Business Nagad', 'Dutch-Bangla Bank - Current', 'Office Cash']),
            'transaction_id' => fake()->numerify('TXN########'),
            'note' => fake()->optional()->sentence(),
            'created_by' => User::factory(),
            'updated_by' => null,
        ];
    }

    public function forInvoice(Invoice $invoice): self
    {
        return $this->state(fn (array $attributes) => [
            'invoice_id' => $invoice->id,
            'client_id' => $invoice->client_id,
            'order_id' => $invoice->task_id,
        ]);
    }

    public function bkash(): self
    {
        return $this->state(fn (array $attributes) => [
            'gateway' => PaymentMethod::BKASH->value,
            'method' => PaymentMethod::BKASH->value,
            'receiving_account' => 'Personal bKash',
        ]);
    }

    public function nagad(): self
    {
        return $this->state(fn (array $attributes) => [
            'gateway' => PaymentMethod::NAGAD->value,
            'method' => PaymentMethod::NAGAD->value,
            'receiving_account' => 'Business Nagad',
        ]);
    }

    public function bank(): self
    {
        return $this->state(fn (array $attributes) => [
            'gateway' => PaymentMethod::BANK->value,
            'method' => PaymentMethod::BANK->value,
            'receiving_account' => 'Dutch-Bangla Bank - Current',
        ]);
    }

    public function cash(): self
    {
        return $this->state(fn (array $attributes) => [
            'gateway' => PaymentMethod::CASH->value,
            'method' => PaymentMethod::CASH->value,
            'receiving_account' => 'Office Cash',
        ]);
    }
}
