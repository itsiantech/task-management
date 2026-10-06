<?php

namespace Tests\Feature;

use App\Models\Board;
use App\Models\BoardCard;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class KanbanBoardTest extends TestCase
{
    use RefreshDatabase;

    private function member(string $role = 'member'): User
    {
        return User::factory()->create(['role' => $role, 'is_approved' => true]);
    }

    private function boardFor(User $owner): Board
    {
        $this->actingAs($owner)->post('/boards', ['name' => 'Vault Board', 'is_private' => 1])->assertRedirect();

        return Board::firstOrFail();
    }

    public function test_creating_a_board_adds_owner_and_default_lists(): void
    {
        $owner = $this->member();
        $board = $this->boardFor($owner);

        $this->assertSame(['To Do', 'In Progress', 'Done'], $board->columns->pluck('title')->all());
        $this->assertSame('owner', $board->memberships()->where('user_id', $owner->id)->value('role'));

        $this->actingAs($owner)->get('/boards')->assertOk()->assertSee('Vault Board');
        $this->actingAs($owner)->get("/boards/{$board->id}")->assertOk()->assertSee('Secret Credentials');
    }

    public function test_private_board_is_hidden_from_non_members(): void
    {
        $board = $this->boardFor($this->member());
        $stranger = $this->member();

        $this->actingAs($stranger)->get("/boards/{$board->id}")->assertForbidden();
        $this->actingAs($stranger)->get('/boards')->assertOk()->assertDontSee('Vault Board');
    }

    public function test_card_credentials_are_encrypted_at_rest_and_returned_to_members(): void
    {
        $owner = $this->member();
        $board = $this->boardFor($owner);
        $column = $board->columns->first();

        $card = $this->actingAs($owner)
            ->postJson("/boards/{$board->id}/columns/{$column->id}/cards", ['title' => 'Facebook Ad Manager Login'])
            ->assertCreated()
            ->json('card');

        $this->actingAs($owner)->putJson("/boards/{$board->id}/cards/{$card['id']}", [
            'title' => 'Facebook Ad Manager Login',
            'description' => 'Main agency account',
            'credentials' => ['username' => 'ads@example.com', 'password' => 'S3cret!pass', 'url' => 'facebook.com/ads'],
        ])->assertOk()->assertJsonPath('card.has_credentials', true);

        $raw = DB::table('board_cards')->where('id', $card['id'])->value('credentials_data');
        $this->assertStringNotContainsString('S3cret!pass', $raw);

        $this->actingAs($owner)->getJson("/boards/{$board->id}/cards/{$card['id']}")
            ->assertOk()
            ->assertJsonPath('card.credentials.username', 'ads@example.com')
            ->assertJsonPath('card.credentials.password', 'S3cret!pass')
            ->assertJsonPath('card.credentials.url', 'https://facebook.com/ads');

        // Initial board HTML must not embed secrets.
        $this->actingAs($owner)->get("/boards/{$board->id}")->assertDontSee('S3cret!pass');
    }

    public function test_sharing_grants_roles_and_viewers_cannot_edit(): void
    {
        $owner = $this->member();
        $editor = $this->member();
        $viewer = $this->member();
        $board = $this->boardFor($owner);
        $column = $board->columns->first();
        $card = BoardCard::create(['column_id' => $column->id, 'board_id' => $board->id, 'title' => 'Card', 'position' => 0]);

        $this->actingAs($owner)->post("/boards/{$board->id}/share", [
            'user_ids' => [$editor->id, $viewer->id],
            'roles' => [$editor->id => 'editor', $viewer->id => 'viewer'],
        ])->assertRedirect();

        $this->actingAs($viewer)->get("/boards/{$board->id}")->assertOk();
        $this->actingAs($viewer)->putJson("/boards/{$board->id}/cards/{$card->id}", ['title' => 'Hacked'])->assertForbidden();
        $this->actingAs($viewer)->postJson('/kanban/update-position', [
            'board_id' => $board->id, 'columns' => [['id' => $column->id, 'cards' => [$card->id]]],
        ])->assertForbidden();
        $this->actingAs($viewer)->post("/boards/{$board->id}/share", ['user_ids' => []])->assertForbidden();

        $this->actingAs($editor)->putJson("/boards/{$board->id}/cards/{$card->id}", ['title' => 'Renamed'])->assertOk();
        $this->assertSame('Renamed', $card->fresh()->title);
        $this->actingAs($editor)->delete("/boards/{$board->id}")->assertForbidden();
    }

    public function test_update_position_moves_cards_across_columns_and_reorders_lists(): void
    {
        $owner = $this->member();
        $board = $this->boardFor($owner);
        [$todo, $doing, $done] = $board->columns->all();

        $a = BoardCard::create(['column_id' => $todo->id, 'board_id' => $board->id, 'title' => 'A', 'position' => 0]);
        $b = BoardCard::create(['column_id' => $todo->id, 'board_id' => $board->id, 'title' => 'B', 'position' => 1]);
        $c = BoardCard::create(['column_id' => $doing->id, 'board_id' => $board->id, 'title' => 'C', 'position' => 0]);

        // Drag A from "To Do" to the top of "In Progress".
        $this->actingAs($owner)->postJson('/kanban/update-position', [
            'board_id' => $board->id,
            'columns' => [
                ['id' => $doing->id, 'cards' => [$a->id, $c->id]],
                ['id' => $todo->id, 'cards' => [$b->id]],
            ],
        ])->assertOk();

        $this->assertSame([$doing->id, 0], [$a->fresh()->column_id, $a->fresh()->position]);
        $this->assertSame([$doing->id, 1], [$c->fresh()->column_id, $c->fresh()->position]);
        $this->assertSame([$todo->id, 0], [$b->fresh()->column_id, $b->fresh()->position]);

        // Reorder lists.
        $this->actingAs($owner)->postJson('/kanban/update-position', [
            'board_id' => $board->id,
            'column_order' => [$done->id, $todo->id, $doing->id],
        ])->assertOk();
        $this->assertSame([$done->id, $todo->id, $doing->id], $board->fresh()->columns->pluck('id')->all());
    }

    public function test_update_position_rejects_cards_and_lists_from_other_boards(): void
    {
        $owner = $this->member();
        $this->actingAs($owner)->post('/boards', ['name' => 'One']);
        $this->actingAs($owner)->post('/boards', ['name' => 'Two']);
        [$one, $two] = Board::orderBy('id')->get()->all();

        $foreignCard = BoardCard::create(['column_id' => $two->columns->first()->id, 'board_id' => $two->id, 'title' => 'X', 'position' => 0]);

        $this->actingAs($owner)->postJson('/kanban/update-position', [
            'board_id' => $one->id,
            'columns' => [['id' => $one->columns->first()->id, 'cards' => [$foreignCard->id]]],
        ])->assertStatus(422);

        $this->actingAs($owner)->postJson('/kanban/update-position', [
            'board_id' => $one->id,
            'columns' => [['id' => $two->columns->first()->id, 'cards' => []]],
        ])->assertStatus(422);

        $this->assertSame($two->columns->first()->id, $foreignCard->fresh()->column_id);
    }

    public function test_scoped_bindings_block_cards_from_other_boards(): void
    {
        $owner = $this->member();
        $this->actingAs($owner)->post('/boards', ['name' => 'One']);
        $this->actingAs($owner)->post('/boards', ['name' => 'Two']);
        [$one, $two] = Board::orderBy('id')->get()->all();
        $card = BoardCard::create(['column_id' => $two->columns->first()->id, 'board_id' => $two->id, 'title' => 'X', 'position' => 0]);

        $this->actingAs($owner)->getJson("/boards/{$one->id}/cards/{$card->id}")->assertNotFound();
    }
}
