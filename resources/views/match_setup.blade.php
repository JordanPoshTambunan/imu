@extends('layouts.app')

@section('title', 'Pilih Kategori')

@section('content')
@php
    $categories = [
        'friends' => ['fa-user-group', 'Kenalan dan nongkrong'],
        'jobs' => ['fa-briefcase', 'Lowongan dan magang'],
        'committee' => ['fa-calendar-check', 'Panitia acara kampus'],
        'pkm' => ['fa-lightbulb', 'Tim Program Kreativitas'],
        'kkn' => ['fa-map-location-dot', 'Rekan Kuliah Kerja Nyata'],
        'contest' => ['fa-trophy', 'Tim kompetisi'],
    ];
@endphp

<div class="card w-full max-w-3xl p-6 sm:p-10">
    @include('_onboarding_steps', ['step' => 2])
    <h2 class="page-title">Apa yang kamu cari?</h2>
    <p class="mt-1 text-sm text-gray-500">Pilih satu atau lebih. Kamu hanya dipertemukan dengan orang yang mencari hal serupa.</p>

    @if (session('success') || session('info'))
        <div class="mt-6 rounded-xl border border-gold-500/50 bg-gold-100 px-4 py-3 text-sm text-navy-900" role="status">{{ session('success') ?? session('info') }}</div>
    @endif

    <form method="POST" action="{{ route('profile.store_match_categories') }}" class="mt-8">
        @csrf
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3" id="match-categories-container">
            @foreach ($categories as $key => [$icon, $hint])
                @php($label = \App\Models\User::MATCH_CATEGORIES[$key])
                <button type="button" data-category="{{ $key }}" aria-pressed="false"
                        class="category group flex flex-col items-center gap-2 rounded-2xl border-2 border-navy-100 bg-white p-5 text-center transition hover:border-navy-700 hover:bg-navy-50">
                    <span class="flex h-12 w-12 items-center justify-center rounded-xl bg-navy-50 text-xl text-navy-800 transition"><i class="fas {{ $icon }}"></i></span>
                    <span class="font-semibold text-navy-900">{{ $label }}</span>
                    <span class="text-xs text-gray-500">{{ $hint }}</span>
                </button>
            @endforeach
        </div>

        <input type="hidden" name="match_categories" id="match-categories-hidden-input" value="{{ old('match_categories', json_encode(Auth::user()->match_categories ?? [])) }}">
        @error('match_categories') <p class="field-error mt-3">{{ $message }}</p> @enderror

        <button type="submit" id="match-submit" class="btn-primary mt-8 w-full">Mulai cari teman <i class="fas fa-arrow-right"></i></button>
    </form>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const buttons = document.querySelectorAll('#match-categories-container [data-category]');
        const hiddenInput = document.getElementById('match-categories-hidden-input');
        const submit = document.getElementById('match-submit');

        let selected = [];
        try { selected = JSON.parse(hiddenInput.value) || []; } catch (e) { selected = []; }

        function render() {
            buttons.forEach(button => {
                const isSelected = selected.includes(button.dataset.category);
                button.setAttribute('aria-pressed', isSelected);
                button.classList.toggle('border-gold-500', isSelected);
                button.classList.toggle('bg-gold-100', isSelected);
                button.querySelector('span').classList.toggle('bg-gold-500', isSelected);
            });
            hiddenInput.value = JSON.stringify(selected);
            submit.disabled = selected.length === 0;
        }

        buttons.forEach(button => button.addEventListener('click', function () {
            const category = this.dataset.category;
            selected = selected.includes(category) ? selected.filter(c => c !== category) : [...selected, category];
            render();
        }));

        render();
    });
</script>
@endpush
