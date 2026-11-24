@extends('layouts.app')

@section('title', 'Cari Teman')

@section('content')
@php($myCategories = Auth::user()->match_categories ?? [])

<div class="w-full max-w-md">
    <div class="mb-4 flex items-center justify-between">
        <h1 class="page-title">Cari Teman</h1>
        <a href="{{ route('match.setup') }}" class="text-sm font-semibold text-navy-700 hover:underline"><i class="fas fa-sliders mr-1"></i>Kategori</a>
    </div>

    <div id="cards-container" class="relative h-[560px]">
        @foreach ($usersToDisplay as $index => $user)
            @php($shared = array_intersect($myCategories, $user->match_categories ?? []))
            <article class="profile-card-swipe card absolute inset-0 flex flex-col overflow-hidden {{ $index > 0 ? 'hidden' : '' }}"
                     data-user-id="{{ $user->id }}" data-user-full-name="{{ $user->full_name }}">
                <div class="bg-navy-900 px-6 pb-14 pt-6 text-center text-white">
                    <span class="tag bg-white/10 text-gold-400">{{ ucfirst(str_replace('_', ' ', $user->role)) }}</span>
                </div>
                <div class="-mt-12 flex flex-1 flex-col items-center overflow-y-auto px-6 text-center">
                    <x-avatar :user="$user" size="w-24 h-24 text-3xl" class="ring-4 ring-white" />
                    <h2 class="mt-3 text-xl font-bold text-navy-900">{{ $user->full_name }}</h2>
                    <p class="text-sm text-gray-500">{{ $user->prodi }} · {{ $user->fakultas }}</p>

                    @if (count($shared) > 0)
                        <p class="mt-4 text-xs font-semibold uppercase tracking-wide text-gray-500">Sama-sama mencari</p>
                        <div class="mt-2 flex flex-wrap justify-center gap-1.5">
                            @foreach ($shared as $category)
                                <span class="tag bg-gold-500 text-navy-900">{{ \App\Models\User::MATCH_CATEGORIES[$category] ?? $category }}</span>
                            @endforeach
                        </div>
                    @endif

                    @if (! empty($user->interests))
                        <p class="mt-4 text-xs font-semibold uppercase tracking-wide text-gray-500">Minat</p>
                        <div class="mt-2 flex flex-wrap justify-center gap-1.5">
                            @foreach ($user->interests as $interest)
                                <span class="tag bg-navy-50 text-navy-800">{{ ucfirst(str_replace('_', ' ', $interest)) }}</span>
                            @endforeach
                        </div>
                    @endif

                    <p class="mt-4 text-sm leading-relaxed text-gray-700">{{ $user->description ?: 'Belum ada deskripsi.' }}</p>
                </div>

                <div class="flex justify-center gap-6 border-t border-gray-100 py-5">
                    <button type="button" class="reject-button flex h-14 w-14 items-center justify-center rounded-full border-2 border-gray-200 bg-white text-xl text-gray-500 shadow transition hover:scale-110 hover:border-gray-400" aria-label="Lewati {{ $user->full_name }}">
                        <i class="fas fa-xmark"></i>
                    </button>
                    <button type="button" class="accept-button flex h-14 w-14 items-center justify-center rounded-full bg-gold-500 text-xl text-navy-900 shadow-lg transition hover:scale-110 hover:bg-gold-400" aria-label="Suka {{ $user->full_name }}">
                        <i class="fas fa-heart"></i>
                    </button>
                </div>
            </article>
        @endforeach

        <div id="empty-state" class="card absolute inset-0 flex-col items-center justify-center p-8 text-center {{ $usersToDisplay->isEmpty() ? 'flex' : 'hidden' }}">
            <span class="flex h-16 w-16 items-center justify-center rounded-full bg-navy-50 text-2xl text-navy-800"><i class="fas fa-magnifying-glass"></i></span>
            <p class="mt-4 text-lg font-semibold text-navy-900">Belum ada orang baru</p>
            <p class="mt-1 text-sm text-gray-500">Coba lagi nanti, atau tambah kategori yang kamu cari.</p>
            <a href="{{ route('find.people') }}" class="btn-primary mt-6"><i class="fas fa-rotate-right"></i> Muat ulang</a>
        </div>
    </div>

    @if ($usersToDisplay->isNotEmpty())
        <p class="mt-4 hidden text-center text-xs text-gray-500 sm:block">Pintasan: <kbd class="rounded border bg-white px-1.5">←</kbd> lewati · <kbd class="rounded border bg-white px-1.5">→</kbd> suka</p>
    @endif
