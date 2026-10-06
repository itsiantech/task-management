<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\AssignedLead;
use App\Models\Client;
use App\Models\LeadNote;
use App\Models\Meeting;
use App\Models\User;
use Illuminate\Http\Request;

class LeadManagementController extends Controller
{
    public function index()
    {
        $clients = Client::orderBy('name')->get();
        $staffMembers = User::where('is_approved', true)->orderBy('name')->get();
        $appointments = Appointment::with('client')->latest('appointment_date')->get();
        $meetings = Meeting::with(['client', 'staff'])->latest('id')->get();
        $assignedLeads = AssignedLead::with(['leadManager', 'assignee', 'assignedMembers', 'latestNote.user', 'notes.user'])
            ->latest('next_follow_up_date')
            ->get();

        return view('lead-management.index', compact('clients', 'staffMembers', 'appointments', 'meetings', 'assignedLeads'));
    }

    public function storeAppointment(Request $request)
    {
        $validated = $request->validate([
            'appointment_date' => ['required', 'date'],
            'client_id' => ['nullable', 'exists:clients,id'],
            'custom_phone' => ['nullable', 'string', 'max:255'],
            'location' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        Appointment::create($validated);

        return redirect()->route('lead-management.index')->with('success', 'Appointment created successfully.');
    }

    public function updateAppointment(Request $request, Appointment $appointment)
    {
        $validated = $request->validate([
            'appointment_date' => ['required', 'date'],
            'client_id' => ['nullable', 'exists:clients,id'],
            'custom_phone' => ['nullable', 'string', 'max:255'],
            'location' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        $appointment->update($validated);

        return redirect()->route('lead-management.index')->with('success', 'Appointment updated successfully.');
    }

    public function destroyAppointment(Appointment $appointment)
    {
        $appointment->delete();

        return redirect()->route('lead-management.index')->with('success', 'Appointment deleted successfully.');
    }

    public function storeMeeting(Request $request)
    {
        $validated = $request->validate([
            'client_id' => ['nullable', 'exists:clients,id'],
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_email' => ['required', 'email', 'max:255'],
            'staff_id' => ['required', 'exists:users,id'],
            'lead_type' => ['required', 'in:customer,lead'],
            'zoom_link' => ['nullable', 'url', 'max:255'],
            'status' => ['required', 'in:scheduled,completed,cancelled,rescheduled'],
            'remark' => ['nullable', 'string'],
        ]);

        Meeting::create($validated);

        return redirect()->route('lead-management.index')->with('success', 'Meeting created successfully.');
    }

    public function updateMeeting(Request $request, Meeting $meeting)
    {
        $validated = $request->validate([
            'client_id' => ['nullable', 'exists:clients,id'],
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_email' => ['required', 'email', 'max:255'],
            'staff_id' => ['required', 'exists:users,id'],
            'lead_type' => ['required', 'in:customer,lead'],
            'zoom_link' => ['nullable', 'url', 'max:255'],
            'status' => ['required', 'in:scheduled,completed,cancelled,rescheduled'],
            'remark' => ['nullable', 'string'],
        ]);

        $meeting->update($validated);

        return redirect()->route('lead-management.index')->with('success', 'Meeting updated successfully.');
    }

    public function destroyMeeting(Meeting $meeting)
    {
        $meeting->delete();

        return redirect()->route('lead-management.index')->with('success', 'Meeting deleted successfully.');
    }

    public function storeAssignedLead(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'lead_manager_id' => ['required', 'exists:users,id'],
            'company' => ['nullable', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'source' => ['nullable', 'string', 'max:255'],
            'assigned_to' => ['required', 'exists:users,id'],
            'status' => ['required', 'in:new,contacted,qualified,proposal_sent,won,lost'],
            'last_contact_date' => ['nullable', 'date'],
            'next_follow_up_date' => ['nullable', 'date'],
        ]);

        $lead = AssignedLead::create($validated);

        if ($request->has('member_ids')) {
            $memberIds = collect($request->input('member_ids', []))->filter()->map(fn ($id) => (int) $id)->all();
            if (! empty($memberIds)) {
                $lead->assignedMembers()->sync($memberIds);
            }
        }

        return redirect()->route('lead-management.index')->with('success', 'Assigned lead created successfully.');
    }

    public function showLead(AssignedLead $assignedLead)
    {
        $assignedLead->load(['leadManager', 'assignee', 'assignedMembers', 'notes.user', 'latestNote.user']);

        return response()->json([
            'lead' => [
                'id' => $assignedLead->id,
                'name' => $assignedLead->name,
                'company' => $assignedLead->company,
                'phone' => $assignedLead->phone,
                'email' => $assignedLead->email,
                'source' => $assignedLead->source,
                'status' => $assignedLead->status,
                'lead_manager_id' => $assignedLead->lead_manager_id,
                'assigned_to' => $assignedLead->assigned_to,
                'last_contact_date' => $assignedLead->last_contact_date?->format('Y-m-d'),
                'next_follow_up_date' => $assignedLead->next_follow_up_date?->format('Y-m-d'),
                'member_ids' => $assignedLead->assignedMembers->pluck('id')->all(),
                'assignee_name' => $assignedLead->assignee?->name,
                'manager_name' => $assignedLead->leadManager?->name,
                'last_note' => $assignedLead->last_note,
            ],
            'notes' => $assignedLead->notes->map(fn (LeadNote $note) => [
                'id' => $note->id,
                'note' => $note->note,
                'created_at' => $note->created_at?->format('Y-m-d H:i:s'),
                'formatted_date' => $note->created_at?->format('M d, Y h:i A'),
                'user' => ['name' => $note->user?->name ?? 'System'],
            ])->values(),
        ]);
    }

    public function assignLeadMembers(Request $request, AssignedLead $assignedLead)
    {
        $validated = $request->validate([
            'member_ids' => ['nullable', 'array'],
            'member_ids.*' => ['required', 'integer', 'exists:users,id'],
        ]);

        $memberIds = $validated['member_ids'] ?? [];

        if (! empty($memberIds)) {
            $assignedLead->assignedMembers()->sync($memberIds);
            $assignedLead->update(['assigned_to' => (int) $memberIds[0]]);
        }

        return back()->with('success', 'Lead assignment updated successfully.');
    }

    public function storeLeadNote(Request $request, AssignedLead $assignedLead)
    {
        $validated = $request->validate([
            'note' => ['required', 'string', 'max:2000'],
        ]);

        $assignedLead->notes()->create([
            'user_id' => auth()->id(),
            'note' => $validated['note'],
        ]);

        return back()->with('success', 'Follow-up note added successfully.');
    }

    public function updateAssignedLead(Request $request, AssignedLead $assignedLead)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'lead_manager_id' => ['required', 'exists:users,id'],
            'company' => ['nullable', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'source' => ['nullable', 'string', 'max:255'],
            'assigned_to' => ['required', 'exists:users,id'],
            'status' => ['required', 'in:new,contacted,qualified,proposal_sent,won,lost'],
            'last_contact_date' => ['nullable', 'date'],
            'next_follow_up_date' => ['nullable', 'date'],
        ]);

        $assignedLead->update($validated);

        if ($request->has('member_ids')) {
            $memberIds = collect($request->input('member_ids', []))->filter()->map(fn ($id) => (int) $id)->all();
            if (! empty($memberIds)) {
                $assignedLead->assignedMembers()->sync($memberIds);
            }
        }

        return redirect()->route('lead-management.index')->with('success', 'Assigned lead updated successfully.');
    }

    public function destroyAssignedLead(AssignedLead $assignedLead)
    {
        $assignedLead->delete();

        return redirect()->route('lead-management.index')->with('success', 'Assigned lead deleted successfully.');
    }
}
