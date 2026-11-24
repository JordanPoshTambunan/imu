{{-- Penanda langkah onboarding. $step: 1 (profil) atau 2 (kategori). --}}
<ol class="mb-6 flex items-center gap-3 text-sm font-medium" aria-label="Langkah pendaftaran">
    @foreach ([1 => 'Profil', 2 => 'Yang kamu cari'] as $number => $label)
        <li class="flex items-center gap-2 {{ $number === $step ? 'text-navy-900' : 'text-gray-400' }}" @if ($number === $step) aria-current="step" @endif>
            <span class="flex h-7 w-7 items-center justify-center rounded-full text-xs font-bold {{ $number < $step ? 'bg-navy-800 text-white' : ($number === $step ? 'bg-gold-500 text-navy-900' : 'bg-gray-200 text-gray-500') }}">
                @if ($number < $step) <i class="fas fa-check"></i> @else {{ $number }} @endif
            </span>
            {{ $label }}
        </li>
        @if ($number === 1) <li class="h-px w-8 bg-gray-300" aria-hidden="true"></li> @endif
    @endforeach
</ol>
