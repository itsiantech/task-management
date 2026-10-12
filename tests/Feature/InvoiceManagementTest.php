<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoice_can_be_created_with_line_items_and_calculated_totals(): void
    {
        $user = User::factory()->create([
            'role' => 'admin',
            'is_approved' => true,
        ]);

        $client = Client::create([
            'name' => 'Alpha Labs',
            'phone' => '01999999999',
            'email' => 'hello@alphalabs.com',
            'address' => 'Dhaka, Bangladesh',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($user)->post('/invoices', [
            'client_id' => $client->id,
            'status' => 'Draft',
            'invoice_date' => '2026-10-04',
            'due_date' => '2026-10-14',
            'payment_method' => 'bkash',
            'advance_paid_credit' => 250,
            'items' => [
                ['description' => 'Website design', 'amount' => 1000],
                ['description' => 'Development', 'amount' => 500],
            ],
            'transactions' => [
                ['transaction_date' => '2026-10-04', 'gateway' => 'bkash', 'transaction_id' => 'TXN-123', 'amount' => 300],
            ],
        ]);

        $response->assertRedirect();
        $invoice = \App\Models\Invoice::first();
        // Invoice numbers are auto-generated (INV-YYYY-NNNN) and the status
        // is recalculated from payments (300 paid of 1250 due => partial).
        $this->assertStringStartsWith('INV-', $invoice->invoice_number);
        $this->assertDatabaseHas('invoices', [
            'client_id' => $client->id,
            'invoice_number' => $invoice->invoice_number,
            'status' => 'partial',
        ]);

        $this->assertDatabaseHas('invoice_items', [
            'description' => 'Website design',
        ]);

        $this->assertSame(1500.0, (float) $invoice->sub_total);
        $this->assertSame(1250.0, (float) $invoice->total_amount);
        $this->assertSame(950.0, (float) $invoice->total_due);
    }
}
