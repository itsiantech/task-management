<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ProfileController extends Controller
{
    public function show(Request $request)
    {
        return view('profile.edit', ['user' => $request->user()]);
    }

    public function update(Request $request)
    {
        $user = $request->user();

        $validated = $request->validateWithBag('profile', [
            'phone' => ['nullable', 'string', 'max:50'],
            'family_phone' => ['nullable', 'string', 'max:50'],
            'present_address' => ['nullable', 'string', 'max:1000'],
            'permanent_address' => ['nullable', 'string', 'max:1000'],
            'nid' => ['nullable', 'file', 'max:2048', 'extensions:jpg,jpeg,png,pdf,doc,docx'],
            'cv' => ['nullable', 'file', 'max:2048', 'extensions:jpg,jpeg,png,pdf,doc,docx'],
        ]);

        $user->fill([
            'phone' => $validated['phone'] ?? null,
            'family_phone' => $validated['family_phone'] ?? null,
            'present_address' => $validated['present_address'] ?? null,
            'permanent_address' => $validated['permanent_address'] ?? null,
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

        return redirect()->route('profile.show')->with('success', 'Profile updated successfully.');
    }

    public function updatePassword(Request $request)
    {
        $validated = $request->validateWithBag('password', [
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed', 'different:current_password'],
        ]);

        $user = $request->user();

        if (! Hash::check($validated['current_password'], $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['The current password is incorrect.'],
            ])->errorBag('password');
        }

        $user->forceFill(['password' => $validated['password']])->save();

        return redirect()->route('profile.show')->with('success', 'Password changed successfully.');
    }

    public function downloadDocument(Request $request, User $user, string $type)
    {
        $currentUser = $request->user();

        if (! $currentUser->isAdmin() && $currentUser->id !== $user->id) {
            abort(403);
        }

        abort_unless(in_array($type, ['nid', 'cv'], true), 404);

        $path = $type === 'nid' ? $user->nid_path : $user->cv_path;

        abort_unless($path && Storage::disk('public')->exists($path), 404);

        return Storage::disk('public')->download($path);
    }
}
