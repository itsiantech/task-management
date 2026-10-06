<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class GoogleLoginController extends Controller
{
    /** Send the visitor to Google's consent screen. */
    public function redirect(): RedirectResponse
    {
        if (! config('services.google.client_id') || ! config('services.google.client_secret')) {
            return redirect()->route('login')->withErrors([
                'email' => 'Google sign-in is not configured yet. Please use your email and password.',
            ]);
        }

        return Socialite::driver('google')->redirect();
    }

    /** Handle Google's response. */
    public function callback(Request $request): RedirectResponse
    {
        if ($request->filled('error')) {
            return $this->failed('Google sign-in was cancelled.');
        }

        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Throwable $e) {
            Log::warning('Google OAuth callback failed', ['exception' => $e->getMessage()]);

            return $this->failed('Unable to sign in with Google. Please try again.');
        }

        $email = strtolower((string) $googleUser->getEmail());
        $verified = filter_var($googleUser->user['email_verified'] ?? $googleUser->user['verified_email'] ?? false, FILTER_VALIDATE_BOOLEAN);

        if ($email === '' || ! $verified) {
            return $this->failed('Your Google account does not have a verified email address.');
        }

        // 1) Known Google account, 2) existing local account with the same (verified) email.
        $user = User::where('google_id', $googleUser->getId())->first()
            ?? User::where('email', $email)->first();

        if ($user) {
            $user->forceFill([
                'google_id' => $user->google_id ?: $googleUser->getId(),
                'avatar' => $googleUser->getAvatar() ?: $user->avatar,
                'email_verified_at' => $user->email_verified_at ?? now(),
            ])->save();
        } else {
            // New accounts follow the normal approval workflow: members, pending admin approval.
            $user = User::create([
                'name' => $googleUser->getName() ?: Str::before($email, '@'),
                'email' => $email,
                'password' => Hash::make(Str::random(40)), // unusable random password
                'google_id' => $googleUser->getId(),
                'avatar' => $googleUser->getAvatar(),
                'role' => 'member',
                'is_approved' => false,
            ]);
            $user->forceFill(['email_verified_at' => now()])->save();
        }

        if (! $user->is_approved) {
            return redirect()->route('login')->withErrors([
                'email' => 'Account pending admin approval.',
            ]);
        }

        Auth::login($user, true);
        $request->session()->regenerate();

        return redirect()->intended(route('tasks.index'));
    }

    protected function failed(string $message): RedirectResponse
    {
        return redirect()->route('login')->withErrors(['email' => $message]);
    }
}
