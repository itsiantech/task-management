{{-- "Create Client" modal. Opened by accounts._client-search via open-create-client-modal event. --}}
<div x-data="{ open: false, name: '', phone: '', email: '', saving: false, error: '' }"
     @open-create-client-modal.window="open = true; name = $event.detail.search || ''; error = ''"
     x-show="open" class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display: none;">
    <div class="absolute inset-0 bg-slate-900/40" @click="open = false"></div>
    <div class="relative w-full max-w-md rounded-2xl bg-white p-6 shadow-xl" @click.away="open = false">
        <h3 class="text-lg font-semibold text-slate-900">Create Client</h3>
        <p class="mb-4 text-sm text-slate-500">Add a new client, then select it on the invoice.</p>
        <p x-show="error" x-text="error" class="mb-3 rounded-lg bg-rose-50 px-3 py-2 text-sm text-rose-600"></p>
        <div class="space-y-3">
            <input type="text" x-model="name" placeholder="Company / client name *" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm">
            <input type="text" x-model="phone" placeholder="Phone *" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm">
            <input type="email" x-model="email" placeholder="Email (optional)" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm">
        </div>
        <div class="mt-5 flex justify-end gap-2">
            <button type="button" @click="open = false" class="rounded-xl border border-slate-200 px-4 py-2 text-sm text-slate-600 hover:bg-slate-50">Cancel</button>
            <button type="button" :disabled="saving"
                @click="saving = true; error = '';
                    fetch('{{ route('clients.store') }}', { method: 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }, body: JSON.stringify({ name: name, phone: phone, email: email, status: 'confirm' }) })
                    .then(r => r.json().then(data => ({ ok: r.ok, data })))
                    .then(({ ok, data }) => {
                        saving = false;
                        if (!ok) { error = data.message || 'Could not create client. Name and phone are required.'; return; }
                        const client = data.client;
                        const text = client.name + (client.contact_person ? ' (' + client.contact_person + ')' : '') + ' - ' + client.phone;
                        window.dispatchEvent(new CustomEvent('client-selected', { detail: { client: { id: client.id, text: text, name: client.name, contact_person: client.contact_person, phone: client.phone, email: client.email, address: client.address } } }));
                        open = false; name = ''; phone = ''; email = '';
                    })
                    .catch(() => { saving = false; error = 'Network error. Please try again.'; })"
                class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800" x-text="saving ? 'Saving...' : 'Create & Select'"></button>
        </div>
    </div>
</div>
