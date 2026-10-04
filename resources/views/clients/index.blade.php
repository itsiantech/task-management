@extends('layouts.app')

@section('title', 'Client Management')

@section('content')
<div class="mx-auto max-w-7xl">
    <div class="mb-8 flex flex-col gap-4 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:flex-row md:items-center md:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-indigo-600">CRM</p>
            <h1 class="text-2xl font-bold text-slate-900">Client Management</h1>
        </div>
    </div>

    <div class="mb-6 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <form method="GET" action="{{ route('clients.index') }}" class="w-full max-w-md">
            <label for="clientSearch" class="mb-1 block text-sm font-medium text-slate-700">Search clients</label>
            <div class="flex items-center gap-2">
                <input id="clientSearch" name="search" value="{{ $search ?? '' }}" type="search" placeholder="Search by name, phone, or email" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-slate-800 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200">
                <input type="hidden" name="per_page" value="{{ $perPage ?? 10 }}">
                @if(($search ?? '') !== '')
                    <a href="{{ route('clients.index', ['per_page' => $perPage ?? 10]) }}" class="whitespace-nowrap text-sm font-medium text-slate-600 hover:text-slate-800">Clear</a>
                @endif
            </div>
        </form>

        <div class="flex items-center gap-3">
            <div class="flex items-center gap-2">
                <label for="perPage" class="text-sm font-medium text-slate-700">Rows</label>
                <select id="perPage" class="rounded-lg border border-slate-300 bg-white px-2.5 py-2 text-sm text-slate-800 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200">
                    @foreach ([10, 20, 50, 100] as $pageSize)
                        <option value="{{ $pageSize }}" @selected(($perPage ?? 10) == $pageSize)>{{ $pageSize }}</option>
                    @endforeach
                </select>
            </div>

            <button type="button" id="openImportModal" class="rounded-lg border border-indigo-200 bg-indigo-50 px-4 py-2.5 text-sm font-semibold text-indigo-700 hover:bg-indigo-100">
                Import CSV
            </button>
            <button type="button" id="openAddClientModal" class="rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-500">
                + New Client
            </button>
        </div>
    </div>

    <div id="bulkActionBar" class="mb-4 hidden rounded-xl border border-violet-200 bg-violet-50 p-3 shadow-sm">
        <form id="bulkAssignmentForm" method="POST" action="{{ route('clients.bulkAssign') }}">
            @csrf
            <div id="bulkSelectedInputs"></div>

            <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                <span id="bulkSelectionCount" class="text-sm font-semibold text-violet-700">0 selected</span>

                <div class="flex flex-col gap-3 md:flex-row md:items-center">
                    <select name="member_ids[]" id="bulkMemberSelect" multiple size="4" class="min-w-[220px] rounded-lg border border-violet-200 bg-white px-3 py-2 text-sm text-slate-800 focus:border-violet-500 focus:outline-none focus:ring-2 focus:ring-violet-200">
                        @forelse ($teamMembers as $member)
                            <option value="{{ $member->id }}">{{ $member->name }}</option>
                        @empty
                            <option value="">No active members available</option>
                        @endforelse
                    </select>

                    <button type="submit" class="rounded-lg bg-violet-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-violet-500">
                        Assign Selected Clients
                    </button>
                </div>
            </div>
        </form>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-left">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-3 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <input type="checkbox" id="selectAll" aria-label="Select all clients">
                        </th>
                        <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Client</th>
                        <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Phone</th>
                        <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Email</th>
                        <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Address</th>
                        <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Status</th>
                        <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse ($clients as $client)
                        <tr class="client-row hover:bg-slate-50">
                            <td class="px-3 py-4">
                                <input type="checkbox" class="client-checkbox" value="{{ $client->id }}" aria-label="Select client {{ $client->name }}">
                            </td>
                            <td class="px-4 py-4">
                                <div>
                                    <p class="font-semibold text-slate-900">{{ $client->name }}</p>
                                    <p class="text-xs text-slate-500">ID #{{ $client->id }}</p>
                                </div>
                            </td>
                            <td class="px-4 py-4 text-sm text-slate-700">{{ $client->phone }}</td>
                            <td class="px-4 py-4 text-sm text-slate-700">{{ $client->email ?? '—' }}</td>
                            <td class="px-4 py-4 text-sm text-slate-700">{{ $client->address ?? '—' }}</td>
                            <td class="px-4 py-4">
                                @php
                                    $statusClass = [
                                        'pending' => 'bg-amber-100 text-amber-700',
                                        'confirm' => 'bg-emerald-100 text-emerald-700',
                                        'reject' => 'bg-rose-100 text-rose-700',
                                    ];
                                    $statusLabel = [
                                        'pending' => 'Pending',
                                        'confirm' => 'Confirm',
                                        'reject' => 'Reject',
                                    ];
                                @endphp
                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $statusClass[$client->status] ?? 'bg-slate-100 text-slate-700' }}">
                                    {{ $statusLabel[$client->status] ?? ucfirst($client->status) }}
                                </span>
                            </td>
                            <td class="px-4 py-4">
                                <div class="flex flex-wrap items-center gap-2">
                                    <button type="button" data-open-client="{{ $client->id }}" class="rounded-lg border border-indigo-200 bg-indigo-50 px-2.5 py-1.5 text-xs font-medium text-indigo-700 hover:bg-indigo-100">
                                        View
                                    </button>

                                    <form action="{{ route('clients.status', $client) }}" method="POST" class="inline">
                                        @csrf
                                        <input type="hidden" name="status" value="pending">
                                        <button type="submit" class="rounded-lg border border-amber-200 bg-amber-50 px-2.5 py-1.5 text-xs font-medium text-amber-700 hover:bg-amber-100">Pending</button>
                                    </form>

                                    <form action="{{ route('clients.status', $client) }}" method="POST" class="inline">
                                        @csrf
                                        <input type="hidden" name="status" value="confirm">
                                        <button type="submit" class="rounded-lg border border-emerald-200 bg-emerald-50 px-2.5 py-1.5 text-xs font-medium text-emerald-700 hover:bg-emerald-100">Confirm</button>
                                    </form>

                                    <form action="{{ route('clients.status', $client) }}" method="POST" class="inline">
                                        @csrf
                                        <input type="hidden" name="status" value="reject">
                                        <button type="submit" class="rounded-lg border border-rose-200 bg-rose-50 px-2.5 py-1.5 text-xs font-medium text-rose-700 hover:bg-rose-100">Reject</button>
                                    </form>

                                    <form action="{{ route('clients.destroy', $client) }}" method="POST" onsubmit="return confirm('Delete this client?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="rounded-lg border border-slate-200 bg-slate-50 px-2.5 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-100">
                                            Delete
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-12 text-center text-slate-500">
                                No clients found yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($clients->hasPages())
            <div class="border-t border-slate-200 bg-slate-50 px-4 py-3">
                {{ $clients->links() }}
            </div>
        @endif
    </div>
