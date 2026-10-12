<?php

namespace App\Http\Controllers;

/**
 * Serves the CloudVault Sheets React build (storage/app/cloudvault/app.html,
 * assets under public/cloudvault-assets) from inside the admin panel.
 *
 * User identity + CSRF are injected as window.__CV__ so the app can talk to
 * the same-origin JSON API (/api/cloudvault/...) with the session cookie.
 * All sheet data lives server-side; the browser keeps no credentials.
 */
class CloudVaultController extends Controller
{
    public function index()
    {
        $path = storage_path('app/cloudvault/app.html');

        abort_unless(
            is_file($path),
            404,
            'CloudVault build missing. Rebuild with `npm run build` in cloudvault-sheets, copy dist/assets/ to public/cloudvault-assets/assets/ (+ favicon) and dist/index.html to storage/app/cloudvault/app.html.'
        );

        $user = auth()->user();

        $boot = [
            'user' => $user ? [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'isAdmin' => method_exists($user, 'isAdmin') ? $user->isAdmin() : false,
                'isAccountant' => method_exists($user, 'isAccountant') ? $user->isAccountant() : false,
            ] : null,
            'csrf' => csrf_token(),
        ];

        $html = str_replace(
            '</head>',
            '<script>window.__CV__ = ' . json_encode($boot, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) . ';</script></head>',
            file_get_contents($path)
        );

        return response($html, 200, ['Content-Type' => 'text/html; charset=UTF-8']);
    }
}
