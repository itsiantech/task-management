<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureAccountsManager
{
    /**
     * Allow Super Admin (admin) and Accountant to manage accounts data.
     */
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        if (! $user || ! (method_exists($user, 'isAdmin') && method_exists($user, 'isAccountant'))
            || ! ($user->isAdmin() || $user->isAccountant())) {
            abort(403, 'Access denied. Accounts manager privileges required.');
        }

        return $next($request);
    }
}
