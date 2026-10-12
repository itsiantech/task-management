<?php

namespace Tests\Feature;

use App\Models\Cloudvault\Sheet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CloudVaultApiTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'is_approved' => true]);
    }

    private function member(string $email = 'member@example.com'): User
    {
        return User::factory()->create(['role' => 'member', 'is_approved' => true, 'email' => $email]);
    }

    private function payload(): array
    {
        return [
            'title' => 'Member Sheet QA',
            'color' => '#0284c7',
            'rows_count' => 3,
            'columns' => [
                ['title' => 'Domain Name', 'type' => 'domain'],
                ['title' => 'WHMCS Pass', 'type' => 'password'],
            ],
        ];
    }

    public function test_member_creates_and_reads_own_sheet(): void
    {
        $member = $this->member();

        $created = $this->actingAs($member)->postJson('/api/cloudvault/sheets', $this->payload())
            ->assertStatus(201)->json('sheet');

        $this->assertEquals('Member Sheet QA', $created['title']);
        $this->assertCount(2, $created['columns']);
        $this->assertEquals($member->email, $created['owner']['email']);
        $this->assertEquals('Admin', $created['my_role']); // owner maps to Admin UI role

        $list = $this->actingAs($member)->getJson('/api/cloudvault/sheets')->assertStatus(200)->json('sheets');
        $this->assertCount(1, $list);
    }

    public function test_sheets_are_isolated_between_members(): void
    {
        $a = $this->member('a@example.com');
        $b = $this->member('b@example.com');

        $this->actingAs($a)->postJson('/api/cloudvault/sheets', $this->payload())->assertStatus(201);

        $list = $this->actingAs($b)->getJson('/api/cloudvault/sheets')->assertStatus(200)->json('sheets');
        $this->assertCount(0, $list);

        $sheetId = Sheet::first()->id;
        $this->actingAs($b)->getJson("/api/cloudvault/sheets/{$sheetId}")->assertStatus(403);
    }

    public function test_share_with_member_and_role_enforcement(): void
    {
        $owner = $this->member('owner@example.com');
        $teammate = $this->member('mate@example.com');

        $sheetId = $this->actingAs($owner)->postJson('/api/cloudvault/sheets', $this->payload())
            ->assertStatus(201)->json('sheet.id');

        // Share as Viewer: teammate sees it, read-only.
        $this->actingAs($owner)->postJson("/api/cloudvault/sheets/{$sheetId}/shares", [
            'email' => 'mate@example.com', 'role' => 'Viewer',
        ])->assertStatus(201);

        $list = $this->actingAs($teammate)->getJson('/api/cloudvault/sheets')->assertStatus(200)->json('sheets');
        $this->assertCount(1, $list);
        $this->assertEquals('Viewer', $list[0]['my_role']);

        $colId = $list[0]['columns'][0]['id'];
        $rowId = $this->actingAs($teammate)->getJson("/api/cloudvault/sheets/{$sheetId}")
            ->assertStatus(200)->json('sheet.rows.0.id');
        $this->assertNotNull($rowId);

        // Viewer cannot write (policy denies before row lookup).
        $this->actingAs($teammate)->patchJson("/api/cloudvault/sheets/{$sheetId}/rows/{$rowId}/cell", [
            'column_id' => $colId, 'value' => 'hacked.com',
        ])->assertStatus(403);

        // Promote to Editor: write works, delete still forbidden.
        $this->actingAs($owner)->postJson("/api/cloudvault/sheets/{$sheetId}/shares", [
            'email' => 'mate@example.com', 'role' => 'Editor',
        ])->assertStatus(201);

        $this->actingAs($teammate)->patchJson("/api/cloudvault/sheets/{$sheetId}/rows/{$rowId}/cell", [
            'column_id' => $colId, 'value' => 'edited.com',
        ])->assertStatus(200);
        $this->actingAs($teammate)->deleteJson("/api/cloudvault/sheets/{$sheetId}")->assertStatus(403);
    }

    public function test_admin_sees_member_sheets(): void
    {
        $admin = $this->admin();
        $member = $this->member();

        $this->actingAs($member)->postJson('/api/cloudvault/sheets', $this->payload())->assertStatus(201);

        $list = $this->actingAs($admin)->getJson('/api/cloudvault/sheets')->assertStatus(200)->json('sheets');
        $this->assertCount(1, $list);
        $this->assertEquals($member->email, $list[0]['owner']['email']);
        $this->assertEquals('Admin', $list[0]['my_role']);
    }

    public function test_row_crud_and_cell_update(): void
    {
        $member = $this->member();
        $sheetId = $this->actingAs($member)->postJson('/api/cloudvault/sheets', $this->payload())
            ->assertStatus(201)->json('sheet.id');

        $sheet = $this->actingAs($member)->getJson("/api/cloudvault/sheets/{$sheetId}")->assertStatus(200)->json('sheet');
        $this->assertCount(3, $sheet['rows']);

        $colId = $sheet['columns'][0]['id'];
        $rowId = $sheet['rows'][0]['id'];

        $this->actingAs($member)->patchJson("/api/cloudvault/sheets/{$sheetId}/rows/{$rowId}/cell", [
            'column_id' => $colId, 'value' => 'example.com',
        ])->assertStatus(200)->assertJsonPath('row.cells.' . $colId, 'example.com');

        // Cells are encrypted at rest.
        $raw = \DB::table('cloudvault_rows')->where('id', $rowId)->value('cells');
        $this->assertStringNotContainsString('example.com', (string) $raw);

        $this->actingAs($member)->deleteJson("/api/cloudvault/sheets/{$sheetId}/rows/{$rowId}")->assertStatus(200);
        $this->assertDatabaseMissing('cloudvault_rows', ['id' => $rowId]);
    }

    public function test_token_row_share_is_public_but_expiry_enforced(): void
    {
        $member = $this->member();
        $sheetId = $this->actingAs($member)->postJson('/api/cloudvault/sheets', $this->payload())
            ->assertStatus(201)->json('sheet.id');
        $rowId = Sheet::find($sheetId)->rows()->first()->id;

        $token = $this->actingAs($member)->postJson("/api/cloudvault/sheets/{$sheetId}/row-shares", [
            'row_id' => $rowId, 'expires_in' => '24h',
        ])->assertStatus(201)->json('share.token');

        // Guest (no login) can open with token.
        $this->getJson("/api/cloudvault/shared-row/{$token}")
            ->assertStatus(200)
            ->assertJsonPath('row.id', $rowId);

        // Bad token 404s.
        $this->getJson('/api/cloudvault/shared-row/deadbeef')->assertStatus(404);

        // Expired token 404s.
        \DB::table('cloudvault_row_shares')->where('token', $token)->update(['expires_at' => now()->subHour()]);
        $this->getJson("/api/cloudvault/shared-row/{$token}")->assertStatus(404);
    }

    public function test_members_directory_for_sharing(): void
    {
        $this->member('a@example.com');
        $this->member('b@example.com');

        $list = $this->actingAs($this->admin())->getJson('/api/cloudvault/members')
            ->assertStatus(200)->json('members');

        $emails = collect($list)->pluck('email')->all();
        $this->assertContains('a@example.com', $emails);
        $this->assertContains('b@example.com', $emails);
        $this->assertArrayNotHasKey('password', $list[0]);
    }

    public function test_guest_cannot_access_members(): void
    {
        $this->getJson('/api/cloudvault/members')->assertUnauthorized();
    }
}