</div>

{{-- Modal match --}}
<div id="match-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-navy-900/80 p-4" role="dialog" aria-modal="true" aria-labelledby="match-title">
    <div class="card w-full max-w-sm p-8 text-center">
        <span class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-gold-500 text-2xl text-navy-900"><i class="fas fa-handshake"></i></span>
        <h2 id="match-title" class="mt-4 text-2xl font-bold text-navy-900">Kalian match!</h2>
        <p class="mt-2 text-gray-600">Kamu dan <span id="matched-user-name" class="font-semibold text-navy-800"></span> saling menyukai.</p>
        <div class="mt-6 flex flex-col gap-2">
            <button id="start-chat-button" type="button" class="btn-primary"><i class="fas fa-comment"></i> Mulai chat</button>
            <button id="continue-swiping-button" type="button" class="btn-ghost">Lanjut mencari</button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const cards = document.querySelectorAll('.profile-card-swipe');
        const emptyState = document.getElementById('empty-state');
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
        const matchModal = document.getElementById('match-modal');
        let currentCardIndex = 0;
        let busy = false; // cegah klik/tombol ganda selama animasi

        function showNextCard() {
            cards.forEach((card, idx) => card.classList.toggle('hidden', idx !== currentCardIndex));
            if (currentCardIndex >= cards.length) {
                emptyState.classList.remove('hidden');
                emptyState.classList.add('flex');
            }
            busy = false;
        }

        function advance() {
            currentCardIndex++;
            showNextCard();
        }

        function swipe(action) {
            if (busy || currentCardIndex >= cards.length) return;
            busy = true;
            const card = cards[currentCardIndex];
            card.style.transition = 'transform .3s ease-out, opacity .3s ease-out';
            card.style.transform = `translateX(${action === 'dislike' ? -120 : 120}%) rotate(${action === 'dislike' ? -12 : 12}deg)`;
            card.style.opacity = '0';
            setTimeout(() => sendInteraction(card, action), 300);
        }

        async function sendInteraction(card, action) {
            try {
                const response = await fetch('{{ route('user.interact') }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                    body: JSON.stringify({ target_user_id: card.dataset.userId, action_type: action }),
                });
                const data = await response.json();

                if (response.ok && data.matched) {
                    document.getElementById('matched-user-name').textContent = card.dataset.userFullName;
                    matchModal.classList.replace('hidden', 'flex');
                    document.getElementById('start-chat-button').onclick = () => {
                        window.location.href = `{{ route('chat.personal') }}?with=${card.dataset.userId}`;
                    };
                    document.getElementById('continue-swiping-button').onclick = () => {
                        matchModal.classList.replace('flex', 'hidden');
                        advance();
                    };
                    return;
                }
                showToast(response.ok ? data.message : (data.message || 'Gagal menyimpan pilihan.'), response.ok ? action : 'error');
            } catch (error) {
                showToast('Koneksi bermasalah. Pilihanmu belum tersimpan.', 'error');
            }
            advance();
        }

        function showToast(message, type) {
            document.getElementById('feedback-toast')?.remove();
            const styles = { like: 'bg-gold-500 text-navy-900', dislike: 'bg-navy-900 text-white', error: 'bg-red-600 text-white' };
            const toast = document.createElement('div');
            toast.id = 'feedback-toast';
            toast.setAttribute('role', 'status');
            toast.className = `fixed bottom-6 left-1/2 z-50 -translate-x-1/2 rounded-xl px-5 py-3 text-sm font-semibold shadow-lg ${styles[type] ?? styles.dislike}`;
            toast.textContent = message;
            document.body.appendChild(toast);
            setTimeout(() => toast.remove(), 2500);
        }

        cards.forEach(card => {
            card.querySelector('.reject-button').addEventListener('click', () => swipe('dislike'));
            card.querySelector('.accept-button').addEventListener('click', () => swipe('like'));
        });

        document.addEventListener('keydown', function (e) {
            if (!matchModal.classList.contains('hidden') || e.target.matches('input, textarea')) return;
            if (e.key === 'ArrowLeft') { e.preventDefault(); swipe('dislike'); }
            if (e.key === 'ArrowRight') { e.preventDefault(); swipe('like'); }
        });
    });
</script>
@endpush
