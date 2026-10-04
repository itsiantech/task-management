<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\ClientNote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ClientController extends Controller
{
    public function index(Request $request)
    {
        $perPage = in_array((int) $request->input('per_page', 10), [10, 20, 50, 100], true)
            ? (int) $request->input('per_page', 10)
            : 10;

        $search = trim((string) $request->input('search', ''));

        $clients = Client::query()
            ->with(['notes.user', 'assignedMembers'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($innerQuery) use ($search) {
                    $innerQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('address', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate($perPage)
            ->appends($request->query());

        $teamMembers = \App\Models\User::query()
            ->where('is_approved', true)
            ->whereIn('role', ['admin', 'member'])
            ->orderBy('name')
            ->get();

        return view('clients.index', compact('clients', 'teamMembers', 'search', 'perPage'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string'],
            'status' => ['nullable', Rule::in(['pending', 'confirm', 'reject'])],
        ]);

        Client::create($validated + ['status' => $validated['status'] ?? 'pending']);

        return redirect()->route('clients.index')->with('success', 'Client created successfully.');
    }

    public function show(Client $client)
    {
        $client->load('notes.user');

        return response()->json([
            'client' => [
                'id' => $client->id,
                'name' => $client->name,
                'phone' => $client->phone,
                'email' => $client->email,
                'address' => $client->address,
                'status' => $client->status,
                'status_label' => $this->statusLabel($client->status),
            ],
            'notes' => $client->notes->map(function (ClientNote $note) {
                return [
                    'id' => $note->id,
                    'note' => $note->note,
                    'interaction_date' => $note->interaction_date ? $note->interaction_date->format('Y-m-d\TH:i') : null,
                    'formatted_date' => $note->interaction_date ? $note->interaction_date->format('M d, Y h:i A') : null,
                    'user' => [
                        'name' => $note->user?->name ?? 'System',
                    ],
                ];
            })->values(),
        ]);
    }

    public function update(Request $request, Client $client)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string'],
            'status' => ['nullable', Rule::in(['pending', 'confirm', 'reject'])],
        ]);

        $client->update($validated + ['status' => $validated['status'] ?? $client->status]);

        return redirect()->route('clients.index')->with('success', 'Client updated successfully.');
    }

    public function destroy(Client $client)
    {
        $client->delete();

        return redirect()->route('clients.index')->with('success', 'Client deleted successfully.');
    }

    public function bulkAssign(Request $request)
    {
        $validated = $request->validate([
            'client_ids' => ['required', 'array'],
            'client_ids.*' => ['required', 'integer', 'exists:clients,id'],
            'member_ids' => ['nullable', 'array'],
            'member_ids.*' => ['required', 'integer', 'exists:users,id'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $memberIds = collect($validated['member_ids'] ?? [])
            ->merge(($validated['user_id'] ?? null) ? [(int) $validated['user_id']] : [])
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (empty($memberIds)) {
            return back()->withErrors(['member_ids' => 'Please select at least one team member.']);
        }

        foreach ($validated['client_ids'] as $clientId) {
            $client = Client::findOrFail($clientId);
            $client->assignedMembers()->syncWithoutDetaching($memberIds);
        }

        return redirect()->route('clients.index')->with('success', 'Clients assigned successfully.');
    }

    public function updateStatus(Request $request, Client $client)
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['pending', 'confirm', 'reject'])],
        ]);

        $client->update(['status' => $validated['status']]);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'status' => $client->status,
                'status_label' => $this->statusLabel($client->status),
            ]);
        }

        return back()->with('success', 'Client status updated successfully.');
    }

    public function import(Request $request)
    {
        $request->validate([
            'csv_file' => ['required', 'file', 'mimes:csv,xls,xlsx,txt', 'max:20480'],
        ]);

        $file = $request->file('csv_file');
        $storedPath = $file->storeAs('client-imports', uniqid('client-import-') . '.' . $file->getClientOriginalExtension(), 'local');
        $fullPath = Storage::path($storedPath);

        $rows = $this->readImportRows($fullPath, $file->getClientOriginalExtension());
        $imported = 0;

        foreach ($rows as $row) {
            $phone = $this->extractPhoneFromRow($row);
            if ($phone === null) {
                continue;
            }

            $name = $this->extractNameFromRow($row, $phone);
            $email = $this->extractStringFromRow($row, ['email', 'e-mail', 'mail']);
            $address = $this->extractStringFromRow($row, ['address', 'street', 'city', 'location']);
            $status = $this->extractStatusFromRow($row);

            Client::updateOrCreate(
                ['phone' => $phone],
                [
                    'name' => $name,
                    'email' => $email ?: null,
                    'address' => $address ?: null,
                    'status' => $status,
                ]
            );

            $imported++;
        }

        return redirect()->route('clients.index')->with('success', "Imported {$imported} client(s) successfully.");
    }

    protected function readImportRows(string $fullPath, string $extension): array
    {
        $extension = strtolower($extension);

        if (in_array($extension, ['csv', 'txt'], true)) {
            if (($handle = fopen($fullPath, 'r')) === false) {
                return [];
            }

            $rows = [];
            while (($row = fgetcsv($handle)) !== false) {
                $rows[] = $row;
            }
            fclose($handle);

            if (empty($rows)) {
                return [];
            }

            $headerRow = array_map(fn ($value) => strtolower(trim((string) $value)), $rows[0]);
            $dataRows = [];
            foreach (array_slice($rows, 1) as $row) {
                $record = [];
                foreach ($headerRow as $index => $header) {
                    $record[$header] = $row[$index] ?? null;
                }
                $dataRows[] = $record;
            }

            return $dataRows;
        }

        try {
            $spreadsheet = IOFactory::load($fullPath);
            $sheet = $spreadsheet->getActiveSheet();
            $rows = [];

            foreach ($sheet->getRowIterator() as $row) {
                $values = [];

                foreach ($row->getCellIterator() as $cell) {
                    $values[] = $cell->getValue();
                }

                $rows[] = $values;
            }

            if (empty($rows)) {
                return [];
            }

            $headerRow = array_map(fn ($value) => strtolower(trim((string) $value)), $rows[0]);
            $dataRows = [];
            foreach (array_slice($rows, 1) as $row) {
                $record = [];
                foreach ($headerRow as $index => $header) {
                    $record[$header] = $row[$index] ?? null;
                }
                $dataRows[] = $record;
            }

            return $dataRows;
        } catch (\Throwable $e) {
            return [];
        }
    }

    protected function extractPhoneFromRow(array $row): ?string
    {
        $candidateValues = array_values($row);

        foreach ($candidateValues as $value) {
            $phone = $this->normalizePhone((string) $value);
            if ($phone !== null) {
                return $phone;
            }
        }

        foreach ($row as $key => $value) {
            if (str_contains((string) $key, 'phone')) {
                $phone = $this->normalizePhone((string) $value);
                if ($phone !== null) {
                    return $phone;
                }
            }
        }

        return null;
    }

    protected function extractNameFromRow(array $row, string $phone): string
    {
        $nameCandidates = [
            $row['name'] ?? null,
            $row['client_name'] ?? null,
            $row['full_name'] ?? null,
            $row['customer_name'] ?? null,
        ];

        foreach ($nameCandidates as $candidate) {
            $clean = trim((string) $candidate);
            if ($clean !== '') {
                return $clean;
            }
        }

        return 'Client ' . $phone;
    }

    protected function extractStringFromRow(array $row, array $keys): ?string
    {
        foreach ($keys as $key) {
            if (isset($row[$key])) {
                $value = trim((string) $row[$key]);
                if ($value !== '') {
                    return $value;
                }
            }
        }

        foreach ($row as $key => $value) {
            if (in_array(strtolower((string) $key), $keys, true)) {
                $value = trim((string) $value);
                if ($value !== '') {
                    return $value;
                }
            }
        }

        return null;
    }

    protected function extractStatusFromRow(array $row): string
    {
        $statusValue = strtolower(trim((string) ($row['status'] ?? 'pending')));

        if (! in_array($statusValue, ['pending', 'confirm', 'reject'], true)) {
            return 'pending';
        }

        return $statusValue;
    }

    protected function normalizePhone(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $value);
        if ($digits === '' || strlen($digits) < 7 || strlen($digits) > 15) {
            return null;
        }

        if (str_starts_with($value, '+') || str_starts_with($value, '00')) {
            return '+' . ltrim($digits, '0');
        }

        return '+' . $digits;
    }

    public function downloadTemplate()
    {
        $csv = <<<'CSV'
name,phone,email,address,status
John Smith,+1234567890,john@example.com,123 Main St,pending
Jane Doe,+1987654321,jane@example.com,456 Side Ave,confirm
, +15550000001,, ,pending
, +15550000002,, ,confirm

CSV;

        return response($csv)
            ->header('Content-Type', 'text/csv; charset=UTF-8')
            ->header('Content-Disposition', 'attachment; filename="clients_template.csv"');
    }

    public function storeNote(Request $request, Client $client)
    {
        $validated = $request->validate([
            'note' => ['required', 'string'],
            'interaction_date' => ['nullable', 'date'],
        ]);

        $note = $client->notes()->create([
            'user_id' => auth()->id(),
            'note' => $validated['note'],
            'interaction_date' => $validated['interaction_date'] ?? now(),
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'note' => [
                    'id' => $note->id,
                    'note' => $note->note,
                    'interaction_date' => $note->interaction_date?->format('Y-m-d\TH:i'),
                    'formatted_date' => $note->interaction_date?->format('M d, Y h:i A'),
                    'user' => [
                        'name' => auth()->user()->name,
                    ],
                ],
            ]);
        }

        return redirect()->route('clients.index')->with('success', 'Interaction note added.');
    }

    public function updateNote(Request $request, Client $client, ClientNote $note)
    {
        $this->authorizeNoteAccess($client, $note);

        $validated = $request->validate([
            'note' => ['required', 'string'],
            'interaction_date' => ['nullable', 'date'],
        ]);

        $note->update([
            'note' => $validated['note'],
            'interaction_date' => $validated['interaction_date'] ?? $note->interaction_date,
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'note' => [
                    'id' => $note->id,
                    'note' => $note->note,
                    'interaction_date' => $note->interaction_date?->format('Y-m-d\TH:i'),
                    'formatted_date' => $note->interaction_date?->format('M d, Y h:i A'),
                    'user' => ['name' => $note->user?->name ?? auth()->user()->name],
                ],
            ]);
        }

        return redirect()->route('clients.index')->with('success', 'Interaction note updated successfully.');
    }

    public function destroyNote(Request $request, Client $client, ClientNote $note)
    {
        $this->authorizeNoteAccess($client, $note);

        $note->delete();

        if ($request->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->route('clients.index')->with('success', 'Interaction note deleted.');
    }

    protected function authorizeNoteAccess(Client $client, ClientNote $note): void
    {
        if ($note->client_id !== $client->id) {
            abort(404);
        }
    }

    protected function statusLabel(?string $status): string
    {
        return match ($status) {
            'confirm' => 'Confirmed',
            'reject' => 'Rejected',
            default => 'Pending',
        };
    }
}
