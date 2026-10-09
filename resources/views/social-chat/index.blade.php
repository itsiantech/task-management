@extends('layouts.app')

@section('title', 'Social Chat Inbox')

@section('content')
@php
    $initialConversations = $conversations->map(function ($c) {
        $last = $c->messages->last();

        return [
            'id' => $c->id,
            'platform' => $c->platform,
            'sender_id' => $c->sender_id,
            'sender_name' => $c->sender_name,
            'status' => $c->status,
            'priority' => $c->priority,
            'follow_up' => (bool) $c->follow_up,
            'unread' => (bool) $c->unread,
            'assigned_to' => $c->assigned_to,
            'assignee_name' => $c->assignee?->name,
            'last_message_at' => optional($c->last_message_at)->toISOString(),
            'preview' => $last ? ($last->message_text ?: ($last->attachment_url ? '[Attachment]' : 'No messages yet')) : 'No messages yet',
            'profile_url' => $c->platform === 'facebook' ? 'https://www.facebook.com/profile.php?id=' . $c->sender_id : null,
        ];
    })->values();

    $initialMessages = $selectedConversation
        ? $selectedConversation->messages->map(fn ($m) => [
            'id' => $m->id,
            'sender_type' => $m->sender_type,
            'message_text' => $m->message_text,
            'attachment_url' => $m->attachment_url,
            'attachment_type' => $m->attachment_type,
            'agent_name' => $m->agent?->name,
            'created_at' => $m->created_at->format('Y-m-d H:i:s'),
        ])->values()
        : collect();

    $initialConversation = $selectedConversation ? [
        'id' => $selectedConversation->id,
        'platform' => $selectedConversation->platform,
        'sender_id' => $selectedConversation->sender_id,
        'sender_name' => $selectedConversation->sender_name,
        'status' => $selectedConversation->status,
        'priority' => $selectedConversation->priority,
        'follow_up' => (bool) $selectedConversation->follow_up,
        'unread' => (bool) $selectedConversation->unread,
        'assigned_to' => $selectedConversation->assigned_to,
        'assignee_name' => $selectedConversation->assignee?->name,
        'last_message_at' => optional($selectedConversation->last_message_at)->toISOString(),
        'profile_url' => $selectedConversation->platform === 'facebook'
            ? 'https://www.facebook.com/profile.php?id=' . $selectedConversation->sender_id
            : null,
    ] : null;

    $membersData = $members->map(fn ($m) => ['id' => $m->id, 'name' => $m->name])->values();
@endphp

