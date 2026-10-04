@extends('layouts.app')

@section('title', 'Social Chat Inbox')

@section('content')
<style>
    .conversation-item {
        padding: 0.625rem 0.75rem;
        margin-bottom: 0.5rem;
    }

    .conversation-user {
        font-size: 14px;
        font-weight: 600;
        line-height: 1.25;
    }

    .conversation-preview {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        text-overflow: ellipsis;
        word-break: break-word;
        overflow-wrap: anywhere;
    }

    .chat-message-list {
        height: calc(100vh - 280px);
        min-height: 360px;
        overflow-y: auto;
        scroll-behavior: smooth;
    }

    .message-bubble {
        max-width: min(80%, 560px);
        word-break: break-word;
        overflow-wrap: anywhere;
        white-space: pre-wrap;
    }

    .message-bubble a,
    .message-bubble p,
    .message-bubble span {
        word-break: break-word;
        overflow-wrap: anywhere;
    }

    .chat-composer {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem 0.75rem;
        border-top: 1px solid rgb(226 232 240);
        background: #fff;
    }

    .chat-attachment-button {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 38px;
        height: 38px;
        border: 1px solid rgb(203 213 225);
        border-radius: 9999px;
        background: rgb(248 250 252);
        color: rgb(71 85 105);
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .chat-attachment-button:hover {
        background: rgb(241 245 249);
        border-color: rgb(148 163 184);
    }

    .chat-textarea {
        flex: 1;
        min-height: 40px;
        max-height: 110px;
        border-radius: 20px;
        border: 1px solid rgb(203 213 225);
        resize: none;
        overflow: hidden;
        padding: 0.625rem 0.875rem;
        font-size: 0.875rem;
        line-height: 1.5;
        color: rgb(15 23 42);
        background: rgb(255 255 255);
    }

    .chat-send-button {
        border: none;
        border-radius: 9999px;
        background: linear-gradient(135deg, #7c3aed, #6d28d9);
        color: white;
        font-weight: 600;
        padding: 0.625rem 1.1rem;
        min-width: 82px;
    }

    .chat-file-hidden {
        position: absolute;
        width: 1px;
        height: 1px;
        padding: 0;
        margin: -1px;
        overflow: hidden;
        clip: rect(0, 0, 0, 0);
        white-space: nowrap;
        border: 0;
    }

    .chat-previewer {
        display: none;
        margin: 0 0.75rem 0.5rem 0.75rem;
        position: relative;
        width: fit-content;
        max-width: 160px;
        border: 1px solid rgb(226 232 240);
        border-radius: 12px;
        background: rgb(255 255 255);
        box-shadow: 0 8px 20px rgba(15, 23, 42, 0.08);
    }

    .chat-previewer.is-visible {
        display: block;
    }

    .chat-previewer img {
        display: block;
        width: 100%;
        max-width: 150px;
        height: auto;
        border-radius: 10px 10px 0 0;
        object-fit: cover;
    }

    .chat-previewer-remove {
        position: absolute;
        top: 6px;
        right: 6px;
        width: 22px;
        height: 22px;
        border-radius: 9999px;
        border: none;
        background: rgba(15, 23, 42, 0.7);
        color: #fff;
        font-size: 14px;
        line-height: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
    }
</style>

<div class="mx-auto max-w-7xl">
    <div class="mb-6 flex flex-col gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:flex-row md:items-center md:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-violet-600">Omnichannel Inbox</p>
            <h1 class="text-2xl font-bold text-slate-900">Social Chat</h1>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('social-chat.index') }}" class="rounded-lg border border-violet-200 bg-violet-50 px-3 py-2 text-sm font-semibold text-violet-700">All</a>
            <a href="{{ route('social-chat.index', ['platform' => 'facebook']) }}" class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700">Facebook</a>
            <a href="{{ route('social-chat.index', ['platform' => 'whatsapp']) }}" class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700">WhatsApp</a>

            @if (auth()->user()->isAdmin())
                <form action="{{ route('social-chat.sync') }}" method="POST">
                    @csrf
                    <button type="submit" class="rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm font-semibold text-emerald-700 hover:bg-emerald-100">
                        Sync Conversations
                    </button>
                </form>
            @endif
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-[360px_minmax(0,1fr)]">
        <aside class="rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 p-4">
                <div class="grid gap-3 sm:grid-cols-2">
                    <select onchange="window.location='{{ route('social-chat.index') }}?platform=' + this.value" class="rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-700 focus:border-violet-500 focus:outline-none focus:ring-2 focus:ring-violet-200">
                        <option value="">All Platforms</option>
                        <option value="facebook" {{ request('platform') === 'facebook' ? 'selected' : '' }}>Facebook</option>
                        <option value="whatsapp" {{ request('platform') === 'whatsapp' ? 'selected' : '' }}>WhatsApp</option>
                    </select>

                    <select onchange="window.location='{{ route('social-chat.index') }}?status=' + this.value" class="rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-700 focus:border-violet-500 focus:outline-none focus:ring-2 focus:ring-violet-200">
                        <option value="">All Status</option>
                        <option value="open" {{ request('status') === 'open' ? 'selected' : '' }}>Open</option>
                        <option value="assigned" {{ request('status') === 'assigned' ? 'selected' : '' }}>Assigned</option>
                        <option value="closed" {{ request('status') === 'closed' ? 'selected' : '' }}>Closed</option>
                    </select>
                </div>
            </div>

            <div class="max-h-[720px] overflow-y-auto p-2">
                @forelse ($conversations as $conversation)
                    @php
                        $previewMessage = optional($conversation->messages->last())->message_text ?? 'No messages yet';
                    @endphp

                    <a href="{{ route('social-chat.show', $conversation) }}" class="conversation-item block rounded-xl border {{ $selectedConversation && $selectedConversation->id === $conversation->id ? 'border-violet-200 bg-violet-50' : 'border-slate-200 bg-slate-50 hover:bg-slate-100' }}">
                        <div class="flex items-start justify-between gap-2">
                            <div class="min-w-0 flex-1">
                                <p class="conversation-user truncate text-slate-900">{{ $conversation->sender_name ?? $conversation->sender_id }}</p>
                                <p class="conversation-preview mt-1 text-xs text-slate-500">{{ \Illuminate\Support\Str::limit($previewMessage, 60) }}</p>
                            </div>
                            <span class="rounded-full px-2 py-1 text-[10px] font-semibold {{ $conversation->platform === 'facebook' ? 'bg-blue-100 text-blue-700' : 'bg-emerald-100 text-emerald-700' }}">
                                {{ ucfirst($conversation->platform) }}
                            </span>
                        </div>

                        <div class="mt-2 flex items-center justify-between gap-2 text-[10px] text-slate-500">
                            <span class="rounded-full bg-slate-200 px-2 py-1 font-medium text-slate-700">{{ ucfirst($conversation->status) }}</span>
                            <span class="truncate">{{ $conversation->assignee?->name ?? 'Unassigned' }}</span>
                        </div>
                    </a>
                @empty
                    <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50 p-8 text-center text-sm text-slate-500">
                        No conversations found.
                    </div>
                @endforelse
            </div>
        </aside>

        <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
            @if ($selectedConversation)
                <div class="border-b border-slate-200 p-4">
                    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                        <div>
                            <h2 class="text-lg font-semibold text-slate-900">{{ $selectedConversation->sender_name ?? $selectedConversation->sender_id }}</h2>
                            <p class="text-sm text-slate-500">{{ $selectedConversation->platform }} • {{ $selectedConversation->sender_id }}</p>
                        </div>

                        <div class="flex flex-wrap items-center gap-2">
                            @if (auth()->user()->isAdmin())
                                <form action="{{ route('social-chat.assign', $selectedConversation) }}" method="POST" class="flex items-center gap-2">
                                    @csrf
                                    <select name="assigned_to" class="rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-700 focus:border-violet-500 focus:outline-none focus:ring-2 focus:ring-violet-200">
                                        <option value="">Unassigned</option>
                                        @foreach ($members as $member)
                                            <option value="{{ $member->id }}" {{ $selectedConversation->assigned_to == $member->id ? 'selected' : '' }}>{{ $member->name }}</option>
                                        @endforeach
                                    </select>
                                    <button type="submit" class="rounded-lg bg-violet-600 px-3 py-2 text-sm font-semibold text-white hover:bg-violet-500">Assign</button>
                                </form>
                            @else
                                <span class="rounded-full bg-violet-100 px-2.5 py-1 text-xs font-semibold text-violet-700">Assigned to {{ $selectedConversation->assignee?->name ?? 'Unassigned' }}</span>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="flex h-[620px] flex-col">
                    <div class="chat-message-list space-y-3 bg-slate-50 p-4" data-chat-box>
                        @forelse ($selectedConversation->messages as $message)
                            @php
                                $imageUrl = $message->attachment_url;
                                $isImageAttachment = $message->attachment_type === 'image' || preg_match('/\.(jpg|jpeg|png|gif|webp|bmp|svg)(\?.*)?$/i', (string) $imageUrl);
                            @endphp
                            <div class="flex {{ $message->sender_type === 'customer' ? 'justify-start' : 'justify-end' }}">
                                <div class="message-bubble rounded-2xl px-4 py-3 {{ $message->sender_type === 'customer' ? 'bg-white text-slate-700 ring-1 ring-slate-200' : 'bg-violet-600 text-white' }}">
                                    @if ($imageUrl && $isImageAttachment)
                                        <img src="{{ $imageUrl }}" alt="Attachment" class="mb-2 max-w-[250px] cursor-pointer rounded-xl border border-slate-200" style="max-width: 250px; border-radius: 12px;" />
                                    @endif

                                    @if ($message->message_text)
                                        <p class="text-sm leading-6">{{ $message->message_text }}</p>
                                    @endif

                                    @if ($message->attachment_url && ! $isImageAttachment)
                                        <div class="mt-2">
                                            <a href="{{ $message->attachment_url }}" target="_blank" class="text-xs underline {{ $message->sender_type === 'customer' ? 'text-violet-700' : 'text-violet-100' }}">Open attachment</a>
                                        </div>
                                    @endif
                                    <p class="mt-2 text-[10px] {{ $message->sender_type === 'customer' ? 'text-slate-400' : 'text-violet-100' }}">{{ $message->created_at->format('M d, Y h:i A') }}</p>
                                </div>
                            </div>
                        @empty
                            <div class="flex h-full items-center justify-center text-sm text-slate-500">No messages yet.</div>
                        @endforelse
                    </div>

                    <div class="bg-white p-3">
                        <div class="chat-previewer" data-chat-previewer>
                            <img src="" alt="Attachment preview" data-chat-preview-image />
                            <button type="button" class="chat-previewer-remove" data-chat-remove-preview aria-label="Remove attachment">×</button>
                        </div>

                        <form class="chat-composer" method="POST" action="{{ route('social-chat.reply', $selectedConversation) }}" enctype="multipart/form-data">
                            @csrf
                            <label class="chat-attachment-button" for="chat-attachment-input" aria-label="Attach a file">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-5 w-5">
                                    <path d="M21.44 11.05l-8.49 8.49a5.5 5.5 0 0 1-7.78-7.78l9.9-9.9a4 4 0 0 1 5.66 5.66l-9.9 9.9a2.5 2.5 0 0 1-3.54-3.54l8.49-8.49" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </label>
                            <input id="chat-attachment-input" type="file" name="attachment" class="chat-file-hidden" accept="image/*,.pdf,.doc,.docx,.csv,.xlsx,.txt" />
                            <textarea name="message" rows="1" class="chat-textarea" placeholder="Type a message..." style="overflow:hidden; resize:none;"></textarea>
                            <button type="submit" class="chat-send-button">Send</button>
                        </form>
                    </div>
                </div>
            @else
                <div class="flex h-[620px] items-center justify-center text-slate-500">Select a conversation to view details.</div>
            @endif
        </section>
    </div>
