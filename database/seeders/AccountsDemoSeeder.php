<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\ExpenseCategory;
use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Enums\ServiceType;
use App\Models\Accounts\Expense;
use App\Models\Accounts\Payment;
use App\Models\Accounts\Service;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AccountsDemoSeeder extends Seeder
{
    public function run(): void
    {
        // Ensure services are seeded first
        $this->call(ServiceSeeder::class);
        $this->call(PaymentAccountSeeder::class);

        // Get admin user
        $admin = User::where('role', 'admin')->first();
        if (!$admin) {
            $admin = User::factory()->create(['role' => 'admin', 'is_approved' => true]);
        }

        // Create demo clients
        $clients = Client::factory()->count(5)->create([
            'status' => 'confirm',
        ]);

        // Create demo services for easy reference
        $domainService = Service::where('type', ServiceType::DOMAIN->value)->first();
        $hostingService = Service::where('type', ServiceType::HOSTING->value)->first();
        $webDevService = Service::where('type', ServiceType::WEB_DEVELOPMENT->value)->first();
        $marketingService = Service::where('type', ServiceType::DIGITAL_MARKETING->value)->first();

        // Create demo tasks (orders)
        foreach ($clients as $client) {
            $task = Task::factory()->create([
                'client_id' => $client->id,
                'created_by' => $admin->id,
                'total_budget' => fake()->randomFloat(2, 20000, 100000),
                'paid_amount' => 0,
                'due_amount' => 0,
                'payment_status' => 'pending',
            ]);

            // Generate order number after creation
            $task->order_number = 'ORD-' . str_pad((string) $task->id, 5, '0', STR_PAD_LEFT);
            $task->saveQuietly();

            // Create invoice for the task
            $invoice = Invoice::factory()->create([
                'client_id' => $client->id,
                'task_id' => $task->id,
                'status' => fake()->randomElement([
                    InvoiceStatus::UNPAID->value,
                    InvoiceStatus::PARTIAL->value,
                    InvoiceStatus::PAID->value,
                    InvoiceStatus::DUE->value,
                ]),
                'invoice_date' => now()->subDays(fake()->numberBetween(1, 30)),
                'due_date' => now()->addDays(fake()->numberBetween(7, 30)),
                'total_amount' => $task->total_budget,
                'total_due' => $task->total_budget,
            ]);

            // Add invoice items
            $itemCount = fake()->numberBetween(1, 3);
            $services = [$domainService, $hostingService, $webDevService, $marketingService];

            for ($i = 0; $i < $itemCount; $i++) {
                $service = $services[fake()->numberBetween(0, count($services) - 1)];
                $qty = fake()->numberBetween(1, 2);
                $unitPrice = $service->default_price;
                $discount = fake()->boolean(30) ? fake()->randomFloat(2, 500, 2000) : 0;
                $tax = 0;

                $lineTotal = ($unitPrice * $qty) - $discount + $tax;

                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'service_id' => $service->id,
                    'description' => $service->description,
                    'qty' => $qty,
                    'unit_price' => $unitPrice,
                    'discount' => $discount,
                    'tax' => $tax,
                    'line_total' => $lineTotal,
                ]);
            }

            // Recalculate invoice totals
            $invoice->recalculateTotals();

            // Add payments if status is partial or paid
            if (in_array($invoice->status, [InvoiceStatus::PARTIAL->value, InvoiceStatus::PAID->value])) {
                $paymentCount = $invoice->status === InvoiceStatus::PAID->value ? 2 : 1;

                for ($i = 0; $i < $paymentCount; $i++) {
                    $amount = $invoice->status === InvoiceStatus::PAID->value
                        ? $invoice->total_amount / 2
                        : fake()->randomFloat(2, 5000, $invoice->total_amount * 0.5);

                    Payment::create([
                        'invoice_id' => $invoice->id,
                        'client_id' => $client->id,
                        'order_id' => $task->id,
                        'amount' => $amount,
                        'payment_date' => now()->subDays(fake()->numberBetween(1, 15)),
                        'gateway' => fake()->randomElement(PaymentMethod::cases())->value,
                        'method' => fake()->randomElement(PaymentMethod::cases())->value,
                        'receiving_account' => fake()->randomElement(['Personal bKash', 'Business Nagad', 'Dutch-Bangla Bank - Current', 'Office Cash']),
                        'transaction_id' => fake()->numerify('TXN########'),
                        'note' => fake()->sentence(),
                        'created_by' => $admin->id,
                    ]);
                }

                // Recalculate after payments
                $invoice->recalculateTotals();
            }
        }

        // Create demo expenses
        Expense::factory()->count(20)->create([
            'expense_date' => now()->subDays(fake()->numberBetween(1, 90)),
        ]);

        // Create some unpaid expenses
        Expense::factory()->count(5)->unpaid()->create();

        $this->command->info('Accounts demo data seeded successfully.');
        $this->command->info('Created:');
        $this->command->info('  - 5 demo clients');
        $this->command->info('  - 5 demo orders (tasks)');
        $this->command->info('  - 5 demo invoices with items');
        $this->command->info('  - Demo payments');
        $this->command->info('  - 25 demo expenses');
    }
}
