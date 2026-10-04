@extends('layouts.app')

@section('title', 'Social Integrations')

@section('content')
<div class="mx-auto max-w-6xl space-y-6">
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-violet-600">Settings</p>
                <h1 class="mt-1 text-3xl font-bold text-slate-900">Social Integrations</h1>
            </div>

            <button type="button" data-open-modal="integration-modal" class="rounded-xl bg-violet-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-violet-500">
                Connect WhatsApp / Facebook Page
            </button>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <p class="text-sm font-medium text-slate-500">Facebook Page Messenger</p>
                    <h2 class="mt-1 text-xl font-bold text-slate-900">Facebook Page</h2>
                </div>
                @php $facebookIntegration = $facebook ?? null; @endphp
                @if ($facebookIntegration && $facebookIntegration->is_active)
                    <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-700">Connected ({{ $facebookIntegration->account_name ?? 'Page' }})</span>
                @else
                    <span class="rounded-full bg-slate-200 px-2.5 py-1 text-xs font-semibold text-slate-600">Not Connected</span>
                @endif
            </div>

            <div class="mt-5 rounded-xl border border-slate-200 bg-slate-50 p-4">
                <p class="text-sm text-slate-600">
                    @if ($facebookIntegration && $facebookIntegration->is_active)
                        Connected to <span class="font-semibold text-slate-900">{{ $facebookIntegration->account_name ?? 'Facebook Page' }}</span>
                        @if ($facebookIntegration->page_id_or_phone_id)
                            <span class="block text-xs text-slate-500 mt-1">Page ID: {{ $facebookIntegration->page_id_or_phone_id }}</span>
                        @endif
                    @else
                        Add your Facebook page ID, page access token, and webhook verify token to start receiving Messenger conversations.
                    @endif
                </p>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <p class="text-sm font-medium text-slate-500">WhatsApp Business API</p>
                    <h2 class="mt-1 text-xl font-bold text-slate-900">WhatsApp</h2>
                </div>
                @php $whatsappIntegration = $whatsapp ?? null; @endphp
                @if ($whatsappIntegration && $whatsappIntegration->is_active)
                    <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-700">Connected ({{ $whatsappIntegration->account_name ?? 'Business Number' }})</span>
                @else
                    <span class="rounded-full bg-slate-200 px-2.5 py-1 text-xs font-semibold text-slate-600">Not Connected</span>
                @endif
            </div>

            <div class="mt-5 rounded-xl border border-slate-200 bg-slate-50 p-4">
                <p class="text-sm text-slate-600">
                    @if ($whatsappIntegration && $whatsappIntegration->is_active)
                        Connected to <span class="font-semibold text-slate-900">{{ $whatsappIntegration->account_name ?? 'WhatsApp Business Account' }}</span>
                        @if ($whatsappIntegration->page_id_or_phone_id)
                            <span class="block text-xs text-slate-500 mt-1">Phone Number ID: {{ $whatsappIntegration->page_id_or_phone_id }}</span>
                        @endif
                    @else
                        Add your WhatsApp business app credentials and phone number ID to enable messaging automation.
                    @endif
                </p>
            </div>
        </div>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="grid gap-6 lg:grid-cols-2">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.2em] text-slate-500">Webhook callback URL</p>
                <div class="mt-3 flex items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2">
                    <input id="webhook-url" value="{{ $webhookUrl }}" readonly class="w-full bg-transparent text-sm text-slate-700 focus:outline-none">
                    <button type="button" data-copy="webhook-url" class="rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-semibold text-slate-700">Copy</button>
                </div>
            </div>

            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.2em] text-slate-500">Verify token</p>
                <div class="mt-3 flex items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2">
                    <input id="verify-token" value="{{ $verifyToken }}" readonly class="w-full bg-transparent text-sm text-slate-700 focus:outline-none">
                    <button type="button" data-copy="verify-token" class="rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-semibold text-slate-700">Copy</button>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="integration-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/50 p-4">
    <div class="w-full max-w-3xl rounded-2xl border border-slate-200 bg-white shadow-2xl">
        <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-violet-600">Setup Wizard</p>
                <h3 class="text-xl font-bold text-slate-900">Connect Meta Integration</h3>
            </div>
            <button type="button" data-close-modal="integration-modal" class="rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-sm text-slate-600">Close</button>
        </div>

        <div class="border-b border-slate-200 px-5 py-4">
            <div class="inline-flex rounded-lg border border-slate-200 bg-slate-100 p-1">
                <button type="button" data-tab="facebook-tab" class="tab-button rounded-md bg-white px-4 py-2 text-sm font-semibold text-slate-900 shadow-sm" aria-selected="true">Facebook Page Setup</button>
                <button type="button" data-tab="whatsapp-tab" class="tab-button rounded-md px-4 py-2 text-sm font-medium text-slate-600" aria-selected="false">WhatsApp Cloud API</button>
            </div>
        </div>

        <form id="integration-form" method="POST" action="{{ route('settings.integrations.store') }}" class="p-5">
            @csrf
            <input type="hidden" name="platform" id="integration-platform" value="facebook">
            <div id="facebook-tab" class="tab-panel">
                <div class="grid gap-4 md:grid-cols-2">

                    <div class="md:col-span-2">
                        <label class="mb-1 block text-sm font-medium text-slate-700">Account Name</label>
                        <input name="account_name" type="text" value="{{ ($facebook && $facebook->account_name) ? $facebook->account_name : '' }}" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-800 focus:border-violet-500 focus:outline-none focus:ring-2 focus:ring-violet-200" placeholder="My Business Page">
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Page ID</label>
                        <input name="page_id_or_phone_id" type="text" value="{{ ($facebook && $facebook->page_id_or_phone_id) ? $facebook->page_id_or_phone_id : '' }}" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-800 focus:border-violet-500 focus:outline-none focus:ring-2 focus:ring-violet-200" placeholder="123456789012345">
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Page Access Token</label>
                        <input name="access_token" type="password" value="{{ $facebook && $facebook->access_token ? '********' : '' }}" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-800 focus:border-violet-500 focus:outline-none focus:ring-2 focus:ring-violet-200" placeholder="EA...">
                    </div>

                    <div class="md:col-span-2">
                        <label class="mb-1 block text-sm font-medium text-slate-700">Webhook Verify Token</label>
                        <input name="webhook_verify_token" type="text" value="{{ $verifyToken }}" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-800 focus:border-violet-500 focus:outline-none focus:ring-2 focus:ring-violet-200">
                    </div>
                </div>

                <div class="mt-5 rounded-xl border border-blue-200 bg-blue-50 p-4 text-sm text-blue-800">
                    <p class="font-semibold">Step-by-step</p>
                    <ol class="mt-2 list-decimal space-y-1 pl-5">
                        <li>Open your Meta Developer App and choose the page.</li>
                        <li>Copy the page ID and permanent page access token from the app.</li>
                        <li>Paste the callback URL and verify token into your Meta webhook settings.</li>
                        <li>Click Test Connection before saving.</li>
                    </ol>
                </div>
            </div>

            <div id="whatsapp-tab" class="tab-panel hidden">
                <div class="grid gap-4 md:grid-cols-2">

                    <div class="md:col-span-2">
                        <label class="mb-1 block text-sm font-medium text-slate-700">Account Name</label>
                        <input name="account_name" type="text" value="{{ ($whatsapp && $whatsapp->account_name) ? $whatsapp->account_name : '' }}" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-800 focus:border-violet-500 focus:outline-none focus:ring-2 focus:ring-violet-200" placeholder="Business WhatsApp Account">
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Meta Business App ID</label>
                        <input name="meta_app_id" type="text" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-800 focus:border-violet-500 focus:outline-none focus:ring-2 focus:ring-violet-200" placeholder="App ID">
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Phone Number ID</label>
                        <input name="page_id_or_phone_id" type="text" value="{{ ($whatsapp && $whatsapp->page_id_or_phone_id) ? $whatsapp->page_id_or_phone_id : '' }}" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-800 focus:border-violet-500 focus:outline-none focus:ring-2 focus:ring-violet-200" placeholder="123456789012345">
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">WhatsApp Business Account ID</label>
                        <input name="business_account_id" type="text" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-800 focus:border-violet-500 focus:outline-none focus:ring-2 focus:ring-violet-200" placeholder="Business Account ID">
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">System User Permanent Access Token</label>
                        <input name="access_token" type="password" value="{{ $whatsapp && $whatsapp->access_token ? '********' : '' }}" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-800 focus:border-violet-500 focus:outline-none focus:ring-2 focus:ring-violet-200" placeholder="EA...">
                    </div>

                    <div class="md:col-span-2">
                        <label class="mb-1 block text-sm font-medium text-slate-700">Verify Token</label>
                        <input name="webhook_verify_token" type="text" value="{{ $verifyToken }}" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-800 focus:border-violet-500 focus:outline-none focus:ring-2 focus:ring-violet-200">
                    </div>
                </div>
            </div>

            <div class="mt-6 flex items-center justify-between gap-3">
                <button type="button" id="test-integration-btn" class="rounded-xl border border-slate-300 bg-slate-100 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-200">
                    Test Connection
                </button>

                <div class="flex items-center gap-3">
                    <button type="button" data-close-modal="integration-modal" class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Cancel</button>
                    <button type="submit" class="rounded-xl bg-violet-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-violet-500">Save Connection</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    const tabButtons = document.querySelectorAll('.tab-button');
    const tabPanels = document.querySelectorAll('.tab-panel');

    const platformInput = document.getElementById('integration-platform');

    tabButtons.forEach((button) => {
        button.addEventListener('click', () => {
            tabButtons.forEach((item) => {
                item.classList.toggle('bg-white', item === button);
                item.classList.toggle('shadow-sm', item === button);
                item.classList.toggle('text-slate-900', item === button);
                item.classList.toggle('font-semibold', item === button);
                item.classList.toggle('text-slate-600', item !== button);
                item.classList.toggle('font-medium', item !== button);
                item.setAttribute('aria-selected', String(item === button));
            });

            const target = button.dataset.tab;
            tabPanels.forEach((panel) => {
                panel.classList.toggle('hidden', panel.id !== target);
            });

            if (platformInput) {
                platformInput.value = target === 'facebook-tab' ? 'facebook' : 'whatsapp';
            }
        });
    });

    document.querySelectorAll('[data-open-modal]').forEach((button) => {
        button.addEventListener('click', () => {
            const modal = document.getElementById(button.dataset.openModal);
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        });
    });

    document.querySelectorAll('[data-close-modal]').forEach((button) => {
        button.addEventListener('click', () => {
            const modal = document.getElementById(button.dataset.closeModal);
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        });
    });

    document.querySelectorAll('[data-copy]').forEach((button) => {
        button.addEventListener('click', async () => {
            const input = document.getElementById(button.dataset.copy);
            if (! input) return;
            await navigator.clipboard.writeText(input.value);
            button.textContent = 'Copied';
            setTimeout(() => button.textContent = 'Copy', 1200);
        });
    });

    const testButton = document.getElementById('test-integration-btn');
    const form = document.getElementById('integration-form');

    testButton?.addEventListener('click', async () => {
        const formData = new FormData(form);
        const platform = platformInput ? platformInput.value : formData.get('platform');
        const token = formData.getAll('access_token').find((value) => value && value.toString().trim() && value.toString().trim() !== '********');

        if (! token || ! token.toString().trim()) {
            alert('Please enter a valid access token before testing the connection.');
            return;
        }

        const response = await fetch('{{ route('settings.integrations.test') }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
            },
            body: new URLSearchParams({
                platform: platform,
                access_token: token,
            })
        });

        const result = await response.json();

        if (! response.ok) {
            alert(result.message || 'Connection test failed.');
            return;
        }

        alert(result.message || 'Connection test successful.');
    });
</script>
@endsection
