{{-- Shared invoice form. Expects: $clients, $tasks, $services, $methods, $invoice? --}}
@php $items = old('items', isset($invoice) ? $invoice->items->map(fn ($i) => ['service_id' => $i->service_id, 'description' => $i->description, 'qty' => $i->qty, 'unit_price' => $i->unit_price, 'discount' => $i->discount, 'tax' => $i->tax])->all() : [['service_id' => '', 'description' => '', 'qty' => 1, 'unit_price' => '', 'discount' => 0, 'tax' => 0]]); @endphp

<div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
    <div class="space-y-6 lg:col-span-2">
        <!-- Client + order -->
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="mb-4 text-lg font-semibold text-slate-900">Client & Order</h2>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label class="mb-1 block text-sm font-medium text-slate-700">Client <span class="text-rose-500">*</span></label>
                    @include('accounts._client-search', ['name' => 'client_id', 'selected' => old('client_id') ? $clients->firstWhere('id', old('client_id')) : ($invoice->client ?? null)])
                    @error('client_id')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                    <p class="mt-1 text-xs text-slate-500">Client not in the list? Search, then click "Create new client". A client is never auto-created from an invoice.</p>
                    <button type="button" onclick="openCreateClientModal()" class="mt-2 inline-flex items-center gap-2 rounded-lg border border-dashed border-slate-300 bg-slate-50 px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100">
                        <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14" stroke-linecap="round"/></svg>
                        New Client
                    </button>
                </div>
                <div class="sm:col-span-2">
                    <label for="task_id" class="mb-1 block text-sm font-medium text-slate-700">Linked Order / Task (optional)</label>
                    <select name="task_id" id="task_id" onchange="onInvoiceTaskChange(this)" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm">
                        <option value="" data-client-id="">— No linked order —</option>
                        @foreach($tasks as $task)
                            <option value="{{ $task->id }}" data-client-id="{{ $task->client_id ?? '' }}" {{ (string)old('task_id', $invoice->task_id ?? '') === (string)$task->id ? 'selected' : '' }}>{{ $task->order_number_label ?? ('#' . $task->id) }} — {{ $task->title }}</option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-slate-500">Selecting an order auto-fills the client and billing details.</p>
                </div>
            </div>
        </div>

        <!-- Invoiced To -->
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="mb-1 text-lg font-semibold text-slate-900">Invoiced To</h2>
            <p class="mb-4 text-xs text-slate-500">Auto-filled from the client. You can adjust it per invoice — it is saved as a snapshot.</p>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label for="billing_company" class="mb-1 block text-sm font-medium text-slate-700">Company Name</label>
                    <input type="text" name="billing_company" id="billing_company" value="{{ old('billing_company', $invoice->billing_company ?? '') }}" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm">
                </div>
                <div>
                    <label for="billing_name" class="mb-1 block text-sm font-medium text-slate-700">Client Name</label>
                    <input type="text" name="billing_name" id="billing_name" value="{{ old('billing_name', $invoice->billing_name ?? '') }}" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm">
                </div>
                <div class="sm:col-span-2">
                    <label for="billing_address" class="mb-1 block text-sm font-medium text-slate-700">Address</label>
                    <input type="text" name="billing_address" id="billing_address" value="{{ old('billing_address', $invoice->billing_address ?? '') }}" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm">
                </div>
                <div>
                    <label for="billing_phone" class="mb-1 block text-sm font-medium text-slate-700">Phone</label>
                    <input type="text" name="billing_phone" id="billing_phone" value="{{ old('billing_phone', $invoice->billing_phone ?? '') }}" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm">
                </div>
                <div>
                    <label for="billing_email" class="mb-1 block text-sm font-medium text-slate-700">Email</label>
                    <input type="email" name="billing_email" id="billing_email" value="{{ old('billing_email', $invoice->billing_email ?? '') }}" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm">
                </div>
                <div class="sm:col-span-2">
                    <label for="billing_country" class="mb-1 block text-sm font-medium text-slate-700">Country</label>
                    <input type="text" name="billing_country" id="billing_country" value="{{ old('billing_country', $invoice->billing_country ?? 'Bangladesh') }}" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm">
                </div>
            </div>
        </div>

        <!-- Items -->
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm" x-data='invoiceItems({{ json_encode($items) }})'>
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-lg font-semibold text-slate-900">Items</h2>
                <button type="button" @click="addRow()" class="rounded-xl border border-slate-200 px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-50">+ Add Item</button>
            </div>
            @error('items')<p class="mb-3 text-sm text-rose-600">{{ $message }}</p>@enderror
            <div class="space-y-3">
                <template x-for="(item, i) in rows" :key="i">
                    <div class="grid grid-cols-12 gap-2 rounded-xl border border-slate-100 bg-slate-50/60 p-3">
                        <div class="col-span-12 sm:col-span-4">
                            <div class="flex gap-1">
                                <select :name="`items[${i}][service_id]`" x-model="item.service_id" @change="const o = $event.target.selectedOptions[0]; const p = o ? Number(o.dataset.price || 0) : 0; if (p > 0 && !Number(item.unit_price)) { item.unit_price = p; }" class="service-select w-full rounded-lg border border-slate-200 px-2 py-2 text-sm">
                                    <option value="">— Service —</option>
                                    @foreach($services as $service)
                                        <option value="{{ $service->id }}" data-price="{{ $service->default_price }}">{{ $service->type_label }}: {{ $service->name }} (৳{{ number_format($service->default_price, 2) }})</option>
                                    @endforeach
                                </select>
                                <button type="button" @click="$dispatch('open-create-service-modal', { row: i })" title="Create new service" class="shrink-0 rounded-lg border border-dashed border-slate-300 px-2.5 text-slate-500 hover:bg-slate-100 hover:text-slate-800">+</button>
                            </div>
                        </div>
                        <div class="col-span-12 sm:col-span-8">
                            <input type="text" :name="`items[${i}][description]`" x-model="item.description" placeholder="Description (optional — service name is used if blank)" class="w-full rounded-lg border border-slate-200 px-2 py-2 text-sm">
                        </div>
                        <div class="col-span-3"><input type="number" :name="`items[${i}][qty]`" x-model.number="item.qty" min="1" placeholder="Qty" required class="w-full rounded-lg border border-slate-200 px-2 py-2 text-sm"></div>
                        <div class="col-span-3"><input type="number" :name="`items[${i}][unit_price]`" x-model.number="item.unit_price" min="0" step="0.01" placeholder="Unit ৳" required class="w-full rounded-lg border border-slate-200 px-2 py-2 text-sm"></div>
                        <div class="col-span-2"><input type="number" :name="`items[${i}][discount]`" x-model.number="item.discount" min="0" step="0.01" placeholder="Disc." class="w-full rounded-lg border border-slate-200 px-2 py-2 text-sm"></div>
                        <div class="col-span-2"><input type="number" :name="`items[${i}][tax]`" x-model.number="item.tax" min="0" step="0.01" placeholder="Tax" class="w-full rounded-lg border border-slate-200 px-2 py-2 text-sm"></div>
                        <div class="col-span-2 flex items-center justify-between gap-1">
                            <span class="text-sm font-semibold text-slate-900" x-text="'৳' + lineTotal(item).toFixed(2)"></span>
                            <button type="button" @click="removeRow(i)" x-show="rows.length > 1" class="text-rose-500 hover:text-rose-700">✕</button>
                        </div>
                    </div>
                </template>
            </div>
            <div class="mt-4 space-y-1 border-t border-slate-100 pt-3 text-sm">
                <div class="flex justify-between"><span class="text-slate-500">Subtotal</span><span class="font-medium" x-text="'৳' + subtotal().toFixed(2)"></span></div>
                <div class="flex justify-between"><span class="text-slate-500">Advance credit</span><span class="font-medium" x-text="'৳' + credit().toFixed(2)"></span></div>
                <div class="flex justify-between text-base font-semibold"><span>Total</span><span x-text="'৳' + total().toFixed(2)"></span></div>
            </div>
        </div>

        <!-- Notes -->
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <label for="notes" class="mb-1 block text-sm font-medium text-slate-700">Notes</label>
            <textarea name="notes" id="notes" rows="2" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm" placeholder="Payment terms, bank details...">{{ old('notes', $invoice->notes ?? '') }}</textarea>
        </div>
    </div>

    <div class="space-y-6">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="mb-4 text-lg font-semibold text-slate-900">Invoice Details</h2>
            <div class="space-y-4">
                <div>
                    <label for="invoice_date" class="mb-1 block text-sm font-medium text-slate-700">Invoice Date <span class="text-rose-500">*</span></label>
                    <input type="date" name="invoice_date" id="invoice_date" value="{{ old('invoice_date', isset($invoice) ? $invoice->invoice_date?->format('Y-m-d') : now()->format('Y-m-d')) }}" required class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm">
                    @error('invoice_date')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="due_date" class="mb-1 block text-sm font-medium text-slate-700">Due Date</label>
                    <input type="date" name="due_date" id="due_date" value="{{ old('due_date', isset($invoice) ? $invoice->due_date?->format('Y-m-d') : '') }}" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm">
                    @error('due_date')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="payment_method" class="mb-1 block text-sm font-medium text-slate-700">Preferred Method</label>
                    <select name="payment_method" id="payment_method" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm">
                        <option value="">—</option>
                        @foreach($methods as $method)
                            <option value="{{ $method->value }}" {{ old('payment_method', $invoice->payment_method ?? '') === $method->value ? 'selected' : '' }}>{{ $method->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="advance_paid_credit" class="mb-1 block text-sm font-medium text-slate-700">Advance Paid Credit (৳)</label>
                    <input type="number" name="advance_paid_credit" id="advance_paid_credit" value="{{ old('advance_paid_credit', $invoice->advance_paid_credit ?? 0) }}" min="0" step="0.01" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm">
                </div>
                @if(!isset($invoice))
                    <label class="flex items-center gap-2 text-sm text-slate-700">
                        <input type="checkbox" name="publish" value="1" checked class="h-4 w-4 rounded border-slate-300">
                        Publish immediately (else saved as Draft)
                    </label>
                @else
                    <p class="text-sm text-slate-500">Status: <span class="font-medium text-slate-900">{{ $invoice->status_enum?->label() ?? ucfirst($invoice->status) }}</span></p>
                @endif
            </div>
        </div>

        <div class="flex flex-col gap-2">
            <button type="submit" class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-medium text-white hover:bg-slate-800">{{ isset($invoice) ? 'Update Invoice' : 'Create Invoice' }}</button>
            <a href="{{ isset($invoice) ? route('accounts.invoices.show', $invoice) : route('accounts.invoices.index') }}" class="rounded-xl border border-slate-200 px-4 py-2.5 text-center text-sm font-medium text-slate-600 hover:bg-slate-50">Cancel</a>
        </div>
    </div>
</div>

@include('accounts._create-client-modal')
@include('accounts._create-service-modal')

<script>
const invoiceClientsMap = @json($clientsMap ?? []);
function fillInvoicedTo(client) {
    if (!client) return;
    const set = (id, value) => { const el = document.getElementById(id); if (el) el.value = value || ''; };
    set('billing_company', client.name);
    set('billing_name', client.contact_person);
    set('billing_address', client.address);
    set('billing_phone', client.phone);
    set('billing_email', client.email);
    const country = document.getElementById('billing_country');
    if (country && !country.value) country.value = 'Bangladesh';
}
window.addEventListener('client-selected', function (e) {
    if (e.detail && e.detail.client) fillInvoicedTo(e.detail.client);
});
function onInvoiceTaskChange(sel) {
    const opt = sel.selectedOptions ? sel.selectedOptions[0] : null;
    const clientId = opt ? opt.dataset.clientId : '';
    if (!clientId) return;
    const client = invoiceClientsMap[clientId] || invoiceClientsMap[String(clientId)];
    if (client) {
        window.dispatchEvent(new CustomEvent('client-selected', { detail: { client: client } }));
    }
}
// After validation errors / back-navigation: a preselected client with
// empty billing fields gets its billing details filled on load.
document.addEventListener('DOMContentLoaded', function () {
    const hidden = document.querySelector('input[name="client_id"]');
    const company = document.getElementById('billing_company');
    if (hidden && hidden.value && company && !company.value) {
        const client = invoiceClientsMap[hidden.value] || invoiceClientsMap[String(hidden.value)];
        if (client) fillInvoicedTo(client);
    }
});
function openCreateClientModal() {
    const input = document.querySelector('input[placeholder^="Search client"]');
    window.dispatchEvent(new CustomEvent('open-create-client-modal', { detail: { search: input ? input.value : '' } }));
}
function invoiceItems(initial) {
    return {
        rows: initial.map(r => ({ service_id: r.service_id ?? '', description: r.description ?? '', qty: Number(r.qty) || 1, unit_price: Number(r.unit_price) || 0, discount: Number(r.discount) || 0, tax: Number(r.tax) || 0 })),
        lineTotal(item) { return Math.max(0, (Number(item.qty) || 0) * (Number(item.unit_price) || 0) - (Number(item.discount) || 0)) + (Number(item.tax) || 0); },
        subtotal() { return this.rows.reduce((s, r) => s + this.lineTotal(r), 0); },
        credit() { const el = document.getElementById('advance_paid_credit'); return el ? (Number(el.value) || 0) : 0; },
        total() { return Math.max(0, this.subtotal() - this.credit()); },
        addRow() { this.rows.push({ service_id: '', description: '', qty: 1, unit_price: 0, discount: 0, tax: 0 }); },
        removeRow(i) { this.rows.splice(i, 1); },
    };
}
</script>
