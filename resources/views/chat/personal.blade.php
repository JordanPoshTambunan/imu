@extends('layouts.app')

@section('title', 'Chat')

@section('content')
@php($showConversation = request()->has('with') && $selectedOtherUser)

<div class="card flex h-[calc(100vh-13rem)] min-h-[480px] w-full max-w-6xl overflow-hidden">
    {{-- Daftar chat. Di layar kecil disembunyikan saat percakapan dibuka. --}}
    <aside class="{{ $showConversation ? 'hidden' : 'flex' }} w-full flex-col border-r border-gray-100 lg:flex lg:w-80">
        <h1 class="border-b border-gray-100 px-5 py-4 text-lg font-bold text-navy-900">Chat</h1>
        <div class="flex-1 overflow-y-auto p-2">
            @forelse ($chatListUsers as $chatUser)
                <a href="{{ route('chat.personal', ['with' => $chatUser->id]) }}"
                   class="flex items-center gap-3 rounded-xl p-3 transition {{ $chatUser->is_active ? 'bg-navy-50' : 'hover:bg-gray-50' }}"
                   @if ($chatUser->is_active) aria-current="page" @endif>
                    <span class="relative">
                        <x-avatar :user="$chatUser" size="w-12 h-12 text-base" />
                        @if ($chatUser->has_unread)
                            <span class="absolute -right-0.5 -top-0.5 h-3.5 w-3.5 rounded-full border-2 border-white bg-gold-500" aria-label="Ada pesan baru"></span>
                        @endif
                    </span>
                    <span class="min-w-0 flex-1">
                        <span class="flex items-baseline justify-between gap-2">
                            <span class="truncate font-semibold text-navy-900">{{ $chatUser->full_name }}</span>
                            @if ($chatUser->last_message_time)
                                <span class="flex-shrink-0 text-xs text-gray-400">{{ $chatUser->last_message_time }}</span>
                            @endif
                        </span>
                        <span class="block truncate text-sm {{ $chatUser->has_unread ? 'font-semibold text-navy-800' : 'text-gray-500' }}">{{ $chatUser->last_message }}</span>
                    </span>
                </a>
            @empty
                <div class="px-6 py-12 text-center">
                    <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-navy-50 text-xl text-navy-800"><i class="fas fa-comments"></i></span>
                    <p class="mt-4 font-semibold text-navy-900">Belum ada chat</p>
                    <p class="mt-1 text-sm text-gray-500">Chat terbuka setelah kamu dan orang lain saling suka.</p>
                    <a href="{{ route('find.people') }}" class="btn-primary mt-6"><i class="fas fa-magnifying-glass"></i> Cari teman</a>
                </div>
            @endforelse
        </div>
    </aside>

    {{-- Percakapan --}}
    <section class="{{ $showConversation ? 'flex' : 'hidden' }} min-w-0 flex-1 flex-col lg:flex">
        @if ($selectedOtherUser)
            <header class="flex items-center gap-3 border-b border-gray-100 px-4 py-3">
                <a href="{{ route('chat.personal') }}" class="flex h-9 w-9 items-center justify-center rounded-full text-navy-800 hover:bg-navy-50 lg:hidden" aria-label="Kembali ke daftar chat">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <x-avatar :user="$selectedOtherUser" />
                <div class="min-w-0">
                    <h2 class="truncate font-semibold text-navy-900">{{ $selectedOtherUser->full_name }}</h2>
                    <p class="truncate text-xs text-gray-500">{{ $selectedOtherUser->prodi }}</p>
                </div>
            </header>

            <div id="messages-container" class="flex-1 space-y-3 overflow-y-auto bg-navy-50/60 p-4" aria-live="polite">
                @forelse ($messages as $message)
                    @php($mine = $message->sender_id === $currentUser->id)
                    <div class="flex {{ $mine ? 'justify-end' : 'justify-start' }}" data-message-id="{{ $message->id }}">
                        <div class="max-w-[75%] rounded-2xl px-4 py-2 shadow-sm {{ $mine ? 'rounded-br-md bg-navy-800 text-white' : 'rounded-bl-md bg-white text-gray-800' }}">
                            <p class="whitespace-pre-wrap break-words">{{ $message->message_content }}</p>
                            <span class="mt-1 block text-right text-[11px] {{ $mine ? 'text-white/60' : 'text-gray-400' }}">{{ $message->created_at->format('H:i') }}</span>
                        </div>
                    </div>
                @empty
                    <div id="chat-empty" class="py-12 text-center">
                        <p class="text-3xl">👋</p>
                        <p class="mt-2 font-semibold text-navy-900">Sapa {{ $selectedOtherUser->full_name }}!</p>
                        <p class="text-sm text-gray-500">Kalian sudah match. Mulai percakapan pertama.</p>
                    </div>
                @endforelse
            </div>

            <form id="message-form" class="flex items-center gap-2 border-t border-gray-100 p-3">
                <label for="message-input" class="sr-only">Tulis pesan</label>
                <input type="text" id="message-input" placeholder="Tulis pesan…" maxlength="1000" autocomplete="off"
                       class="input rounded-full bg-gray-50 py-2.5">
                <button id="send-message-button" type="submit" class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-full bg-gold-500 text-navy-900 transition hover:bg-gold-400 disabled:opacity-40" aria-label="Kirim pesan" disabled>
                    <i class="fas fa-paper-plane"></i>
                </button>
            </form>
        @else
            <div class="flex flex-1 flex-col items-center justify-center p-8 text-center">
                <span class="flex h-16 w-16 items-center justify-center rounded-full bg-navy-50 text-2xl text-navy-800"><i class="fas fa-comment-dots"></i></span>
                <p class="mt-4 font-semibold text-navy-900">Pilih percakapan</p>
                <p class="text-sm text-gray-500">Pilih seseorang dari daftar untuk mulai chat.</p>
            </div>
        @endif
    </section>