</div>

<div id="addClientModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/50 p-4">
    <div class="w-full max-w-xl rounded-2xl bg-white p-6 shadow-2xl">
        <div class="mb-5 flex items-center justify-between">
            <h2 class="text-2xl font-bold text-slate-900">Add Client</h2>
            <button type="button" id="closeAddClientModal" class="text-slate-500 hover:text-slate-700">✕</button>
        </div>

        <form action="{{ route('clients.store') }}" method="POST" class="space-y-4">
            @csrf

            <div>
                <label for="client_name" class="mb-1 block text-sm font-medium text-slate-700">Name</label>
                <input id="client_name" name="name" type="text" required class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-slate-800 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200" placeholder="Client name">
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label for="client_phone" class="mb-1 block text-sm font-medium text-slate-700">Phone</label>
                    <input id="client_phone" name="phone" type="text" required class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-slate-800 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200" placeholder="+1 234 567 890">
                </div>
                <div>
                    <label for="client_email" class="mb-1 block text-sm font-medium text-slate-700">Email</label>
                    <input id="client_email" name="email" type="email" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-slate-800 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200" placeholder="client@example.com">
                </div>
            </div>

            <div>
                <label for="client_address" class="mb-1 block text-sm font-medium text-slate-700">Address</label>
                <textarea id="client_address" name="address" rows="3" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-slate-800 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200" placeholder="Street, city, country"></textarea>
            </div>

            <div>
                <label for="client_status" class="mb-1 block text-sm font-medium text-slate-700">Status</label>
                <select id="client_status" name="status" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-slate-800 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200">
                    <option value="pending">Pending</option>
                    <option value="confirm">Confirm</option>
                    <option value="reject">Reject</option>
                </select>
            </div>

            <div class="flex items-center justify-end gap-3 pt-2">
                <button type="button" id="cancelAddClientModal" class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50">
                    Cancel
                </button>
                <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-500">
                    Save Client
                </button>
            </div>
        </form>
    </div>
