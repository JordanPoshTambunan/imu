@extends('layouts.app')

@section('title', 'Profil')

@section('content')
<div class="grid w-full max-w-5xl gap-6 lg:grid-cols-3">
    <aside class="card flex flex-col items-center p-8 text-center">
        <x-avatar :user="$user" size="w-28 h-28 text-4xl" class="ring-4 ring-gold-500" />
        <h1 class="mt-4 text-2xl font-bold text-navy-900">{{ $user->full_name }}</h1>
        <p class="text-sm text-gray-500">{{ '@' . $user->username }}</p>
        <span class="tag mt-3 bg-navy-50 text-navy-800">{{ ucfirst(str_replace('_', ' ', $user->role)) }}</span>

        <dl class="mt-6 w-full space-y-3 border-t border-gray-100 pt-6 text-left text-sm">
            <div class="flex gap-3"><dt class="w-5 text-navy-700"><i class="fas fa-graduation-cap"></i><span class="sr-only">Prodi</span></dt><dd>{{ $user->prodi ?? 'Prodi belum diisi' }}</dd></div>
            <div class="flex gap-3"><dt class="w-5 text-navy-700"><i class="fas fa-building-columns"></i><span class="sr-only">Fakultas</span></dt><dd>{{ $user->fakultas ?? 'Fakultas belum diisi' }}</dd></div>
            <div class="flex gap-3"><dt class="w-5 text-navy-700"><i class="fas fa-user"></i><span class="sr-only">Jenis kelamin</span></dt><dd>{{ $user->gender ?? 'Belum diisi' }}</dd></div>
        </dl>

        <a href="{{ route('profile.setup') }}" class="btn-primary mt-8 w-full"><i class="fas fa-pen"></i> Edit profil</a>
    </aside>

    <section class="card space-y-8 p-8 lg:col-span-2">
        <div>
            <h2 class="mb-2 font-semibold text-navy-900">Tentang saya</h2>
            <p class="whitespace-pre-line leading-relaxed text-gray-700">{{ $user->description ?: 'Belum ada deskripsi diri.' }}</p>
        </div>

        <div>
            <h2 class="mb-3 font-semibold text-navy-900">Minat</h2>
            <div class="flex flex-wrap gap-2">
                @forelse ($user->interests ?? [] as $interest)
                    <span class="tag bg-navy-50 px-3 py-1.5 text-sm text-navy-800">{{ ucfirst(str_replace('_', ' ', $interest)) }}</span>
                @empty
                    <p class="text-sm text-gray-500">Belum ada minat yang dipilih.</p>
                @endforelse
            </div>
        </div>

        <div>
            <div class="mb-3 flex items-center justify-between">
                <h2 class="font-semibold text-navy-900">Sedang mencari</h2>
                <a href="{{ route('match.setup') }}" class="text-sm font-semibold text-navy-700 hover:underline">Ubah</a>
            </div>
            <div class="flex flex-wrap gap-2">
                @forelse ($user->match_categories ?? [] as $category)
                    <span class="tag bg-gold-500 px-3 py-1.5 text-sm text-navy-900">{{ \App\Models\User::MATCH_CATEGORIES[$category] ?? $category }}</span>
                @empty
                    <p class="text-sm text-gray-500">Belum memilih kategori.</p>
                @endforelse
            </div>
        </div>
    </section>
</div>
@endsection
