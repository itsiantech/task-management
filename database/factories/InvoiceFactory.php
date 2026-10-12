<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\InvoiceStatus;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Task;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<Invoice>
 */
class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    public function definition(): array
    {
        return [
            'invoice_number' => null, // Will be auto-generated
            'client_id' => Client::factory(),
            'task_id' => Task::factory(),
            'status' => fake()->randomElement([
                InvoiceStatus::DRAFT->value,
                InvoiceStatus::UNPAID->value,
                InvoiceStatus::PARTIAL->value,
                InvoiceStatus::PAID->value,
                InvoiceStatus::DUE->value,
            ]),
            'invoice_date' => fake()->dateTimeBetween('-30 days', 'now'),
            'due_date' => fake()->dateTimeBetween('now', '+30 days'),
            'date_paid' => null,
            'cancelled_date' => null,
            'refunded_date' => null,
            'sub_total' => fake()->randomFloat(2, 5000, 100000),
            'advance_paid_credit' => 0,
            'total_amount' => fn (array $attributes) => $attributes['sub_total'],
            'total_due' => fn (array $attributes) => $attributes['sub_total'],
            'notes' => fake()->optional()->sentence(),
            'published_at' => fake()->optional()->dateTime(),
            'void_reason' => null,
            'held_at' => null,
            'held_by' => null,
            'voided_at' => null,
            'voided_by' => null,
        ];
    }

    public function draft(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => InvoiceStatus::DRAFT->value,
        ]);
    }

    public function unpaid(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => InvoiceStatus::UNPAID->value,
        ]);
    }

    public function paid(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => InvoiceStatus::PAID->value,
            'date_paid' => fake()->dateTimeBetween('-10 days', 'now'),
            'total_due' => 0,
        ]);
    }

    public function due(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => InvoiceStatus::DUE->value,
            'due_date' => fake()->dateTimeBetween('-30 days', '-1 day'),
        ]);
    }

    public function void(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => InvoiceStatus::VOID->value,
            'void_reason' => fake()->sentence(),
            'voided_at' => fake()->dateTimeBetween('-10 days', 'now'),
        ]);
    }
}
