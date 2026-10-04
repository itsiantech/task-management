@php
    $clientData = $clients->map(function ($client) {
        return [
            'id' => $client->id,
            'name' => $client->name,
            'company_name' => $client->name,
            'address' => $client->address ?? '',
            'phone' => $client->phone ?? '',
            'email' => $client->email ?? '',
            'country' => 'Bangladesh',
        ];
    })->values()->all();

    $taskData = $tasks->map(function ($task) {
        return [
            'id' => $task->id,
            'title' => $task->title,
            'client_id' => $task->client_id ?? null,
        ];
    })->values()->all();
@endphp

@extends('layouts.app')

@section('title', 'Create Invoice')

@section('content')
    <div class="mx-auto max-w-7xl space-y-6">
        <div class="flex flex-col gap-4 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm md:flex-row md:items-center md:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Finance</p>
                <h1 class="mt-2 text-3xl font-bold text-slate-900">Create Invoice</h1>
            </div>
            <a href="{{ route('invoices.index') }}" class="inline-flex items-center justify-center rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                Back to Invoices
            </a>
        </div>

        @if ($errors->any())
            <div class="rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700">
                <ul class="list-disc space-y-1 pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('invoices.store') }}" method="POST" id="invoiceForm">
            @csrf

            <div class="grid gap-6 xl:grid-cols-[1.2fr_0.8fr]">
                <div class="space-y-6">
                    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                        <div class="mb-5 flex items-center justify-between">
                            <h2 class="text-lg font-semibold text-slate-900">Client & Task</h2>
                            <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[10px] font-semibold uppercase tracking-[0.2em] text-slate-600">Auto-fill</span>
                        </div>

                        <div class="grid gap-5 md:grid-cols-2">
                            <div>
                                <label for="client_id" class="mb-2 block text-sm font-medium text-slate-700">Client</label>
                                <select id="client_id" name="client_id" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-800 focus:border-slate-400 focus:outline-none">
                                    <option value="">Select client</option>
                                    @foreach ($clients as $client)
                                        <option value="{{ $client->id }}">{{ $client->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label for="task_id" class="mb-2 block text-sm font-medium text-slate-700">Task (optional)</label>
                                <select id="task_id" name="task_id" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-800 focus:border-slate-400 focus:outline-none">
                                    <option value="">Select related task</option>
                                    @foreach ($tasks as $task)
                                        <option value="{{ $task->id }}">{{ $task->title }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                        <div class="mb-5 flex items-center justify-between">
                            <h2 class="text-lg font-semibold text-slate-900">Invoiced To</h2>
                            <img src="{{ asset('images/itsian-logo.svg') }}" alt="ITSIAN" class="h-9 w-auto" />
                        </div>

                        <div class="grid gap-5 md:grid-cols-2">
                            <div>
                                <label for="client_name" class="mb-2 block text-sm font-medium text-slate-700">Client Name</label>
                                <input id="client_name" name="client_name" type="text" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm text-slate-800 focus:border-slate-400 focus:outline-none" />
                            </div>
                            <div>
                                <label for="company_name" class="mb-2 block text-sm font-medium text-slate-700">Company Name</label>
                                <input id="company_name" name="company_name" type="text" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm text-slate-800 focus:border-slate-400 focus:outline-none" />
                            </div>
                            <div class="md:col-span-2">
                                <label for="client_address" class="mb-2 block text-sm font-medium text-slate-700">Address</label>
                                <textarea id="client_address" name="client_address" rows="3" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm text-slate-800 focus:border-slate-400 focus:outline-none"></textarea>
                            </div>
                            <div>
                                <label for="client_phone" class="mb-2 block text-sm font-medium text-slate-700">Phone</label>
                                <input id="client_phone" name="client_phone" type="text" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm text-slate-800 focus:border-slate-400 focus:outline-none" />
                            </div>
                            <div>
                                <label for="client_email" class="mb-2 block text-sm font-medium text-slate-700">Email</label>
                                <input id="client_email" name="client_email" type="email" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm text-slate-800 focus:border-slate-400 focus:outline-none" />
                            </div>
                            <div>
                                <label for="client_country" class="mb-2 block text-sm font-medium text-slate-700">Country</label>
                                <input id="client_country" name="client_country" type="text" value="Bangladesh" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm text-slate-800 focus:border-slate-400 focus:outline-none" />
                            </div>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                        <div class="mb-5 flex items-center justify-between">
                            <h2 class="text-lg font-semibold text-slate-900">Items</h2>
                            <button type="button" id="addItemBtn" class="rounded-xl bg-slate-900 px-3 py-2 text-sm font-semibold text-white hover:bg-slate-700">Add Item</button>
                        </div>

                        <div id="itemRows" class="space-y-3">
                            <div class="item-row grid gap-3 rounded-xl border border-slate-200 bg-slate-50 p-3 md:grid-cols-[1.5fr_0.7fr_120px]">
                                <input type="text" name="items[0][description]" placeholder="Description" class="item-description rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-800 focus:border-slate-400 focus:outline-none" required>
                                <input type="number" step="0.01" min="0" name="items[0][amount]" placeholder="0.00" class="item-amount rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-800 focus:border-slate-400 focus:outline-none" required>
                                <button type="button" data-remove-item class="rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-sm font-medium text-rose-700 hover:bg-rose-100">Delete</button>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                        <div class="mb-5 flex items-center justify-between">
                            <h2 class="text-lg font-semibold text-slate-900">Transactions</h2>
                            <button type="button" id="addTransactionBtn" class="rounded-xl bg-emerald-600 px-3 py-2 text-sm font-semibold text-white hover:bg-emerald-500">Add Payment</button>
                        </div>

                        <div id="transactionRows" class="space-y-3">
                            <div class="transaction-row grid gap-3 rounded-xl border border-slate-200 bg-slate-50 p-3 md:grid-cols-[1fr_1fr_1fr_0.8fr_120px]">
                                <input type="date" name="transactions[0][transaction_date]" value="{{ now()->toDateString() }}" class="transaction-date rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-800 focus:border-slate-400 focus:outline-none">
                                <select name="transactions[0][gateway]" class="transaction-gateway rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-800 focus:border-slate-400 focus:outline-none">
                                    <option value="cash_in">Cash</option>
                                    <option value="bkash">bKash</option>
                                    <option value="nagad">Nagad</option>
                                    <option value="bank">Bank</option>
                                </select>
                                <input type="text" name="transactions[0][transaction_id]" placeholder="Transaction ID" class="transaction-id rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-800 focus:border-slate-400 focus:outline-none">
                                <input type="number" step="0.01" min="0" name="transactions[0][amount]" value="0" class="transaction-amount rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-800 focus:border-slate-400 focus:outline-none">
                                <button type="button" data-remove-transaction class="rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-sm font-medium text-rose-700 hover:bg-rose-100">Delete</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="space-y-6">
                    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                        <h2 class="text-lg font-semibold text-slate-900">Invoice Details</h2>

                        <div class="mt-5 space-y-4">
                            <div>
                                <label for="invoice_date" class="mb-2 block text-sm font-medium text-slate-700">Invoice Date</label>
                                <input type="date" id="invoice_date" name="invoice_date" value="{{ now()->toDateString() }}" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm text-slate-800 focus:border-slate-400 focus:outline-none" required>
                            </div>

                            <div>
                                <label for="due_date" class="mb-2 block text-sm font-medium text-slate-700">Due Date</label>
                                <input type="date" id="due_date" name="due_date" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm text-slate-800 focus:border-slate-400 focus:outline-none">
                            </div>

                            <div>
                                <label for="status" class="mb-2 block text-sm font-medium text-slate-700">Status</label>
                                <select id="status" name="status" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm text-slate-800 focus:border-slate-400 focus:outline-none">
                                    <option value="Draft">Draft</option>
                                    <option value="Paid">Paid</option>
                                    <option value="Unpaid">Unpaid</option>
                                    <option value="Due">Due</option>
                                    <option value="Overdue">Overdue</option>
                                    <option value="Cancelled">Cancelled</option>
                                    <option value="Pending Payment">Pending Payment</option>
                                    <option value="Refunded">Refunded</option>
                                </select>
                            </div>

                            <div>
                                <label for="payment_method" class="mb-2 block text-sm font-medium text-slate-700">Payment Method</label>
                                <select id="payment_method" name="payment_method" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm text-slate-800 focus:border-slate-400 focus:outline-none">
                                    <option value="cash_in">Cash</option>
                                    <option value="bkash">bKash</option>
                                    <option value="nagad">Nagad</option>
                                    <option value="bank">Bank</option>
                                </select>
                            </div>

                            <div>
                                <label for="notes" class="mb-2 block text-sm font-medium text-slate-700">Notes</label>
                                <textarea id="notes" name="notes" rows="4" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm text-slate-800 focus:border-slate-400 focus:outline-none" placeholder="Optional notes for this invoice"></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                        <h2 class="text-lg font-semibold text-slate-900">Summary</h2>

                        <div class="mt-5 space-y-4 text-sm text-slate-700">
                            <div class="flex items-center justify-between">
                                <span>Sub Total</span>
                                <input id="sub_total" name="sub_total" type="number" step="0.01" value="0" readonly class="w-28 rounded-lg border border-slate-200 bg-slate-50 px-2 py-1 text-right text-sm font-semibold text-slate-900">
                            </div>
                            <div class="flex items-center justify-between">
                                <span>Credit / Advance</span>
                                <input id="advance_paid_credit" name="advance_paid_credit" type="number" step="0.01" min="0" value="0" class="w-28 rounded-lg border border-slate-200 px-2 py-1 text-right text-sm text-slate-900 focus:border-slate-400 focus:outline-none">
                            </div>
                            <div class="flex items-center justify-between border-t border-slate-200 pt-3">
                                <span class="font-semibold text-slate-900">Total</span>
                                <input id="total_amount" name="total_amount" type="number" step="0.01" value="0" readonly class="w-28 rounded-lg border border-slate-200 bg-slate-50 px-2 py-1 text-right text-sm font-semibold text-slate-900">
                            </div>
                            <div class="flex items-center justify-between border-t border-slate-200 pt-3">
                                <span class="font-semibold text-slate-900">Balance</span>
                                <input id="total_due" name="total_due" type="number" step="0.01" value="0" readonly class="w-28 rounded-lg border border-slate-200 bg-slate-50 px-2 py-1 text-right text-sm font-semibold text-slate-900">
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" class="rounded-xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white shadow-sm hover:bg-slate-700">Save Invoice</button>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <script>
        const clientData = @json($clientData);
        const taskData = @json($taskData);

        const itemRows = document.getElementById('itemRows');
        const transactionRows = document.getElementById('transactionRows');
        const addItemBtn = document.getElementById('addItemBtn');
        const addTransactionBtn = document.getElementById('addTransactionBtn');

        const clientSelect = document.getElementById('client_id');
        const taskSelect = document.getElementById('task_id');

        function fillClientFields(client) {
            document.getElementById('client_name').value = client?.name ?? '';
            document.getElementById('company_name').value = client?.company_name ?? client?.name ?? '';
            document.getElementById('client_address').value = client?.address ?? '';
            document.getElementById('client_phone').value = client?.phone ?? '';
            document.getElementById('client_email').value = client?.email ?? '';
            document.getElementById('client_country').value = client?.country ?? 'Bangladesh';
        }

        function bindAmountListeners(row) {
            const amountInput = row.querySelector('.item-amount, .transaction-amount');
            if (amountInput) {
                amountInput.addEventListener('input', recalculateInvoiceTotals);
                amountInput.addEventListener('change', recalculateInvoiceTotals);
            }
        }

        clientSelect.addEventListener('change', function () {
            const selectedClient = clientData.find((client) => Number(client.id) === Number(this.value));
            fillClientFields(selectedClient || null);
        });

        taskSelect.addEventListener('change', function () {
            const selectedTask = taskData.find((task) => Number(task.id) === Number(this.value));

            if (!selectedTask) {
                return;
            }

            if (selectedTask.client_id) {
                clientSelect.value = selectedTask.client_id;
                const selectedClient = clientData.find((client) => Number(client.id) === Number(selectedTask.client_id));
                fillClientFields(selectedClient || null);
            }
        });

        function getNextIndex(container, prefix) {
            return container.querySelectorAll('.' + prefix + '-row').length;
        }

        function addItemRow(description = '', amount = '') {
            const index = getNextIndex(itemRows, 'item');
            const row = document.createElement('div');
            row.className = 'item-row grid gap-3 rounded-xl border border-slate-200 bg-slate-50 p-3 md:grid-cols-[1.5fr_0.7fr_120px]';
            row.innerHTML = `
                <input type="text" name="items[${index}][description]" value="${description}" placeholder="Description" class="item-description rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-800 focus:border-slate-400 focus:outline-none" required>
                <input type="number" step="0.01" min="0" name="items[${index}][amount]" value="${amount}" placeholder="0.00" class="item-amount rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-800 focus:border-slate-400 focus:outline-none" required>
                <button type="button" data-remove-item class="rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-sm font-medium text-rose-700 hover:bg-rose-100">Delete</button>
            `;
            itemRows.appendChild(row);
            bindAmountListeners(row);
            row.querySelector('.item-description').addEventListener('input', recalculateInvoiceTotals);
        }

        function addTransactionRow(date = '', gateway = 'bank', transactionId = '', amount = '0') {
            const index = getNextIndex(transactionRows, 'transaction');
            const row = document.createElement('div');
            row.className = 'transaction-row grid gap-3 rounded-xl border border-slate-200 bg-slate-50 p-3 md:grid-cols-[1fr_1fr_1fr_0.8fr_120px]';
            row.innerHTML = `
                <input type="date" name="transactions[${index}][transaction_date]" value="${date || new Date().toISOString().slice(0, 10)}" class="transaction-date rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-800 focus:border-slate-400 focus:outline-none">
                <select name="transactions[${index}][gateway]" class="transaction-gateway rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-800 focus:border-slate-400 focus:outline-none">
                    <option value="cash_in" ${gateway === 'cash_in' ? 'selected' : ''}>Cash</option>
                    <option value="bkash" ${gateway === 'bkash' ? 'selected' : ''}>bKash</option>
                    <option value="nagad" ${gateway === 'nagad' ? 'selected' : ''}>Nagad</option>
                    <option value="bank" ${gateway === 'bank' ? 'selected' : ''}>Bank</option>
                </select>
                <input type="text" name="transactions[${index}][transaction_id]" value="${transactionId}" placeholder="Transaction ID" class="transaction-id rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-800 focus:border-slate-400 focus:outline-none">
                <input type="number" step="0.01" min="0" name="transactions[${index}][amount]" value="${amount}" class="transaction-amount rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-800 focus:border-slate-400 focus:outline-none">
                <button type="button" data-remove-transaction class="rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-sm font-medium text-rose-700 hover:bg-rose-100">Delete</button>
            `;
            transactionRows.appendChild(row);
            bindAmountListeners(row);
        }

        addItemBtn.addEventListener('click', () => addItemRow('', ''));
        addTransactionBtn.addEventListener('click', () => addTransactionRow('', 'bank', '', '0'));

        document.addEventListener('click', function (event) {
            if (event.target.matches('[data-remove-item]')) {
                event.target.closest('.item-row').remove();
                recalculateInvoiceTotals();
            }

            if (event.target.matches('[data-remove-transaction]')) {
                event.target.closest('.transaction-row').remove();
                recalculateInvoiceTotals();
            }
        });

        function parseNumber(value) {
            const sanitized = String(value ?? '').replace(/,/g, '').trim();
            return Number.isFinite(Number(sanitized)) ? Number(sanitized) : 0;
        }

        function recalculateInvoiceTotals() {
            let subtotal = 0;
            document.querySelectorAll('.item-amount').forEach((input) => {
                subtotal += parseNumber(input.value);
            });

            let credit = parseNumber(document.getElementById('advance_paid_credit').value);
            let total = Math.max(0, subtotal - credit);

            let transactionTotal = 0;
            document.querySelectorAll('.transaction-amount').forEach((input) => {
                transactionTotal += parseNumber(input.value);
            });

            let balance = Math.max(0, total - transactionTotal);

            document.getElementById('sub_total').value = subtotal.toFixed(2);
            document.getElementById('total_amount').value = total.toFixed(2);
            document.getElementById('total_due').value = balance.toFixed(2);
        }

        document.getElementById('advance_paid_credit').addEventListener('input', recalculateInvoiceTotals);
        document.getElementById('advance_paid_credit').addEventListener('change', recalculateInvoiceTotals);
        document.addEventListener('input', function (event) {
            if (event.target.matches('.item-amount, .transaction-amount')) {
                recalculateInvoiceTotals();
            }
        });

        document.querySelectorAll('.item-amount, .transaction-amount').forEach((input) => {
            bindAmountListeners(input.closest('.item-row, .transaction-row'));
        });

        if (clientSelect.value) {
            const selectedClient = clientData.find((client) => Number(client.id) === Number(clientSelect.value));
            fillClientFields(selectedClient || null);
        }

        addItemRow('Website design package', '2500.00');
        addItemRow('Development support', '1200.00');
        recalculateInvoiceTotals();
    </script>
@endsection
