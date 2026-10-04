<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class AdminApprovalController extends Controller
{
    public function index()
    {
        $pendingUsers = User::where('is_approved', false)->orderBy('created_at', 'desc')->get();

        return view('admin.approvals', compact('pendingUsers'));
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'status' => ['required', 'in:approve,reject'],
        ]);

        $user->update([
            'is_approved' => $request->status === 'approve',
        ]);

        return redirect()->route('admin.approvals')->with('success', $request->status === 'approve' ? 'User approved successfully.' : 'User rejected successfully.');
    }
}