</div>
@endsection

@if ($selectedOtherUser && $activeMatch)
@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const container = document.getElementById('messages-container');
        const form = document.getElementById('message-form');
        const input = document.getElementById('message-input');
        const sendButton = document.getElementById('send-message-button');
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
        const receiverId = {{ $selectedOtherUser->id }};
        const matchId = {{ $activeMatch->id }};
        const myId = {{ $currentUser->id }};

        let lastMessageId = {{ $messages->last()?->id ?? 0 }};

        const scrollToBottom = () => { container.scrollTop = container.scrollHeight; };
        scrollToBottom();

        function appendMessage(message) {
            if (message.id <= lastMessageId) return; // sudah ditampilkan
            lastMessageId = message.id;
            document.getElementById('chat-empty')?.remove();

            const mine = message.sender_id === myId;
            const row = document.createElement('div');
            row.className = `flex ${mine ? 'justify-end' : 'justify-start'}`;
            row.dataset.messageId = message.id;

            const bubble = document.createElement('div');
            bubble.className = `max-w-[75%] rounded-2xl px-4 py-2 shadow-sm ${mine ? 'rounded-br-md bg-navy-800 text-white' : 'rounded-bl-md bg-white text-gray-800'}`;
            const text = document.createElement('p');
            text.className = 'whitespace-pre-wrap break-words';
            text.textContent = message.message_content;
            const time = document.createElement('span');
            time.className = `mt-1 block text-right text-[11px] ${mine ? 'text-white/60' : 'text-gray-400'}`;
            time.textContent = message.timestamp;
            bubble.append(text, time);
            row.appendChild(bubble);
            container.appendChild(row);
            scrollToBottom();
        }

        input.addEventListener('input', () => { sendButton.disabled = input.value.trim() === ''; });

        form.addEventListener('submit', async function (e) {
            e.preventDefault();
            const content = input.value.trim();
            if (!content) return;

            sendButton.disabled = true;
            try {
                const response = await fetch('{{ route('chat.send_message') }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                    body: JSON.stringify({ receiver_id: receiverId, match_id: matchId, message_content: content }),
                });
                const data = await response.json();
                if (!response.ok) throw new Error(data.message || 'Pesan gagal dikirim.');
                appendMessage(data.sent_message);
                input.value = '';
            } catch (error) {
                alert(error.message || 'Koneksi bermasalah. Pesan belum terkirim.');
                sendButton.disabled = false;
            }
            input.focus();
        });

        // shortcut: polling tiap 5 detik, ganti dengan broadcasting (Reverb) jika pengguna sudah banyak.
        setInterval(async function () {
            if (document.hidden) return;
            try {
                const params = new URLSearchParams({ match_id: matchId, last_message_id: lastMessageId });
                const response = await fetch(`{{ route('chat.get_messages') }}?${params}`, { headers: { 'Accept': 'application/json' } });
                const data = await response.json();
                if (data.status === 'success') data.messages.forEach(appendMessage);
            } catch (error) {
                console.error('Gagal memuat pesan baru:', error);
            }
        }, 5000);
    });
</script>
@endpush
@endif
