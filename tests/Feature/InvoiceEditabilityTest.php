<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceEditabilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoice_edit_page_and_update_work_for_published_or_draft_invoices(): void
    {
        $user = User::factory()->create([
            'role' => 'admin',
            'is_approved' => true,
        ]);

        $client = Client::create([
            'name' => 'Acme Studio',
            'phone' => '01777777777',
            'email' => 'billing@acme.com',
            'address' => 'Dhaka, Bangladesh',
            'status' => 'pending',
        ]);

        $invoice = Invoice::create([
            'invoice_number' => 901,
            'client_id' => $client->id,
            'status' => 'Draft',
            'invoice_date' => '2026-10-01',
            'due_date' => '2026-10-10',
            'payment_method' => 'bank',
            'advance_paid_credit' => 0,
            'notes' => 'Original note',
        ]);

        $invoice->items()->create([
            'description' => 'Original design',
            'amount' => 1500,
        ]);

        $this->actingAs($user)
            ->get('/invoices/'.$invoice->id.'/edit')
            ->assertOk();

        $this->actingAs($user)
            ->put('/invoices/'.$invoice->id, [
                'client_id' => $client->id,
                'status' => 'Paid',
                'invoice_date' => '2026-10-02',
                'due_date' => '2026-10-12',
                'payment_method' => 'bkash',
                'advance_paid_credit' => 200,
                'notes' => 'Updated note',
                'items' => [
                    ['description' => 'Updated design', 'amount' => 1800],
                ],
                'transactions' => [
                    ['transaction_date' => '2026-10-03', 'gateway' => 'bkash', 'transaction_id' => 'TXN-NEW', 'amount' => 1600],
                ],
            ])
            ->assertRedirect();

        $invoice->refresh();

        $this->assertSame('Updated note', $invoice->notes);
        $this->assertSame('Paid', $invoice->status);
        $this->assertSame('bkash', $invoice->payment_method);
        $this->assertSame('1800.00', (string) $invoice->items()->first()->amount);
        $this->assertSame('1600.00', (string) $invoice->transactions()->first()->amount);
    }
}
