<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\InvoiceStatus;
use App\Models\Accounts\Expense;
use App\Models\Accounts\Payment;
use App\Models\Accounts\PaymentAccount;
use App\Models\Accounts\PaymentEdit;
use App\Models\Accounts\Service;
use App\Models\ActivityLog;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountsModuleTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'is_approved' => true]);
    }

    private function accountant(): User
    {
        return User::factory()->create(['role' => 'accountant', 'is_approved' => true]);
    }

    private function member(): User
    {
        return User::factory()->create(['role' => 'member', 'is_approved' => true]);
    }

    private function client(): Client
    {
        return Client::factory()->create(['status' => 'confirm']);
    }

    private function invoicePayload(?int $clientId = null): array
    {
        $service = Service::factory()->create();

        return [
            'client_id' => $clientId ?? $this->client()->id,
            'invoice_date' => now()->format('Y-m-d'),
            'due_date' => now()->addDays(7)->format('Y-m-d'),
            'advance_paid_credit' => 0,
            'notes' => 'Test invoice',
            'items' => [
                ['service_id' => $service->id, 'description' => 'Test item', 'qty' => 2, 'unit_price' => 5000, 'discount' => 0, 'tax' => 0],
            ],
        ];
    }

    public function test_dashboard_orders_reports_pages_load(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->get(route('accounts.dashboard'))->assertStatus(200);
        $this->actingAs($admin)->get(route('accounts.orders.index'))->assertStatus(200);
        $this->actingAs($admin)->get(route('accounts.invoices.index'))->assertStatus(200);
        $this->actingAs($admin)->get(route('accounts.payments.index'))->assertStatus(200);
        $this->actingAs($admin)->get(route('accounts.expenses.index'))->assertStatus(200);
        $this->actingAs($admin)->get(route('accounts.payment-accounts.index'))->assertStatus(200);
        $this->actingAs($admin)->get(route('accounts.reports.index'))->assertStatus(200);
        $this->actingAs($admin)->get(route('accounts.services.index'))->assertStatus(200);

        // Member (staff) can read but not write.
        $member = $this->member();
        $this->actingAs($member)->get(route('accounts.dashboard'))->assertStatus(200);
        $this->actingAs($member)->post(route('accounts.expenses.store'), [])->assertStatus(403);
    }

    public function test_invoice_lifecycle_partial_then_auto_paid(): void
    {
        $admin = $this->admin();

        // Create as draft.
        $response = $this->actingAs($admin)->post(route('accounts.invoices.store'), $this->invoicePayload());
        $response->assertRedirect();
        $invoice = Invoice::latest('id')->first();
        $this->assertEquals(InvoiceStatus::DRAFT->value, $invoice->status);
        $this->assertEquals(10000.0, (float) $invoice->total_amount);
        $this->assertStringStartsWith('INV-', $invoice->invoice_number);

        // Publish → unpaid.
        $this->actingAs($admin)->post(route('accounts.invoices.publish', $invoice));
        $this->assertEquals(InvoiceStatus::UNPAID->value, $invoice->fresh()->status);

        // Partial payment → partial.
        $this->actingAs($admin)->post(route('accounts.invoices.payments.store', $invoice), [
            'amount' => 4000, 'payment_date' => now()->format('Y-m-d'), 'method' => 'bkash', 'transaction_id' => 'TXN-1',
        ])->assertRedirect();
        $invoice->refresh();
        $this->assertEquals(InvoiceStatus::PARTIAL->value, $invoice->status);
        $this->assertEquals(6000.0, (float) $invoice->total_due);

        // Full payment → paid automatically.
        $this->actingAs($admin)->post(route('accounts.invoices.payments.store', $invoice), [
            'amount' => 6000, 'payment_date' => now()->format('Y-m-d'), 'method' => 'bank',
        ])->assertRedirect();
        $invoice->refresh();
        $this->assertEquals(InvoiceStatus::PAID->value, $invoice->status);
        $this->assertEquals(0.0, (float) $invoice->total_due);
        $this->assertNotNull($invoice->date_paid);
    }

    public function test_void_requires_reason_and_invoice_cannot_be_deleted(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->post(route('accounts.invoices.store'), $this->invoicePayload());
        $invoice = Invoice::latest('id')->first();

        // No reason → validation error.
        $this->actingAs($admin)->post(route('accounts.invoices.void', $invoice), [])
            ->assertSessionHasErrors('void_reason');
        $this->assertNotEquals(InvoiceStatus::VOID->value, $invoice->fresh()->status);

        // With reason → void + audit log.
        $this->actingAs($admin)->post(route('accounts.invoices.void', $invoice), ['void_reason' => 'Duplicate invoice'])
            ->assertRedirect();
        $invoice->refresh();
        $this->assertEquals(InvoiceStatus::VOID->value, $invoice->status);
        $this->assertEquals('Duplicate invoice', $invoice->void_reason);
        $this->assertNotNull($invoice->voided_by);
        $this->assertTrue(ActivityLog::where('action', 'invoice.voided')->where('subject_id', $invoice->id)->exists());

        // Invoices can NEVER be deleted.
        $this->expectException(\Exception::class);
        $invoice->delete();
    }

    public function test_hold_and_resume(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->post(route('accounts.invoices.store'), array_merge($this->invoicePayload(), ['publish' => true]));
        $invoice = Invoice::latest('id')->first();

        $this->actingAs($admin)->post(route('accounts.invoices.hold', $invoice))->assertRedirect();
        $this->assertEquals(InvoiceStatus::HOLD->value, $invoice->fresh()->status);

        // Hold blocks new payments (policy denies with 403).
        $this->actingAs($admin)->post(route('accounts.invoices.payments.store', $invoice), [
            'amount' => 100, 'payment_date' => now()->format('Y-m-d'), 'method' => 'cash',
        ])->assertForbidden();
        $this->assertEquals(0, Payment::where('invoice_id', $invoice->id)->count());

        $this->actingAs($admin)->post(route('accounts.invoices.resume', $invoice))->assertRedirect();
        $this->assertEquals(InvoiceStatus::UNPAID->value, $invoice->fresh()->status);
    }

    public function test_paid_invoice_locked_for_accountant_but_admin_can_adjust(): void
    {
        $admin = $this->admin();
        $accountant = $this->accountant();
        $this->actingAs($admin)->post(route('accounts.invoices.store'), array_merge($this->invoicePayload(), ['publish' => true]));
        $invoice = Invoice::latest('id')->first();

        $this->actingAs($admin)->post(route('accounts.invoices.payments.store', $invoice), [
            'amount' => (float) $invoice->total_amount, 'payment_date' => now()->format('Y-m-d'), 'method' => 'cash',
        ]);
        $this->assertEquals(InvoiceStatus::PAID->value, $invoice->fresh()->status);

        // Accountant gets 403 on edit form and update.
        $this->actingAs($accountant)->get(route('accounts.invoices.edit', $invoice))->assertStatus(403);
        $this->actingAs($accountant)->put(route('accounts.invoices.update', $invoice), $this->invoicePayload($invoice->client_id))
            ->assertStatus(403);

        // Admin can adjust (audit-logged).
        $payload = $this->invoicePayload($invoice->client_id);
        $payload['items'][0]['qty'] = 3;
        $this->actingAs($admin)->put(route('accounts.invoices.update', $invoice), $payload)->assertRedirect();
        $this->assertEquals(15000.0, (float) $invoice->fresh()->total_amount);
        $this->assertTrue(ActivityLog::where('action', 'invoice.updated')->where('subject_id', $invoice->id)->exists());
    }

    public function test_payment_edit_requires_reason_and_is_audit_logged(): void
    {
        $admin = $this->admin();
        $payment = Payment::factory()->create(['amount' => 5000, 'method' => 'bkash']);

        // No reason → 422.
        $this->actingAs($admin)->put(route('accounts.payments.update', $payment), [
            'amount' => 6000, 'payment_date' => now()->format('Y-m-d'), 'method' => 'bkash',
        ])->assertSessionHasErrors('edit_reason');

        // With reason → corrected + immutable history row.
        $this->actingAs($admin)->put(route('accounts.payments.update', $payment), [
            'amount' => 6000, 'payment_date' => now()->format('Y-m-d'), 'method' => 'bkash',
            'edit_reason' => 'Entered 5000 instead of 6000, client confirmed on phone',
        ])->assertRedirect(route('accounts.payments.index'));

        $payment->refresh();
        $this->assertEquals(6000.0, (float) $payment->amount);

        $edit = PaymentEdit::where('payment_id', $payment->id)->first();
        $this->assertNotNull($edit);
        $this->assertEquals(5000.0, (float) $edit->old_values['amount']);
        $this->assertEquals(6000.0, (float) $edit->new_values['amount']);
        $this->assertEquals($admin->id, $edit->edited_by);
        $this->assertStringContainsString('6000', $edit->reason);
        $this->assertTrue($payment->hasEdits());

        // Edit history can NEVER be changed or deleted.
        try {
            $edit->update(['reason' => 'tampered']);
            $this->fail('PaymentEdit update should throw');
        } catch (\Exception) {
            $this->assertTrue(true);
        }
        try {
            $edit->delete();
            $this->fail('PaymentEdit delete should throw');
        } catch (\Exception) {
            $this->assertTrue(true);
        }
    }

    public function test_expense_crud_and_method_balances(): void
    {
        $admin = $this->admin();
        $account = PaymentAccount::factory()->bank()->create(['name' => 'Test Bank']);

        Payment::factory()->bank()->create(['amount' => 20000, 'payment_date' => now()->format('Y-m-d')]);

        $this->actingAs($admin)->post(route('accounts.expenses.store'), [
            'category' => 'server', 'title' => 'VPS yearly', 'amount' => 8000,
            'expense_date' => now()->format('Y-m-d'), 'paid_via' => 'bank',
            'payment_account_id' => $account->id, 'paid_to' => 'Hetzner', 'is_paid' => true,
        ])->assertRedirect();
        $expense = Expense::latest('id')->first();
        $this->assertEquals(8000.0, (float) $expense->amount);
        $this->assertEquals($account->id, $expense->payment_account_id);

        // Balances: bank received 20000, spent 8000 → 12000.
        $balances = app(\App\Services\Accounts\PaymentAccountService::class)->methodBalances();
        $this->assertEquals(20000.0, $balances['bank']['received']);
        $this->assertEquals(8000.0, $balances['bank']['spent']);
        $this->assertEquals(12000.0, $balances['bank']['balance']);

        // Mark due → payable tracked.
        $this->actingAs($admin)->patch(route('accounts.expenses.toggle-paid', $expense))->assertRedirect();
        $this->assertFalse($expense->fresh()->is_paid);

        // Edit page loads, update works.
        $this->actingAs($admin)->get(route('accounts.expenses.edit', $expense))->assertStatus(200);
        $this->actingAs($admin)->put(route('accounts.expenses.update', $expense), [
            'category' => 'server', 'title' => 'VPS yearly updated', 'amount' => 8500,
            'expense_date' => now()->format('Y-m-d'), 'paid_via' => 'bank', 'is_paid' => false,
        ])->assertRedirect();
        $this->assertEquals(8500.0, (float) $expense->fresh()->amount);
    }

    public function test_service_revenue_summary(): void
    {
        $admin = $this->admin();
        $domain = Service::factory()->domain()->create();
        $hosting = Service::factory()->hosting()->create();
        $client = $this->client();

        $this->actingAs($admin)->post(route('accounts.invoices.store'), [
            'client_id' => $client->id,
            'invoice_date' => now()->format('Y-m-d'),
            'items' => [
                ['service_id' => $domain->id, 'description' => 'Domain', 'qty' => 1, 'unit_price' => 2000, 'discount' => 0, 'tax' => 0],
                ['service_id' => $hosting->id, 'description' => 'Hosting', 'qty' => 1, 'unit_price' => 8000, 'discount' => 0, 'tax' => 0],
            ],
            'publish' => true,
        ]);
        $invoice = Invoice::latest('id')->first();
        $this->assertEquals(10000.0, (float) $invoice->total_amount);

        $this->actingAs($admin)->post(route('accounts.invoices.payments.store', $invoice), [
            'amount' => 5000, 'payment_date' => now()->format('Y-m-d'), 'method' => 'nagad',
        ]);

        $revenue = app(\App\Services\Accounts\ReportService::class)->serviceRevenue();
        $this->assertEquals(2000.0, $revenue['domain']['billed']);
        $this->assertEquals(8000.0, $revenue['hosting']['billed']);
        // 5000 paid split proportionally: 20% domain, 80% hosting.
        $this->assertEquals(1000.0, $revenue['domain']['paid']);
        $this->assertEquals(4000.0, $revenue['hosting']['paid']);
        $this->assertEquals(1000.0, $revenue['domain']['due']);
        $this->assertEquals(4000.0, $revenue['hosting']['due']);
    }

    public function test_reports_and_exports(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->post(route('accounts.invoices.store'), $this->invoicePayload());

        $this->actingAs($admin)->get(route('accounts.reports.index', ['period' => 'monthly']))->assertStatus(200);
        $this->actingAs($admin)->get(route('accounts.reports.index', ['period' => 'custom', 'from' => now()->subMonth()->format('Y-m-d'), 'to' => now()->format('Y-m-d')]))->assertStatus(200);

        $csv = $this->actingAs($admin)->get(route('accounts.reports.export', ['type' => 'csv']));
        $csv->assertStatus(200)->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $excel = $this->actingAs($admin)->get(route('accounts.reports.export', ['type' => 'excel']));
        $excel->assertStatus(200);

        $pdf = $this->actingAs($admin)->get(route('accounts.reports.export', ['type' => 'pdf']));
        $pdf->assertStatus(200);

        $invoice = Invoice::latest('id')->first();
        $this->actingAs($admin)->get(route('accounts.invoices.pdf', $invoice))->assertStatus(200);
        $this->actingAs($admin)->get(route('accounts.invoices.print', $invoice))->assertStatus(200);

        // PDF preview page + inline stream.
        $this->actingAs($admin)->get(route('accounts.invoices.preview', $invoice))
            ->assertStatus(200)
            ->assertSee(route('accounts.invoices.print', $invoice), false)
            ->assertSee(route('accounts.invoices.pdf', $invoice), false);
        $stream = $this->actingAs($admin)->get(route('accounts.invoices.pdf.preview', $invoice))->assertStatus(200);
        $this->assertStringContainsString('application/pdf', $stream->headers->get('Content-Type'));
        $this->assertStringContainsString('inline', $stream->headers->get('Content-Disposition'));
    }

    public function test_client_360_view(): void
    {
        $admin = $this->admin();
        $client = $this->client();
        $this->actingAs($admin)->post(route('accounts.invoices.store'), $this->invoicePayload($client->id));

        $this->actingAs($admin)->get(route('accounts.clients.show', $client))
            ->assertStatus(200)
            ->assertSee($client->name)
            ->assertSee('Total Due');
    }

    public function test_service_can_be_created_inline_via_json(): void
    {
        $admin = $this->admin();

        $response = $this->actingAs($admin)->postJson(route('accounts.services.store'), [
            'name' => '.net Domain',
            'type' => 'domain',
            'default_price' => 1800,
            'description' => 'Domain registration',
            'is_active' => true,
        ]);

        $response->assertStatus(201)->assertJsonPath('service.name', '.net Domain');
        $this->assertDatabaseHas('services', ['name' => '.net Domain']);

        // Member cannot create services.
        $this->actingAs($this->member())->postJson(route('accounts.services.store'), [
            'name' => 'X', 'type' => 'domain', 'default_price' => 10,
        ])->assertStatus(403);
    }

    public function test_invoice_item_description_is_optional_and_defaults_to_service_name(): void
    {
        $admin = $this->admin();
        $service = Service::factory()->domain()->create(['name' => 'Test Domain X']);
        $client = $this->client();

        $payload = $this->invoicePayload($client->id);
        unset($payload['items'][0]['description']);
        $payload['items'][0]['service_id'] = $service->id;

        $this->actingAs($admin)->post(route('accounts.invoices.store'), $payload)->assertRedirect();

        $invoice = Invoice::latest('id')->first();
        $this->assertEquals('Test Domain X', $invoice->items()->first()->description);
    }

    public function test_invoice_form_has_new_client_and_service_options(): void
    {
        $admin = $this->admin();
        $client = Client::factory()->create([
            'name' => 'BADHON', 'phone' => '01778202103',
            'email' => 'jadufahad@gmail.com', 'address' => 'Dhaka',
        ]);
        \App\Models\Task::factory()->create(['client_id' => $client->id]);

        $html = $this->actingAs($admin)->get(route('accounts.invoices.create'))
            ->assertStatus(200)->getContent();

        $this->assertStringContainsString('New Client', $html);
        $this->assertStringContainsString('open-create-service-modal', $html);
        $this->assertStringContainsString('Create Service', $html);
        // Description input must not be mandatory.
        $this->assertStringNotContainsString('items[${i}][description]" required', $html);
        // Task options carry their client; the clients map has full billing fields.
        $this->assertStringContainsString('data-client-id="' . $client->id . '"', $html);
        $this->assertStringContainsString('01778202103', $html);
        $this->assertStringContainsString('jadufahad@gmail.com', $html);
        // Selecting a client syncs the Invoiced To block.
        $this->assertStringContainsString('fillInvoicedTo', $html);
        // Client search component must be intact: its x-data attribute must
        // contain the whole component (a literal double quote inside would
        // truncate the attribute and kill all of Alpine for that component).
        preg_match_all('/x-data="([^"]*)"/', $html, $matches);
        $hasIntactClientSearch = false;
        foreach ($matches[1] as $xdata) {
            if (str_contains($xdata, 'setSelected') && str_contains($xdata, 'searchClients')) {
                $hasIntactClientSearch = true;
            }
        }
        $this->assertTrue($hasIntactClientSearch, 'client-search x-data attribute is truncated');
        $this->assertStringContainsString("document.getElementById('client-search-input-client_id')", $html);
    }

    public function test_client_search_works_for_accountant_and_finds_any_status(): void
    {
        $pending = Client::factory()->create(['name' => 'Pending Client Ltd', 'status' => 'pending']);

        // Accountant (not only admin) can search.
        $this->actingAs($this->accountant())->getJson(route('accounts.clients.search', ['q' => 'Pending Client']))
            ->assertStatus(200)
            ->assertJsonFragment(['id' => $pending->id, 'phone' => $pending->phone]);

        // Member can search too.
        $this->actingAs($this->member())->getJson(route('accounts.clients.search', ['q' => 'Pending Client']))
            ->assertStatus(200)
            ->assertJsonFragment(['id' => $pending->id]);

        // Too short → empty.
        $this->actingAs($this->accountant())->getJson(route('accounts.clients.search', ['q' => 'x']))
            ->assertStatus(200)
            ->assertJson(['results' => []]);
    }

    public function test_invoice_saves_billing_snapshot_with_client_defaults(): void
    {
        $admin = $this->admin();
        $client = Client::factory()->create([
            'name' => 'Acme Corp', 'contact_person' => 'John Doe',
            'phone' => '01700000000', 'email' => 'john@acme.com', 'address' => 'Dhaka',
        ]);

        // Explicit billing details are stored as-is.
        $payload = $this->invoicePayload($client->id);
        $payload['billing_company'] = 'Acme Corp Ltd';
        $payload['billing_country'] = 'Bangladesh';
        $this->actingAs($admin)->post(route('accounts.invoices.store'), $payload)->assertRedirect();
        $invoice = Invoice::latest('id')->first();
        $this->assertEquals('Acme Corp Ltd', $invoice->billing_company);
        $this->assertEquals('John Doe', $invoice->billing_name);
        $this->assertEquals('01700000000', $invoice->billing_phone);
        $this->assertEquals('Bangladesh', $invoice->billing_country);

        // Blank fields fall back to the client record.
        $payload2 = $this->invoicePayload($client->id);
        $this->actingAs($admin)->post(route('accounts.invoices.store'), $payload2)->assertRedirect();
        $invoice2 = Invoice::latest('id')->first();
        $this->assertEquals('Acme Corp', $invoice2->billing_company);
        $this->assertEquals('Dhaka', $invoice2->billing_address);
        $this->assertEquals('john@acme.com', $invoice2->billing_email);
    }

    public function test_company_settings_saved_for_invoice_header(): void
    {
        $this->actingAs($this->admin())->post(route('settings.logo'), [
            'site_title' => 'Test Agency',
            'company_name' => 'Test Agency Ltd',
            'company_address' => 'Banani, Dhaka',
            'company_phone' => '01711111111',
            'company_email' => 'info@test.agency',
        ])->assertRedirect();

        $this->assertEquals('Test Agency Ltd', Setting::get('company_name'));
        $this->assertEquals('Banani, Dhaka', Setting::get('company_address'));
    }

    public function test_expense_period_presets_and_category_breakdown(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 10, 15));
        try {
            $admin = $this->admin();
            Expense::factory()->create(['category' => 'server', 'amount' => 8000, 'expense_date' => '2026-10-15']);
            Expense::factory()->create(['category' => 'ads', 'amount' => 3000, 'expense_date' => '2026-10-05']);
            Expense::factory()->create(['category' => 'server', 'amount' => 5000, 'expense_date' => '2026-09-01']);

            // Default = running month (Oct): 8000 + 3000.
            $html = $this->actingAs($admin)->get(route('accounts.expenses.index'))
                ->assertStatus(200)->getContent();
            $this->assertStringContainsString('Running Month', $html);
            $this->assertStringContainsString('11,000.00', $html);

            // Last 7 days (Oct 9-15): only the 8000 server expense.
            $repo = app(\App\Repositories\Accounts\ExpenseRepository::class);
            $week = $repo->categoryTotals(['from' => '2026-10-09', 'to' => '2026-10-15']);
            $this->assertEquals(8000.0, $week['server'] ?? 0);
            $this->assertArrayNotHasKey('ads', $week);

            $html = $this->actingAs($admin)->get(route('accounts.expenses.index', ['period' => '7days']))
                ->assertStatus(200)->getContent();
            $this->assertStringContainsString('Last 7 Days', $html);
            $this->assertStringContainsString('8,000.00', $html);

            // Last 3 months: everything.
            $all = $repo->categoryTotals(['from' => '2026-07-17', 'to' => '2026-10-15']);
            $this->assertEquals(13000.0, $all['server'] ?? 0);
            $this->assertEquals(3000.0, $all['ads'] ?? 0);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_task_invoices_page_lists_task_invoices(): void
    {
        $admin = $this->admin();
        $client = $this->client();
        $task = \App\Models\Task::factory()->create(['client_id' => $client->id]);

        $payload = $this->invoicePayload($client->id);
        $payload['task_id'] = $task->id;
        $this->actingAs($admin)->post(route('accounts.invoices.store'), $payload)->assertRedirect();
        $invoice = Invoice::latest('id')->first();

        $this->actingAs($admin)->get(route('accounts.orders.invoices', $task))
            ->assertStatus(200)
            ->assertSee($invoice->invoice_number)
            ->assertSee('Total Billed');

        // Task page links to it.
        $this->actingAs($admin)->get(route('tasks.show', $task))
            ->assertStatus(200)
            ->assertSee(route('accounts.orders.invoices', $task));
    }
}
