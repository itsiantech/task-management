<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureUserApproved
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        if (! Auth::check()) {
            return redirect()->route('login');
        }

        if (! Auth::user()->is_approved) {
            Auth::logout();

            return redirect()->route('login')->withErrors([
                'email' => 'Account pending admin approval.',
            ]);
        }

        return $next($request);
    }
}