</div>

<div id="importClientModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/50 p-4">
    <div class="w-full max-w-xl rounded-2xl bg-white p-6 shadow-2xl">
        <div class="mb-5 flex items-center justify-between">
            <h2 class="text-2xl font-bold text-slate-900">Import Clients</h2>
            <button type="button" id="closeImportClientModal" class="text-slate-500 hover:text-slate-700">✕</button>
        </div>

        <div class="mb-4 rounded-xl border border-dashed border-indigo-200 bg-indigo-50 p-4 text-sm text-indigo-800">
            <p class="font-medium text-indigo-900">Accepted import formats</p>
            <ul class="mt-2 list-disc space-y-1 pl-5 text-indigo-700">
                <li>Full client rows: name, phone, email, address, status</li>
                <li>Phone-only lists: just a phone column is enough</li>
                <li>Name, email, and address are optional; empty fields are auto-filled safely</li>
            </ul>
            <div class="mt-3">
                Download the sample template: <a href="{{ route('clients.template') }}" class="font-semibold underline">clients_template.csv</a>
            </div>
        </div>

        <form action="{{ route('clients.import') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
            @csrf

            <div>
                <label for="csv_file" class="mb-1 block text-sm font-medium text-slate-700">CSV or Excel file</label>
                <input id="csv_file" name="csv_file" type="file" accept=".csv,.txt,.xls,.xlsx" class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-700 file:mr-3 file:rounded-md file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-sm file:font-medium file:text-slate-700">
            </div>

            <div class="flex items-center justify-end gap-3 pt-2">
                <button type="button" id="cancelImportClientModal" class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50">Cancel</button>
                <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-500">Import Clients</button>
            </div>
        </form>
    </div>
</div>

<script>
    const openAddClientModal = document.getElementById('openAddClientModal');
    const closeAddClientModal = document.getElementById('closeAddClientModal');
    const cancelAddClientModal = document.getElementById('cancelAddClientModal');
    const addClientModal = document.getElementById('addClientModal');
    const openImportModal = document.getElementById('openImportModal');
    const closeImportClientModal = document.getElementById('closeImportClientModal');
    const cancelImportClientModal = document.getElementById('cancelImportClientModal');
    const importClientModal = document.getElementById('importClientModal');

    function toggleModal(modal, show) {
        if (! modal) return;
        modal.classList.toggle('hidden', ! show);
        modal.classList.toggle('flex', show);
    }

    openAddClientModal?.addEventListener('click', () => toggleModal(addClientModal, true));
    closeAddClientModal?.addEventListener('click', () => toggleModal(addClientModal, false));
    cancelAddClientModal?.addEventListener('click', () => toggleModal(addClientModal, false));
    openImportModal?.addEventListener('click', () => toggleModal(importClientModal, true));
    closeImportClientModal?.addEventListener('click', () => toggleModal(importClientModal, false));
    cancelImportClientModal?.addEventListener('click', () => toggleModal(importClientModal, false));
    addClientModal?.addEventListener('click', (event) => {
        if (event.target === addClientModal) toggleModal(addClientModal, false);
    });
    importClientModal?.addEventListener('click', (event) => {
        if (event.target === importClientModal) toggleModal(importClientModal, false);
    });

    const selectAll = document.getElementById('selectAll');
    const bulkActionBar = document.getElementById('bulkActionBar');
    const bulkSelectionCount = document.getElementById('bulkSelectionCount');
    const bulkSelectedInputs = document.getElementById('bulkSelectedInputs');
    const perPageSelect = document.getElementById('perPage');

    function updateBulkSelectionState() {
        const clientCheckboxes = Array.from(document.querySelectorAll('.client-checkbox'));
        const selectedCheckboxes = clientCheckboxes.filter((checkbox) => checkbox.checked);

        bulkSelectionCount.textContent = `${selectedCheckboxes.length} selected`;
        bulkActionBar.classList.toggle('hidden', selectedCheckboxes.length === 0);

        bulkSelectedInputs.innerHTML = '';
        selectedCheckboxes.forEach((checkbox) => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'client_ids[]';
            input.value = checkbox.value;
            bulkSelectedInputs.appendChild(input);
        });

        const visibleCheckboxes = clientCheckboxes.filter((checkbox) => {
            const row = checkbox.closest('.client-row');
            return row && row.style.display !== 'none';
        });

        if (! selectAll) return;

        const allVisibleChecked = visibleCheckboxes.length > 0 && visibleCheckboxes.every((checkbox) => checkbox.checked);
        const someVisibleChecked = visibleCheckboxes.some((checkbox) => checkbox.checked);

        selectAll.checked = allVisibleChecked;
        selectAll.indeterminate = ! allVisibleChecked && someVisibleChecked;
    }

    selectAll?.addEventListener('change', function () {
        document.querySelectorAll('.client-checkbox').forEach((checkbox) => {
            const row = checkbox.closest('.client-row');
            if (row && row.style.display !== 'none') {
                checkbox.checked = this.checked;
            }
        });

        updateBulkSelectionState();
    });

    document.querySelectorAll('.client-checkbox').forEach((checkbox) => {
        checkbox.addEventListener('change', updateBulkSelectionState);
    });

    perPageSelect?.addEventListener('change', function () {
        const url = new URL(window.location.href);
        url.searchParams.set('per_page', this.value);
        url.searchParams.delete('page');
        window.location.href = url.toString();
    });

    updateBulkSelectionState();
