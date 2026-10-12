<?php

declare(strict_types=1);

namespace App\Http\Controllers\Accounts;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClientController extends Controller
{
    /**
     * Client search for invoice/payment forms. Unlike the admin-only
     * clients.search endpoint, this is available to every approved user
     * (accountants included) and searches all clients regardless of status.
     */
    public function search(Request $request): JsonResponse
    {
        $search = trim((string) $request->input('q', ''));

        if (strlen($search) < 2) {
            return response()->json(['results' => []]);
        }

        $clients = Client::query()
            ->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('contact_person', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            })
            ->orderBy('name')
            ->limit(20)
            ->get(['id', 'name', 'contact_person', 'phone', 'email', 'address']);

        return response()->json([
            'results' => $clients->map(fn (Client $client) => [
                'id' => $client->id,
                'text' => $client->name . ($client->contact_person ? " ({$client->contact_person})" : '') . " - {$client->phone}",
                'name' => $client->name,
                'contact_person' => $client->contact_person,
                'phone' => $client->phone,
                'email' => $client->email,
                'address' => $client->address,
            ]),
        ]);
    }

    /**
     * Client 360° view for Accounts: profile, orders, invoices,
     * payments, totals and pending dues.
     */
    public function show(Client $client): View
    {        $client->load([
            'assignedMembers',
            'tasks' => fn ($q) => $q->orderByDesc('id'),
            'invoices' => fn ($q) => $q->orderByDesc('invoice_date')->orderByDesc('id'),
            'accountPayments' => fn ($q) => $q->with('invoice')->orderByDesc('payment_date'),
            'notes',
        ]);

        return view('accounts.clients.show', [
            'client' => $client,
            'totalBilled' => (float) $client->invoices->where('status', '!=', 'void')->sum('total_amount'),
            'totalPaid' => (float) $client->accountPayments->sum('amount'),
            'totalDue' => (float) $client->invoices->where('status', '!=', 'void')->sum('total_due'),
            'pendingInvoices' => $client->invoices->whereIn('status', ['unpaid', 'partial', 'due'])->values(),
        ]);
    }
}
