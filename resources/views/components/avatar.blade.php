{{-- Foto profil, atau inisial nama jika belum ada foto. $user boleh model User atau objek biasa. --}}
@props(['user', 'size' => 'w-10 h-10 text-sm'])

@php($name = $user?->full_name ?? '?')

<span {{ $attributes->merge(['class' => "$size rounded-full overflow-hidden flex-shrink-0 inline-flex items-center justify-center bg-navy-100 text-navy-800 font-semibold"]) }}>
    @if ($user?->profile_picture)
        <img src="{{ asset($user->profile_picture) }}" alt="Foto profil {{ $name }}" class="w-full h-full object-cover">
    @else
        <span aria-hidden="true">{{ mb_strtoupper(mb_substr($name, 0, 1)) }}</span>
    @endif
</span>
