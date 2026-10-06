<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class SettingController extends Controller
{
    public function index()
    {
        return view('settings.index', [
            'user' => Auth::user(),
            'siteTitle' => Setting::get('site_title', Setting::DEFAULTS['site_title']),
            'adminLogo' => Setting::logoUrl(),
        ]);
    }

    public function updateGeneral(Request $request)
    {
        $validated = $request->validateWithBag('general', [
            'site_title' => ['required', 'string', 'max:100'],
            'admin_logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:2048'],
            'remove_logo' => ['nullable', 'boolean'],
        ]);

        Setting::set('site_title', trim($validated['site_title']));

        if ($request->hasFile('admin_logo')) {
            $this->deleteLogo();

            $path = $request->file('admin_logo')->store('logos', 'public');
            Setting::set('admin_logo', $path);
        } elseif ($request->boolean('remove_logo')) {
            $this->deleteLogo();
            Setting::set('admin_logo', null);
        }

        return redirect()->route('settings.index')
            ->with('success', 'General settings updated successfully.')
            ->with('tab', 'general');
    }

    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validateWithBag('profile', [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
        ]);

        $user->update([
            'name' => trim($validated['name']),
            'email' => $validated['email'],
        ]);

        return redirect()->route('settings.index')
            ->with('success', 'Profile updated successfully.')
            ->with('tab', 'profile');
    }

    public function updatePassword(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validateWithBag('password', [
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed', 'different:current_password'],
        ]);

        if (! Hash::check($validated['current_password'], $user->password)) {
            return back()
                ->withErrors(['current_password' => 'The current password is incorrect.'], 'password')
                ->with('tab', 'password');
        }

        $user->forceFill(['password' => Hash::make($validated['password'])])->save();

        return redirect()->route('settings.index')
            ->with('success', 'Password changed successfully.')
            ->with('tab', 'password');
    }

    protected function deleteLogo(): void
    {
        $current = Setting::get('admin_logo');

        if ($current && Storage::disk('public')->exists($current)) {
            Storage::disk('public')->delete($current);
        }
    }
}
