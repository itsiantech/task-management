@extends('layouts.app')

@section('title', 'Member Directory')

@section('content')
<div class="mx-auto max-w-6xl">
    <div class="mb-8 flex flex-col gap-4 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:flex-row md:items-center md:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-sky-600">Team</p>
            <h1 class="text-3xl font-bold text-slate-900">Member Directory</h1>
        </div>
    </div>

    <form method="GET" class="mb-8 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="flex flex-col gap-3 md:flex-row md:items-end">
            <div>
                <label for="month_year" class="mb-1 block text-sm font-medium text-slate-700">Month</label>
                <input id="month_year" type="month" name="month_year" value="{{ $monthYear }}" class="rounded-lg border border-slate-300 px-3 py-2.5 text-slate-800 focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-200">
            </div>
            <button type="submit" class="rounded-lg bg-sky-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-sky-500">
                Filter
            </button>
        </div>
    </form>

    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="overflow-hidden rounded-xl border border-slate-200">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Member</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Email</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">This Month</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Ledger</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 bg-white">
                    @forelse ($members as $member)
                        <tr>
                            <td class="px-4 py-3 text-sm font-medium text-slate-900">{{ $member->name }}</td>
                            <td class="px-4 py-3 text-sm text-slate-700">{{ $member->email }}</td>
                            <td class="px-4 py-3 text-sm font-medium text-emerald-700">${{ number_format((float) ($member->payments?->sum('amount') ?? 0), 2) }}</td>
                            <td class="px-4 py-3 text-sm">
                                <a href="{{ route('members.payments', $member) }}?month_year={{ $monthYear }}" class="font-medium text-indigo-600 hover:text-indigo-500">
                                    View ledger
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-6 text-center text-sm text-slate-500">No approved members available.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
