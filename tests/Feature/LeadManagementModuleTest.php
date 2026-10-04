<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeadManagementModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_appointment_meeting_and_assigned_lead(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_approved' => true,
        ]);

        $client = Client::create([
            'name' => 'Lead Client',
            'phone' => '+1234567890',
            'status' => 'pending',
        ]);

        $this->actingAs($admin)
            ->post('/lead-management/appointments', [
                'appointment_date' => '2026-10-10 15:00:00',
                'client_id' => $client->id,
                'custom_phone' => '+1234567890',
                'location' => 'Zoom',
                'notes' => 'Discuss pricing and timeline',
            ])
            ->assertRedirect('/lead-management');

        $staff = User::factory()->create([
            'role' => 'member',
            'is_approved' => true,
        ]);

        $this->actingAs($admin)
            ->post('/lead-management/meetings', [
                'client_id' => $client->id,
                'customer_name' => 'Lead Client',
                'customer_email' => 'lead@example.com',
                'staff_id' => $staff->id,
                'lead_type' => 'lead',
                'zoom_link' => 'https://zoom.us/j/123',
                'status' => 'scheduled',
                'remark' => 'Follow up after 2 days',
            ])
            ->assertRedirect('/lead-management');

        $this->actingAs($admin)
            ->post('/lead-management/assigned-leads', [
                'name' => 'Alpha Lead',
                'lead_manager_id' => $admin->id,
                'company' => 'Acme Labs',
                'phone' => '+1999888777',
                'assigned_to' => $staff->id,
                'status' => 'new',
                'last_contact_date' => '2026-10-01 11:00:00',
                'next_follow_up_date' => '2026-10-05 11:00:00',
            ])
            ->assertRedirect('/lead-management');

        $this->actingAs($admin)
            ->get('/lead-management')
            ->assertOk()
            ->assertSeeText('Lead Management')
            ->assertSeeText('Appointments')
            ->assertSeeText('Meetings')
            ->assertSeeText('Assigned Leads');
    }
}
