@extends('layouts.app')

@section('title', 'Lead Management')

@section('content')
<div class="mx-auto max-w-7xl">
    <div class="mb-4 flex flex-wrap items-center gap-2">
        @php
            $statusPills = ['HASIF USER PASS', 'Italian Client Project', 'ORDINARY UPDATE', 'Claude free 100', 'cPanel - Tools', 'IT Sian All Website', 'BOOST NUMBER', 'MONTHLY WORK', 'Italian Client Project'];
        @endphp
        @foreach ($statusPills as $pill)
            <span class="inline-flex items-center rounded-md border border-slate-200 bg-white px-2.5 py-1 text-[10px] font-semibold uppercase tracking-[0.12em] text-slate-700 shadow-sm">
                {{ $pill }}
            </span>
        @endforeach
    </div>

    <div class="mb-8 grid gap-4 md:grid-cols-3">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm text-slate-500">Appointments</p>
            <p class="mt-2 text-3xl font-bold text-slate-900">{{ $appointments->count() }}</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm text-slate-500">Meetings</p>
            <p class="mt-2 text-3xl font-bold text-sky-600">{{ $meetings->count() }}</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm text-slate-500">Assigned Leads</p>
            <p class="mt-2 text-3xl font-bold text-violet-600">{{ $assignedLeads->count() }}</p>
        </div>
    </div>

    <div class="space-y-6">
        <section id="appointments" class="rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Section 1</p>
                    <h2 class="text-xl font-bold text-slate-900">Appointments</h2>
                </div>
                <button type="button" data-open-modal="appointment-modal" class="rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-500">
                    + Add Appointment
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-left">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Date</th>
                            <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Client</th>
                            <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Phone</th>
                            <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Location</th>
                            <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Notes</th>
                            <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @forelse ($appointments as $appointment)
                            <tr>
                                <td class="px-4 py-4 text-sm text-slate-700">{{ $appointment->appointment_date?->format('M d, Y h:i A') }}</td>
                                <td class="px-4 py-4 text-sm text-slate-700">{{ $appointment->client?->name ?? 'Manual lead' }}</td>
                                <td class="px-4 py-4 text-sm text-slate-700">{{ $appointment->custom_phone ?? $appointment->client?->phone ?? '—' }}</td>
                                <td class="px-4 py-4 text-sm text-slate-700">{{ $appointment->location }}</td>
                                <td class="px-4 py-4 text-sm text-slate-700">{{ $appointment->notes ?: '—' }}</td>
                                <td class="px-4 py-4">
                                    <div class="flex gap-2">
                                        <button type="button" data-edit-appointment='@json($appointment)' class="rounded-lg border border-indigo-200 bg-indigo-50 px-2.5 py-1.5 text-xs font-medium text-indigo-700 hover:bg-indigo-100">
                                            Edit
                                        </button>
                                        <form action="{{ route('lead-management.appointments.destroy', $appointment) }}" method="POST" onsubmit="return confirm('Delete this appointment?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="rounded-lg border border-rose-200 bg-rose-50 px-2.5 py-1.5 text-xs font-medium text-rose-700 hover:bg-rose-100">
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-10 text-center text-slate-500">No appointments scheduled yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section id="meetings" class="rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Section 2</p>
                    <h2 class="text-xl font-bold text-slate-900">Meetings</h2>
                </div>
                <button type="button" data-open-modal="meeting-modal" class="rounded-lg bg-sky-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-sky-500">
                    + Add Meeting
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-left">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Customer</th>
                            <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Staff</th>
                            <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Type</th>
                            <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Status</th>
                            <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Zoom</th>
                            <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @forelse ($meetings as $meeting)
                            <tr>
                                <td class="px-4 py-4 text-sm text-slate-700">{{ $meeting->customer_name }}<br><span class="text-xs text-slate-500">{{ $meeting->customer_email }}</span></td>
                                <td class="px-4 py-4 text-sm text-slate-700">{{ $meeting->staff?->name ?? 'Unassigned' }}</td>
                                <td class="px-4 py-4 text-sm text-slate-700">{{ ucfirst($meeting->lead_type) }}</td>
                                <td class="px-4 py-4">
                                    @php
                                        $meetingState = [
                                            'scheduled' => 'bg-sky-100 text-sky-700',
                                            'completed' => 'bg-emerald-100 text-emerald-700',
                                            'cancelled' => 'bg-rose-100 text-rose-700',
                                            'rescheduled' => 'bg-violet-100 text-violet-700',
                                        ];
                                    @endphp
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $meetingState[$meeting->status] ?? 'bg-slate-100 text-slate-700' }}">
                                        {{ ucfirst($meeting->status) }}
                                    </span>
                                </td>
                                <td class="px-4 py-4 text-sm text-slate-700">{{ $meeting->zoom_link ? 'Linked' : '—' }}</td>
                                <td class="px-4 py-4">
                                    <div class="flex gap-2">
                                        <button type="button" data-edit-meeting='@json($meeting)' class="rounded-lg border border-indigo-200 bg-indigo-50 px-2.5 py-1.5 text-xs font-medium text-indigo-700 hover:bg-indigo-100">
                                            Edit
                                        </button>
                                        <form action="{{ route('lead-management.meetings.destroy', $meeting) }}" method="POST" onsubmit="return confirm('Delete this meeting?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="rounded-lg border border-rose-200 bg-rose-50 px-2.5 py-1.5 text-xs font-medium text-rose-700 hover:bg-rose-100">
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-10 text-center text-slate-500">No meetings logged yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section id="assigned-leads" class="rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Section 3</p>
                    <h2 class="text-xl font-bold text-slate-900">Assigned Leads</h2>
                </div>
                <button type="button" data-open-modal="lead-modal" class="rounded-lg bg-violet-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-violet-500">
                    + Add Assigned Lead
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-left">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Name</th>
                            <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Manager</th>
                            <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Company</th>
                            <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Phone</th>
                            <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Status</th>
                            <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Last Note</th>
                            <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Next Follow-up</th>
                            <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @forelse ($assignedLeads as $lead)
                            <tr>
                                <td class="px-4 py-4 text-sm text-slate-700">{{ $lead->name }}</td>
                                <td class="px-4 py-4 text-sm text-slate-700">{{ $lead->leadManager?->name ?? '—' }}</td>
                                <td class="px-4 py-4 text-sm text-slate-700">{{ $lead->company ?: '—' }}</td>
                                <td class="px-4 py-4 text-sm text-slate-700">{{ $lead->phone }}</td>
                                <td class="px-4 py-4">
                                    @php
                                        $leadState = [
                                            'new' => 'bg-slate-100 text-slate-700',
                                            'contacted' => 'bg-cyan-100 text-cyan-700',
                                            'qualified' => 'bg-violet-100 text-violet-700',
                                            'proposal_sent' => 'bg-amber-100 text-amber-700',
                                            'won' => 'bg-emerald-100 text-emerald-700',
                                            'lost' => 'bg-rose-100 text-rose-700',
                                        ];
                                    @endphp
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $leadState[$lead->status] ?? 'bg-slate-100 text-slate-700' }}">
                                        {{ ucfirst(str_replace('_', ' ', $lead->status)) }}
                                    </span>
                                </td>
                                <td class="px-4 py-4 text-sm text-slate-700">
                                    <div class="max-w-[200px] truncate text-ellipsis whitespace-nowrap" title="{{ $lead->last_note ?: 'No notes yet' }}">
                                        {{ $lead->last_note ?: 'No notes yet' }}
                                    </div>
                                </td>
                                <td class="px-4 py-4 text-sm text-slate-700">{{ $lead->next_follow_up_date?->format('M d, Y') ?? '—' }}</td>
                                <td class="px-4 py-4">
                                    <div class="flex flex-wrap gap-2">
                                        <button type="button" data-view-lead="{{ $lead->id }}" class="rounded-lg border border-indigo-200 bg-indigo-50 px-2.5 py-1.5 text-xs font-medium text-indigo-700 hover:bg-indigo-100">
                                            View
                                        </button>
                                        <button type="button" data-edit-lead='@json($lead)' class="rounded-lg border border-violet-200 bg-violet-50 px-2.5 py-1.5 text-xs font-medium text-violet-700 hover:bg-violet-100">
                                            Edit
                                        </button>
                                        <button type="button" data-assign-lead="{{ $lead->id }}" class="rounded-lg border border-sky-200 bg-sky-50 px-2.5 py-1.5 text-xs font-medium text-sky-700 hover:bg-sky-100">
                                            Assign
                                        </button>
                                        <form action="{{ route('lead-management.assigned-leads.destroy', $lead) }}" method="POST" onsubmit="return confirm('Delete this assigned lead?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="rounded-lg border border-rose-200 bg-rose-50 px-2.5 py-1.5 text-xs font-medium text-rose-700 hover:bg-rose-100">
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-4 py-10 text-center text-slate-500">No assigned leads yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
            </div>
        </div>

        <div id="appointment-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/50 p-4">
            <div class="w-full max-w-2xl rounded-2xl bg-white p-6 shadow-2xl">
                <div class="mb-5 flex items-center justify-between">
                    <h2 class="text-2xl font-bold text-slate-900">Add Appointment</h2>
                    <button type="button" class="close-modal text-slate-500 hover:text-slate-700">✕</button>
                </div>

                <form action="{{ route('lead-management.appointments.store') }}" method="POST" class="space-y-4">
                    @csrf
                    <input type="hidden" name="_method" value="POST">
                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">Appointment Date</label>
                            <input type="datetime-local" name="appointment_date" required class="w-full rounded-lg border border-slate-300 px-3 py-2.5 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200">
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">Location</label>
                            <input type="text" name="location" required placeholder="Office / Zoom / Client site" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200">
                        </div>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">Existing Client</label>
                            <select name="client_id" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200">
                                <option value="">-- Select client --</option>
                                @foreach ($clients as $client)
                                    <option value="{{ $client->id }}">{{ $client->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">Custom Phone</label>
                            <input type="text" name="custom_phone" placeholder="Optional phone" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200">
                        </div>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Notes</label>
                        <textarea name="notes" rows="4" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200" placeholder="Meeting agenda or context"></textarea>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-2">
                        <button type="button" class="close-modal rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50">
                            Cancel
                        </button>
                        <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-500">
                            Save Appointment
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div id="meeting-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/50 p-4">
            <div class="w-full max-w-2xl rounded-2xl bg-white p-6 shadow-2xl">
                <div class="mb-5 flex items-center justify-between">
                    <h2 class="text-2xl font-bold text-slate-900">Add Meeting</h2>
                    <button type="button" class="close-modal text-slate-500 hover:text-slate-700">✕</button>
                </div>

                <form action="{{ route('lead-management.meetings.store') }}" method="POST" class="space-y-4">
                    @csrf
                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">Client</label>
                            <select name="client_id" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200">
                                <option value="">-- Select optional client --</option>
                                @foreach ($clients as $client)
                                    <option value="{{ $client->id }}">{{ $client->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">Customer Name</label>
                            <input type="text" name="customer_name" required class="w-full rounded-lg border border-slate-300 px-3 py-2.5 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200">
                        </div>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">Customer Email</label>
                            <input type="email" name="customer_email" required class="w-full rounded-lg border border-slate-300 px-3 py-2.5 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200">
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">Staff</label>
                            <select name="staff_id" required class="w-full rounded-lg border border-slate-300 px-3 py-2.5 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200">
                                @foreach ($staffMembers as $staff)
                                    <option value="{{ $staff->id }}">{{ $staff->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="grid gap-4 md:grid-cols-3">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">Lead Type</label>
                            <select name="lead_type" required class="w-full rounded-lg border border-slate-300 px-3 py-2.5 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200">
                                <option value="customer">Customer</option>
                                <option value="lead">Lead</option>
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">Status</label>
                            <select name="status" required class="w-full rounded-lg border border-slate-300 px-3 py-2.5 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200">
                                <option value="scheduled">Scheduled</option>
                                <option value="completed">Completed</option>
                                <option value="cancelled">Cancelled</option>
                                <option value="rescheduled">Rescheduled</option>
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">Zoom Link</label>
                            <input type="url" name="zoom_link" placeholder="https://zoom.us/..." class="w-full rounded-lg border border-slate-300 px-3 py-2.5 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200">
                        </div>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Remark</label>
                        <textarea name="remark" rows="3" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200" placeholder="Summary, follow-up note, next actions"></textarea>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-2">
                        <button type="button" class="close-modal rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50">
                            Cancel
                        </button>
                        <button type="submit" class="rounded-lg bg-sky-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-sky-500">
                            Save Meeting
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div id="lead-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/50 p-4">
            <div class="w-full max-w-2xl rounded-2xl bg-white p-6 shadow-2xl">
                <div class="mb-5 flex items-center justify-between">
                    <h2 class="text-2xl font-bold text-slate-900">Add Assigned Lead</h2>
                    <button type="button" class="close-modal text-slate-500 hover:text-slate-700">✕</button>
                </div>

                <form action="{{ route('lead-management.assigned-leads.store') }}" method="POST" class="space-y-4">
                    @csrf
                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">Lead Name</label>
                            <input type="text" name="name" required class="w-full rounded-lg border border-slate-300 px-3 py-2.5 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200">
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">Company</label>
                            <input type="text" name="company" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200">
                        </div>
                    </div>

                    <div class="grid gap-4 md:grid-cols-3">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">Phone</label>
                            <input type="text" name="phone" required class="w-full rounded-lg border border-slate-300 px-3 py-2.5 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200">
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">Email</label>
                            <input type="email" name="email" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200">
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">Source</label>
                            <input type="text" name="source" placeholder="Website / Referral / LinkedIn" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200">
                        </div>
                    </div>

                    <div class="grid gap-4 md:grid-cols-3">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">Manager</label>
                            <select name="lead_manager_id" required class="w-full rounded-lg border border-slate-300 px-3 py-2.5 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200">
                                @foreach ($staffMembers as $staff)
                                    <option value="{{ $staff->id }}">{{ $staff->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">Assigned To</label>
                            <select name="assigned_to" required class="w-full rounded-lg border border-slate-300 px-3 py-2.5 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200">
                                @foreach ($staffMembers as $staff)
                                    <option value="{{ $staff->id }}">{{ $staff->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">Status</label>
                            <select name="status" required class="w-full rounded-lg border border-slate-300 px-3 py-2.5 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200">
                                <option value="new">New</option>
                                <option value="contacted">Contacted</option>
                                <option value="qualified">Qualified</option>
                                <option value="proposal_sent">Proposal Sent</option>
                                <option value="won">Won</option>
                                <option value="lost">Lost</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">Last Contact</label>
                            <input type="date" name="last_contact_date" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200">
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">Next Follow-up</label>
                            <input type="date" name="next_follow_up_date" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200">
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-2">
                        <button type="button" class="close-modal rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50">
                            Cancel
                        </button>
                        <button type="submit" class="rounded-lg bg-violet-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-violet-500">
                            Save Assigned Lead
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div id="lead-detail-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/50 p-4">
            <div class="w-full max-w-5xl rounded-2xl bg-white p-6 shadow-2xl">
                <div class="mb-5 flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-violet-600">Lead details</p>
                        <h2 id="leadDetailTitle" class="text-2xl font-bold text-slate-900">Lead profile</h2>
                    </div>
                    <button type="button" class="close-modal text-slate-500 hover:text-slate-700">✕</button>
                </div>

                <div class="grid gap-6 lg:grid-cols-[0.9fr_1.1fr]">
                    <div class="space-y-4 rounded-2xl border border-slate-200 bg-slate-50 p-5">
                        <div class="flex items-center justify-between gap-3">
                            <h3 class="text-lg font-semibold text-slate-900">Contact details</h3>
                            <span id="leadDetailStatus" class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold"></span>
                        </div>

                        <dl class="space-y-3 text-sm text-slate-700">
                            <div class="flex justify-between gap-3 border-b border-slate-200 pb-2">
                                <dt class="font-medium text-slate-500">Name</dt>
                                <dd id="leadDetailName" class="text-right font-semibold text-slate-900"></dd>
                            </div>
                            <div class="flex justify-between gap-3 border-b border-slate-200 pb-2">
                                <dt class="font-medium text-slate-500">Phone</dt>
                                <dd id="leadDetailPhone" class="text-right"></dd>
                            </div>
                            <div class="flex justify-between gap-3 border-b border-slate-200 pb-2">
                                <dt class="font-medium text-slate-500">Email</dt>
                                <dd id="leadDetailEmail" class="text-right"></dd>
                            </div>
                            <div class="flex justify-between gap-3 border-b border-slate-200 pb-2">
                                <dt class="font-medium text-slate-500">Company</dt>
                                <dd id="leadDetailCompany" class="text-right"></dd>
                            </div>
                            <div class="flex justify-between gap-3 border-b border-slate-200 pb-2">
                                <dt class="font-medium text-slate-500">Source</dt>
                                <dd id="leadDetailSource" class="text-right"></dd>
                            </div>
                            <div class="flex justify-between gap-3 border-b border-slate-200 pb-2">
                                <dt class="font-medium text-slate-500">Assigned member</dt>
                                <dd id="leadDetailAssignedTo" class="text-right"></dd>
                            </div>
                            <div class="flex justify-between gap-3 border-b border-slate-200 pb-2">
                                <dt class="font-medium text-slate-500">Manager</dt>
                                <dd id="leadDetailManager" class="text-right"></dd>
                            </div>
                            <div class="flex justify-between gap-3">
                                <dt class="font-medium text-slate-500">Last note</dt>
                                <dd id="leadDetailLastNote" class="max-w-[200px] text-right text-slate-900"></dd>
                            </div>
                        </dl>
                    </div>

                    <div class="space-y-4">
                        <div class="rounded-2xl border border-slate-200 bg-white p-4">
                            <h3 class="mb-3 text-lg font-semibold text-slate-900">Quick assign</h3>
                            <form id="leadAssignmentForm" method="POST" class="space-y-3">
                                @csrf
                                <select name="member_ids[]" multiple size="5" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-slate-800 focus:border-violet-500 focus:outline-none focus:ring-2 focus:ring-violet-200">
                                    @foreach ($staffMembers as $staff)
                                        <option value="{{ $staff->id }}">{{ $staff->name }}</option>
                                    @endforeach
                                </select>
                                <button type="submit" class="rounded-lg bg-violet-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-violet-500">
                                    Save assignment
                                </button>
                            </form>
                        </div>

                        <div class="rounded-2xl border border-slate-200 bg-white p-4">
                            <h3 class="mb-3 text-lg font-semibold text-slate-900">Follow-up notes</h3>
                            <form id="leadNoteForm" method="POST" class="mb-4 space-y-3">
                                @csrf
                                <textarea name="note" rows="3" placeholder="Last call summary..." class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-slate-800 focus:border-violet-500 focus:outline-none focus:ring-2 focus:ring-violet-200"></textarea>
                                <button type="submit" class="rounded-lg border border-violet-200 bg-violet-50 px-3 py-2 text-sm font-medium text-violet-700 hover:bg-violet-100">
                                    Add note
                                </button>
                            </form>

                            <div id="leadNotesList" class="space-y-3"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <script>
            document.querySelectorAll('[data-open-modal]').forEach(function (button) {
                button.addEventListener('click', function () {
                    const target = document.getElementById(button.dataset.openModal);
                    if (target) {
                        target.classList.remove('hidden');
                        target.classList.add('flex');
                    }
                });
            });

            document.querySelectorAll('.close-modal').forEach(function (button) {
                button.addEventListener('click', function () {
                    const modal = button.closest('[id$="-modal"]');
                    if (modal) {
                        modal.classList.add('hidden');
                        modal.classList.remove('flex');
                    }
                });
            });

            document.querySelectorAll('[data-edit-appointment]').forEach(function (button) {
                button.addEventListener('click', function () {
                    const appointment = JSON.parse(button.dataset.editAppointment);
                    const modal = document.getElementById('appointment-modal');
                    const form = modal.querySelector('form');
                    form.action = '/lead-management/appointments/' + appointment.id;

                    const methodInput = document.createElement('input');
                    methodInput.type = 'hidden';
                    methodInput.name = '_method';
                    methodInput.value = 'PUT';
                    form.appendChild(methodInput);

                    form.querySelector('[name="appointment_date"]').value = appointment.appointment_date ? appointment.appointment_date.slice(0, 16) : '';
                    form.querySelector('[name="client_id"]').value = appointment.client_id || '';
                    form.querySelector('[name="custom_phone"]').value = appointment.custom_phone || '';
                    form.querySelector('[name="location"]').value = appointment.location || '';
                    form.querySelector('[name="notes"]').value = appointment.notes || '';
                    modal.classList.remove('hidden');
                    modal.classList.add('flex');
                });
            });

            document.querySelectorAll('[data-edit-meeting]').forEach(function (button) {
                button.addEventListener('click', function () {
                    const meeting = JSON.parse(button.dataset.editMeeting);
                    const modal = document.getElementById('meeting-modal');
                    const form = modal.querySelector('form');
                    form.action = '/lead-management/meetings/' + meeting.id;

                    const methodInput = document.createElement('input');
                    methodInput.type = 'hidden';
                    methodInput.name = '_method';
                    methodInput.value = 'PUT';
                    form.appendChild(methodInput);

                    form.querySelector('[name="client_id"]').value = meeting.client_id || '';
                    form.querySelector('[name="customer_name"]').value = meeting.customer_name || '';
                    form.querySelector('[name="customer_email"]').value = meeting.customer_email || '';
                    form.querySelector('[name="staff_id"]').value = meeting.staff_id || '';
                    form.querySelector('[name="lead_type"]').value = meeting.lead_type || 'lead';
                    form.querySelector('[name="zoom_link"]').value = meeting.zoom_link || '';
                    form.querySelector('[name="status"]').value = meeting.status || 'scheduled';
                    form.querySelector('[name="remark"]').value = meeting.remark || '';
                    modal.classList.remove('hidden');
                    modal.classList.add('flex');
                });
            });

            document.querySelectorAll('[data-view-lead]').forEach(function (button) {
                button.addEventListener('click', function () {
                    const leadId = button.dataset.viewLead;
                    fetch('/lead-management/assigned-leads/' + leadId)
                        .then(function (response) {
                            return response.json();
                        })
                        .then(function (data) {
                            const lead = data.lead;
                            const modal = document.getElementById('lead-detail-modal');
                            const assignmentForm = document.getElementById('leadAssignmentForm');
                            const noteForm = document.getElementById('leadNoteForm');
                            const notesList = document.getElementById('leadNotesList');

                            document.getElementById('leadDetailTitle').textContent = lead.name;
                            document.getElementById('leadDetailStatus').textContent = lead.status ? lead.status.replace('_', ' ') : 'New';
                            document.getElementById('leadDetailName').textContent = lead.name || '—';
                            document.getElementById('leadDetailPhone').textContent = lead.phone || '—';
                            document.getElementById('leadDetailEmail').textContent = lead.email || '—';
                            document.getElementById('leadDetailCompany').textContent = lead.company || '—';
                            document.getElementById('leadDetailSource').textContent = lead.source || '—';
                            document.getElementById('leadDetailAssignedTo').textContent = lead.assignee_name || '—';
                            document.getElementById('leadDetailManager').textContent = lead.manager_name || '—';
                            document.getElementById('leadDetailLastNote').textContent = lead.last_note || 'No notes yet';

                            assignmentForm.action = '/lead-management/assigned-leads/' + lead.id + '/assign';
                            noteForm.action = '/lead-management/assigned-leads/' + lead.id + '/notes';

                            const selectedMembers = lead.member_ids || [];
                            Array.from(assignmentForm.querySelectorAll('select[name="member_ids[]"] option')).forEach(function (option) {
                                option.selected = selectedMembers.includes(Number(option.value));
                            });

                            if (data.notes.length === 0) {
                                notesList.innerHTML = '<div class="rounded-lg border border-dashed border-slate-200 bg-slate-50 p-3 text-sm text-slate-500">No notes yet.</div>';
                            } else {
                                notesList.innerHTML = data.notes.map(function (note) {
                                    return '<div class="rounded-lg border border-slate-200 bg-slate-50 p-3"><p class="text-sm text-slate-700">' + (note.note || '') + '</p><p class="mt-2 text-[11px] uppercase tracking-wide text-slate-500">' + (note.user.name || 'System') + ' • ' + (note.formatted_date || '') + '</p></div>';
                                }).join('');
                            }

                            modal.classList.remove('hidden');
                            modal.classList.add('flex');
                        });
                });
            });

            document.querySelectorAll('[data-assign-lead]').forEach(function (button) {
                button.addEventListener('click', function () {
                    const leadId = button.dataset.assignLead;
                    fetch('/lead-management/assigned-leads/' + leadId)
                        .then(function (response) {
                            return response.json();
                        })
                        .then(function (data) {
                            const lead = data.lead;
                            const assignmentForm = document.getElementById('leadAssignmentForm');
                            assignmentForm.action = '/lead-management/assigned-leads/' + lead.id + '/assign';

                            const selectedMembers = lead.member_ids || [];
                            Array.from(assignmentForm.querySelectorAll('select[name="member_ids[]"] option')).forEach(function (option) {
                                option.selected = selectedMembers.includes(Number(option.value));
                            });

                            const modal = document.getElementById('lead-detail-modal');
                            modal.classList.remove('hidden');
                            modal.classList.add('flex');
                        });
                });
            });

            document.querySelectorAll('[data-edit-lead]').forEach(function (button) {
                button.addEventListener('click', function () {
                    const lead = JSON.parse(button.dataset.editLead);
                    const modal = document.getElementById('lead-modal');
                    const form = modal.querySelector('form');
                    form.action = '/lead-management/assigned-leads/' + lead.id;

                    const methodInput = document.createElement('input');
                    methodInput.type = 'hidden';
                    methodInput.name = '_method';
                    methodInput.value = 'PUT';
                    form.appendChild(methodInput);

                    form.querySelector('[name="name"]').value = lead.name || '';
                    form.querySelector('[name="company"]').value = lead.company || '';
                    form.querySelector('[name="phone"]').value = lead.phone || '';
                    form.querySelector('[name="email"]').value = lead.email || '';
                    form.querySelector('[name="source"]').value = lead.source || '';
                    form.querySelector('[name="lead_manager_id"]').value = lead.lead_manager_id || '';
                    form.querySelector('[name="assigned_to"]').value = lead.assigned_to || '';
                    form.querySelector('[name="status"]').value = lead.status || 'new';
                    form.querySelector('[name="last_contact_date"]').value = lead.last_contact_date ? lead.last_contact_date.slice(0, 10) : '';
                    form.querySelector('[name="next_follow_up_date"]').value = lead.next_follow_up_date ? lead.next_follow_up_date.slice(0, 10) : '';
                    modal.classList.remove('hidden');
                    modal.classList.add('flex');
                });
            });
        </script>
    </body>
</html>
