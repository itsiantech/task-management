@extends('layouts.app')

@section('title', 'User Approval Panel')

@section('content')
<div class="mx-auto max-w-5xl">
    <div class="mb-8 flex items-center justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-indigo-600">Admin</p>
            <h1 class="text-3xl font-bold text-slate-900">User Approval Panel</h1>
        </div>
    </div>

    @if ($pendingUsers->isEmpty())
        <div class="rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-12 text-center shadow-sm">
            <p class="text-lg font-medium text-slate-700">No pending user approvals.</p>
        </div>
    @else
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Email</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Role</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 bg-white">
                    @foreach ($pendingUsers as $user)
                        <tr>
                            <td class="px-6 py-4 text-sm font-medium text-slate-900">{{ $user->name }}</td>
                            <td class="px-6 py-4 text-sm text-slate-600">{{ $user->email }}</td>
                            <td class="px-6 py-4 text-sm text-slate-600">{{ ucfirst($user->role) }}</td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <form action="{{ route('admin.approvals.update', $user) }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="status" value="approve">
                                        <button type="submit" class="rounded-lg bg-emerald-600 px-3 py-2 text-sm font-medium text-white hover:bg-emerald-500">
                                            Approve
                                        </button>
                                    </form>
                                    <form action="{{ route('admin.approvals.update', $user) }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="status" value="reject">
                                        <button type="submit" class="rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-sm font-medium text-rose-700 hover:bg-rose-100">
                                            Reject
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
