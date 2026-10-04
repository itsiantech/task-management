@extends('layouts.app')

@section('title', 'Conversation Details')

@section('content')
<style>
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
        margin: 0 0 0.5rem 0;
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
    <div class="mb-6 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-violet-600">Conversation</p>
                <h1 class="text-2xl font-bold text-slate-900">{{ $conversation->sender_name ?? $conversation->sender_id }}</h1>
            </div>

            <div class="flex items-center gap-2">
                <span class="rounded-full {{ $conversation->platform === 'facebook' ? 'bg-blue-100 text-blue-700' : 'bg-emerald-100 text-emerald-700' }} px-2.5 py-1 text-xs font-semibold">
                    {{ ucfirst($conversation->platform) }}
                </span>
                <span class="rounded-full bg-slate-200 px-2.5 py-1 text-xs font-semibold text-slate-700">{{ ucfirst($conversation->status) }}</span>
            </div>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-[300px_minmax(0,1fr)]">
        <aside class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <h2 class="mb-4 text-sm font-semibold uppercase tracking-[0.2em] text-slate-500">Assignment</h2>

            <form action="{{ route('social-chat.assign', $conversation) }}" method="POST" class="space-y-3">
                @csrf
                <select name="assigned_to" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-700 focus:border-violet-500 focus:outline-none focus:ring-2 focus:ring-violet-200">
                    <option value="">Unassigned</option>
                    @foreach ($members as $member)
                        <option value="{{ $member->id }}" {{ $conversation->assigned_to == $member->id ? 'selected' : '' }}>{{ $member->name }}</option>
                    @endforeach
                </select>

                <select name="status" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-700 focus:border-violet-500 focus:outline-none focus:ring-2 focus:ring-violet-200">
                    <option value="open" {{ $conversation->status === 'open' ? 'selected' : '' }}>Open</option>
                    <option value="assigned" {{ $conversation->status === 'assigned' ? 'selected' : '' }}>Assigned</option>
                    <option value="closed" {{ $conversation->status === 'closed' ? 'selected' : '' }}>Closed</option>
                </select>

                <button type="submit" class="w-full rounded-lg bg-violet-600 px-3 py-2.5 text-sm font-semibold text-white hover:bg-violet-500">Save Assignment</button>
            </form>
        </aside>

        <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 p-4">
                <h2 class="text-lg font-semibold text-slate-900">Conversation History</h2>
                <p class="text-sm text-slate-500">{{ $conversation->sender_id }}</p>
            </div>

            <div class="chat-message-list space-y-3 bg-slate-50 p-4" data-chat-box>
                @forelse ($conversation->messages as $message)
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
                                <p class="mt-2 text-xs underline {{ $message->sender_type === 'customer' ? 'text-violet-700' : 'text-violet-100' }}">
                                    <a href="{{ $message->attachment_url }}" target="_blank">Open attachment</a>
                                </p>
                            @endif
                            <p class="mt-2 text-[10px] {{ $message->sender_type === 'customer' ? 'text-slate-400' : 'text-violet-100' }}">{{ $message->created_at->format('M d, Y h:i A') }}</p>
                        </div>
                    </div>
                @empty
                    <div class="rounded-xl border border-dashed border-slate-300 bg-white p-8 text-center text-sm text-slate-500">No messages yet.</div>
                @endforelse
            </div>

            <div class="bg-white p-3">
                <div class="chat-previewer" data-chat-previewer>
                    <img src="" alt="Attachment preview" data-chat-preview-image />
                    <button type="button" class="chat-previewer-remove" data-chat-remove-preview aria-label="Remove attachment">×</button>
                </div>

                <form action="{{ route('social-chat.reply', $conversation) }}" method="POST" enctype="multipart/form-data" class="chat-composer">
                    @csrf
                    <label class="chat-attachment-button" for="chat-attachment-input" aria-label="Attach a file">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-5 w-5">
                            <path d="M21.44 11.05l-8.49 8.49a5.5 5.5 0 0 1-7.78-7.78l9.9-9.9a4 4 0 0 1 5.66 5.66l-9.9 9.9a2.5 2.5 0 0 1-3.54-3.54l8.49-8.49" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </label>
                    <input id="chat-attachment-input" type="file" name="attachment" class="chat-file-hidden" accept="image/*,.pdf,.doc,.docx,.csv,.xlsx,.txt" />
                    <textarea name="message" rows="1" class="chat-textarea" placeholder="Type your reply..." style="overflow:hidden; resize:none;"></textarea>
                    <button type="submit" class="chat-send-button">Send</button>
                </form>
            </div>
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
