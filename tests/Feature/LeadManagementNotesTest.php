<?php

namespace Tests\Feature;

use App\Models\AssignedLead;
use App\Models\LeadNote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeadManagementNotesTest extends TestCase
{
    use RefreshDatabase;

    public function test_lead_management_index_shows_latest_note_for_each_lead(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_approved' => true,
        ]);

        $member = User::factory()->create([
            'role' => 'member',
            'is_approved' => true,
        ]);

        $lead = AssignedLead::create([
            'name' => 'Test Lead',
            'lead_manager_id' => $admin->id,
            'company' => 'Acme',
            'phone' => '+1234567890',
            'assigned_to' => $member->id,
            'status' => 'contacted',
        ]);

        $olderNote = LeadNote::create([
            'lead_id' => $lead->id,
            'user_id' => $admin->id,
            'note' => 'Initial contact call scheduled.',
        ]);

        $latestNote = LeadNote::create([
            'lead_id' => $lead->id,
            'user_id' => $member->id,
            'note' => 'Follow-up completed and quote sent.',
        ]);

        $response = $this->actingAs($admin)->get(route('lead-management.index'));

        $response->assertOk();
        $response->assertSeeText('Follow-up completed and quote sent.');
        $this->assertSame($latestNote->id, $lead->fresh()->latestNote?->id);
        $this->assertSame($olderNote->id, $lead->fresh()->notes()->oldest()->first()->id);
    }
}
