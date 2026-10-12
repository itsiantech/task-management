{{-- "Create Service" modal for the invoice form. Opened per item-row via open-create-service-modal event. --}}
<div x-data="{ open: false, row: null, name: '', type: 'domain', price: '', desc: '', saving: false, error: '' }"
     @open-create-service-modal.window="open = true; row = ($event.detail && $event.detail.row !== undefined) ? $event.detail.row : null; error = ''"
     x-show="open" class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display: none;">
    <div class="absolute inset-0 bg-slate-900/40" @click="open = false"></div>
    <div class="relative w-full max-w-md rounded-2xl bg-white p-6 shadow-xl" @click.away="open = false">
        <h3 class="text-lg font-semibold text-slate-900">Create Service</h3>
        <p class="mb-4 text-sm text-slate-500">It will be added to the catalog and selected in this row.</p>
        <p x-show="error" x-text="error" class="mb-3 rounded-lg bg-rose-50 px-3 py-2 text-sm text-rose-600"></p>
        <div class="space-y-3">
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">Service Name *</label>
                <input type="text" x-model="name" placeholder="e.g. .com Domain Registration" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm">
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Type *</label>
                    <select x-model="type" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm">
                        <option value="domain">Domain</option>
                        <option value="hosting">Hosting</option>
                        <option value="web_development">Web Development</option>
                        <option value="digital_marketing">Digital Marketing</option>
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Default Price (৳) *</label>
                    <input type="number" x-model="price" min="0" step="0.01" placeholder="0.00" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm">
                </div>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">Description</label>
                <input type="text" x-model="desc" placeholder="Optional" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm">
            </div>
        </div>
        <div class="mt-5 flex justify-end gap-2">
            <button type="button" @click="open = false" class="rounded-xl border border-slate-200 px-4 py-2 text-sm text-slate-600 hover:bg-slate-50">Cancel</button>
            <button type="button" :disabled="saving"
                @click="saving = true; error = '';
                    fetch('{{ route('accounts.services.store') }}', { method: 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }, body: JSON.stringify({ name: name, type: type, default_price: price === '' ? 0 : price, description: desc, is_active: true }) })
                    .then(r => r.json().then(data => ({ ok: r.ok, data })))
                    .then(({ ok, data }) => {
                        saving = false;
                        if (!ok || !data.service) { error = (data && data.message) || 'Could not create service. Name and price are required.'; return; }
                        addServiceOptionToRows(data.service, row);
                        open = false; name = ''; price = ''; desc = '';
                    })
                    .catch(() => { saving = false; error = 'Network error. Please try again.'; })"
                class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800" x-text="saving ? 'Saving...' : 'Create & Select'"></button>
        </div>
    </div>
</div>

<script>
function addServiceOptionToRows(service, row) {
    const label = service.type_label + ': ' + service.name + ' (৳' + Number(service.default_price).toFixed(2) + ')';
    const makeOption = () => {
        const o = document.createElement('option');
        o.value = service.id;
        o.textContent = label;
        o.dataset.price = service.default_price;
        return o;
    };
    // Existing rendered rows.
    document.querySelectorAll('select.service-select').forEach(sel => {
        if (!sel.querySelector('option[value="' + service.id + '"]')) sel.appendChild(makeOption());
    });
    // Future rows (inside the Alpine template fragment).
    document.querySelectorAll('template').forEach(t => {
        t.content.querySelectorAll('select.service-select').forEach(sel => {
            if (!sel.querySelector('option[value="' + service.id + '"]')) sel.appendChild(makeOption());
        });
    });
    // Select it in the triggering row (x-model picks it up via change event).
    if (row !== null && row !== undefined) {
        const target = document.querySelector('select[name="items[' + row + '][service_id]"]');
        if (target) {
            target.value = service.id;
            target.dispatchEvent(new Event('change', { bubbles: true }));
        }
    }
}
</script>
