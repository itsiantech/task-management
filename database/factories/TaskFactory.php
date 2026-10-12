<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Client;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<Task>
 */
class TaskFactory extends Factory
{
    protected $model = Task::class;

    public function definition(): array
    {
        return [
            'title' => fake()->sentence(6),
            'description' => fake()->paragraph(),
            'sticky_note' => fake()->optional()->sentence(),
            'status' => fake()->randomElement(['pending', 'in_progress', 'completed', 'on_hold']),
            'start_date' => fake()->optional()->dateTimeBetween('-30 days', 'now'),
            'due_date' => fake()->dateTimeBetween('now', '+30 days'),
            'priority' => fake()->randomElement(['low', 'medium', 'high', 'urgent']),
            'tags' => fake()->randomElements(['development', 'design', 'marketing', 'urgent'], fake()->numberBetween(0, 3)),
            'assigned_to' => User::factory(),
            'created_by' => User::factory(),
            'attachment_path' => null,
            'total_budget' => fake()->randomFloat(2, 10000, 100000),
            'paid_amount' => 0,
            'due_amount' => fn (array $attributes) => $attributes['total_budget'],
            'payment_status' => 'pending',
        ];
    }

    public function withClient(): self
    {
        return $this->state(fn (array $attributes) => [
            'client_id' => Client::factory(),
        ]);
    }

    public function completed(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
        ]);
    }

    public function inProgress(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'in_progress',
        ]);
    }
}
