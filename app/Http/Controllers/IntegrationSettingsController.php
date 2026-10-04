<?php

namespace App\Http\Controllers;

use App\Models\SocialIntegration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Validation\Rule;

class IntegrationSettingsController extends Controller
{
    public function index()
    {
        $integrations = SocialIntegration::orderBy('platform')->get()->keyBy('platform');

        return view('settings.integrations', [
            'facebook' => $integrations->get('facebook'),
            'whatsapp' => $integrations->get('whatsapp'),
            'webhookUrl' => url('/api/meta/webhook'),
            'verifyToken' => config('services.meta.verify_token', 'demo_verify_token'),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'platform' => ['required', Rule::in(['facebook', 'whatsapp'])],
            'account_name' => ['nullable', 'string', 'max:255'],
            'page_id_or_phone_id' => ['nullable', 'string', 'max:255'],
            'access_token' => ['nullable', 'string'],
            'webhook_verify_token' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $platform = $validated['platform'];
        $existing = SocialIntegration::where('platform', $platform)->first();

        if (isset($validated['access_token']) && $validated['access_token'] === '********') {
            $validated['access_token'] = $existing?->access_token ?? null;
        }

        SocialIntegration::updateOrCreate(
            ['platform' => $platform],
            [
                'account_name' => $validated['account_name'] ?? ($platform === 'facebook' ? 'Facebook Page' : 'WhatsApp Business'),
                'page_id_or_phone_id' => $validated['page_id_or_phone_id'] ?? null,
                'access_token' => $validated['access_token'] ?? null,
                'webhook_verify_token' => $validated['webhook_verify_token'] ?? config('services.meta.verify_token', 'demo_verify_token'),
                'is_active' => (bool) ($validated['is_active'] ?? true),
            ]
        );

        return redirect()->route('settings.integrations')->with('success', ucfirst($platform) . ' integration saved successfully.');
    }

    public function testConnection(Request $request)
    {
        $validated = $request->validate([
            'platform' => ['required', Rule::in(['facebook', 'whatsapp'])],
            'access_token' => ['required', 'string'],
        ]);

        $response = Http::acceptJson()->get('https://graph.facebook.com/v18.0/me', [
            'access_token' => $validated['access_token'],
            'fields' => 'id,name',
        ]);

        if ($response->successful()) {
            return response()->json([
                'ok' => true,
                'message' => 'Connection test successful.',
                'data' => $response->json(),
            ]);
        }

        $message = $response->json('error.message', 'Unable to validate credentials against Meta Graph API.');

        return response()->json([
            'ok' => false,
            'message' => $message,
        ], 422);
    }
}
