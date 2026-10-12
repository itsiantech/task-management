<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ExpenseCategory;
use App\Enums\PaymentMethod;
use App\Models\Accounts\Expense;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<Expense>
 */
class ExpenseFactory extends Factory
{
    protected $model = Expense::class;

    public function definition(): array
    {
        $category = fake()->randomElement(ExpenseCategory::defaultOptions());

        return [
            'category' => $category->value,
            'custom_category' => null,
            'title' => match ($category) {
                ExpenseCategory::DAILY => fake()->randomElement(['Office Lunch', 'Transportation', 'Utilities']),
                ExpenseCategory::ADS => fake()->randomElement(['Facebook Ads', 'Google Ads', 'LinkedIn Ads']),
                ExpenseCategory::SERVER => fake()->randomElement(['AWS Hosting', 'Domain Renewal', 'SSL Certificate']),
                ExpenseCategory::FAMILY => fake()->randomElement(['Family Support', 'Medical', 'Education']),
                ExpenseCategory::OFFICE => fake()->randomElement(['Office Rent', 'Internet Bill', 'Office Supplies']),
                ExpenseCategory::MARKETING => fake()->randomElement(['Brochure Printing', 'Event Marketing', 'Brand Campaign']),
                ExpenseCategory::SALARY => fake()->randomElement(['Monthly Salary', 'Bonus', 'Overtime']),
                ExpenseCategory::TOOLS => fake()->randomElement(['Software License', 'Dev Tools', 'Design Tools']),
                ExpenseCategory::INVESTOR => fake()->randomElement(['Capital Investment', 'Profit Share', 'Return']),
                ExpenseCategory::CUSTOM => fake()->sentence(3),
            },
            'amount' => fake()->randomFloat(2, 500, 50000),
            'expense_date' => fake()->dateTimeBetween('-6 months', 'now'),
            'paid_via' => fake()->randomElement(PaymentMethod::cases())->value,
            'paid_to' => fake()->randomElement([fake()->name(), fake()->company(), null]),
            'reference' => fake()->optional()->numerify('REF-#####'),
            'note' => fake()->optional()->sentence(),
            'attachment' => null,
            'payable_amount' => fn (array $attributes) => $attributes['amount'],
            'is_paid' => true,
        ];
    }

    public function daily(): self
    {
        return $this->state(fn (array $attributes) => [
            'category' => ExpenseCategory::DAILY->value,
            'title' => fake()->randomElement(['Office Lunch', 'Transportation', 'Utilities']),
            'amount' => fake()->randomFloat(2, 100, 2000),
        ]);
    }

    public function salary(): self
    {
        return $this->state(fn (array $attributes) => [
            'category' => ExpenseCategory::SALARY->value,
            'title' => fake()->randomElement(['Monthly Salary', 'Bonus', 'Overtime']),
            'amount' => fake()->randomFloat(2, 15000, 50000),
        ]);
    }

    public function unpaid(): self
    {
        return $this->state(fn (array $attributes) => [
            'is_paid' => false,
            'payable_amount' => fake()->randomFloat(2, 1000, 20000),
        ]);
    }

    public function custom(string $customCategory): self
    {
        return $this->state(fn (array $attributes) => [
            'category' => ExpenseCategory::CUSTOM->value,
            'custom_category' => $customCategory,
            'title' => $customCategory,
        ]);
    }
}
