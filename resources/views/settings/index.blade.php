@extends('layouts.app')

@section('title', 'Setup')

@section('content')
@php
    $generalErrors = $errors->getBag('general');
    $profileErrors = $errors->getBag('profile');
    $passwordErrors = $errors->getBag('password');

    $activeTab = session('tab')
        ?? ($passwordErrors->any() ? 'password' : ($profileErrors->any() ? 'profile' : 'general'));

    $inputClass = 'w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm text-slate-800 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200';
    $tabs = [
        'general' => 'General Settings',
        'profile' => 'Admin Profile',
        'password' => 'Change Password',
    ];
@endphp

<div class="mx-auto max-w-4xl">
    <div class="mb-6 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-indigo-600">Administration</p>
        <h1 class="text-3xl font-bold text-slate-900">Setup</h1>
        <p class="mt-1 text-sm text-slate-500">Manage your branding, admin profile and account security.</p>
    </div>

    {{-- Tabs --}}
    <div class="mb-6 flex flex-wrap gap-2 rounded-2xl border border-slate-200 bg-white p-2 shadow-sm" role="tablist">
        @foreach ($tabs as $key => $label)
            <button type="button" role="tab" data-tab="{{ $key }}"
                class="setup-tab rounded-xl px-4 py-2 text-sm font-medium transition {{ $activeTab === $key ? 'bg-slate-900 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100' }}">
                {{ $label }}
            </button>
        @endforeach
    </div>

    {{-- a) Logo & General Setup --}}
    <section data-panel="general" class="setup-panel rounded-2xl border border-slate-200 bg-white p-6 shadow-sm {{ $activeTab === 'general' ? '' : 'hidden' }}">
        <h2 class="text-lg font-semibold text-slate-900">Logo &amp; General Setup</h2>
        <p class="mb-5 text-sm text-slate-500">Upload your logo and set the site title shown across the CRM.</p>

        <form action="{{ route('settings.logo') }}" method="POST" enctype="multipart/form-data" class="space-y-5">
            @csrf

            <div>
                <label class="mb-2 block text-sm font-medium text-slate-700">Admin logo</label>
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
                    <div class="flex h-24 w-24 shrink-0 items-center justify-center overflow-hidden rounded-xl border border-dashed border-slate-300 bg-slate-50">
                        <img id="logoPreview" src="{{ $adminLogo ?? '' }}" alt="Logo preview" class="max-h-full max-w-full object-contain {{ $adminLogo ? '' : 'hidden' }}">
                        <span id="logoPlaceholder" class="px-2 text-center text-xs text-slate-400 {{ $adminLogo ? 'hidden' : '' }}">No logo</span>
                    </div>
                    <div class="flex-1">
                        <input id="admin_logo" name="admin_logo" type="file" accept="image/png,image/jpeg,image/webp,image/gif"
                            class="block w-full text-sm text-slate-600 file:mr-4 file:rounded-lg file:border-0 file:bg-indigo-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-indigo-700 hover:file:bg-indigo-100">
                        <p class="mt-1 text-xs text-slate-500">PNG, JPG, WEBP or GIF. Max 2 MB.</p>
                        @if ($generalErrors->has('admin_logo'))
                            <p class="mt-1 text-xs text-rose-600">{{ $generalErrors->first('admin_logo') }}</p>
                        @endif
                        @if ($adminLogo)
                            <label class="mt-3 inline-flex items-center gap-2 text-sm text-slate-600">
                                <input type="checkbox" name="remove_logo" value="1" class="rounded border-slate-300">
                                Remove current logo
                            </label>
                        @endif
                    </div>
                </div>
            </div>

            <div>
                <label for="site_title" class="mb-1 block text-sm font-medium text-slate-700">Site title</label>
                <input id="site_title" name="site_title" type="text" required maxlength="100" value="{{ old('site_title', $siteTitle) }}" class="{{ $inputClass }}">
                @if ($generalErrors->has('site_title'))
                    <p class="mt-1 text-xs text-rose-600">{{ $generalErrors->first('site_title') }}</p>
                @endif
            </div>

            <div class="flex justify-end">
                <button type="submit" class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-500">Save General Settings</button>
            </div>
        </form>
    </section>

    {{-- b) Admin Profile Setup --}}
    <section data-panel="profile" class="setup-panel rounded-2xl border border-slate-200 bg-white p-6 shadow-sm {{ $activeTab === 'profile' ? '' : 'hidden' }}">
        <h2 class="text-lg font-semibold text-slate-900">Admin Profile Setup</h2>
        <p class="mb-5 text-sm text-slate-500">Update the name and email address of your account.</p>

        <form action="{{ route('settings.profile') }}" method="POST" class="space-y-5">
            @csrf

            <div>
                <label for="profile_name" class="mb-1 block text-sm font-medium text-slate-700">Name</label>
                <input id="profile_name" name="name" type="text" required maxlength="255" value="{{ old('name', $user->name) }}" class="{{ $inputClass }}">
                @if ($profileErrors->has('name'))
                    <p class="mt-1 text-xs text-rose-600">{{ $profileErrors->first('name') }}</p>
                @endif
            </div>

            <div>
                <label for="profile_email" class="mb-1 block text-sm font-medium text-slate-700">Email address</label>
                <input id="profile_email" name="email" type="email" required maxlength="255" value="{{ old('email', $user->email) }}" class="{{ $inputClass }}">
                @if ($profileErrors->has('email'))
                    <p class="mt-1 text-xs text-rose-600">{{ $profileErrors->first('email') }}</p>
                @endif
            </div>

            <div class="flex justify-end">
                <button type="submit" class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-500">Update Profile</button>
            </div>
        </form>
    </section>

    {{-- c) Security Setup --}}
    <section data-panel="password" class="setup-panel rounded-2xl border border-slate-200 bg-white p-6 shadow-sm {{ $activeTab === 'password' ? '' : 'hidden' }}">
        <h2 class="text-lg font-semibold text-slate-900">Security Setup</h2>
        <p class="mb-5 text-sm text-slate-500">Choose a strong password of at least 8 characters.</p>

        <form action="{{ route('settings.password') }}" method="POST" class="space-y-5">
            @csrf

            <div>
                <label for="current_password" class="mb-1 block text-sm font-medium text-slate-700">Current password</label>
                <input id="current_password" name="current_password" type="password" required autocomplete="current-password" class="{{ $inputClass }}">
                @if ($passwordErrors->has('current_password'))
                    <p class="mt-1 text-xs text-rose-600">{{ $passwordErrors->first('current_password') }}</p>
                @endif
            </div>

            <div>
                <label for="new_password" class="mb-1 block text-sm font-medium text-slate-700">New password</label>
                <input id="new_password" name="password" type="password" required minlength="8" autocomplete="new-password" class="{{ $inputClass }}">
                @if ($passwordErrors->has('password'))
                    <p class="mt-1 text-xs text-rose-600">{{ $passwordErrors->first('password') }}</p>
                @endif
            </div>

            <div>
                <label for="password_confirmation" class="mb-1 block text-sm font-medium text-slate-700">Confirm new password</label>
                <input id="password_confirmation" name="password_confirmation" type="password" required minlength="8" autocomplete="new-password" class="{{ $inputClass }}">
            </div>

            <div class="flex justify-end">
                <button type="submit" class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-500">Change Password</button>
            </div>
        </form>
    </section>
</div>

<script>
    (function () {
        const tabs = document.querySelectorAll('.setup-tab');
        const panels = document.querySelectorAll('.setup-panel');
        const active = ['bg-slate-900', 'text-white', 'shadow-sm'];
        const idle = ['text-slate-600', 'hover:bg-slate-100'];

        function show(name) {
            panels.forEach((p) => p.classList.toggle('hidden', p.dataset.panel !== name));
            tabs.forEach((t) => {
                const on = t.dataset.tab === name;
                active.forEach((c) => t.classList.toggle(c, on));
                idle.forEach((c) => t.classList.toggle(c, !on));
            });
        }

        tabs.forEach((t) => t.addEventListener('click', () => show(t.dataset.tab)));

        // Live logo preview
        const input = document.getElementById('admin_logo');
        const preview = document.getElementById('logoPreview');
        const placeholder = document.getElementById('logoPlaceholder');
        input?.addEventListener('change', function () {
            const file = this.files && this.files[0];
            if (!file) return;
            preview.src = URL.createObjectURL(file);
            preview.classList.remove('hidden');
            placeholder.classList.add('hidden');
        });
    })();
</script>
@endsection