<style>
    .sc-pane { height: calc(100vh - 170px); min-height: 540px; }
    .sc-scroll { overflow-y: auto; scrollbar-width: thin; scrollbar-color: rgb(203 213 225) transparent; }
    .sc-scroll::-webkit-scrollbar { width: 6px; }
    .sc-scroll::-webkit-scrollbar-thumb { background: rgb(203 213 225); border-radius: 9999px; }
    .sc-scroll::-webkit-scrollbar-track { background: transparent; }

    .sc-bubble { max-width: min(78%, 560px); word-break: break-word; overflow-wrap: anywhere; white-space: pre-wrap; }
    .sc-bubble img, .sc-bubble video { max-width: min(260px, 100%); border-radius: 12px; }
    .sc-bubble audio { width: min(260px, 100%); }

    .sc-composer { display: flex; align-items: flex-end; gap: 0.5rem; padding: 0.625rem 0.75rem; border-top: 1px solid rgb(226 232 240); background: #fff; }
    .sc-textarea { flex: 1; min-height: 40px; max-height: 120px; border-radius: 20px; border: 1px solid rgb(203 213 225); resize: none; overflow: hidden; padding: 0.55rem 0.9rem; font-size: 0.875rem; line-height: 1.5; color: rgb(15 23 42); background: rgb(248 250 252); outline: none; transition: border-color .15s ease, box-shadow .15s ease; }
    .sc-textarea:focus { border-color: #8b5cf6; box-shadow: 0 0 0 3px rgb(139 92 246 / 0.15); background: #fff; }
    .sc-icon-btn { display: inline-flex; align-items: center; justify-content: center; width: 38px; height: 38px; border-radius: 9999px; border: 1px solid rgb(203 213 225); background: #fff; color: rgb(71 85 105); cursor: pointer; transition: all .15s ease; flex-shrink: 0; }
    .sc-icon-btn:hover { background: rgb(241 245 249); border-color: rgb(148 163 184); }
    .sc-send-btn { border: none; border-radius: 9999px; background: linear-gradient(135deg, #7c3aed, #6d28d9); color: #fff; font-weight: 600; padding: 0.55rem 1.15rem; min-width: 84px; cursor: pointer; transition: opacity .15s ease, transform .1s ease; flex-shrink: 0; }
    .sc-send-btn:hover { opacity: .92; }
    .sc-send-btn:active { transform: scale(.97); }
    .sc-send-btn:disabled { opacity: .55; cursor: not-allowed; }

    .sc-file-hidden { position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0, 0, 0, 0); white-space: nowrap; border: 0; }

    .sc-preview-chip { display: none; align-items: center; gap: .5rem; margin: 0 .75rem .5rem; padding: .4rem .6rem; width: fit-content; max-width: 260px; border: 1px solid rgb(226 232 240); border-radius: 12px; background: rgb(248 250 252); }
    .sc-preview-chip.is-visible { display: inline-flex; }
    .sc-preview-chip img { width: 40px; height: 40px; object-fit: cover; border-radius: 8px; }
    .sc-preview-chip .sc-preview-name { font-size: 11px; color: rgb(71 85 105); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 150px; }
    .sc-preview-remove { width: 20px; height: 20px; border-radius: 9999px; border: none; background: rgb(203 213 225); color: rgb(51 65 85); font-size: 13px; line-height: 1; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0; }

    .sc-tab { padding: .4rem .8rem; border-radius: 9999px; font-size: 12px; font-weight: 600; border: 1px solid rgb(226 232 240); background: #fff; color: rgb(71 85 105); cursor: pointer; transition: all .15s ease; }
    .sc-tab:hover { border-color: rgb(196 181 253); color: #6d28d9; }
    .sc-tab.is-active { background: linear-gradient(135deg, #7c3aed, #6d28d9); border-color: transparent; color: #fff; }
    .sc-tab .sc-count { opacity: .75; font-weight: 700; margin-left: .25rem; }

    .sc-chip { padding: .3rem .7rem; border-radius: 9999px; font-size: 11px; font-weight: 600; border: 1px solid rgb(226 232 240); background: #fff; color: rgb(100 116 139); cursor: pointer; transition: all .15s ease; }
    .sc-chip:hover { border-color: rgb(196 181 253); }
    .sc-chip.is-active { background: rgb(237 233 254); border-color: rgb(196 181 253); color: #6d28d9; }

    .sc-conv-item { display: block; width: 100%; text-align: left; padding: .625rem .75rem; margin-bottom: .375rem; border-radius: 14px; border: 1px solid transparent; cursor: pointer; transition: all .15s ease; }
    .sc-conv-item:hover { background: rgb(241 245 249); }
    .sc-conv-item.is-active { background: rgb(237 233 254); border-color: rgb(196 181 253); }

    .sc-flag-btn { width: 32px; height: 32px; border-radius: 9999px; border: 1px solid rgb(226 232 240); background: #fff; color: rgb(100 116 139); display: inline-flex; align-items: center; justify-content: center; cursor: pointer; transition: all .15s ease; }
    .sc-flag-btn:hover { border-color: rgb(196 181 253); color: #6d28d9; }
    .sc-flag-btn.is-on-priority { background: rgb(254 243 199); border-color: rgb(252 211 77); color: rgb(180 83 9); }
    .sc-flag-btn.is-on-follow { background: rgb(255 228 230); border-color: rgb(253 164 175); color: rgb(190 18 60); }

    .sc-select { border-radius: 10px; border: 1px solid rgb(203 213 225); padding: .4rem .6rem; font-size: 12px; color: rgb(51 65 85); background: #fff; outline: none; }
    .sc-select:focus { border-color: #8b5cf6; box-shadow: 0 0 0 3px rgb(139 92 246 / .15); }

    .sc-toggle { position: relative; width: 40px; height: 22px; border-radius: 9999px; background: rgb(203 213 225); transition: background .2s ease; cursor: pointer; border: none; flex-shrink: 0; }
    .sc-toggle::after { content: ''; position: absolute; top: 3px; left: 3px; width: 16px; height: 16px; border-radius: 9999px; background: #fff; transition: transform .2s ease; box-shadow: 0 1px 3px rgba(15,23,42,.25); }
    .sc-toggle.is-on { background: linear-gradient(135deg, #7c3aed, #6d28d9); }
    .sc-toggle.is-on::after { transform: translateX(18px); }

    .sc-toast { position: fixed; bottom: 1.25rem; right: 1.25rem; z-index: 60; padding: .65rem 1rem; border-radius: 12px; font-size: 13px; font-weight: 500; color: #fff; box-shadow: 0 10px 25px rgba(15,23,42,.2); opacity: 0; transform: translateY(8px); pointer-events: none; transition: all .25s ease; }
    .sc-toast.is-visible { opacity: 1; transform: translateY(0); }
    .sc-toast.is-error { background: rgb(225 29 72); }
    .sc-toast.is-success { background: rgb(5 150 105); }

    .sc-day-divider { display: flex; align-items: center; gap: .75rem; margin: 1rem 0 .75rem; }
    .sc-day-divider::before, .sc-day-divider::after { content: ''; flex: 1; height: 1px; background: rgb(226 232 240); }
    .sc-day-divider span { font-size: 11px; font-weight: 600; color: rgb(100 116 139); background: rgb(248 250 252); padding: .15rem .7rem; border-radius: 9999px; border: 1px solid rgb(226 232 240); }

    .sc-empty { flex-direction: column; align-items: center; justify-content: center; height: 100%; color: rgb(100 116 139); gap: .75rem; }

    .sc-day-divider { position: sticky; top: 6px; z-index: 5; }

    #sc-app button { touch-action: manipulation; -webkit-tap-highlight-color: transparent; }
    .sc-conv-item:active { transform: scale(.985); }

    @keyframes scPaneIn { from { opacity: 0; transform: translateX(28px); } to { opacity: 1; transform: translateX(0); } }
    @keyframes scSheetUp { from { transform: translateY(60px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }

    @media (max-width: 1023.98px) {
        .sc-pane { height: calc(100vh - 225px); height: calc(100dvh - 225px); min-height: 420px; }
        #sc-app .sc-pane-list { display: flex; }
        #sc-app .sc-pane-chat { display: none; }
        #sc-app.sc-chat-open .sc-pane-list { display: none; }
        #sc-app.sc-chat-open .sc-pane-chat { display: flex; animation: scPaneIn .24s cubic-bezier(.2,.9,.3,1); }
        #sc-messages { padding: .75rem; }
        .sc-bubble { max-width: 85%; }
        .sc-composer { padding-bottom: calc(.625rem + env(safe-area-inset-bottom)); }
        .sc-toast { left: 1rem; right: 1rem; bottom: calc(1rem + env(safe-area-inset-bottom)); text-align: center; }
    }

    .sc-pane-details { display: none; }
    @media (min-width: 1280px) {
        .sc-pane-details { display: flex; }
    }
    @media (max-width: 1279.98px) {
        .sc-pane-details.sc-open {
            display: flex;
            position: fixed;
            left: .5rem; right: .5rem; bottom: .5rem;
            height: auto; min-height: 0; max-height: 72dvh;
            z-index: 55;
            background: rgb(248 250 252);
            border: 1px solid rgb(226 232 240);
            border-radius: 20px;
            padding: .75rem;
            box-shadow: 0 -12px 40px rgba(15,23,42,.18);
            animation: scSheetUp .26s cubic-bezier(.2,.9,.3,1);
        }
        .sc-pane-details.sc-open .sc-sheet-bar { display: flex; }
    }
    .sc-sheet-bar { display: none; position: relative; align-items: center; gap: .5rem; margin-bottom: .75rem; }
    .sc-sheet-handle { position: absolute; top: -2px; left: 50%; transform: translateX(-50%); width: 40px; height: 4px; border-radius: 9999px; background: rgb(203 213 225); }
    .sc-sheet-title { flex: 1; text-align: center; font-size: 13px; font-weight: 700; color: rgb(51 65 85); }
    .sc-sheet-close { width: 28px; height: 28px; border-radius: 9999px; border: none; background: rgb(226 232 240); color: rgb(71 85 105); font-size: 16px; line-height: 1; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; }
</style>

<div class="mx-auto max-w-[1600px] px-1">
    <div class="mb-4 flex flex-col gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:flex-row md:items-center md:justify-between">
        <div>
            <p class="hidden text-xs font-semibold uppercase tracking-[0.2em] text-violet-600 sm:block">Omnichannel Inbox</p>
            <h1 class="text-xl font-bold text-slate-900 sm:text-2xl">Social Chat</h1>
        </div>
        <div class="flex items-center gap-2">
            @if (auth()->user()->isAdmin())
                <form id="sync-form" action="{{ route('social-chat.sync') }}" method="POST">
                    @csrf
                    <button type="submit" id="sync-btn" class="rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm font-semibold text-emerald-700 hover:bg-emerald-100">
                        Sync Conversations
                    </button>
                </form>
            @endif
        </div>
    </div>

    <div id="sc-app" class="grid gap-4 lg:grid-cols-[340px_minmax(0,1fr)] xl:grid-cols-[340px_minmax(0,1fr)_300px]">
        {{-- Pane 1: conversation list --}}
        <aside class="sc-pane sc-pane-list flex flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 p-3">
                <div class="relative mb-2.5">
                    <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3" stroke-linecap="round"/></svg>
                    <input id="sc-search" type="text" placeholder="Search conversations..." class="w-full rounded-xl border border-slate-300 py-2 pl-9 pr-3 text-sm text-slate-700 placeholder-slate-400 focus:border-violet-500 focus:outline-none focus:ring-2 focus:ring-violet-200">
                </div>
                <div class="mb-2.5 flex flex-wrap gap-1.5">
                    <button type="button" class="sc-tab is-active" data-tab="all">All<span class="sc-count" id="sc-count-all">{{ $counts['all'] }}</span></button>
                    <button type="button" class="sc-tab" data-tab="priority">Priority<span class="sc-count" id="sc-count-priority">{{ $counts['priority'] }}</span></button>
                    <button type="button" class="sc-tab" data-tab="follow_up">Follow Up<span class="sc-count" id="sc-count-follow_up">{{ $counts['follow_up'] }}</span></button>
                </div>
                <div class="flex flex-wrap gap-1.5">
                    <button type="button" class="sc-chip is-active" data-platform="all">All Platforms</button>
                    <button type="button" class="sc-chip" data-platform="facebook">Facebook</button>
                    <button type="button" class="sc-chip" data-platform="whatsapp">WhatsApp</button>
                </div>
            </div>
            <div id="sc-list" class="sc-scroll flex-1 p-2"></div>
        </aside>

        {{-- Pane 2: chat --}}
        <section class="sc-pane sc-pane-chat flex flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div id="sc-chat-header" class="hidden border-b border-slate-200 bg-white px-4 py-3">
                <div class="flex items-center gap-3">
                    <button type="button" id="sc-back" class="sc-icon-btn -ml-1 lg:hidden" aria-label="Back to conversations" title="Back">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 18l-6-6 6-6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </button>
                    <div id="sc-hd-avatar" class="relative shrink-0"></div>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2">
                            <h2 id="sc-hd-name" class="truncate text-base font-semibold text-slate-900"></h2>
                            <a id="sc-hd-profile" href="#" target="_blank" rel="noopener" class="hidden shrink-0 items-center gap-1 rounded-full bg-blue-50 px-2 py-0.5 text-[11px] font-semibold text-blue-700 hover:bg-blue-100">
                                <svg class="h-3 w-3" viewBox="0 0 24 24" fill="currentColor"><path d="M24 12.07C24 5.4 18.63 0 12 0S0 5.4 0 12.07C0 18.1 4.39 23.09 10.13 24v-8.44H7.08v-3.49h3.05V9.41c0-3.02 1.79-4.7 4.53-4.7 1.31 0 2.68.24 2.68.24v2.97h-1.51c-1.49 0-1.96.93-1.96 1.89v2.26h3.33l-.53 3.49h-2.8V24C19.61 23.09 24 18.1 24 12.07z"/></svg>
                                Profile
                            </a>
                        </div>
                        <p id="sc-hd-sub" class="truncate text-xs text-slate-500"></p>
                    </div>
                    <div class="flex shrink-0 items-center gap-1.5">
                        <button type="button" id="sc-hd-star" class="sc-flag-btn" title="Toggle priority">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l2.9 6.6 7.1.7-5.4 4.8 1.6 7-6.2-3.7-6.2 3.7 1.6-7L2 9.3l7.1-.7z"/></svg>
                        </button>
                        <button type="button" id="sc-hd-flag" class="sc-flag-btn" title="Toggle follow up">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor"><path d="M4 2h16v13l-4-3-4 3V2z" transform="translate(0 1)"/></svg>
                        </button>
                        <button type="button" id="sc-hd-info" class="sc-icon-btn xl:hidden" title="Conversation details">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 16v-4M12 8h.01" stroke-linecap="round"/></svg>
                        </button>
                        <span id="sc-hd-assign-wrap" class="hidden md:inline-block"></span>
                    </div>
                </div>
            </div>

            <div id="sc-empty" class="sc-empty flex-1">
                <svg class="h-14 w-14 text-slate-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z" stroke-linecap="round" stroke-linejoin="round"/></svg>
                <p class="text-sm font-medium">Select a conversation to start chatting</p>
            </div>

            <div id="sc-messages" class="sc-scroll hidden flex-1 space-y-3 bg-slate-50 p-4"></div>

            <div id="sc-composer-wrap" class="hidden bg-white">
                <div id="sc-preview-chip" class="sc-preview-chip">
                    <img id="sc-preview-img" src="" alt="" class="hidden">
                    <span id="sc-preview-icon" class="hidden text-slate-400"></span>
                    <span id="sc-preview-name" class="sc-preview-name"></span>
                    <button type="button" id="sc-preview-remove" class="sc-preview-remove" aria-label="Remove attachment">&times;</button>
                </div>
                <form id="sc-composer" class="sc-composer" enctype="multipart/form-data">
                    <label class="sc-icon-btn" for="sc-file" title="Attach file">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21.44 11.05l-8.49 8.49a5.5 5.5 0 0 1-7.78-7.78l9.9-9.9a4 4 0 0 1 5.66 5.66l-9.9 9.9a2.5 2.5 0 0 1-3.54-3.54l8.49-8.49" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </label>
                    <input id="sc-file" type="file" class="sc-file-hidden" accept="image/*,video/*,audio/*,.pdf,.doc,.docx,.xls,.xlsx,.csv,.txt,.ppt,.pptx,.zip">
                    <textarea id="sc-input" rows="1" class="sc-textarea" placeholder="Type a message... (Enter to send)"></textarea>
                    <button type="submit" id="sc-send" class="sc-send-btn">Send</button>
                </form>
            </div>
        </section>

        {{-- Pane 3: details --}}
        <aside id="sc-details" class="sc-pane sc-scroll sc-pane-details flex-col gap-4 overflow-y-auto">
            <div class="sc-sheet-bar">
                <span class="sc-sheet-handle"></span>
                <span class="sc-sheet-title">Details</span>
                <button type="button" id="sc-sheet-close" class="sc-sheet-close" aria-label="Close details">&times;</button>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="mb-3 text-xs font-semibold uppercase tracking-wider text-slate-400">Customer</p>
                <div class="mb-3 flex items-center gap-3">
                    <div id="sc-dt-avatar"></div>
                    <div class="min-w-0">
                        <p id="sc-dt-name" class="truncate text-sm font-semibold text-slate-900"></p>
                        <p id="sc-dt-platform" class="text-xs text-slate-500"></p>
                    </div>
                </div>
                <a id="sc-dt-profile" href="#" target="_blank" rel="noopener" class="mb-3 hidden w-full rounded-xl bg-blue-600 px-3 py-2 text-center text-sm font-semibold text-white hover:bg-blue-500">
                    Open Facebook Profile
                </a>
                <div class="rounded-xl bg-slate-50 p-3">
                    <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">Sender ID</p>
                    <p id="sc-dt-sender" class="break-all font-mono text-xs text-slate-600"></p>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="mb-3 text-xs font-semibold uppercase tracking-wider text-slate-400">Conversation</p>
                <div class="space-y-3">
                    <div>
                        <label class="mb-1 block text-xs font-medium text-slate-500">Status</label>
                        <select id="sc-dt-status" class="sc-select w-full"></select>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-slate-500">Assignee</label>
                        <select id="sc-dt-assign" class="sc-select w-full"></select>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-slate-500">Priority</label>
                        <select id="sc-dt-priority" class="sc-select w-full">
                            <option value="none">None</option>
                            <option value="high">High</option>
                            <option value="medium">Medium</option>
                            <option value="low">Low</option>
                        </select>
                    </div>
                    <div class="flex items-center justify-between rounded-xl bg-slate-50 px-3 py-2.5">
                        <span class="text-xs font-medium text-slate-600">Follow up</span>
                        <button type="button" id="sc-dt-follow" class="sc-toggle" aria-label="Toggle follow up"></button>
                    </div>
                </div>
            </div>
        </aside>
    </div>
</div>

<div id="sc-sheet-backdrop" class="fixed inset-0 z-50 hidden bg-slate-900/40"></div>
<div id="sc-toast" class="sc-toast"></div>

<script>
(function () {
    'use strict';

    const BOOT = {
        conversations: @json($initialConversations),
        members: @json($membersData),
        activeConversation: @json($initialConversation),
        messages: @json($initialMessages),
        isAdmin: @json(auth()->user()->isAdmin()),
        counts: @json($counts),
    };

    const ROUTES = {
        index: @json(route('social-chat.index')),
        show: @json(route('social-chat.show', ['conversation' => '__ID__'])),
        reply: @json(route('social-chat.reply', ['conversation' => '__ID__'])),
        flags: @json(route('social-chat.flags', ['conversation' => '__ID__'])),
        assign: @json(route('social-chat.assign', ['conversation' => '__ID__'])),
    };

    const CSRF = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    const state = {
        conversations: BOOT.conversations.slice(),
        active: BOOT.activeConversation,
        messages: BOOT.messages.slice(),
        lastRenderedId: 0,
        filterTab: 'all',
        filterPlatform: 'all',
        search: '',
        loading: false,
        sending: false,
    };

    const els = {
        app: document.getElementById('sc-app'),
        list: document.getElementById('sc-list'),
        search: document.getElementById('sc-search'),
        chatHeader: document.getElementById('sc-chat-header'),
        empty: document.getElementById('sc-empty'),
        messages: document.getElementById('sc-messages'),
        composerWrap: document.getElementById('sc-composer-wrap'),
        composer: document.getElementById('sc-composer'),
        input: document.getElementById('sc-input'),
        send: document.getElementById('sc-send'),
        file: document.getElementById('sc-file'),
        previewChip: document.getElementById('sc-preview-chip'),
        previewImg: document.getElementById('sc-preview-img'),
        previewIcon: document.getElementById('sc-preview-icon'),
        previewName: document.getElementById('sc-preview-name'),
        previewRemove: document.getElementById('sc-preview-remove'),
        hdAvatar: document.getElementById('sc-hd-avatar'),
        hdName: document.getElementById('sc-hd-name'),
        hdSub: document.getElementById('sc-hd-sub'),
        hdProfile: document.getElementById('sc-hd-profile'),
        hdStar: document.getElementById('sc-hd-star'),
        hdFlag: document.getElementById('sc-hd-flag'),
        hdAssignWrap: document.getElementById('sc-hd-assign-wrap'),
        back: document.getElementById('sc-back'),
        info: document.getElementById('sc-hd-info'),
        details: document.getElementById('sc-details'),
        sheetBackdrop: document.getElementById('sc-sheet-backdrop'),
        sheetClose: document.getElementById('sc-sheet-close'),
        dtAvatar: document.getElementById('sc-dt-avatar'),
        dtName: document.getElementById('sc-dt-name'),
        dtPlatform: document.getElementById('sc-dt-platform'),
        dtProfile: document.getElementById('sc-dt-profile'),
        dtSender: document.getElementById('sc-dt-sender'),
        dtStatus: document.getElementById('sc-dt-status'),
        dtAssign: document.getElementById('sc-dt-assign'),
        dtPriority: document.getElementById('sc-dt-priority'),
        dtFollow: document.getElementById('sc-dt-follow'),
        toast: document.getElementById('sc-toast'),
        syncForm: document.getElementById('sync-form'),
        syncBtn: document.getElementById('sync-btn'),
    };

    function esc(value) {
        return String(value ?? '').replace(/[&<>"']/g, function (ch) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[ch];
        });
    }

    function url(tpl, id) { return tpl.replace('__ID__', id); }

    function parseDate(value) {
        if (!value) return null;
        const d = new Date(value);
        return isNaN(d.getTime()) ? null : d;
    }

    function timeAgo(value) {
        const d = parseDate(value);
        if (!d) return '';
        const diff = Date.now() - d.getTime();
        const min = Math.floor(diff / 60000);
        if (min < 1) return 'now';
        if (min < 60) return min + 'm';
        const hr = Math.floor(min / 60);
        if (hr < 24) return hr + 'h';
        const day = Math.floor(hr / 24);
        if (day < 7) return day + 'd';
        return d.toLocaleDateString(undefined, { month: 'short', day: 'numeric' });
    }

    function fmtTime(value) {
        const d = parseDate(value);
        if (!d) return '';
        return d.toLocaleTimeString(undefined, { hour: 'numeric', minute: '2-digit' });
    }

    function dayLabel(value) {
        const d = parseDate(value);
        if (!d) return '';
        const today = new Date();
        const startOf = function (x) { return new Date(x.getFullYear(), x.getMonth(), x.getDate()).getTime(); };
        const diffDays = Math.round((startOf(today) - startOf(d)) / 86400000);
        if (diffDays === 0) return 'Today';
        if (diffDays === 1) return 'Yesterday';
        return d.toLocaleDateString(undefined, { weekday: 'short', month: 'short', day: 'numeric', year: diffDays > 300 ? 'numeric' : undefined });
    }

    const PLATFORM_GRADIENT = {
        facebook: 'from-blue-500 to-blue-700',
        whatsapp: 'from-emerald-400 to-emerald-600',
    };

    const STATUS_STYLES = {
        open: 'bg-amber-100 text-amber-700',
        assigned: 'bg-violet-100 text-violet-700',
        closed: 'bg-slate-200 text-slate-600',
    };

    function avatarHtml(conv, size) {
        const name = conv.sender_name || conv.sender_id || '?';
        const initial = String(name).trim().charAt(0).toUpperCase() || '?';
        const grad = PLATFORM_GRADIENT[conv.platform] || 'from-slate-400 to-slate-600';
        const unreadDot = conv.unread
            ? '<span class="absolute -right-0.5 -top-0.5 h-3 w-3 rounded-full bg-violet-600 ring-2 ring-white"></span>'
            : '';
        return '<div class="relative">' +
            '<div class="flex ' + size + ' items-center justify-center rounded-full bg-gradient-to-br ' + grad + ' font-bold text-white">' + esc(initial) + '</div>' +
            unreadDot +
            '</div>';
    }

    let toastTimer = null;
    function toast(message, type) {
        els.toast.textContent = message;
        els.toast.className = 'sc-toast is-visible is-' + (type || 'error');
        clearTimeout(toastTimer);
        toastTimer = setTimeout(function () {
            els.toast.classList.remove('is-visible');
        }, 4000);
    }

    function syncConversation(updated) {
        if (!updated) return;
        const idx = state.conversations.findIndex(function (c) { return c.id === updated.id; });
        if (idx >= 0) {
            state.conversations[idx] = Object.assign({}, state.conversations[idx], updated);
        }
        if (state.active && state.active.id === updated.id) {
            state.active = Object.assign({}, state.active, updated);
            renderChatHeader();
            renderDetails();
        }
    }

    // ---------- Conversation list ----------
    function filteredConversations() {
        const q = state.search.trim().toLowerCase();
        return state.conversations
            .filter(function (c) {
                if (state.filterTab === 'priority' && (!c.priority || c.priority === 'none')) return false;
                if (state.filterTab === 'follow_up' && !c.follow_up) return false;
                if (state.filterPlatform !== 'all' && c.platform !== state.filterPlatform) return false;
                if (q) {
                    const name = (c.sender_name || '').toLowerCase();
                    const sid = String(c.sender_id || '').toLowerCase();
                    if (name.indexOf(q) === -1 && sid.indexOf(q) === -1) return false;
                }
                return true;
            })
            .sort(function (a, b) {
                return new Date(b.last_message_at || 0) - new Date(a.last_message_at || 0);
            });
    }

    function conversationItemHtml(conv) {
        const isActive = state.active && state.active.id === conv.id;
        const statusStyle = STATUS_STYLES[conv.status] || STATUS_STYLES.open;
        const stars = (conv.priority && conv.priority !== 'none')
            ? '<svg class="h-3 w-3 shrink-0 text-amber-500" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l2.9 6.6 7.1.7-5.4 4.8 1.6 7-6.2-3.7-6.2 3.7 1.6-7L2 9.3l7.1-.7z"/></svg>'
            : '';
        const flag = conv.follow_up
            ? '<svg class="h-3 w-3 shrink-0 text-rose-500" viewBox="0 0 24 24" fill="currentColor"><path d="M4 3h16v13l-4-3-4 3V3z"/></svg>'
            : '';
        return '<button type="button" class="sc-conv-item ' + (isActive ? 'is-active' : '') + '" data-id="' + conv.id + '">' +
            '<div class="flex items-start gap-2.5">' +
                avatarHtml(conv, 'h-10 w-10 text-sm') +
                '<div class="min-w-0 flex-1">' +
                    '<div class="flex items-center justify-between gap-2">' +
                        '<p class="truncate text-sm font-semibold ' + (conv.unread ? 'text-slate-900' : 'text-slate-700') + '">' + esc(conv.sender_name || conv.sender_id) + '</p>' +
                        '<span class="shrink-0 text-[10px] text-slate-400">' + esc(timeAgo(conv.last_message_at)) + '</span>' +
                    '</div>' +
                    '<div class="mt-0.5 flex items-center gap-1">' + stars + flag +
                        '<p class="truncate text-xs text-slate-500">' + esc(conv.preview || 'No messages yet') + '</p>' +
                    '</div>' +
                '</div>' +
            '</div>' +
            '<div class="mt-2 flex items-center gap-1.5 pl-12">' +
                '<span class="rounded-full px-2 py-0.5 text-[10px] font-semibold ' + statusStyle + '">' + esc(conv.status) + '</span>' +
                '<span class="rounded-full px-2 py-0.5 text-[10px] font-semibold ' + (conv.platform === 'facebook' ? 'bg-blue-100 text-blue-700' : 'bg-emerald-100 text-emerald-700') + '">' + esc(conv.platform) + '</span>' +
                '<span class="ml-auto truncate text-[10px] text-slate-400">' + esc(conv.assignee_name || 'Unassigned') + '</span>' +
            '</div>' +
        '</button>';
    }

    function renderList() {
        const items = filteredConversations();
        if (!items.length) {
            els.list.innerHTML = '<div class="rounded-xl border border-dashed border-slate-300 bg-slate-50 p-8 text-center text-sm text-slate-500">No conversations found.</div>';
            return;
        }
        els.list.innerHTML = items.map(conversationItemHtml).join('');
    }

    // ---------- Chat header / details ----------
    function assigneeSelectHtml(id, current) {
        let html = '<select id="' + id + '" class="sc-select max-w-[140px]">';
        html += '<option value="">Unassigned</option>';
        BOOT.members.forEach(function (m) {
            html += '<option value="' + m.id + '"' + (String(current) === String(m.id) ? ' selected' : '') + '>' + esc(m.name) + '</option>';
        });
        html += '</select>';
        return html;
    }

    function renderChatHeader() {
        const conv = state.active;
        if (!conv) {
            els.chatHeader.classList.add('hidden');
            els.hdAssignWrap.innerHTML = '';
            return;
        }
        els.chatHeader.classList.remove('hidden');
        els.hdAvatar.innerHTML = avatarHtml(conv, 'h-10 w-10 text-sm');
        els.hdName.textContent = conv.sender_name || conv.sender_id;
        els.hdSub.textContent = conv.platform + ' · ' + (conv.assignee_name || 'Unassigned') + ' · ' + conv.status;
        if (conv.profile_url) {
            els.hdProfile.href = conv.profile_url;
            els.hdProfile.classList.remove('hidden');
            els.hdProfile.classList.add('inline-flex');
        } else {
            els.hdProfile.classList.add('hidden');
            els.hdProfile.classList.remove('inline-flex');
        }
        els.hdStar.className = 'sc-flag-btn' + (conv.priority && conv.priority !== 'none' ? ' is-on-priority' : '');
        els.hdFlag.className = 'sc-flag-btn' + (conv.follow_up ? ' is-on-follow' : '');
        if (BOOT.isAdmin) {
            els.hdAssignWrap.innerHTML = assigneeSelectHtml('sc-hd-assign', conv.assigned_to);
            document.getElementById('sc-hd-assign').addEventListener('change', function () {
                assignConversation(conv.id, { assigned_to: this.value || null });
            });
        } else {
            els.hdAssignWrap.innerHTML = '<span class="rounded-full bg-violet-100 px-2.5 py-1 text-xs font-semibold text-violet-700">' + esc(conv.assignee_name || 'Unassigned') + '</span>';
        }
    }

    function renderDetails() {
        const conv = state.active;
        if (!conv) {
            ['dtAvatar', 'dtName', 'dtPlatform', 'dtSender'].forEach(function (k) { els[k].textContent = ''; });
            els.dtProfile.classList.add('hidden');
            return;
        }
        els.dtAvatar.innerHTML = avatarHtml(conv, 'h-14 w-14 text-lg');
        els.dtName.textContent = conv.sender_name || 'Unknown';
        els.dtPlatform.textContent = conv.platform === 'facebook' ? 'Facebook Messenger' : 'WhatsApp Business';
        els.dtSender.textContent = conv.sender_id;
        if (conv.profile_url) {
            els.dtProfile.href = conv.profile_url;
            els.dtProfile.classList.remove('hidden');
        } else {
            els.dtProfile.classList.add('hidden');
        }
        els.dtStatus.innerHTML = ['open', 'assigned', 'closed'].map(function (s) {
            return '<option value="' + s + '"' + (conv.status === s ? ' selected' : '') + '>' + s.charAt(0).toUpperCase() + s.slice(1) + '</option>';
        }).join('');
        els.dtAssign.innerHTML = '<option value="">Unassigned</option>' + BOOT.members.map(function (m) {
            return '<option value="' + m.id + '"' + (String(conv.assigned_to) === String(m.id) ? ' selected' : '') + '>' + esc(m.name) + '</option>';
        }).join('');
        els.dtPriority.value = conv.priority || 'none';
        els.dtFollow.classList.toggle('is-on', !!conv.follow_up);
        const canEdit = BOOT.isAdmin || (conv.assigned_to != null);
        els.dtStatus.disabled = !BOOT.isAdmin;
        els.dtAssign.disabled = !BOOT.isAdmin;
        els.dtPriority.disabled = !canEdit;
        els.dtFollow.disabled = !canEdit;
    }

    // ---------- Messages ----------
    function attachmentHtml(msg) {
        const u = msg.attachment_url;
        if (!u) return '';
        let type = msg.attachment_type;
        if (!type) {
            if (/\.(jpg|jpeg|png|gif|webp|bmp|svg)(\?|$)/i.test(u)) type = 'image';
            else if (/\.(mp4|mov|avi|mkv|webm)(\?|$)/i.test(u)) type = 'video';
            else if (/\.(mp3|wav|m4a|aac)(\?|$)/i.test(u)) type = 'audio';
            else type = 'document';
        }
        if (type === 'image') {
            return '<a href="' + esc(u) + '" target="_blank" rel="noopener"><img src="' + esc(u) + '" alt="Attachment" class="mb-1.5"></a>';
        }
        if (type === 'video') {
            return '<video controls src="' + esc(u) + '" class="mb-1.5"></video>';
        }
        if (type === 'audio') {
            return '<audio controls src="' + esc(u) + '" class="mb-1.5"></audio>';
        }
        return '<a href="' + esc(u) + '" target="_blank" rel="noopener" class="mb-1.5 flex items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-semibold text-violet-700 hover:bg-slate-100">' +
            '<svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" stroke-linecap="round" stroke-linejoin="round"/><path d="M14 2v6h6"/></svg>' +
            'Open Document</a>';
    }

    function messageHtml(msg) {
        const isCustomer = msg.sender_type === 'customer';
        const bubbleCls = isCustomer
            ? 'bg-white text-slate-700 ring-1 ring-slate-200'
            : 'bg-gradient-to-br from-violet-600 to-violet-700 text-white';
        const timeCls = isCustomer ? 'text-slate-400' : 'text-violet-200';
        let agentLabel = '';
        if (!isCustomer && msg.agent_name) {
            agentLabel = '<p class="mb-0.5 text-[10px] font-semibold uppercase tracking-wide ' + timeCls + '">' + esc(msg.agent_name) + '</p>';
        }
        const text = msg.message_text
            ? '<p class="text-sm leading-6">' + esc(msg.message_text) + '</p>'
            : '';
        const time = '<p class="mt-1 text-right text-[10px] ' + timeCls + '">' + esc(fmtTime(msg.created_at)) + '</p>';
        return '<div class="flex ' + (isCustomer ? 'justify-start' : 'justify-end') + '">' +
            '<div class="sc-bubble rounded-2xl px-3.5 py-2.5 ' + bubbleCls + '">' +
                agentLabel + attachmentHtml(msg) + text + time +
            '</div>' +
        '</div>';
    }

    function scrollToBottom(force) {
        const nearBottom = els.messages.scrollHeight - els.messages.scrollTop - els.messages.clientHeight < 120;
        if (force || nearBottom) {
            els.messages.scrollTop = els.messages.scrollHeight;
        }
    }

    function renderMessages() {
        els.messages.innerHTML = '';
        state.lastRenderedId = 0;
        let lastDay = null;
        const frag = [];
        state.messages.forEach(function (msg) {
            const label = dayLabel(msg.created_at);
            if (label !== lastDay) {
                frag.push('<div class="sc-day-divider"><span>' + esc(label) + '</span></div>');
                lastDay = label;
            }
            frag.push(messageHtml(msg));
            if (msg.id > state.lastRenderedId) state.lastRenderedId = msg.id;
        });
        els.messages.innerHTML = frag.join('');
        scrollToBottom(true);
    }

    function appendMessages(newMessages) {
        let lastDay = state.messages.length ? dayLabel(state.messages[state.messages.length - 1].created_at) : null;
        newMessages.forEach(function (msg) {
            if (msg.id <= state.lastRenderedId) return;
            const label = dayLabel(msg.created_at);
            if (label !== lastDay) {
                const div = document.createElement('div');
                div.className = 'sc-day-divider';
                div.innerHTML = '<span>' + esc(label) + '</span>';
                els.messages.appendChild(div);
                lastDay = label;
            }
            els.messages.insertAdjacentHTML('beforeend', messageHtml(msg));
            state.messages.push(msg);
            if (msg.id > state.lastRenderedId) state.lastRenderedId = msg.id;
        });
        scrollToBottom(false);
    }

    function renderChat() {
        if (!state.active) {
            els.empty.classList.remove('hidden');
            els.empty.classList.add('flex');
            els.messages.classList.add('hidden');
            els.composerWrap.classList.add('hidden');
            return;
        }
        els.empty.classList.add('hidden');
        els.empty.classList.remove('flex');
        els.messages.classList.remove('hidden');
        els.composerWrap.classList.remove('hidden');
        renderMessages();
    }

    // ---------- Data loading ----------
    function loadConversation(id, pushState) {
        if (state.loading) return;
        state.loading = true;
        fetch(url(ROUTES.show, id), {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
        })
            .then(function (res) {
                if (!res.ok) throw new Error('Failed to load conversation (' + res.status + ')');
                return res.json();
            })
            .then(function (data) {
                state.active = data.conversation;
                state.messages = data.messages.slice();
                const idx = state.conversations.findIndex(function (c) { return c.id === data.conversation.id; });
                if (idx >= 0) state.conversations[idx] = Object.assign({}, state.conversations[idx], data.conversation, { unread: false });
                if (pushState !== false) {
                    const qs = '?conversation=' + data.conversation.id;
                    history.pushState({ id: data.conversation.id }, '', ROUTES.index + qs);
                }
                renderChatHeader();
                renderDetails();
                renderChat();
                renderList();
                els.app.classList.add('sc-chat-open');
                closeSheet();
                clearAttachment();
                els.input.value = '';
                els.input.style.height = 'auto';
                if (window.innerWidth < 1024) {
                    requestAnimationFrame(function () { els.input.focus(); });
                }
            })
            .catch(function (err) {
                toast(err.message || 'Failed to load conversation');
            })
            .finally(function () {
                state.loading = false;
            });
    }

    function assignConversation(id, payload) {
        const body = new FormData();
        Object.keys(payload).forEach(function (k) { body.append(k, payload[k] === null ? '' : payload[k]); });
        fetch(url(ROUTES.assign, id), {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF, 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            body: body,
            credentials: 'same-origin',
        })
            .then(function (res) { return res.json().then(function (data) { return { ok: res.ok, data: data }; }); })
            .then(function (result) {
                if (!result.ok) throw new Error(result.data.message || 'Update failed');
                syncConversation({
                    id: id,
                    assigned_to: result.data.assigned_to,
                    assignee_name: result.data.assignee_name,
                    status: result.data.status,
                });
                renderChatHeader();
                renderDetails();
                renderList();
                toast('Conversation updated', 'success');
            })
            .catch(function (err) { toast(err.message || 'Update failed'); });
    }

    function updateFlags(id, payload) {
        fetch(url(ROUTES.flags, id), {
            method: 'PATCH',
            headers: {
                'X-CSRF-TOKEN': CSRF,
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(payload),
            credentials: 'same-origin',
        })
            .then(function (res) { return res.json().then(function (data) { return { ok: res.ok, data: data }; }); })
            .then(function (result) {
                if (!result.ok) throw new Error(result.data.message || 'Update failed');
                syncConversation({ id: id, priority: result.data.priority, follow_up: result.data.follow_up });
                updateCounts();
                renderChatHeader();
                renderDetails();
                renderList();
            })
            .catch(function (err) { toast(err.message || 'Update failed'); });
    }

    function updateCounts() {
        const priority = state.conversations.filter(function (c) { return c.priority && c.priority !== 'none'; }).length;
        const follow = state.conversations.filter(function (c) { return c.follow_up; }).length;
        document.getElementById('sc-count-all').textContent = state.conversations.length;
        document.getElementById('sc-count-priority').textContent = priority;
        document.getElementById('sc-count-follow_up').textContent = follow;
    }

    // ---------- Composer ----------
    function clearAttachment() {
        els.file.value = '';
        els.previewChip.classList.remove('is-visible');
        els.previewImg.src = '';
        els.previewImg.classList.add('hidden');
        els.previewIcon.classList.add('hidden');
        els.previewName.textContent = '';
    }

    els.file.addEventListener('change', function () {
        const file = this.files && this.files[0];
        if (!file) { clearAttachment(); return; }
        els.previewName.textContent = file.name;
        if (file.type.indexOf('image/') === 0) {
            const reader = new FileReader();
            reader.onload = function (e) {
                els.previewImg.src = e.target.result;
                els.previewImg.classList.remove('hidden');
                els.previewIcon.classList.add('hidden');
            };
            reader.readAsDataURL(file);
        } else {
            els.previewImg.classList.add('hidden');
            els.previewIcon.classList.remove('hidden');
            els.previewIcon.innerHTML = '<svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" stroke-linecap="round" stroke-linejoin="round"/><path d="M14 2v6h6"/></svg>';
        }
        els.previewChip.classList.add('is-visible');
    });

    els.previewRemove.addEventListener('click', clearAttachment);

    els.input.addEventListener('input', function () {
        this.style.height = 'auto';
        this.style.height = Math.min(this.scrollHeight, 120) + 'px';
    });

    els.input.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            els.composer.requestSubmit();
        }
    });

    els.composer.addEventListener('submit', function (e) {
        e.preventDefault();
        if (!state.active || state.sending) return;
        const text = els.input.value.trim();
        const file = els.file.files && els.file.files[0];
        if (!text && !file) return;

        state.sending = true;
        els.send.disabled = true;
        els.send.textContent = 'Sending...';

        const formData = new FormData();
        formData.append('message', text);
        if (file) formData.append('attachment', file);

        fetch(url(ROUTES.reply, state.active.id), {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF, 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            body: formData,
            credentials: 'same-origin',
        })
            .then(function (res) { return res.json().then(function (data) { return { ok: res.ok, status: res.status, data: data }; }); })
            .then(function (result) {
                if (!result.ok) throw new Error(result.data.message || 'Failed to send message');
                if (result.data.stored) appendMessages([result.data.stored]);
                if (result.data.conversation) {
                    const conv = Object.assign({}, result.data.conversation, {
                        preview: text || '[Attachment]',
                        last_message_at: new Date().toISOString(),
                    });
                    const idx = state.conversations.findIndex(function (c) { return c.id === conv.id; });
                    if (idx >= 0) {
                        state.conversations.splice(idx, 1);
                        state.conversations.unshift(conv);
                    }
                    syncConversation(conv);
                }
                els.input.value = '';
                els.input.style.height = 'auto';
                clearAttachment();
                renderList();
            })
            .catch(function (err) {
                toast(err.message || 'Failed to send message');
            })
            .finally(function () {
                state.sending = false;
                els.send.disabled = false;
                els.send.textContent = 'Send';
            });
    });

    // ---------- Header flag buttons ----------
    els.hdStar.addEventListener('click', function () {
        if (!state.active) return;
        const next = (state.active.priority && state.active.priority !== 'none') ? 'none' : 'high';
        updateFlags(state.active.id, { priority: next });
    });

    els.hdFlag.addEventListener('click', function () {
        if (!state.active) return;
        updateFlags(state.active.id, { follow_up: !state.active.follow_up });
    });

    // ---------- Details panel events ----------
    els.dtStatus.addEventListener('change', function () {
        if (!state.active) return;
        assignConversation(state.active.id, { status: this.value });
    });

    els.dtAssign.addEventListener('change', function () {
        if (!state.active) return;
        assignConversation(state.active.id, { assigned_to: this.value || null });
    });

    els.dtPriority.addEventListener('change', function () {
        if (!state.active) return;
        updateFlags(state.active.id, { priority: this.value });
    });

    els.dtFollow.addEventListener('click', function () {
        if (!state.active) return;
        updateFlags(state.active.id, { follow_up: !state.active.follow_up });
    });

    // ---------- Mobile navigation / details sheet ----------
    function closeSheet() {
        els.details.classList.remove('sc-open');
        els.sheetBackdrop.classList.add('hidden');
    }

    els.back.addEventListener('click', function () {
        els.app.classList.remove('sc-chat-open');
        closeSheet();
        els.input.blur();
    });

    els.info.addEventListener('click', function () {
        if (!state.active) return;
        els.details.classList.add('sc-open');
        els.sheetBackdrop.classList.remove('hidden');
    });

    els.sheetBackdrop.addEventListener('click', closeSheet);
    els.sheetClose.addEventListener('click', closeSheet);

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') closeSheet();
    });

    window.addEventListener('resize', function () {
        if (window.innerWidth >= 1280) closeSheet();
    });

    // ---------- List events ----------
    els.list.addEventListener('click', function (e) {
        const btn = e.target.closest('[data-id]');
        if (!btn) return;
        loadConversation(parseInt(btn.dataset.id, 10));
    });

    els.search.addEventListener('input', function () {
        state.search = this.value;
        renderList();
    });

    document.querySelectorAll('[data-tab]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.querySelectorAll('[data-tab]').forEach(function (b) { b.classList.remove('is-active'); });
            btn.classList.add('is-active');
            state.filterTab = btn.dataset.tab;
            renderList();
        });
    });

    document.querySelectorAll('[data-platform]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.querySelectorAll('[data-platform]').forEach(function (b) { b.classList.remove('is-active'); });
            btn.classList.add('is-active');
            state.filterPlatform = btn.dataset.platform;
            renderList();
        });
    });

    // ---------- Sync button ----------
    if (els.syncForm) {
        els.syncForm.addEventListener('submit', function () {
            els.syncBtn.disabled = true;
            els.syncBtn.textContent = 'Syncing...';
        });
    }

    // ---------- Polling ----------
    function poll() {
        if (!state.active || document.hidden) return;
        fetch(url(ROUTES.show, state.active.id), {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
        })
            .then(function (res) { return res.ok ? res.json() : null; })
            .then(function (data) {
                if (!data) return;
                const fresh = data.messages.filter(function (m) { return m.id > state.lastRenderedId; });
                if (fresh.length) {
                    appendMessages(fresh);
                    const lastMsg = data.messages[data.messages.length - 1];
                    const idx = state.conversations.findIndex(function (c) { return c.id === data.conversation.id; });
                    if (idx >= 0 && lastMsg) {
                        state.conversations[idx].preview = lastMsg.message_text || (lastMsg.attachment_url ? '[Attachment]' : state.conversations[idx].preview);
                        state.conversations[idx].last_message_at = lastMsg.created_at;
                        state.conversations[idx].unread = false;
                    }
                    renderList();
                }
                syncConversation(data.conversation);
            })
            .catch(function () { /* silent */ });
    }
    setInterval(poll, 10000);

    // ---------- History ----------
    window.addEventListener('popstate', function () {
        const params = new URLSearchParams(window.location.search);
        const id = parseInt(params.get('conversation'), 10);
        if (id) loadConversation(id, false);
    });

    // ---------- Init ----------
    if (state.active) els.app.classList.add('sc-chat-open');
    renderList();
    renderChatHeader();
    renderDetails();
    renderChat();
})();
</script>
@endsection
