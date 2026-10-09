<?php

namespace App\Http\Controllers;

use App\Models\Board;
use App\Models\BoardCard;
use App\Models\BoardColumn;
use App\Models\User;
use App\Support\SafeHtml;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class KanbanBoardController extends Controller
{
    use AuthorizesRequests;

    /* ------------------------------------------------------------------
     |  Boards
     | ------------------------------------------------------------------ */

    public function index()
    {
        $user = Auth::user();

        $boards = Board::query()
            ->accessibleTo($user)
            ->with(['creator', 'memberships'])
            ->withCount(['columns', 'cards'])
            ->latest('updated_at')
            ->get()
            ->each(fn (Board $board) => $board->setAttribute('my_role', $board->roleFor($user)));

        return view('boards.index', compact('boards'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000000'],
            'is_private' => ['nullable', 'boolean'],
        ]);

        $board = DB::transaction(function () use ($validated, $request) {
            $board = Board::create([
                'name' => trim($validated['name']),
                'description' => SafeHtml::clean($validated['description'] ?? ''),
                'is_private' => $request->boolean('is_private', true),
                'created_by' => Auth::id(),
            ]);

            $board->memberships()->create(['user_id' => Auth::id(), 'role' => 'owner']);

            foreach (['To Do', 'In Progress', 'Done'] as $i => $title) {
                $board->columns()->create(['title' => $title, 'position' => $i]);
            }

            return $board;
        });

        return redirect()->route('boards.show', $board)->with('success', 'Board created successfully.');
    }

    public function show(Board $board)
    {
        $this->authorize('view', $board);

        $user = Auth::user();
        $board->load([
            'columns.cards' => fn ($q) => $q->orderBy('position'),
            'memberships.user',
            'creator',
        ]);

        $role = $board->roleFor($user);
        $canEdit = in_array($role, ['owner', 'editor'], true);
        $canManage = $role === 'owner';

        $teamMembers = $canManage
            ? User::where('is_approved', true)->where('id', '!=', $board->created_by)->orderBy('name')->get()
            : collect();

        $sharedRoles = $board->memberships->pluck('role', 'user_id');

        return view('boards.show', compact('board', 'role', 'canEdit', 'canManage', 'teamMembers', 'sharedRoles'));
    }

    public function update(Request $request, Board $board)
    {
        $this->authorize('update', $board);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000000'],
            'is_private' => ['nullable', 'boolean'],
        ]);

        $board->update([
            'name' => trim($validated['name']),
            'description' => SafeHtml::clean($validated['description'] ?? ''),
            'is_private' => $request->boolean('is_private'),
        ]);

        return redirect()->route('boards.show', $board)->with('success', 'Board updated successfully.');
    }

    /** Debounced auto-save target for the rich-text board notes editor. */
    public function updateDescription(Request $request, Board $board): JsonResponse
    {
        $this->authorize('contribute', $board);

        $validated = $request->validate([
            'description' => ['nullable', 'string', 'max:1000000'],
        ]);

        $board->update(['description' => SafeHtml::clean($validated['description'] ?? '')]);

        return response()->json(['ok' => true, 'saved_at' => now()->toISOString()]);
    }

    public function destroy(Board $board)
    {
        $this->authorize('delete', $board);

        $board->delete();

        return redirect()->route('boards.index')->with('success', 'Board deleted.');
    }

    public function share(Request $request, Board $board)
    {
        $this->authorize('share', $board);

        $validated = $request->validate([
            'user_ids' => ['nullable', 'array'],
            'user_ids.*' => ['integer', 'exists:users,id'],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['in:editor,viewer'],
        ]);

        $approvedIds = User::where('is_approved', true)
            ->whereIn('id', $validated['user_ids'] ?? [])
            ->where('id', '!=', $board->created_by)
            ->pluck('id');

        DB::transaction(function () use ($board, $approvedIds, $validated) {
            // Rebuild the shared list: the creator always stays owner.
            $board->memberships()->where('user_id', '!=', $board->created_by)->delete();

            foreach ($approvedIds as $id) {
                $board->memberships()->create([
                    'user_id' => $id,
                    'role' => $validated['roles'][$id] ?? 'viewer',
                ]);
            }

            $board->memberships()->firstOrCreate(
                ['user_id' => $board->created_by],
                ['role' => 'owner']
            );
        });

        return redirect()->route('boards.show', $board)->with('success', 'Board sharing updated.');
    }

    /* ------------------------------------------------------------------
     |  Columns (lists)
     | ------------------------------------------------------------------ */

    public function storeColumn(Request $request, Board $board): JsonResponse
    {
        $this->authorize('contribute', $board);

        $validated = $request->validate(['title' => ['required', 'string', 'max:255']]);

        $column = $board->columns()->create([
            'title' => trim($validated['title']),
            'position' => ((int) $board->columns()->max('position')) + 1,
        ]);

        return response()->json(['ok' => true, 'column' => ['id' => $column->id, 'title' => $column->title]], 201);
    }

    public function updateColumn(Request $request, Board $board, BoardColumn $column): JsonResponse
    {
        $this->authorize('contribute', $board);

        $validated = $request->validate(['title' => ['required', 'string', 'max:255']]);
        $column->update(['title' => trim($validated['title'])]);

        return response()->json(['ok' => true, 'column' => ['id' => $column->id, 'title' => $column->title]]);
    }

    public function destroyColumn(Board $board, BoardColumn $column): JsonResponse
    {
        $this->authorize('contribute', $board);

        $column->delete();

        return response()->json(['ok' => true]);
    }

    /* ------------------------------------------------------------------
     |  Cards
     | ------------------------------------------------------------------ */

    public function storeCard(Request $request, Board $board, BoardColumn $column): JsonResponse
    {
        $this->authorize('contribute', $board);

        $validated = $request->validate(['title' => ['required', 'string', 'max:255']]);

        $card = $column->cards()->create([
            'board_id' => $board->id,
            'title' => trim($validated['title']),
            'position' => ((int) $column->cards()->max('position')) + 1,
            'created_by' => Auth::id(),
        ]);

        return response()->json(['ok' => true, 'card' => $this->summary($card)], 201);
    }

    /** Full card incl. decrypted credentials (loaded only when the modal opens). */
    public function showCard(Board $board, BoardCard $card): JsonResponse
    {
        $this->authorize('view', $board);

        return response()->json(['ok' => true, 'card' => $this->detail($card)]);
    }

    public function updateCard(Request $request, Board $board, BoardCard $card): JsonResponse
    {
        $this->authorize('contribute', $board);

        // Allow "facebook.com/ads" style input.
        $url = trim((string) $request->input('credentials.url', ''));
        if ($url !== '' && ! preg_match('#^https?://#i', $url)) {
            $request->merge(['credentials' => array_merge((array) $request->input('credentials', []), ['url' => 'https://'.$url])]);
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:20000'],
            'credentials' => ['nullable', 'array'],
            'credentials.username' => ['nullable', 'string', 'max:500'],
            'credentials.password' => ['nullable', 'string', 'max:1000'],
            'credentials.url' => ['nullable', 'url:http,https', 'max:2048'],
        ]);

        $credentials = collect($validated['credentials'] ?? [])
            ->only(['username', 'password', 'url'])
            ->map(fn ($v) => $v === null ? '' : (string) $v)
            ->filter(fn ($v) => $v !== '');

        $card->update([
            'title' => trim($validated['title']),
            'description' => $validated['description'] ?? null,
            'credentials_data' => $credentials->isEmpty() ? null : $credentials->all(),
        ]);

        return response()->json(['ok' => true, 'card' => $this->detail($card->fresh())]);
    }

    public function destroyCard(Board $board, BoardCard $card): JsonResponse
    {
        $this->authorize('contribute', $board);

        $card->delete();

        return response()->json(['ok' => true]);
    }

    /* ------------------------------------------------------------------
     |  Drag & drop persistence
     | ------------------------------------------------------------------ */

    /**
     * Payload:
     *  board_id      int
     *  column_order  int[]                      (optional) new order of lists
     *  columns       [{id:int, cards:int[]}]    (optional) card order for every affected list
     */
    public function updatePosition(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'board_id' => ['required', 'integer', 'exists:boards,id'],
            'column_order' => ['nullable', 'array'],
            'column_order.*' => ['integer'],
            'columns' => ['nullable', 'array'],
            'columns.*.id' => ['required', 'integer'],
            'columns.*.cards' => ['nullable', 'array'],
            'columns.*.cards.*' => ['integer'],
        ]);

        $board = Board::findOrFail($validated['board_id']);
        $this->authorize('contribute', $board);

        $boardColumnIds = $board->columns()->pluck('id')->all();

        DB::transaction(function () use ($validated, $board, $boardColumnIds) {
            foreach ($validated['column_order'] ?? [] as $position => $columnId) {
                $this->assertIn($columnId, $boardColumnIds);
                BoardColumn::where('id', $columnId)->update(['position' => $position]);
            }

            foreach ($validated['columns'] ?? [] as $columnData) {
                $this->assertIn($columnData['id'], $boardColumnIds);

                foreach (array_values($columnData['cards'] ?? []) as $position => $cardId) {
                    $updated = BoardCard::where('id', $cardId)
                        ->where('board_id', $board->id)
                        ->update(['column_id' => $columnData['id'], 'position' => $position]);

                    if ($updated !== 1) {
                        throw ValidationException::withMessages(['columns' => "Card {$cardId} does not belong to this board."]);
                    }
                }
            }

            $board->touch();
        });

        return response()->json(['ok' => true]);
    }

    /* ------------------------------------------------------------------
     |  Helpers
     | ------------------------------------------------------------------ */

    protected function assertIn(int $id, array $allowed): void
    {
        if (! in_array($id, $allowed, true)) {
            throw ValidationException::withMessages(['columns' => "List {$id} does not belong to this board."]);
        }
    }

    protected function summary(BoardCard $card): array
    {
        return [
            'id' => $card->id,
            'title' => $card->title,
            'has_credentials' => $card->hasCredentials(),
            'has_description' => filled($card->description),
            'excerpt' => Str::limit((string) $card->description, 80),
        ];
    }

    protected function detail(BoardCard $card): array
    {
        $credentials = $card->credentials_data ?? [];

        return $this->summary($card) + [
            'description' => $card->description ?? '',
            'credentials' => [
                'username' => $credentials['username'] ?? '',
                'password' => $credentials['password'] ?? '',
                'url' => $credentials['url'] ?? '',
            ],
        ];
    }
}