</script>

<div id="clientDetailsModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/60 p-4">
            <div class="w-full max-w-4xl rounded-2xl bg-white p-6 shadow-2xl">
                <div class="mb-5 flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-indigo-600">Client profile</p>
                        <h2 id="clientModalTitle" class="text-2xl font-bold text-slate-900">Client</h2>
                    </div>
                    <button type="button" id="closeClientDetailsModal" class="text-slate-500 hover:text-slate-700">✕</button>
                </div>

                <div class="grid gap-6 lg:grid-cols-[0.9fr_1.1fr]">
                    <div class="space-y-4 rounded-2xl border border-slate-200 bg-slate-50 p-5">
                        <div class="flex items-center justify-between gap-3">
                            <h3 class="text-lg font-semibold text-slate-900">Overview</h3>
                            <span id="clientStatusBadge" class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold"></span>
                        </div>

                        <dl class="space-y-3 text-sm">
                            <div>
                                <dt class="text-slate-500">Name</dt>
                                <dd id="modalClientName" class="font-medium text-slate-800"></dd>
                            </div>
                            <div>
                                <dt class="text-slate-500">Phone</dt>
                                <dd id="modalClientPhone" class="font-medium text-slate-800"></dd>
                            </div>
                            <div>
                                <dt class="text-slate-500">Email</dt>
                                <dd id="modalClientEmail" class="font-medium text-slate-800"></dd>
                            </div>
                            <div>
                                <dt class="text-slate-500">Address</dt>
                                <dd id="modalClientAddress" class="font-medium text-slate-800"></dd>
                            </div>
                        </dl>

                        <div class="flex flex-wrap gap-2 pt-2">
                            <button type="button" id="copyClientContact" class="rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-2 text-sm font-medium text-indigo-700 hover:bg-indigo-100">
                                Copy contact
                            </button>
                            <a id="whatsappClientLink" href="#" target="_blank" class="rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm font-medium text-emerald-700 hover:bg-emerald-100">
                                WhatsApp
                            </a>
                            <a id="smsClientLink" href="#" class="rounded-lg border border-sky-200 bg-sky-50 px-3 py-2 text-sm font-medium text-sky-700 hover:bg-sky-100">
                                Send SMS
                            </a>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div class="rounded-2xl border border-slate-200 bg-white p-5">
                            <h3 class="mb-4 text-lg font-semibold text-slate-900">Interaction notes</h3>
                            <div id="clientNotesList" class="max-h-[360px] space-y-3 overflow-y-auto"></div>
                        </div>

                        <div class="rounded-2xl border border-slate-200 bg-white p-5">
                            <h3 class="mb-4 text-lg font-semibold text-slate-900">Add note</h3>
                            <form id="clientNoteForm" class="space-y-3">
                                @csrf
                                <input id="noteInteractionDate" name="interaction_date" type="datetime-local" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-slate-800 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200">
                                <textarea id="noteInput" name="note" rows="4" required class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-slate-800 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200" placeholder="Call summary, meeting notes, next step..."></textarea>
                                <div class="flex items-center justify-end gap-3">
                                    <button type="button" id="resetNoteForm" class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50">
                                        Clear
                                    </button>
                                    <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-500">
                                        Save note
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <script>
            const clientSearchInput = document.getElementById('clientSearch');
            const addClientModal = document.getElementById('addClientModal');
            const importClientModal = document.getElementById('importClientModal');
            const clientDetailsModal = document.getElementById('clientDetailsModal');
            const clientNotesList = document.getElementById('clientNotesList');
            const clientNoteForm = document.getElementById('clientNoteForm');
            const noteInput = document.getElementById('noteInput');
            const noteInteractionDate = document.getElementById('noteInteractionDate');
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            let activeClientId = null;
            let editingNoteId = null;

            function setModalVisible(modal, visible) {
                if (visible) {
                    modal.classList.remove('hidden');
                    modal.classList.add('flex');
                    return;
                }

                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }

            function resetNoteForm() {
                editingNoteId = null;
                noteInput.value = '';
                noteInteractionDate.value = '';
                clientNoteForm.querySelector('button[type="submit"]').textContent = 'Save note';
            }

            document.getElementById('openAddClientModal').addEventListener('click', () => setModalVisible(addClientModal, true));
            document.getElementById('closeAddClientModal').addEventListener('click', () => setModalVisible(addClientModal, false));
            document.getElementById('cancelAddClientModal').addEventListener('click', () => setModalVisible(addClientModal, false));
            addClientModal.addEventListener('click', (event) => {
                if (event.target === addClientModal) {
                    setModalVisible(addClientModal, false);
                }
            });

            document.getElementById('openImportModal').addEventListener('click', () => setModalVisible(importClientModal, true));
            document.getElementById('closeImportClientModal').addEventListener('click', () => setModalVisible(importClientModal, false));
            document.getElementById('cancelImportClientModal').addEventListener('click', () => setModalVisible(importClientModal, false));
            importClientModal.addEventListener('click', (event) => {
                if (event.target === importClientModal) {
                    setModalVisible(importClientModal, false);
                }
            });

            document.getElementById('closeClientDetailsModal').addEventListener('click', () => {
                setModalVisible(clientDetailsModal, false);
                resetNoteForm();
            });
            clientDetailsModal.addEventListener('click', (event) => {
                if (event.target === clientDetailsModal) {
                    setModalVisible(clientDetailsModal, false);
                    resetNoteForm();
                }
            });

            clientSearchInput.addEventListener('input', (event) => {
                const search = event.target.value.toLowerCase();
                document.querySelectorAll('.client-row').forEach((row) => {
                    const matches = row.dataset.search.includes(search);
                    row.style.display = search && ! matches ? 'none' : '';
                });
            });

            function formatStatus(status) {
                const map = {
                    pending: { label: 'Pending', className: 'bg-amber-100 text-amber-700' },
                    confirm: { label: 'Confirm', className: 'bg-emerald-100 text-emerald-700' },
                    reject: { label: 'Reject', className: 'bg-rose-100 text-rose-700' },
                };

                return map[status] || { label: 'Pending', className: 'bg-slate-100 text-slate-700' };
            }

            async function openClientModal(clientId) {
                activeClientId = clientId;

                const response = await fetch(`/clients/${clientId}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                if (! response.ok) {
                    return;
                }

                const data = await response.json();
                const client = data.client;
                const notes = data.notes || [];

                document.getElementById('clientModalTitle').textContent = client.name;
                document.getElementById('modalClientName').textContent = client.name;
                document.getElementById('modalClientPhone').textContent = client.phone;
                document.getElementById('modalClientEmail').textContent = client.email || '—';
                document.getElementById('modalClientAddress').textContent = client.address || '—';

                const statusInfo = formatStatus(client.status);
                const statusBadge = document.getElementById('clientStatusBadge');
                statusBadge.textContent = statusInfo.label;
                statusBadge.className = `inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ${statusInfo.className}`;

                const phoneDigits = (client.phone || '').replace(/\D/g, '');
                const contactText = `Name: ${client.name}\nPhone: ${client.phone}\nEmail: ${client.email || 'N/A'}\nAddress: ${client.address || 'N/A'}`;

                document.getElementById('copyClientContact').onclick = async () => {
                    try {
                        await navigator.clipboard.writeText(contactText);
                        document.getElementById('copyClientContact').textContent = 'Copied!';
                        setTimeout(() => {
                            document.getElementById('copyClientContact').textContent = 'Copy contact';
                        }, 1200);
                    } catch (error) {
                        document.getElementById('copyClientContact').textContent = 'Copy failed';
                    }
                };

                document.getElementById('whatsappClientLink').href = phoneDigits ? `https://wa.me/${phoneDigits}?text=${encodeURIComponent(contactText)}` : '#';
                document.getElementById('smsClientLink').href = phoneDigits ? `sms:${phoneDigits}?body=${encodeURIComponent('Hello ' + client.name + ',')}` : '#';

                if (! notes.length) {
                    clientNotesList.innerHTML = `
                        <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50 px-4 py-8 text-center">
                            <p class="text-sm font-medium text-slate-700">No interaction notes yet.</p>
                            <p class="mt-2 text-xs text-slate-500">Add the first call or meeting update below.</p>
                        </div>
                    `;
                    setModalVisible(clientDetailsModal, true);
                    return;
                }

                clientNotesList.innerHTML = notes.map((note) => `
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                        <div class="mb-2 flex items-center justify-between gap-3">
                            <div>
                                <p class="text-sm font-semibold text-slate-900">${note.user.name}</p>
                                <p class="text-[11px] text-slate-500">${note.formatted_date || 'No date'}</p>
                            </div>
                            <div class="flex items-center gap-2">
                                <button type="button" data-edit-note="${note.id}" class="text-[11px] font-medium text-indigo-600 hover:text-indigo-500">Edit</button>
                                <button type="button" data-delete-note="${note.id}" class="text-[11px] font-medium text-rose-600 hover:text-rose-500">Delete</button>
                            </div>
                        </div>
                        <p class="whitespace-pre-wrap text-sm leading-6 text-slate-700">${note.note}</p>
                    </div>
                `).join('');

                clientNotesList.querySelectorAll('[data-edit-note]').forEach((button) => {
                    button.addEventListener('click', () => {
                        const noteId = button.getAttribute('data-edit-note');
                        const note = notes.find((item) => String(item.id) === String(noteId));

                        if (! note) {
                            return;
                        }

                        editingNoteId = noteId;
                        noteInput.value = note.note;
                        noteInteractionDate.value = note.interaction_date || '';
                        clientNoteForm.querySelector('button[type="submit"]').textContent = 'Update note';
                    });
                });

                clientNotesList.querySelectorAll('[data-delete-note]').forEach((button) => {
                    button.addEventListener('click', async () => {
                        const noteId = button.getAttribute('data-delete-note');
                        const confirmed = confirm('Delete this interaction note?');
                        if (! confirmed) {
                            return;
                        }

                        const response = await fetch(`/clients/${clientId}/notes/${noteId}`, {
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': csrfToken,
                                'Accept': 'application/json',
                            },
                        });

                        if (response.ok) {
                            openClientModal(clientId);
                        }
                    });
                });

                setModalVisible(clientDetailsModal, true);
            }

            document.querySelectorAll('[data-open-client]').forEach((button) => {
                button.addEventListener('click', () => openClientModal(button.getAttribute('data-open-client')));
            });

            document.getElementById('resetNoteForm').addEventListener('click', resetNoteForm);

            clientNoteForm.addEventListener('submit', async (event) => {
                event.preventDefault();

                if (! activeClientId) {
                    return;
                }

                const formData = new FormData(clientNoteForm);
                const url = editingNoteId ? `/clients/${activeClientId}/notes/${editingNoteId}` : `/clients/${activeClientId}/notes`;
                const method = editingNoteId ? 'PUT' : 'POST';

                const response = await fetch(url, {
                    method,
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                    body: formData,
                });

                if (response.ok) {
                    resetNoteForm();
                    openClientModal(activeClientId);
                }
            });
        </script>
@endsection
    </body>
</html>
