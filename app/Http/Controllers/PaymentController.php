<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class PaymentController extends Controller
{
    public function adminDashboard(Request $request)
    {
        $monthYear = $request->get('month_year', now()->format('Y-m'));
        $hasPaymentsTable = Schema::hasTable('payments');

        $memberBreakdown = collect();

        if ($hasPaymentsTable) {
            $memberBreakdown = User::where('role', 'member')
                ->where('is_approved', true)
                ->with(['payments' => function ($query) use ($monthYear) {
                    $query->where('month_year', $monthYear);
                }])
                ->orderBy('name')
                ->get()
                ->map(function ($user) {
                    $total = $user->payments->sum('amount');

                    return [
                        'user' => $user,
                        'total' => $total,
                    ];
                });
        } else {
            $memberBreakdown = User::where('role', 'member')
                ->where('is_approved', true)
                ->orderBy('name')
                ->get()
                ->map(function ($user) {
                    $user->setRelation('payments', collect());

                    return [
                        'user' => $user,
                        'total' => 0,
                    ];
                });
        }

        $payments = $hasPaymentsTable
            ? Payment::with(['user', 'task'])
                ->when($monthYear, fn ($query) => $query->where('month_year', $monthYear))
                ->orderByDesc('payment_date')
                ->get()
            : collect();

        $totals = [
            'budget' => Task::sum('total_budget'),
            'collected' => $hasPaymentsTable ? Payment::sum('amount') : 0,
            'dues' => Task::sum('due_amount'),
        ];

        return view('admin.financial-dashboard', compact('monthYear', 'memberBreakdown', 'payments', 'totals'));
    }

    public function memberLedger(Request $request, ?User $user = null)
    {
        $currentUser = Auth::user();

        if ($user && ! $currentUser->isAdmin() && $currentUser->id !== $user->id) {
            abort(403, 'You cannot view this member ledger.');
        }

        $selectedUser = $user ?? $currentUser;
        $monthYear = $request->get('month_year', now()->format('Y-m'));
        $hasPaymentsTable = Schema::hasTable('payments');

        $payments = $hasPaymentsTable
            ? Payment::with(['task'])
                ->where('user_id', $selectedUser->id)
                ->when($monthYear, fn ($query) => $query->where('month_year', $monthYear))
                ->orderByDesc('payment_date')
                ->get()
            : collect();

        $tasks = $selectedUser->tasks()->with(['members', 'assignee', 'creator'])->get();

        $monthSummary = $hasPaymentsTable
            ? Payment::where('user_id', $selectedUser->id)
                ->selectRaw('month_year, SUM(amount) as total_paid')
                ->groupBy('month_year')
                ->orderByDesc('month_year')
                ->get()
            : collect();

        $dueBalance = $tasks->sum('due_amount');

        return view('payments.member-ledger', compact('selectedUser', 'payments', 'tasks', 'monthSummary', 'monthYear', 'dueBalance'));
    }

    public function memberDirectory(Request $request)
    {
        $monthYear = $request->get('month_year', now()->format('Y-m'));
        $hasPaymentsTable = Schema::hasTable('payments');

        $members = User::where('role', 'member')
            ->where('is_approved', true)
            ->when($hasPaymentsTable, function ($query) use ($monthYear) {
                $query->with(['payments' => function ($inner) use ($monthYear) {
                    $inner->where('month_year', $monthYear);
                }]);
            })
            ->orderBy('name')
            ->get();

        if (! $hasPaymentsTable) {
            $members->each(function ($member) {
                $member->setRelation('payments', collect());
            });
        }

        return view('payments.member-directory', compact('members', 'monthYear'));
    }
}
