<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cloudvault\Column;
use App\Models\Cloudvault\Row;
use App\Models\Cloudvault\RowShare;
use App\Models\Cloudvault\Share;
use App\Models\Cloudvault\Sheet;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CloudVaultController extends Controller
{
    use AuthorizesRequests;

    private const COLUMN_TYPES = ['domain', 'dropdown', 'password', 'email', 'text', 'note'];
    private const STATUSES = ['Active', 'Deactive', 'Suspended'];
    private const SHARE_ROLES = ['Admin', 'Editor', 'Viewer'];

    /** Effective UI role for the requester on a sheet. */
    private function myRole(Sheet $sheet): ?string
    {
        $user = auth()->user();

        if ($user && ($sheet->user_id === $user->id || $user->isAdmin())) {
            return 'Admin';
        }

        return $sheet->shareRoleFor($user);
    }

    private function serializeSheet(Sheet $sheet, bool $full = true): array
    {
        $sheet->loadMissing(['user:id,name,email', 'columns', 'shares']);

        $data = [
            'id' => $sheet->id,
            'title' => $sheet->title,
            'color' => $sheet->color,
            'public_link' => (bool) $sheet->public_link,
            'owner' => $sheet->user ? ['id' => $sheet->user->id, 'name' => $sheet->user->name, 'email' => $sheet->user->email] : null,
            'my_role' => $this->myRole($sheet),
            'columns' => $sheet->columns->map(fn (Column $c) => $this->serializeColumn($c))->all(),
            'rows_count' => $sheet->rows()->count(),
            'shares' => $sheet->shares->map(fn (Share $s) => ['email' => $s->email, 'role' => $s->role])->all(),
        ];

        if ($full) {
            $data['rows'] = $sheet->rows()->orderBy('position')->get()
                ->map(fn (Row $r) => $this->serializeRow($r))->all();
        }

        return $data;
    }

    private function serializeColumn(Column $column): array
    {
        return [
            'id' => $column->id,
            'title' => $column->title,
            'type' => $column->type,
            'width' => (int) $column->width,
            'header_bg' => $column->header_bg,
            'header_color' => $column->header_color,
            'header_size' => (int) $column->header_size,
            'header_align' => $column->header_align,
            'header_bold' => (bool) $column->header_bold,
            'options' => $column->options ?? [],
            'sticky' => (bool) $column->sticky,
            'position' => (int) $column->position,
        ];
    }

    private function serializeRow(Row $row): array
    {
        $cells = [];
        foreach ((array) ($row->cells ?? []) as $key => $value) {
            $cells[(string) $key] = (string) $value;
        }

        return ['id' => $row->id, 'cells' => $cells, 'status' => $row->status, 'position' => (int) $row->position];
    }

    private function columnRules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(self::COLUMN_TYPES)],
            'width' => ['nullable', 'integer', 'min:60', 'max:800'],
            'header_bg' => ['nullable', 'string', 'max:20'],
            'header_color' => ['nullable', 'string', 'max:20'],
            'header_size' => ['nullable', 'integer', 'in:12,14,16'],
            'header_align' => ['nullable', Rule::in(['left', 'center', 'right'])],
            'header_bold' => ['nullable', 'boolean'],
            'options' => ['nullable', 'array'],
            'options.*.label' => ['required_with:options', 'string', 'max:100'],
            'options.*.bg' => ['nullable', 'string', 'max:20'],
            'options.*.color' => ['nullable', 'string', 'max:20'],
            'sticky' => ['nullable', 'boolean'],
        ];
    }

    // ---------- sheets ----------

    public function index(): JsonResponse
    {
        $user = auth()->user();

        $sheets = Sheet::with(['user:id,name,email', 'columns', 'shares'])
            ->withCount('rows')
            ->where(function ($query) use ($user) {
                $query->where('user_id', $user->id)
                    ->orWhereIn('id', Share::where('email', $user->email)->select('sheet_id'));

                if ($user->isAdmin()) {
                    $query->orWhereNotNull('cloudvault_sheets.id');
                }
            })
            ->orderByDesc('updated_at')
            ->get()
            ->map(fn (Sheet $sheet) => $this->serializeSheet($sheet, false));

        return response()->json(['sheets' => $sheets]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'color' => ['nullable', 'string', 'max:20'],
            'columns' => ['required', 'array', 'min:1', 'max:50'],
            'columns.*.title' => ['required', 'string', 'max:255'],
            'columns.*.type' => ['required', Rule::in(self::COLUMN_TYPES)],
            'columns.*.options' => ['nullable', 'array'],
            'rows_count' => ['nullable', 'integer', 'min:0', 'max:500'],
        ]);

        $sheet = DB::transaction(function () use ($data) {
            $sheet = Sheet::create([
                'user_id' => auth()->id(),
                'title' => $data['title'],
                'color' => $data['color'] ?? '#0f766e',
            ]);

            foreach ($data['columns'] as $i => $col) {
                $sheet->columns()->create([
                    'title' => $col['title'],
                    'type' => $col['type'],
                    'options' => $col['options'] ?? null,
                    'sticky' => ($i === 0),
                    'position' => $i,
                ]);
            }

            $rowCount = max(0, (int) ($data['rows_count'] ?? 0));
            for ($i = 0; $i < $rowCount; $i++) {
                $sheet->rows()->create(['cells' => [], 'position' => $i]);
            }

            return $sheet;
        });

        return response()->json(['sheet' => $this->serializeSheet($sheet)], 201);
    }

    public function show(Sheet $sheet): JsonResponse
    {
        $this->authorize('view', $sheet);

        return response()->json(['sheet' => $this->serializeSheet($sheet)]);
    }

    public function update(Request $request, Sheet $sheet): JsonResponse
    {
        $this->authorize('update', $sheet);

        $data = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'color' => ['sometimes', 'string', 'max:20'],
            'public_link' => ['sometimes', 'boolean'],
        ]);

        if (array_key_exists('public_link', $data)) {
            $this->authorize('manage', $sheet);
        }

        $sheet->update($data);

        return response()->json(['sheet' => $this->serializeSheet($sheet, false)]);
    }

    public function destroy(Sheet $sheet): JsonResponse
    {
        $this->authorize('manage', $sheet);
        $sheet->delete();

        return response()->json(['message' => 'Sheet deleted.']);
    }

    public function duplicate(Sheet $sheet): JsonResponse
    {
        $this->authorize('duplicate', $sheet);

        $copy = DB::transaction(function () use ($sheet) {
            $sheet->loadMissing(['columns', 'rows']);

            $copy = Sheet::create([
                'user_id' => auth()->id(),
                'title' => $sheet->title . ' (copy)',
                'color' => $sheet->color,
            ]);

            $map = [];
            foreach ($sheet->columns as $col) {
                $new = $copy->columns()->create([...$col->only([
                    'title', 'type', 'width', 'header_bg', 'header_color',
                    'header_size', 'header_align', 'header_bold', 'options', 'sticky', 'position',
                ])]);
                $map[$col->id] = $new->id;
            }

            foreach ($sheet->rows as $row) {
                $cells = [];
                foreach ((array) ($row->cells ?? []) as $key => $value) {
                    $cells[(string) ($map[$key] ?? $key)] = (string) $value;
                }
                $copy->rows()->create(['cells' => $cells, 'status' => $row->status, 'position' => $row->position]);
            }

            return $copy;
        });

        return response()->json(['sheet' => $this->serializeSheet($copy)], 201);
    }

    // ---------- columns ----------

    public function storeColumn(Request $request, Sheet $sheet): JsonResponse
    {
        $this->authorize('update', $sheet);

        $data = $request->validate([...$this->columnRules(), 'position' => ['nullable', 'integer', 'min:0']]);
        $position = $data['position'] ?? $sheet->columns()->count();
        unset($data['position']);

        $sheet->columns()->where('position', '>=', $position)->increment('position');

        $column = $sheet->columns()->create([...$data, 'position' => $position]);

        return response()->json(['column' => $this->serializeColumn($column)], 201);
    }

    public function updateColumn(Request $request, Sheet $sheet, Column $column): JsonResponse
    {
        $this->authorize('update', $sheet);
        abort_unless($column->sheet_id === $sheet->id, 404);

        $column->update($request->validate($this->columnRules()));

        return response()->json(['column' => $this->serializeColumn($column->fresh())]);
    }

    public function destroyColumn(Sheet $sheet, Column $column): JsonResponse
    {
        $this->authorize('update', $sheet);
        abort_unless($column->sheet_id === $sheet->id, 404);

        DB::transaction(function () use ($sheet, $column) {
            $cells = [];
            foreach ($sheet->rows()->cursor() as $row) {
                $data = (array) ($row->cells ?? []);
                unset($data[(string) $column->id]);
                $cells[$row->id] = $data;
            }
            foreach ($cells as $id => $data) {
                Row::where('id', $id)->update(['cells' => $data]);
            }

            $column->delete();
            $sheet->columns()->orderBy('position')->get()->values()
                ->each(fn (Column $c, int $i) => $c->update(['position' => $i]));
        });

        return response()->json(['message' => 'Column deleted.']);
    }

    // ---------- rows ----------

    public function rows(Request $request, Sheet $sheet): JsonResponse
    {
        $this->authorize('view', $sheet);

        $query = $sheet->rows()->orderBy('position');

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        $rows = $query->paginate($request->integer('per_page', 25));

        return response()->json([
            'rows' => collect($rows->items())->map(fn (Row $r) => $this->serializeRow($r))->all(),
            'meta' => ['current_page' => $rows->currentPage(), 'last_page' => $rows->lastPage(), 'total' => $rows->total()],
        ]);
    }

    public function storeRow(Request $request, Sheet $sheet): JsonResponse
    {
        $this->authorize('update', $sheet);

        $data = $request->validate([
            'cells' => ['nullable', 'array'],
            'cells.*' => ['nullable', 'string'],
            'status' => ['nullable', Rule::in(self::STATUSES)],
            'position' => ['nullable', 'integer', 'min:0'],
        ]);

        $position = $data['position'] ?? $sheet->rows()->count();
        $sheet->rows()->where('position', '>=', $position)->increment('position');

        $row = $sheet->rows()->create([
            'cells' => $data['cells'] ?? [],
            'status' => $data['status'] ?? 'Active',
            'position' => $position,
        ]);

        return response()->json(['row' => $this->serializeRow($row)], 201);
    }

    public function updateRow(Request $request, Sheet $sheet, Row $row): JsonResponse
    {
        $this->authorize('update', $sheet);
        abort_unless($row->sheet_id === $sheet->id, 404);

        $data = $request->validate([
            'cells' => ['sometimes', 'array'],
            'cells.*' => ['nullable', 'string'],
            'status' => ['sometimes', Rule::in(self::STATUSES)],
            'position' => ['sometimes', 'integer', 'min:0'],
        ]);

        $row->update($data);

        return response()->json(['row' => $this->serializeRow($row->fresh())]);
    }

    public function updateCell(Request $request, Sheet $sheet, Row $row): JsonResponse
    {
        $this->authorize('update', $sheet);
        abort_unless($row->sheet_id === $sheet->id, 404);

        $data = $request->validate([
            'column_id' => ['required', 'integer'],
            'value' => ['nullable', 'string'],
        ]);

        $column = $sheet->columns()->findOrFail($data['column_id']);

        $cells = (array) ($row->cells ?? []);
        $cells[(string) $column->id] = (string) ($data['value'] ?? '');
        $row->update(['cells' => $cells]);

        return response()->json(['row' => $this->serializeRow($row->fresh())]);
    }

    public function destroyRow(Sheet $sheet, Row $row): JsonResponse
    {
        $this->authorize('update', $sheet);
        abort_unless($row->sheet_id === $sheet->id, 404);
        $row->delete();

        return response()->json(['message' => 'Row deleted.']);
    }

    // ---------- sharing ----------

    public function storeShare(Request $request, Sheet $sheet): JsonResponse
    {
        $this->authorize('manage', $sheet);

        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'role' => ['required', Rule::in(self::SHARE_ROLES)],
        ]);

        $share = Share::updateOrCreate(
            ['sheet_id' => $sheet->id, 'email' => strtolower($data['email'])],
            ['role' => $data['role']]
        );

        return response()->json(['share' => ['email' => $share->email, 'role' => $share->role]], 201);
    }

    public function destroyShare(Sheet $sheet, string $email): JsonResponse
    {
        $this->authorize('manage', $sheet);

        Share::where('sheet_id', $sheet->id)->where('email', urldecode($email))->delete();

        return response()->json(['message' => 'Share removed.']);
    }

    public function rowShares(Sheet $sheet): JsonResponse
    {
        $this->authorize('manage', $sheet);

        return response()->json([
            'shares' => RowShare::where('sheet_id', $sheet->id)->orderByDesc('id')->get()
                ->map(fn (RowShare $s) => [
                    'token' => $s->token,
                    'row_id' => $s->row_id,
                    'expires_at' => $s->expires_at?->toISOString(),
                    'created_at' => $s->created_at?->toISOString(),
                ]),
        ]);
    }

    public function storeRowShare(Request $request, Sheet $sheet): JsonResponse
    {
        $this->authorize('manage', $sheet);

        $data = $request->validate([
            'row_id' => ['required', 'integer'],
            'expires_in' => ['nullable', Rule::in(['24h', '7d', 'never'])],
        ]);

        $row = $sheet->rows()->findOrFail($data['row_id']);

        $share = RowShare::create([
            'sheet_id' => $sheet->id,
            'row_id' => $row->id,
            'token' => Str::random(32),
            'expires_at' => match ($data['expires_in'] ?? '24h') {
                '7d' => now()->addDays(7),
                'never' => null,
                default => now()->addDay(),
            },
        ]);

        return response()->json([
            'share' => [
                'token' => $share->token,
                'row_id' => $share->row_id,
                'expires_at' => $share->expires_at?->toISOString(),
            ],
        ], 201);
    }

    public function destroyRowShare(Sheet $sheet, string $token): JsonResponse
    {
        $this->authorize('manage', $sheet);

        RowShare::where('sheet_id', $sheet->id)->where('token', $token)->delete();

        return response()->json(['message' => 'Link revoked.']);
    }

    /** Public single-row view behind a token (no login required). */
    public function sharedRow(string $token): JsonResponse
    {
        $share = RowShare::where('token', $token)->first();

        abort_if(! $share || $share->isExpired(), 404, 'This share link is invalid or expired.');

        $sheet = $share->sheet()->with(['columns', 'user:id,name,email'])->firstOrFail();
        $row = $share->row()->firstOrFail();

        abort_if($row->sheet_id !== $sheet->id, 404);

        return response()->json([
            'sheet' => ['id' => $sheet->id, 'title' => $sheet->title, 'color' => $sheet->color,
                'columns' => $sheet->columns->map(fn (Column $c) => $this->serializeColumn($c))->all()],
            'row' => $this->serializeRow($row),
            'expires_at' => $share->expires_at?->toISOString(),
        ]);
    }

    /** Team directory for the share dialog (authenticated users only). */
    public function members(): JsonResponse
    {
        return response()->json([
            'members' => User::where('is_approved', true)->orderBy('name')
                ->get(['id', 'name', 'email', 'role'])
                ->map(fn (User $u) => ['id' => $u->id, 'name' => $u->name, 'email' => $u->email, 'role' => $u->role]),
        ]);
    }
}
