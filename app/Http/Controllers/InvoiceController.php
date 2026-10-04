<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\InvoiceTransaction;
use App\Models\Task;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class InvoiceController extends Controller
{
    public function index(): View
    {
        $invoices = Invoice::with(['client', 'task', 'items', 'transactions'])
            ->orderByDesc('invoice_date')
            ->paginate(15);

        return view('invoices.index', compact('invoices'));
    }

    public function create(): View
    {
        $clients = Client::orderBy('name')->get();
        $tasks = Task::orderBy('title')->get();

        return view('invoices.create', compact('clients', 'tasks'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'client_id' => ['required', 'exists:clients,id'],
            'task_id' => ['nullable', 'exists:tasks,id'],
            'status' => ['nullable', 'in:Draft,Paid,Unpaid,Due,Overdue,Cancelled,Pending Payment,Refunded'],
            'invoice_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date'],
            'date_paid' => ['nullable', 'date'],
            'cancelled_date' => ['nullable', 'date'],
            'refunded_date' => ['nullable', 'date'],
            'payment_method' => ['nullable', 'in:cash_in,bkash,nagad,bank'],
            'advance_paid_credit' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array'],
            'items.*.description' => ['required', 'string'],
            'items.*.amount' => ['required', 'numeric', 'min:0'],
            'transactions' => ['nullable', 'array'],
            'transactions.*.transaction_date' => ['nullable', 'date'],
            'transactions.*.gateway' => ['nullable', 'in:cash_in,bkash,nagad,bank'],
            'transactions.*.transaction_id' => ['nullable', 'string'],
            'transactions.*.amount' => ['nullable', 'numeric', 'min:0'],
        ]);

        $items = $request->input('items', []);
        $transactions = $request->input('transactions', []);

        $invoice = DB::transaction(function () use ($validated, $items, $transactions) {
            $invoice = Invoice::create([
                'client_id' => $validated['client_id'],
                'task_id' => $validated['task_id'] ?? null,
                'status' => $validated['status'] ?? 'Draft',
                'invoice_date' => $validated['invoice_date'],
                'due_date' => $validated['due_date'] ?? null,
                'date_paid' => $validated['date_paid'] ?? null,
                'cancelled_date' => $validated['cancelled_date'] ?? null,
                'refunded_date' => $validated['refunded_date'] ?? null,
                'payment_method' => $validated['payment_method'] ?? 'bank',
                'advance_paid_credit' => $validated['advance_paid_credit'] ?? 0,
                'notes' => $validated['notes'] ?? null,
                'published_at' => $validated['published_at'] ?? null,
            ]);

            foreach ($items as $item) {
                if (empty(trim((string) ($item['description'] ?? '')))) {
                    continue;
                }

                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'description' => trim((string) $item['description']),
                    'amount' => (float) ($item['amount'] ?? 0),
                ]);
            }

            foreach ($transactions as $transaction) {
                if ((float) ($transaction['amount'] ?? 0) <= 0) {
                    continue;
                }

                InvoiceTransaction::create([
                    'invoice_id' => $invoice->id,
                    'transaction_date' => $transaction['transaction_date'] ?? now()->toDateString(),
                    'gateway' => $transaction['gateway'] ?? 'bank',
                    'transaction_id' => $transaction['transaction_id'] ?? 'TXN-'.time(),
                    'amount' => (float) ($transaction['amount'] ?? 0),
                ]);
            }

            $invoice->recalculateTotals();

            return $invoice;
        });

        return redirect()->route('invoices.show', $invoice)->with('success', 'Invoice created successfully.');
    }

    public function edit(Invoice $invoice): View
    {
        $invoice->load(['client', 'task', 'items', 'transactions']);
        $clients = Client::orderBy('name')->get();
        $tasks = Task::orderBy('title')->get();

        return view('invoices.edit', compact('invoice', 'clients', 'tasks'));
    }

    public function show(Invoice $invoice)
    {
        $invoice->load(['client', 'task', 'items', 'transactions']);

        return view('invoices.show', compact('invoice'));
    }

    public function update(Request $request, Invoice $invoice)
    {
        $validated = $request->validate([
            'client_id' => ['required', 'exists:clients,id'],
            'task_id' => ['nullable', 'exists:tasks,id'],
            'status' => ['nullable', 'in:Draft,Paid,Unpaid,Due,Overdue,Cancelled,Pending Payment,Refunded'],
            'invoice_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date'],
            'date_paid' => ['nullable', 'date'],
            'cancelled_date' => ['nullable', 'date'],
            'refunded_date' => ['nullable', 'date'],
            'payment_method' => ['nullable', 'in:cash_in,bkash,nagad,bank'],
            'advance_paid_credit' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array'],
            'items.*.description' => ['required', 'string'],
            'items.*.amount' => ['required', 'numeric', 'min:0'],
            'transactions' => ['nullable', 'array'],
            'transactions.*.transaction_date' => ['nullable', 'date'],
            'transactions.*.gateway' => ['nullable', 'in:cash_in,bkash,nagad,bank'],
            'transactions.*.transaction_id' => ['nullable', 'string'],
            'transactions.*.amount' => ['nullable', 'numeric', 'min:0'],
        ]);

        $items = $request->input('items', []);
        $transactions = $request->input('transactions', []);

        DB::transaction(function () use ($invoice, $validated, $items, $transactions) {
            $invoice->update([
                'client_id' => $validated['client_id'],
                'task_id' => $validated['task_id'] ?? null,
                'status' => $validated['status'] ?? $invoice->status,
                'invoice_date' => $validated['invoice_date'],
                'due_date' => $validated['due_date'] ?? null,
                'date_paid' => $validated['date_paid'] ?? null,
                'cancelled_date' => $validated['cancelled_date'] ?? null,
                'refunded_date' => $validated['refunded_date'] ?? null,
                'payment_method' => $validated['payment_method'] ?? $invoice->payment_method,
                'advance_paid_credit' => $validated['advance_paid_credit'] ?? 0,
                'notes' => $validated['notes'] ?? null,
            ]);

            $invoice->items()->delete();
            foreach ($items as $item) {
                if (empty(trim((string) ($item['description'] ?? '')))) {
                    continue;
                }

                $invoice->items()->create([
                    'description' => trim((string) $item['description']),
                    'amount' => (float) ($item['amount'] ?? 0),
                ]);
            }

            $invoice->transactions()->delete();
            foreach ($transactions as $transaction) {
                if ((float) ($transaction['amount'] ?? 0) <= 0) {
                    continue;
                }

                $invoice->transactions()->create([
                    'transaction_date' => $transaction['transaction_date'] ?? now()->toDateString(),
                    'gateway' => $transaction['gateway'] ?? 'bank',
                    'transaction_id' => $transaction['transaction_id'] ?? 'TXN-'.time(),
                    'amount' => (float) ($transaction['amount'] ?? 0),
                ]);
            }

            $invoice->recalculateTotals();
        });

        return redirect()->route('invoices.show', $invoice)->with('success', 'Invoice updated successfully.');
    }

    public function publish(Invoice $invoice)
    {
        if (empty($invoice->published_at)) {
            $invoice->published_at = now();
        }

        if ($invoice->status === 'Draft') {
            $invoice->status = $invoice->total_due > 0 ? 'Unpaid' : 'Paid';
        }

        $invoice->save();

        return redirect()->route('invoices.show', $invoice)->with('success', 'Invoice published successfully.');
    }

    public function print(Invoice $invoice)
    {
        $invoice->load(['client', 'task', 'items', 'transactions']);

        return view('invoices.print', compact('invoice'));
    }

    public function downloadPdf(Invoice $invoice)
    {
        $invoice->load(['client', 'task', 'items', 'transactions']);

        $pdf = Pdf::loadView('invoices.print', ['invoice' => $invoice])
            ->setPaper('a4', 'portrait');

        return $pdf->download('invoice-'.$invoice->invoice_number.'.pdf');
    }
}
