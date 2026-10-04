@php
    $user = auth()->user();
    $isAdmin = $user && method_exists($user, 'isAdmin') ? $user->isAdmin() : false;
    $mainDashboardRoute = route('tasks.index');
    $leadManagementRoute = route('lead-management.index');
    $financialRoute = $isAdmin ? route('admin.payments') : route('payments.member');
    $membersRoute = $isAdmin ? route('admin.members') : route('payments.member');
    $leadNavOpen = request()->routeIs('lead-management.*') || request()->routeIs('clients.*');
@endphp

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>@yield('title', 'Workspace')</title>
        <script src="https://cdn.tailwindcss.com"></script>
    </head>
    <body class="bg-[#f3f4f6] text-slate-800 antialiased">
        <div class="min-h-screen flex flex-col md:flex-row bg-[#f3f4f6]">
            <div id="mobileSidebarOverlay" class="fixed inset-0 z-30 hidden bg-slate-900/30 md:hidden"></div>

            <aside id="sidebar" class="hidden md:flex md:w-64 md:sticky md:top-0 md:h-screen md:flex-shrink-0 md:flex-col md:border-r md:border-slate-200 md:bg-[#f5f5f4] md:px-4 md:py-6 md:shadow-none">
                <div class="rounded-2xl border border-slate-200 bg-white/80 p-4 shadow-sm backdrop-blur-sm">
                    <div class="flex items-center gap-3">
                        <div class="flex h-11 w-11 items-center justify-center rounded-full bg-gradient-to-br from-slate-800 to-slate-500 text-sm font-semibold text-white">
                            {{ strtoupper(substr((string) ($user->name ?? 'U'), 0, 1)) }}
                        </div>
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold text-slate-900">{{ $user->name ?? 'User' }}</p>
                            <p class="truncate text-xs text-slate-500">{{ $user->email ?? '' }}</p>
                        </div>
                    </div>
                </div>

                <nav class="mt-8 space-y-1.5 px-2">
                    <div class="space-y-1">
                        <a href="{{ $mainDashboardRoute }}" class="group flex items-center justify-between rounded-xl px-3 py-2.5 text-sm font-medium {{ request()->routeIs('tasks.index') ? 'bg-white text-slate-900 shadow-sm ring-1 ring-slate-200' : 'text-slate-600 hover:bg-white hover:text-slate-900' }}">
                            <span class="flex items-center gap-3">
                                <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8">
                                    <path d="M3 11.5 12 4l9 7.5" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M5 10.5V19h14v-8.5" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                Dashboard
                            </span>
                        </a>

                        <a href="{{ $mainDashboardRoute }}" class="group flex items-center justify-between rounded-xl px-3 py-2.5 text-sm font-medium {{ request()->routeIs('tasks.index') ? 'bg-slate-200/80 text-slate-900 shadow-sm ring-1 ring-slate-200' : 'text-slate-600 hover:bg-white hover:text-slate-900' }}">
                            <span class="flex items-center gap-3">
                                <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8">
                                    <path d="M8 5h8a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2Z"/>
                                    <path d="M8 9h8M8 13h5" stroke-linecap="round"/>
                                </svg>
                                Tasks
                            </span>
                            <span class="rounded-full bg-slate-200 px-1.5 py-0.5 text-[10px] font-semibold text-slate-600">{{ $isAdmin ? '12' : '8' }}</span>
                        </a>

                        <a href="{{ route('social-chat.index') }}" class="group flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium {{ request()->routeIs('social-chat.*') ? 'bg-white text-slate-900 shadow-sm ring-1 ring-slate-200' : 'text-slate-600 hover:bg-white hover:text-slate-900' }}">
                            <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8">
                                <path d="M7 9.5A3.5 3.5 0 0 1 10.5 6h6A3.5 3.5 0 0 1 20 9.5v5A3.5 3.5 0 0 1 16.5 18H12l-4 3v-3H10.5A3.5 3.5 0 0 1 7 14.5v-5Z" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M10 10h7M10 13h5" stroke-linecap="round"/>
                            </svg>
                            Social Chat
                        </a>

                        <a href="{{ route('invoices.index') }}" class="group flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium {{ request()->routeIs('invoices.*') ? 'bg-white text-slate-900 shadow-sm ring-1 ring-slate-200' : 'text-slate-600 hover:bg-white hover:text-slate-900' }}">
                            <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8">
                                <path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2M9 5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2M9 5a2 2 0 0 0-2 2v1M19 7v1" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M9 11h6M9 15h6" stroke-linecap="round"/>
                            </svg>
                            Invoice
                        </a>

                        <div data-lead-menu-group class="rounded-xl {{ $leadNavOpen ? 'bg-white text-slate-900 shadow-sm ring-1 ring-slate-200' : 'text-slate-600' }}">
                            <button type="button" data-lead-menu-toggle class="flex w-full items-center justify-between px-3 py-2.5 text-left text-sm font-medium focus:outline-none" aria-expanded="{{ $leadNavOpen ? 'true' : 'false' }}">
                                <span class="flex items-center gap-3">
                                    <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8">
                                        <path d="M4 18V8l8-4 8 4v10M4 12h16" stroke-linecap="round" stroke-linejoin="round"/>
                                        <path d="M9 16h6" stroke-linecap="round"/>
                                    </svg>
                                    Lead Management
                                </span>
                                <svg viewBox="0 0 24 24" class="h-4 w-4 transition-transform duration-200 {{ $leadNavOpen ? 'rotate-90 text-slate-700' : 'text-slate-500' }}" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="m9 6 6 6-6 6" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </button>
                            <div data-lead-menu class="space-y-1 px-2 pb-2 {{ $leadNavOpen ? '' : 'hidden' }}">
                                <a href="{{ $leadManagementRoute }}#appointments" class="block rounded-lg px-3 py-2 text-sm text-slate-600 hover:bg-slate-100 hover:text-slate-900">Add Appointment</a>
                                <a href="{{ $leadManagementRoute }}#meetings" class="block rounded-lg px-3 py-2 text-sm text-slate-600 hover:bg-slate-100 hover:text-slate-900">Meetings</a>
                                <a href="{{ $leadManagementRoute }}#assigned-leads" class="block rounded-lg px-3 py-2 text-sm text-slate-600 hover:bg-slate-100 hover:text-slate-900">Assigned Leads</a>
                                <a href="{{ route('clients.index') }}" class="block rounded-lg px-3 py-2 text-sm {{ request()->routeIs('clients.*') ? 'bg-violet-50 text-violet-700 font-medium' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">Clients</a>
                            </div>
                        </div>

                        @if ($isAdmin)
                            <a href="{{ route('admin.approvals') }}" class="group flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium {{ request()->routeIs('admin.approvals*') ? 'bg-white text-slate-900 shadow-sm ring-1 ring-slate-200' : 'text-slate-600 hover:bg-white hover:text-slate-900' }}">
                                <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8">
                                    <path d="M7 12.5 10 15.5l7-8" stroke-linecap="round" stroke-linejoin="round"/>
                                    <circle cx="12" cy="12" r="9"/>
                                </svg>
                                Approvals
                            </a>
                        @endif

                        <a href="{{ $financialRoute }}" class="group flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium {{ request()->routeIs('admin.payments') || request()->routeIs('payments.member') ? 'bg-white text-slate-900 shadow-sm ring-1 ring-slate-200' : 'text-slate-600 hover:bg-white hover:text-slate-900' }}">
                            <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8">
                                <path d="M3 6.5A2.5 2.5 0 0 1 5.5 4h13A2.5 2.5 0 0 1 21 6.5v11A2.5 2.5 0 0 1 18.5 20h-13A2.5 2.5 0 0 1 3 17.5v-11Z"/>
                                <path d="M3 9.5h18M8 14h2.5M14 14h2.5" stroke-linecap="round"/>
                            </svg>
                            Financial Ledger
                        </a>

                        @if ($isAdmin)
                            <a href="{{ route('admin.members') }}" class="group flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium {{ request()->routeIs('admin.members*') || request()->routeIs('members.payments') ? 'bg-white text-slate-900 shadow-sm ring-1 ring-slate-200' : 'text-slate-600 hover:bg-white hover:text-slate-900' }}">
                                <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8">
                                    <path d="M16 19v-1a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v1" stroke-linecap="round"/>
                                    <circle cx="9.5" cy="7" r="3.5"/>
                                    <path d="M19 19v-1a4 4 0 0 0-3-3.87M15 4.13a3.5 3.5 0 0 1 0 6.74" stroke-linecap="round"/>
                                </svg>
                                Members
                            </a>
                        @endif

                        @if ($isAdmin)
                            <a href="{{ route('settings.integrations') }}" class="group flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium {{ request()->routeIs('settings.*') ? 'bg-white text-slate-900 shadow-sm ring-1 ring-slate-200' : 'text-slate-600 hover:bg-white hover:text-slate-900' }}">
                                <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8">
                                    <path d="M12 3v3M12 18v3M4.93 4.93l2.12 2.12M16.95 16.95l2.12 2.12M3 12h3M18 12h3M4.93 19.07l2.12-2.12M16.95 7.05l2.12-2.12" stroke-linecap="round"/>
                                    <circle cx="12" cy="12" r="4"/>
                                </svg>
                                Integrations
                            </a>
                        @endif
                    </div>

                    <div class="mt-8 border-t border-slate-200 pt-4">
                        <form action="{{ route('logout') }}" method="POST">
                            @csrf
                            <button type="submit" class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-left text-sm font-medium text-slate-600 transition hover:bg-rose-50 hover:text-rose-700">
                                <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8">
                                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" stroke-linecap="round"/>
                                    <path d="M16 17l5-5-5-5" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M21 12H9" stroke-linecap="round"/>
                                </svg>
                                Logout
                            </button>
                        </form>
                    </div>
                </nav>
            </aside>

            <div id="mobileSidebar" class="fixed inset-y-0 left-0 z-40 w-[260px] -translate-x-full border-r border-slate-200 bg-[#f5f5f4] px-4 py-6 shadow-xl transition-transform duration-200 md:hidden">
                <div class="flex items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-white/80 p-4 shadow-sm backdrop-blur-sm">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-gradient-to-br from-slate-800 to-slate-500 text-sm font-semibold text-white">
                            {{ strtoupper(substr((string) ($user->name ?? 'U'), 0, 1)) }}
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-slate-900">{{ $user->name ?? 'User' }}</p>
                            <p class="text-[11px] text-slate-500">{{ $user->email ?? '' }}</p>
                        </div>
                    </div>
                    <button id="mobileSidebarClose" type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-slate-100 text-slate-600">
                        <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M6 6l12 12M18 6L6 18" stroke-linecap="round"/>
                        </svg>
                    </button>
                </div>

                <nav class="mt-8 space-y-1.5 px-2">
                    <a href="{{ $mainDashboardRoute }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium {{ request()->routeIs('tasks.index') ? 'bg-white text-slate-900 shadow-sm ring-1 ring-slate-200' : 'text-slate-600 hover:bg-white hover:text-slate-900' }}">
                        <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path d="M3 11.5 12 4l9 7.5" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M5 10.5V19h14v-8.5" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        Dashboard
                    </a>
                    <a href="{{ $mainDashboardRoute }}" class="flex items-center justify-between rounded-xl px-3 py-2.5 text-sm font-medium {{ request()->routeIs('tasks.index') ? 'bg-slate-200/80 text-slate-900 shadow-sm ring-1 ring-slate-200' : 'text-slate-600 hover:bg-white hover:text-slate-900' }}">
                        <span class="flex items-center gap-3">
                            <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8">
                                <path d="M8 5h8a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2Z"/>
                                <path d="M8 9h8M8 13h5" stroke-linecap="round"/>
                            </svg>
                            Tasks
                        </span>
                        <span class="rounded-full bg-slate-200 px-1.5 py-0.5 text-[10px] font-semibold text-slate-600">{{ $isAdmin ? '12' : '8' }}</span>
                    </a>
                    <a href="{{ route('social-chat.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium {{ request()->routeIs('social-chat.*') ? 'bg-white text-slate-900 shadow-sm ring-1 ring-slate-200' : 'text-slate-600 hover:bg-white hover:text-slate-900' }}">
                        <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path d="M7 9.5A3.5 3.5 0 0 1 10.5 6h6A3.5 3.5 0 0 1 20 9.5v5A3.5 3.5 0 0 1 16.5 18H12l-4 3v-3H10.5A3.5 3.5 0 0 1 7 14.5v-5Z" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M10 10h7M10 13h5" stroke-linecap="round"/>
                        </svg>
                        Social Chat
                    </a>
                    <div data-lead-menu-group class="rounded-xl {{ $leadNavOpen ? 'bg-white text-slate-900 shadow-sm ring-1 ring-slate-200' : 'text-slate-600' }}">
                        <button type="button" data-lead-menu-toggle class="flex w-full items-center justify-between px-3 py-2.5 text-left text-sm font-medium focus:outline-none" aria-expanded="{{ $leadNavOpen ? 'true' : 'false' }}">
                            <span class="flex items-center gap-3">
                                <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8">
                                    <path d="M4 18V8l8-4 8 4v10M4 12h16" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M9 16h6" stroke-linecap="round"/>
                                </svg>
                                Lead Management
                            </span>
                            <svg viewBox="0 0 24 24" class="h-4 w-4 transition-transform duration-200 {{ $leadNavOpen ? 'rotate-90 text-slate-700' : 'text-slate-500' }}" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="m9 6 6 6-6 6" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </button>
                        <div data-lead-menu class="space-y-1 px-2 pb-2 {{ $leadNavOpen ? '' : 'hidden' }}">
                            <a href="{{ $leadManagementRoute }}#appointments" class="block rounded-lg px-3 py-2 text-sm text-slate-600 hover:bg-slate-100 hover:text-slate-900">Add Appointment</a>
                            <a href="{{ $leadManagementRoute }}#meetings" class="block rounded-lg px-3 py-2 text-sm text-slate-600 hover:bg-slate-100 hover:text-slate-900">Meetings</a>
                            <a href="{{ $leadManagementRoute }}#assigned-leads" class="block rounded-lg px-3 py-2 text-sm text-slate-600 hover:bg-slate-100 hover:text-slate-900">Assigned Leads</a>
                            <a href="{{ route('clients.index') }}" class="block rounded-lg px-3 py-2 text-sm {{ request()->routeIs('clients.*') ? 'bg-violet-50 text-violet-700 font-medium' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">Clients</a>
                        </div>
                    </div>
                    @if ($isAdmin)
                        <a href="{{ route('admin.approvals') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium {{ request()->routeIs('admin.approvals*') ? 'bg-white text-slate-900 shadow-sm ring-1 ring-slate-200' : 'text-slate-600 hover:bg-white hover:text-slate-900' }}">
                            <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8">
                                <path d="M7 12.5 10 15.5l7-8" stroke-linecap="round" stroke-linejoin="round"/>
                                <circle cx="12" cy="12" r="9"/>
                            </svg>
                            Approvals
                        </a>
                    @endif
                    <a href="{{ $financialRoute }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium {{ request()->routeIs('admin.payments') || request()->routeIs('payments.member') ? 'bg-white text-slate-900 shadow-sm ring-1 ring-slate-200' : 'text-slate-600 hover:bg-white hover:text-slate-900' }}">
                        <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path d="M3 6.5A2.5 2.5 0 0 1 5.5 4h13A2.5 2.5 0 0 1 21 6.5v11A2.5 2.5 0 0 1 18.5 20h-13A2.5 2.5 0 0 1 3 17.5v-11Z"/>
                            <path d="M3 9.5h18M8 14h2.5M14 14h2.5" stroke-linecap="round"/>
                        </svg>
                        Financial Ledger
                    </a>
                    @if ($isAdmin)
                        <a href="{{ route('admin.members') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium {{ request()->routeIs('admin.members*') || request()->routeIs('members.payments') ? 'bg-white text-slate-900 shadow-sm ring-1 ring-slate-200' : 'text-slate-600 hover:bg-white hover:text-slate-900' }}">
                            <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8">
                                <path d="M16 19v-1a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v1" stroke-linecap="round"/>
                                <circle cx="9.5" cy="7" r="3.5"/>
                                <path d="M19 19v-1a4 4 0 0 0-3-3.87M15 4.13a3.5 3.5 0 0 1 0 6.74" stroke-linecap="round"/>
                            </svg>
                            Members
                        </a>
                    @endif
                    @if ($isAdmin)
                        <a href="{{ route('settings.integrations') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium {{ request()->routeIs('settings.*') ? 'bg-white text-slate-900 shadow-sm ring-1 ring-slate-200' : 'text-slate-600 hover:bg-white hover:text-slate-900' }}">
                            <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8">
                                <path d="M12 3v3M12 18v3M4.93 4.93l2.12 2.12M16.95 16.95l2.12 2.12M3 12h3M18 12h3M4.93 19.07l2.12-2.12M16.95 7.05l2.12-2.12" stroke-linecap="round"/>
                                <circle cx="12" cy="12" r="4"/>
                            </svg>
                            Integrations
                        </a>
                    @endif
                    <form action="{{ route('logout') }}" method="POST" class="pt-4">
                        @csrf
                        <button type="submit" class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-left text-sm font-medium text-slate-600 transition hover:bg-rose-50 hover:text-rose-700">
                            <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8">
                                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" stroke-linecap="round"/>
                                <path d="M16 17l5-5-5-5" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M21 12H9" stroke-linecap="round"/>
                            </svg>
                            Logout
                        </button>
                    </form>
                </nav>
            </div>

            <main class="flex-1 overflow-y-auto p-4 sm:p-5 lg:p-8">
                <header class="sticky top-0 z-40 mb-4 flex items-center justify-between rounded-2xl border border-slate-200 bg-white/90 p-3 shadow-sm backdrop-blur-sm md:hidden">
                    <button id="sidebarToggle" type="button" class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-700 shadow-sm">
                        <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M4 7h16M4 12h16M4 17h16" stroke-linecap="round"/>
                        </svg>
                    </button>

                    <div class="flex items-center gap-2 text-center">
                        <div class="flex h-8 w-8 items-center justify-center rounded-full bg-slate-800 text-xs font-bold text-white">
                            {{ strtoupper(substr((string) ($user->name ?? 'U'), 0, 1)) }}
                        </div>
                        <div>
                            <p class="text-[10px] font-semibold uppercase tracking-[0.2em] text-slate-500">Workspace</p>
                            <p class="text-sm font-semibold text-slate-800">{{ $user->name ?? 'User' }}</p>
                        </div>
                    </div>

                    <button type="button" class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-slate-100 text-slate-700">
                        <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 5v14M5 12h14" stroke-linecap="round"/>
                        </svg>
                    </button>
                </header>

                @if (session('success'))
                    <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                        {{ session('success') }}
                    </div>
                @endif

                @if (session('error'))
                    <div class="mb-6 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-700">
                        {{ session('error') }}
                    </div>
                @endif

                @if ($errors ?? false)
                    @if ($errors->any())
                        <div class="mb-6 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                            <ul class="list-disc pl-5">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                @endif

                @yield('content')
            </main>
        </div>

        <script>
            const mobileSidebar = document.getElementById('mobileSidebar');
            const overlay = document.getElementById('mobileSidebarOverlay');
            const sidebarToggle = document.getElementById('sidebarToggle');
            const mobileSidebarClose = document.getElementById('mobileSidebarClose');
            const leadMenuGroups = document.querySelectorAll('[data-lead-menu-group]');

            leadMenuGroups.forEach((group) => {
                const button = group.querySelector('[data-lead-menu-toggle]');
                const panel = group.querySelector('[data-lead-menu]');
                if (! button || ! panel) return;

                button.addEventListener('click', function (event) {
                    event.preventDefault();
                    const isOpen = button.getAttribute('aria-expanded') === 'true';

                    button.setAttribute('aria-expanded', String(! isOpen));
                    panel.classList.toggle('hidden', isOpen);

                    const icon = button.querySelector('svg:last-of-type');
                    if (icon) {
                        icon.classList.toggle('rotate-90', ! isOpen);
                        icon.classList.toggle('text-slate-700', ! isOpen);
                        icon.classList.toggle('text-slate-500', isOpen);
                    }
                });
            });

            function setSidebar(open) {
                if (! mobileSidebar || ! overlay) return;
                const isDesktop = window.innerWidth >= 768;
                if (isDesktop) {
                    mobileSidebar.classList.add('-translate-x-full');
                    overlay.classList.add('hidden');
                    return;
                }
                mobileSidebar.classList.toggle('-translate-x-full', ! open);
                overlay.classList.toggle('hidden', ! open);
                document.body.classList.toggle('overflow-hidden', open);
            }

            sidebarToggle?.addEventListener('click', function () {
                setSidebar(true);
            });

            mobileSidebarClose?.addEventListener('click', function () {
                setSidebar(false);
            });

            overlay?.addEventListener('click', function () {
                setSidebar(false);
            });

            window.addEventListener('resize', function () {
                if (window.innerWidth >= 768) {
                    setSidebar(false);
                    document.body.classList.remove('overflow-hidden');
                }
            });
        </script>
    </body>
</html>
