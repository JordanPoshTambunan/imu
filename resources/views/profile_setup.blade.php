@extends('layouts.app')

@section('title', 'Lengkapi Profil')

@section('content')
@php
    $user = Auth::user();
    $interests = [
        'Photography' => 'fa-camera', 'Shopping' => 'fa-bag-shopping', 'Karaoke' => 'fa-microphone',
        'Yoga' => 'fa-leaf', 'Cooking' => 'fa-utensils', 'Tennis' => 'fa-table-tennis-paddle-ball',
        'Run' => 'fa-person-running', 'Art' => 'fa-palette', 'Traveling' => 'fa-plane-departure',
        'Extreme' => 'fa-mountain', 'Music' => 'fa-music', 'Drink' => 'fa-mug-hot',
        'Video games' => 'fa-gamepad',
    ];
@endphp

<div class="card w-full max-w-5xl p-6 sm:p-10">
    @include('_onboarding_steps', ['step' => 1])
    <h2 class="page-title">Lengkapi profilmu</h2>
    <p class="mt-1 text-sm text-gray-500">Profil ini yang dilihat orang lain di halaman Cari Teman.</p>

    @if (session('info'))
        <div class="mt-6 rounded-xl border border-gold-500/50 bg-gold-100 px-4 py-3 text-sm text-navy-900" role="status">{{ session('info') }}</div>
    @endif

    <form method="POST" action="{{ route('profile.store') }}" class="mt-8 grid gap-10 lg:grid-cols-2">
        @csrf

        <div class="space-y-4">
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="username" class="mb-1 block text-sm font-medium text-navy-900">Username</label>
                    <input type="text" id="username" class="input cursor-not-allowed bg-gray-50 text-gray-500" value="{{ $user->username }}" readonly>
                </div>
                <div>
                    <label for="full_name" class="mb-1 block text-sm font-medium text-navy-900">Nama lengkap</label>
                    <input type="text" id="full_name" name="full_name" class="input @error('full_name') input-error @enderror" value="{{ old('full_name', $user->full_name) }}" required>
                    @error('full_name') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="prodi" class="mb-1 block text-sm font-medium text-navy-900">Program studi</label>
                    <input type="text" id="prodi" name="prodi" placeholder="mis. Teknologi Informasi" class="input @error('prodi') input-error @enderror" value="{{ old('prodi', $user->prodi) }}" required>
                    @error('prodi') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="fakultas" class="mb-1 block text-sm font-medium text-navy-900">Fakultas</label>
                    <input type="text" id="fakultas" name="fakultas" placeholder="mis. Teknik" class="input @error('fakultas') input-error @enderror" value="{{ old('fakultas', $user->fakultas) }}" required>
                    @error('fakultas') <p class="field-error">{{ $message }}</p> @enderror
                </div>
            </div>
            <div>
                <label for="gender" class="mb-1 block text-sm font-medium text-navy-900">Jenis kelamin</label>
                <select id="gender" name="gender" class="input @error('gender') input-error @enderror" required>
                    <option value="">Pilih jenis kelamin</option>
                    @foreach (['Laki-laki', 'Perempuan'] as $gender)
                        <option value="{{ $gender }}" @selected(old('gender', $user->gender) === $gender)>{{ $gender }}</option>
                    @endforeach
                </select>
                @error('gender') <p class="field-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="description" class="mb-1 block text-sm font-medium text-navy-900">Tentang kamu <span class="font-normal text-gray-500">(opsional)</span></label>
                <textarea id="description" name="description" rows="4" maxlength="1000" placeholder="Ceritakan singkat apa yang sedang kamu kerjakan atau cari." class="input resize-none @error('description') input-error @enderror">{{ old('description', $user->description) }}</textarea>
                @error('description') <p class="field-error">{{ $message }}</p> @enderror
            </div>
        </div>

        <div>
            <div class="mb-3 flex items-baseline justify-between">
                <h3 class="font-semibold text-navy-900">Minat</h3>
                <span class="text-sm text-gray-500"><span id="interest-count">0</span>/3 dipilih</span>
            </div>
            <div class="flex flex-wrap gap-2" id="interests-container">
                @foreach ($interests as $name => $icon)
                    <button type="button" class="chip" data-interest="{{ strtolower(str_replace(' ', '_', $name)) }}" aria-pressed="false">
                        <i class="fas {{ $icon }}"></i>{{ $name }}
                    </button>
                @endforeach
            </div>
            @error('interests') <p class="field-error mt-3">{{ $message }}</p> @enderror
            <input type="hidden" name="interests" id="interests-hidden-input" value="{{ old('interests', json_encode($user->interests ?? [])) }}">

            <button type="submit" class="btn-primary mt-10 w-full">Simpan & lanjut <i class="fas fa-arrow-right"></i></button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const MAX_INTERESTS = 3;
        const buttons = document.querySelectorAll('#interests-container [data-interest]');
        const hiddenInput = document.getElementById('interests-hidden-input');
        const counter = document.getElementById('interest-count');

        let selected = [];
        try { selected = JSON.parse(hiddenInput.value) || []; } catch (e) { selected = []; }

        function render() {
            buttons.forEach(button => {
                const isSelected = selected.includes(button.dataset.interest);
                const isFull = selected.length >= MAX_INTERESTS && !isSelected;
                button.classList.toggle('selected', isSelected);
                button.setAttribute('aria-pressed', isSelected);
                button.disabled = isFull;
                button.classList.toggle('opacity-40', isFull);
            });
            counter.textContent = selected.length;
            hiddenInput.value = JSON.stringify(selected);
        }

        buttons.forEach(button => button.addEventListener('click', function () {
            const interest = this.dataset.interest;
            selected = selected.includes(interest)
                ? selected.filter(item => item !== interest)
                : [...selected, interest].slice(0, MAX_INTERESTS);
            render();
        }));

        render();
    });
</script>
@endpush
