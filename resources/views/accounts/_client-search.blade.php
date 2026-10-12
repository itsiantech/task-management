{{-- Client Search Dropdown Component --}}
{{-- Usage: @include('accounts._client-search', ['name' => 'client_id', 'selected' => $client]) --}}

<div class="relative" x-data="{
    search: '',
    results: [],
    loading: false,
    selected: {{ $selected ? json_encode(['id' => $selected->id, 'text' => $selected->name . ($selected->contact_person ? " ({$selected->contact_person})" : '') . " - {$selected->phone}"]) : 'null' }},
    open: false,
    setSelected(client) {
        this.selected = client;
        this.search = '';
        this.results = [];
        this.open = false;
        const hidden = document.getElementById('client-search-input-{{ $name }}');
        if (hidden) hidden.value = client ? client.id : '';
        // Keep the invoice Invoiced-To block in sync (no-op on pages without it).
        if (client && typeof window.fillInvoicedTo === 'function') window.fillInvoicedTo(client);
    },
    async searchClients() {
        if (this.search.length < 2) {
            this.results = [];
            return;
        }
        this.loading = true;
        try {
            const response = await fetch('{{ route('accounts.clients.search') }}?q=' + encodeURIComponent(this.search), {
                headers: { 'Accept': 'application/json' }
            });
            const data = await response.json();
            this.results = data.results || [];
        } catch (error) {
            this.results = [];
        }
        this.loading = false;
    },
    selectClient(client) {
        this.setSelected(client);
    },
    clearSelection() {
        this.setSelected(null);
    }
}" x-init="$watch('search', value => { if(value.length >= 2) searchClients(); })"
    @client-selected.window="if ($event.detail.client) setSelected($event.detail.client)">
    <input type="hidden" id="client-search-input-{{ $name }}" name="{{ $name }}" value="{{ $selected?->id ?? '' }}">

    <div class="relative">
        <input
            type="text"
            x-model="search"
            @focus="open = true"
            @click.away="open = false"
            placeholder="Search client by name, phone, or email..."
            class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm focus:border-slate-400 focus:outline-none"
        >

        <div x-show="selected" class="absolute right-10 top-1/2 -translate-y-1/2">
            <button @click="clearSelection()" class="text-slate-400 hover:text-slate-600">
                <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M6 6l12 12M6 18L18 6" stroke-linecap="round"/>
                </svg>
            </button>
        </div>

        <!-- Selected Client Display -->
        <div x-show="selected && !search" class="absolute inset-x-0 top-full mt-1 rounded-xl border border-slate-200 bg-white p-3 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="font-medium text-slate-900" x-text="selected.text"></p>
                </div>
                <button @click="clearSelection()" class="text-rose-500 hover:text-rose-700">Change</button>
            </div>
        </div>

        <!-- Search Results Dropdown -->
        <div x-show="open && (results.length > 0 || loading)" class="absolute inset-x-0 top-full z-10 mt-1 max-h-64 overflow-auto rounded-xl border border-slate-200 bg-white shadow-sm">
            <div x-show="loading" class="px-4 py-3 text-center text-sm text-slate-500">
                Searching...
            </div>
            <div x-show="!loading && results.length === 0" class="px-4 py-3 text-center text-sm text-slate-500">
                No clients found
            </div>
            <template x-for="client in results" :key="client.id">
                <button
                    @click="selectClient(client)"
                    class="w-full px-4 py-2.5 text-left text-sm hover:bg-slate-50"
                    x-text="client.text"
                ></button>
            </template>
        </div>
    </div>

    <!-- Create Client Button -->
    <div x-show="!selected && search.length >= 2 && results.length === 0 && !loading" class="mt-2">
        <button
            type="button"
            @click="$dispatch('open-create-client-modal', { search: search })"
            class="inline-flex items-center gap-2 rounded-lg border border-dashed border-slate-300 bg-slate-50 px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100"
        >
            <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M12 5v14M5 12h14" stroke-linecap="round"/>
            </svg>
            Create new client: <span x-text="search"></span>
        </button>
    </div>
</div>
