@extends('layouts.app')

@section('title', 'Beranda')

@section('content')
@php
    $stats = [
        ['label' => 'Match', 'value' => $matchCount, 'icon' => 'fa-handshake', 'route' => 'chat.personal'],
        ['label' => 'Pesan baru', 'value' => $unreadMessageCount, 'icon' => 'fa-envelope', 'route' => 'chat.personal'],
        ['label' => 'Like baru', 'value' => $newLikeCount, 'icon' => 'fa-heart', 'route' => 'notifications.index'],
    ];
@endphp

<div class="w-full max-w-5xl space-y-6">
    {{-- Sapaan --}}
    <section class="relative overflow-hidden rounded-2xl bg-navy-900 p-6 text-white sm:p-8">
        <div class="absolute -right-10 -top-10 h-40 w-40 rounded-full bg-gold-500/20" aria-hidden="true"></div>
        <div class="relative flex flex-col gap-6 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-sm text-white/70">Halo,</p>
                <h1 class="text-2xl font-bold sm:text-3xl">{{ $user->full_name }}</h1>
                <p class="mt-2 text-sm text-white/70">Kamu sedang mencari:
                    @foreach ($user->match_categories ?? [] as $category)
                        <span class="tag ml-1 bg-gold-500 text-navy-900">{{ \App\Models\User::MATCH_CATEGORIES[$category] ?? $category }}</span>
                    @endforeach
                    <a href="{{ route('match.setup') }}" class="ml-1 text-gold-400 underline-offset-4 hover:underline">ubah</a>
                </p>
            </div>
            <a href="{{ route('find.people') }}" class="btn-accent self-start sm:self-auto"><i class="fas fa-magnifying-glass"></i> Cari teman</a>
        </div>
    </section>

    {{-- Ringkasan --}}
    <section class="grid grid-cols-3 gap-3 sm:gap-4" aria-label="Ringkasan">
        @foreach ($stats as $stat)
            <a href="{{ route($stat['route']) }}" class="card flex flex-col items-center gap-2 p-4 text-center transition hover:-translate-y-0.5 hover:shadow-xl sm:flex-row sm:gap-4 sm:p-5 sm:text-left">
                <span class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-xl bg-navy-50 text-navy-800 sm:h-12 sm:w-12 sm:text-lg"><i class="fas {{ $stat['icon'] }}"></i></span>
                <span>
                    <span class="block text-xl font-bold text-navy-900 sm:text-2xl">{{ $stat['value'] }}</span>
                    <span class="text-xs text-gray-500 sm:text-sm">{{ $stat['label'] }}</span>
                </span>
            </a>
        @endforeach
    </section>

    {{-- Postingan komunitas terbaru --}}
    <section class="card p-6">
        <div class="mb-4 flex items-center justify-between">
            <h2 class="text-lg font-bold text-navy-900">Kabar terbaru dari komunitas</h2>
            <a href="{{ route('community') }}" class="text-sm font-semibold text-navy-700 hover:underline">Lihat semua</a>
        </div>

        <div class="divide-y divide-gray-100">
            @forelse ($latestPosts as $post)
                <a href="{{ route('community', ['group' => $post->group_id]) }}" class="flex gap-3 py-4 first:pt-0 last:pb-0 hover:opacity-90">
                    <x-avatar :user="$post->sender" />
                    <div class="min-w-0 flex-1">
                        <p class="text-sm">
                            <span class="font-semibold text-navy-900">{{ $post->sender->full_name }}</span>
                            <span class="text-gray-500">di</span>
                            <span class="font-medium text-navy-700">{{ $post->group->name }}</span>
                            <span class="text-gray-400">· {{ $post->created_at->diffForHumans() }}</span>
                        </p>
                        <p class="mt-1 line-clamp-2 whitespace-pre-line text-sm text-gray-700">{{ $post->message_content }}</p>
                    </div>
                </a>
            @empty
                <p class="py-6 text-center text-sm text-gray-500">Belum ada postingan di komunitas.</p>
            @endforelse
        </div>
    </section>
</div>
@endsection
