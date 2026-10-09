<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\AnnouncementRecipient;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AnnouncementController extends Controller
{
    public function index()
    {
        $announcements = Announcement::with(['sender', 'recipients.user'])->latest()->get();
        $members = User::where('role', 'member')
            ->where('is_approved', true)
            ->orderBy('name')
            ->get(['id', 'name', 'member_code']);

        return view('admin.announcements', compact('announcements', 'members'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:5000'],
            'target_type' => ['required', Rule::in(['all', 'individual'])],
            'member_ids' => ['required_if:target_type,individual', 'array'],
            'member_ids.*' => ['exists:users,id'],
        ]);

        $announcement = Announcement::create([
            'title' => $validated['title'],
            'body' => $validated['body'],
            'sender_id' => $request->user()->id,
            'target_type' => $validated['target_type'],
        ]);

        if ($validated['target_type'] === 'all') {
            $userIds = User::where('is_approved', true)
                ->where('id', '!=', $request->user()->id)
                ->pluck('id');
        } else {
            $userIds = collect($validated['member_ids'] ?? [])->unique();
        }

        foreach ($userIds as $userId) {
            AnnouncementRecipient::create([
                'announcement_id' => $announcement->id,
                'user_id' => $userId,
            ]);
        }

        return redirect()->route('admin.announcements.index')->with('success', 'Announcement sent successfully.');
    }

    public function destroy(Announcement $announcement)
    {
        $announcement->delete();

        return redirect()->route('admin.announcements.index')->with('success', 'Announcement deleted.');
    }

    public function markRead(Request $request, Announcement $announcement)
    {
        $recipient = AnnouncementRecipient::where('announcement_id', $announcement->id)
            ->where('user_id', $request->user()->id)
            ->first();

        abort_unless($recipient, 403);

        $recipient->update(['read_at' => now()]);

        return back();
    }
}
