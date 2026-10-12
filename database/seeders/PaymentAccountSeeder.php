<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\PaymentAccountType;
use App\Models\Accounts\PaymentAccount;
use Illuminate\Database\Seeder;

class PaymentAccountSeeder extends Seeder
{
    public function run(): void
    {
        $accounts = [
            [
                'name' => 'Personal bKash',
                'type' => PaymentAccountType::BKASH->value,
                'account_number' => '01712345678',
                'balance' => 45000.00,
                'notes' => 'Personal bKash account for business transactions',
                'is_active' => true,
            ],
            [
                'name' => 'Business Nagad',
                'type' => PaymentAccountType::NAGAD->value,
                'account_number' => '01898765432',
                'balance' => 32000.00,
                'notes' => 'Business Nagad account',
                'is_active' => true,
            ],
            [
                'name' => 'Dutch-Bangla Bank - Current',
                'type' => PaymentAccountType::BANK->value,
                'account_number' => 'DBBL1234567890',
                'balance' => 150000.00,
                'notes' => 'Primary business account',
                'is_active' => true,
            ],
            [
                'name' => 'Brac Bank - Savings',
                'type' => PaymentAccountType::BANK->value,
                'account_number' => 'BRAC0987654321',
                'balance' => 75000.00,
                'notes' => 'Reserve account',
                'is_active' => true,
            ],
            [
                'name' => 'Office Cash',
                'type' => PaymentAccountType::CASH->value,
                'account_number' => null,
                'balance' => 25000.00,
                'notes' => 'Petty cash for daily expenses',
                'is_active' => true,
            ],
        ];

        foreach ($accounts as $account) {
            PaymentAccount::firstOrCreate(
                ['name' => $account['name']],
                $account
            );
        }

        $this->command->info('Payment accounts seeded successfully.');
    }
}
