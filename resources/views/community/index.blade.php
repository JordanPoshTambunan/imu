@extends('layouts.app')

@section('title', 'Komunitas')

@section('content')
@php
    // Samakan dengan CommunityController::checkPostPermission.
    $canPostIn = fn ($group) => $currentUser->role === 'admin'
        || in_array($currentUser->id, [$group->creator_id, $group->moderator_id], true);
    $postableGroups = $communities->filter($canPostIn);
@endphp

<div class="grid w-full max-w-6xl gap-6 lg:grid-cols-[16rem_1fr]">
    {{-- Daftar komunitas --}}
    <aside class="card h-fit p-3">
        <h1 class="px-2 pb-2 pt-1 text-lg font-bold text-navy-900">Komunitas</h1>
        <nav class="flex gap-2 overflow-x-auto scrollbar-hide lg:flex-col lg:overflow-visible" aria-label="Daftar komunitas">
            @forelse ($communities as $community)
                @php($active = $selectedGroup && $selectedGroup->id === $community->id)
                <a href="{{ route('community', ['group' => $community->id]) }}" @if ($active) aria-current="page" @endif
                   class="flex flex-shrink-0 items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition {{ $active ? 'bg-navy-800 text-white' : 'text-navy-900 hover:bg-navy-50' }}">
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg {{ $active ? 'bg-gold-500 text-navy-900' : 'bg-navy-50 text-navy-800' }}">
                        <i class="fas fa-hashtag text-xs"></i>
                    </span>
                    <span class="whitespace-nowrap lg:truncate">{{ $community->name }}</span>
                </a>
            @empty
                <p class="px-2 py-4 text-sm text-gray-500">Belum ada komunitas. Hubungi admin untuk membuatnya.</p>
            @endforelse
        </nav>
    </aside>

    <section class="min-w-0 space-y-4">
        @if ($selectedGroup)
            <header class="card p-5">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="text-xl font-bold text-navy-900"># {{ $selectedGroup->name }}</h2>
                    <span class="tag bg-navy-50 text-navy-800">{{ $groupMessages->count() }} postingan</span>
                </div>
                @if ($selectedGroup->description)
                    <p class="mt-2 text-sm text-gray-600">{{ $selectedGroup->description }}</p>
                @endif
                @if ($selectedGroup->moderator)
                    <p class="mt-2 text-xs text-gray-500"><i class="fas fa-shield-halved mr-1 text-navy-700"></i>Moderator: {{ $selectedGroup->moderator->full_name }}</p>
                @endif
            </header>

            {{-- Form posting --}}
            @if ($canPostIn($selectedGroup))
                <form id="post-form" class="card p-5" enctype="multipart/form-data">
                    <div class="mb-3 flex items-center gap-3">
                        <x-avatar :user="$currentUser" />
                        <p class="text-sm text-gray-600">Posting sebagai <span class="font-semibold text-navy-800">{{ ucfirst($currentUser->role) }}</span></p>
                    </div>
                    <label for="group-message-input" class="sr-only">Isi postingan</label>
                    <textarea id="group-message-input" rows="3" maxlength="5000" class="input resize-y" placeholder="Bagikan pengumuman atau info penting…" aria-describedby="instagram-hint"></textarea>
                    <p id="instagram-hint" class="mt-1 text-xs text-gray-500"><i class="fab fa-instagram mr-1"></i>Tempel link post Instagram (mis. dari @univ.udayana) untuk menampilkannya di sini.</p>

                    <div class="mt-3 flex flex-wrap items-center justify-between gap-3">
                        <div class="flex flex-wrap items-center gap-3 text-sm">
                            <label class="inline-flex cursor-pointer items-center gap-2 rounded-lg px-3 py-2 font-medium text-navy-800 hover:bg-navy-50">
                                <i class="fas fa-paperclip"></i> <span id="attachment-name">Lampiran</span>
                                <input type="file" id="attachment-input" class="sr-only" accept="image/*,.pdf,.doc,.docx,.xls,.xlsx,.txt">
                            </label>
                            @if ($postableGroups->count() > 1)
                                <label class="inline-flex items-center gap-2 text-gray-600">Ke
                                    <select id="community-select" class="rounded-lg border border-gray-300 bg-white px-2 py-1.5 text-sm focus:border-navy-700 focus:outline-none">
                                        @foreach ($postableGroups as $group)
                                            <option value="{{ $group->id }}" @selected($group->id === $selectedGroup->id)>{{ $group->name }}</option>
                                        @endforeach
                                    </select>
                                </label>
                            @else
                                <input type="hidden" id="community-select" value="{{ $selectedGroup->id }}">
                            @endif
                            <span id="char-counter" class="text-xs text-gray-400">0/5000</span>
                        </div>
                        <button id="publish-button" type="submit" class="btn-primary py-2" disabled>
                            <i class="fas fa-paper-plane"></i> Publikasikan
                        </button>
                    </div>
                </form>
            @else
                <p class="card flex items-center gap-2 p-4 text-sm text-gray-600"><i class="fas fa-lock text-navy-700"></i>Hanya admin, pembuat, dan moderator yang bisa memposting di sini.</p>
            @endif

            {{-- Postingan, terbaru di atas --}}
            @forelse ($groupMessages->sortByDesc('created_at') as $message)
                @php($liked = $message->reactions->contains('user_id', $currentUser->id))
                <article class="card p-5" data-message-id="{{ $message->id }}">
                    <div class="flex items-center gap-3">
                        <x-avatar :user="$message->sender" />
                        <div class="min-w-0">
                            <p class="flex flex-wrap items-center gap-2">
                                <span class="font-semibold text-navy-900">{{ $message->sender->full_name }}</span>
                                <span class="tag {{ $message->sender->role === 'admin' ? 'bg-gold-500 text-navy-900' : 'bg-navy-50 text-navy-800' }}">{{ ucfirst($message->sender->role) }}</span>
                            </p>
                            <p class="text-xs text-gray-500" title="{{ $message->created_at->format('d M Y, H:i') }}">{{ $message->created_at->diffForHumans() }}</p>
                        </div>
                    </div>

                    @if ($text = $message->contentWithoutInstagramUrl())
                        <p class="mt-3 whitespace-pre-wrap break-words leading-relaxed text-gray-800">{{ $text }}</p>
                    @endif

                    @if ($instagramUrl = $message->instagramUrl())
                        {{-- Dirender oleh embed.js Instagram; isi blockquote jadi cadangan jika script gagal dimuat. --}}
                        <div class="mt-3 max-w-[540px]">
                            <blockquote class="instagram-media" data-instgrm-permalink="{{ $instagramUrl }}" data-instgrm-version="14" style="margin:0; width:100%;">
                                <a href="{{ $instagramUrl }}" target="_blank" rel="noopener" class="flex items-center gap-2 rounded-xl border border-navy-100 p-3 text-sm font-medium text-navy-800 hover:bg-navy-50">
                                    <i class="fab fa-instagram text-lg"></i> Lihat post di Instagram
                                </a>
                            </blockquote>
                        </div>
                    @endif

                    @if ($message->hasAttachment())
                        @if ($message->isImageAttachment())
                            <a href="{{ $message->attachment_url }}" target="_blank" rel="noopener" class="mt-3 block">
                                <img src="{{ $message->attachment_url }}" alt="{{ $message->attachment_name }}" class="max-h-96 rounded-xl object-contain">
                            </a>
                        @else
                            <a href="{{ $message->attachment_url }}" download="{{ $message->attachment_name }}" class="mt-3 flex items-center gap-3 rounded-xl border border-navy-100 p-3 hover:bg-navy-50">
                                <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-navy-50 text-navy-800"><i class="fas fa-file-lines"></i></span>
                                <span class="min-w-0">
                                    <span class="block truncate text-sm font-medium text-navy-900">{{ $message->attachment_name }}</span>
                                    <span class="text-xs text-gray-500">{{ $message->formatted_file_size }}</span>
                                </span>
                                <i class="fas fa-download ml-auto text-gray-400"></i>
                            </a>
                        @endif
                    @endif

                    <div class="mt-4 flex items-center gap-2 border-t border-gray-100 pt-3 text-sm">
                        <button type="button" class="like-button inline-flex items-center gap-2 rounded-lg px-3 py-1.5 font-medium transition hover:bg-navy-50 {{ $liked ? 'bg-gold-100 text-navy-900' : 'text-gray-500' }}" aria-pressed="{{ $liked ? 'true' : 'false' }}">
                            <i class="fas fa-heart"></i> <span class="like-count">{{ $message->reactions->count() }}</span><span class="sr-only"> suka</span>
                        </button>
                        <button type="button" class="comment-toggle inline-flex items-center gap-2 rounded-lg px-3 py-1.5 font-medium text-gray-500 transition hover:bg-navy-50" aria-expanded="false">
                            <i class="fas fa-comment"></i> <span class="comment-count">{{ $message->comments_count }}</span><span class="sr-only"> komentar</span>
                        </button>
                    </div>

                    <div class="comment-panel mt-3 hidden space-y-3">
                        <div class="comment-list space-y-3"></div>
                        <form class="comment-form flex items-center gap-2">
                            <label class="sr-only" for="comment-{{ $message->id }}">Tulis komentar</label>
                            <input id="comment-{{ $message->id }}" type="text" maxlength="1000" placeholder="Tulis komentar…" class="input rounded-full bg-gray-50 py-2 text-sm">
                            <button type="submit" class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full bg-navy-800 text-white hover:bg-navy-700" aria-label="Kirim komentar"><i class="fas fa-paper-plane text-xs"></i></button>
                        </form>
                    </div>
                </article>
            @empty
                <div class="card p-10 text-center">
                    <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-navy-50 text-xl text-navy-800"><i class="fas fa-inbox"></i></span>
                    <p class="mt-4 font-semibold text-navy-900">Belum ada postingan</p>
                    <p class="mt-1 text-sm text-gray-500">{{ $canPostIn($selectedGroup) ? 'Jadilah yang pertama memposting di sini.' : 'Tunggu admin atau moderator membagikan info.' }}</p>
                </div>
            @endforelse
        @else
            <div class="card p-10 text-center">
                <p class="font-semibold text-navy-900">Pilih komunitas</p>
                <p class="mt-1 text-sm text-gray-500">Pilih salah satu komunitas untuk melihat postingannya.</p>
            </div>
        @endif
    </section>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
        const jsonHeaders = { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken };

        function toast(message, isError = false) {
            const el = document.createElement('div');
            el.setAttribute('role', 'status');
            el.className = `fixed bottom-6 left-1/2 z-50 -translate-x-1/2 rounded-xl px-5 py-3 text-sm font-semibold shadow-lg ${isError ? 'bg-red-600 text-white' : 'bg-navy-900 text-white'}`;
            el.textContent = message;
            document.body.appendChild(el);
            setTimeout(() => el.remove(), 3000);
        }

        // ---- Posting ----
        const postForm = document.getElementById('post-form');
        if (postForm) {
            const textarea = document.getElementById('group-message-input');
            const attachment = document.getElementById('attachment-input');
            const attachmentName = document.getElementById('attachment-name');
            const publishButton = document.getElementById('publish-button');
            const counter = document.getElementById('char-counter');

            const refresh = () => {
                counter.textContent = `${textarea.value.length}/5000`;
                publishButton.disabled = textarea.value.trim() === '' && !attachment.files.length;
            };
            textarea.addEventListener('input', refresh);
            attachment.addEventListener('change', () => {
                attachmentName.textContent = attachment.files[0]?.name ?? 'Lampiran';
                refresh();
            });

            postForm.addEventListener('submit', async function (e) {
                e.preventDefault();
                const groupId = document.getElementById('community-select').value;
                const body = new FormData();
                body.append('group_id', groupId);
                body.append('message_content', textarea.value.trim());
                if (attachment.files.length) body.append('attachment', attachment.files[0]);

                publishButton.disabled = true;
                try {
                    const response = await fetch('{{ route('community.send_message') }}', {
                        method: 'POST',
                        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                        body,
                    });
                    const data = await response.json();
                    if (!response.ok) {
                        const firstError = data.errors ? Object.values(data.errors)[0][0] : null;
                        throw new Error(firstError || data.message || 'Gagal memposting.');
                    }
                    window.location.href = `{{ route('community') }}?group=${groupId}`;
                } catch (error) {
                    toast(error.message, true);
                    refresh();
                }
            });
        }

        // ---- Suka ----
        document.querySelectorAll('.like-button').forEach(button => button.addEventListener('click', async function () {
            const messageId = this.closest('[data-message-id]').dataset.messageId;
            try {
                const response = await fetch('{{ route('community.add_reaction') }}', {
                    method: 'POST', headers: jsonHeaders,
                    body: JSON.stringify({ message_id: messageId, reaction_type: 'like' }),
                });
                const data = await response.json();
                if (!response.ok) throw new Error(data.message);
                const liked = data.user_reaction !== null;
                this.setAttribute('aria-pressed', liked);
                this.classList.toggle('bg-gold-100', liked);
                this.classList.toggle('text-navy-900', liked);
                this.classList.toggle('text-gray-500', !liked);
                this.querySelector('.like-count').textContent = Object.values(data.reaction_counts).reduce((a, b) => a + Number(b), 0);
            } catch (error) {
                toast(error.message || 'Gagal menyimpan suka.', true);
            }
        }));

        // ---- Komentar ----
        function commentElement(comment, isReply = false) {
            const wrapper = document.createElement('div');
            wrapper.className = `flex gap-2 ${isReply ? 'ml-10' : ''}`;
            wrapper.innerHTML = avatarHtml(comment.user.full_name, 'w-8 h-8 text-xs');
            const bubble = document.createElement('div');
            bubble.className = 'min-w-0 rounded-2xl bg-navy-50 px-3 py-2';
            const name = document.createElement('p');
            name.className = 'text-xs font-semibold text-navy-900';
            name.textContent = comment.user.full_name;
            const text = document.createElement('p');
            text.className = 'whitespace-pre-wrap break-words text-sm text-gray-800';
            text.textContent = comment.comment_content;
            const time = document.createElement('p');
            time.className = 'mt-0.5 text-[11px] text-gray-400';
            time.textContent = comment.created_at;
            bubble.append(name, text, time);
            wrapper.appendChild(bubble);
            return wrapper;
        }

        document.querySelectorAll('.comment-toggle').forEach(button => button.addEventListener('click', async function () {
            const card = this.closest('[data-message-id]');
            const panel = card.querySelector('.comment-panel');
            const open = panel.classList.toggle('hidden') === false;
            this.setAttribute('aria-expanded', open);
            if (!open || panel.dataset.loaded) return;

            const list = panel.querySelector('.comment-list');
            list.innerHTML = '<p class="text-sm text-gray-400"><i class="fas fa-spinner fa-spin mr-1"></i>Memuat komentar…</p>';
            try {
                const response = await fetch(`{{ route('community.load_comments') }}?message_id=${card.dataset.messageId}`, { headers: { 'Accept': 'application/json' } });
                const data = await response.json();
                if (!response.ok) throw new Error(data.message);
                list.innerHTML = data.comments.length ? '' : '<p class="empty-comments text-sm text-gray-400">Belum ada komentar.</p>';
                data.comments.forEach(comment => {
                    list.appendChild(commentElement(comment));
                    comment.replies.forEach(reply => list.appendChild(commentElement(reply, true)));
                });
                panel.dataset.loaded = '1';
            } catch (error) {
                list.innerHTML = '<p class="text-sm text-red-600">Gagal memuat komentar.</p>';
            }
        }));

        document.querySelectorAll('.comment-form').forEach(form => form.addEventListener('submit', async function (e) {
            e.preventDefault();
            const card = this.closest('[data-message-id]');
            const input = this.querySelector('input');
            const content = input.value.trim();
            if (!content) return;
            try {
                const response = await fetch('{{ route('community.add_comment') }}', {
                    method: 'POST', headers: jsonHeaders,
                    body: JSON.stringify({ message_id: card.dataset.messageId, comment_content: content }),
                });
                const data = await response.json();
                if (!response.ok) throw new Error(data.errors ? Object.values(data.errors)[0][0] : data.message);
                const list = card.querySelector('.comment-list');
                list.querySelector('.empty-comments')?.remove();
                list.appendChild(commentElement(data.comment));
                card.querySelector('.comment-count').textContent = data.total_comments;
                input.value = '';
            } catch (error) {
                toast(error.message || 'Gagal mengirim komentar.', true);
            }
        }));
    });
</script>
@endpush

@if ($groupMessages->contains(fn ($message) => $message->instagramUrl()))
@push('scripts')
<script async src="https://www.instagram.com/embed.js"></script>
@endpush
@endif
