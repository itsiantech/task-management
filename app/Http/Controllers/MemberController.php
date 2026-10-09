<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class MemberController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validateWithBag('addMember', [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['required', Rule::in(['member', 'admin'])],
            'phone' => ['nullable', 'string', 'max:50'],
            'monthly_salary' => ['nullable', 'numeric', 'min:0'],
        ]);

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'], // hashed via model cast
            'role' => $validated['role'],
            'is_approved' => true,
            'member_code' => $validated['role'] === 'member' ? User::nextMemberCode() : null,
            'phone' => $validated['phone'] ?? null,
            'monthly_salary' => $validated['monthly_salary'] ?? null,
        ]);

        return redirect()->route('admin.members')->with('success', 'Team member added successfully.');
    }

    public function show(User $user)
    {
        $payments = Payment::with('task')
            ->where('user_id', $user->id)
            ->orderByDesc('payment_date')
            ->limit(20)
            ->get();

        $monthSummary = Payment::where('user_id', $user->id)
            ->selectRaw("month_year,
                SUM(amount) as total_paid,
                SUM(CASE WHEN payment_type = 'salary' THEN amount ELSE 0 END) as salary_total,
                SUM(CASE WHEN payment_type = 'advance' THEN amount ELSE 0 END) as advance_total,
                SUM(CASE WHEN payment_type = 'adjustment' THEN amount ELSE 0 END) as adjustment_total")
            ->groupBy('month_year')
            ->orderByDesc('month_year')
            ->get();

        $advanceBalance = round(
            (float) Payment::where('user_id', $user->id)->where('payment_type', 'advance')->sum('amount')
            - (float) Payment::where('user_id', $user->id)->where('payment_type', 'adjustment')->sum('amount'),
            2
        );

        $thisMonthTotal = (float) Payment::where('user_id', $user->id)
            ->where('month_year', now()->format('Y-m'))
            ->sum('amount');

        return view('members.show', compact('user', 'payments', 'monthSummary', 'advanceBalance', 'thisMonthTotal'));
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validateWithBag('editMember', [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'role' => ['required', Rule::in(['member', 'admin'])],
            'phone' => ['nullable', 'string', 'max:50'],
            'family_phone' => ['nullable', 'string', 'max:50'],
            'present_address' => ['nullable', 'string', 'max:1000'],
            'permanent_address' => ['nullable', 'string', 'max:1000'],
            'monthly_salary' => ['nullable', 'numeric', 'min:0'],
            'nid' => ['nullable', 'file', 'max:2048', 'extensions:jpg,jpeg,png,pdf,doc,docx'],
            'cv' => ['nullable', 'file', 'max:2048', 'extensions:jpg,jpeg,png,pdf,doc,docx'],
        ]);

        if ($validated['role'] === 'member' && ! $user->member_code) {
            $user->member_code = User::nextMemberCode();
        }

        $user->fill([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'phone' => $validated['phone'] ?? null,
            'family_phone' => $validated['family_phone'] ?? null,
            'present_address' => $validated['present_address'] ?? null,
            'permanent_address' => $validated['permanent_address'] ?? null,
            'monthly_salary' => $validated['monthly_salary'] ?? null,
        ]);

        foreach (['nid' => 'nid_path', 'cv' => 'cv_path'] as $input => $column) {
            if ($request->hasFile($input)) {
                if ($user->{$column}) {
                    Storage::disk('public')->delete($user->{$column});
                }
                $user->{$column} = $request->file($input)->store('member-documents', 'public');
            }
        }

        $user->save();

        return redirect()->route('admin.members.show', $user)->with('success', 'Member updated successfully.');
    }

    public function resetPassword(Request $request, User $user)
    {
        $validated = $request->validateWithBag('resetPassword', [
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user->forceFill(['password' => $validated['password']])->save();

        return redirect()->route('admin.members.show', $user)->with('success', 'Password reset successfully.');
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return redirect()->route('admin.members')->with('error', 'You cannot delete your own account.');
        }

        if ($user->role !== 'member') {
            return redirect()->route('admin.members')->with('error', 'Only member accounts can be deleted here.');
        }

        foreach (['nid_path', 'cv_path'] as $column) {
            if ($user->{$column}) {
                Storage::disk('public')->delete($user->{$column});
            }
        }

        $user->delete();

        return redirect()->route('admin.members')->with('success', 'Member deleted successfully.');
    }
}