</div>

<script>
    function scrollChatToBottom(chatBox) {
        if (! chatBox) return;
        chatBox.scrollTop = chatBox.scrollHeight;
    }

    document.addEventListener('DOMContentLoaded', function () {
        const chatBoxes = document.querySelectorAll('[data-chat-box]');

        chatBoxes.forEach((chatBox) => {
            requestAnimationFrame(() => scrollChatToBottom(chatBox));
        });
    });

    window.addEventListener('load', function () {
        const chatBoxes = document.querySelectorAll('[data-chat-box]');
        chatBoxes.forEach((chatBox) => scrollChatToBottom(chatBox));
    });

    document.querySelectorAll('form.chat-composer').forEach((form) => {
        const fileInput = form.querySelector('input[type="file"]');
        const previewer = form.parentElement.querySelector('[data-chat-previewer]');
        const previewImage = previewer?.querySelector('[data-chat-preview-image]');
        const removeButton = previewer?.querySelector('[data-chat-remove-preview]');

        if (! fileInput || ! previewer || ! previewImage || ! removeButton) return;

        fileInput.addEventListener('change', function () {
            const file = this.files && this.files[0];
            if (! file) {
                previewer.classList.remove('is-visible');
                previewImage.src = '';
                return;
            }

            if (file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = function (event) {
                    previewImage.src = event.target.result;
                    previewer.classList.add('is-visible');
                };
                reader.readAsDataURL(file);
                return;
            }

            previewImage.src = '';
            previewer.classList.add('is-visible');
            previewer.querySelector('img').style.display = 'none';
        });

        removeButton.addEventListener('click', function () {
            fileInput.value = '';
            previewImage.src = '';
            previewer.classList.remove('is-visible');
            previewer.querySelector('img').style.display = 'block';
        });
    });
</script>
@endsection
