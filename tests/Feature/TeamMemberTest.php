<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeamMemberTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'is_approved' => true]);
    }

    private function member(): User
    {
        return User::factory()->create(['role' => 'member', 'is_approved' => true]);
    }

    public function test_team_member_rename_visible_in_ui(): void
    {
        $html = $this->actingAs($this->admin())->get(route('admin.members'))
            ->assertStatus(200)->getContent();

        $this->assertStringContainsString('Team Members', $html);
        $this->assertStringContainsString('Team Member Directory', $html);
    }

    public function test_member_sees_full_task_history_on_profile(): void
    {
        $admin = $this->admin();
        $member = $this->member();

        Task::factory()->create(['title' => 'Direct History Task ZZ', 'status' => 'completed', 'assigned_to' => $member->id, 'created_by' => $admin->id]);
        $pivotTask = Task::factory()->create(['title' => 'Pivot History Task ZZ', 'status' => 'hold', 'created_by' => $admin->id]);
        $pivotTask->members()->attach($member->id);

        $html = $this->actingAs($member)->get(route('profile.show'))
            ->assertStatus(200)->getContent();

        $this->assertStringContainsString('My Task History', $html);
        $this->assertStringContainsString('Direct History Task ZZ', $html);
        $this->assertStringContainsString('Pivot History Task ZZ', $html);
        $this->assertStringContainsString('Completed', $html);
        $this->assertStringContainsString('Hold', $html);
    }

    public function test_admin_sees_member_assigned_tasks_with_statuses(): void
    {
        $admin = $this->admin();
        $member = $this->member();

        Task::factory()->create(['title' => 'Admin View Task ZZ', 'status' => 'in_progress', 'assigned_to' => $member->id, 'created_by' => $admin->id]);

        $html = $this->actingAs($admin)->get(route('admin.members.show', $member))
            ->assertStatus(200)->getContent();

        $this->assertStringContainsString('Assigned Tasks', $html);
        $this->assertStringContainsString('Admin View Task ZZ', $html);
        $this->assertStringContainsString('In Progress', $html);
        $this->assertStringContainsString('Team Member Profile', $html);
    }

    public function test_ledger_date_search_and_pagination(): void
    {
        $member = $this->member();

        Payment::create(['user_id' => $member->id, 'amount' => 100, 'payment_date' => '2026-09-01', 'month_year' => '2026-09', 'payment_type' => 'salary', 'payment_status' => 'complete']);
        Payment::create(['user_id' => $member->id, 'amount' => 200, 'payment_date' => '2026-10-10', 'month_year' => '2026-10', 'payment_type' => 'salary', 'payment_status' => 'complete']);

        // Date range keeps only October payment.
        $html = $this->actingAs($member)->get(route('payments.member', ['from' => '2026-10-01', 'to' => '2026-10-31']))
            ->assertStatus(200)->getContent();

        $this->assertStringContainsString('200.00', $html);
        $this->assertStringContainsString('Oct 10, 2026', $html);
        $this->assertStringNotContainsString('Sep 01, 2026', $html);
        $this->assertStringContainsString('Payments in Range', $html);

        // Pagination appears with many rows.
        for ($i = 0; $i < 20; $i++) {
            Payment::create(['user_id' => $member->id, 'amount' => 10, 'payment_date' => '2026-10-11', 'month_year' => '2026-10', 'payment_type' => 'salary', 'payment_status' => 'complete']);
        }
        $html = $this->actingAs($member)->get(route('payments.member', ['month_year' => 'all']))
            ->assertStatus(200)->getContent();
        $this->assertStringContainsString('page=2', $html);
    }
}
