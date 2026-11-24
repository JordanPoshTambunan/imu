@extends('layouts.app')

@section('title', 'Daftar Mahasiswa')
@section('main_class', 'auth-bg items-center justify-center')

@section('content')
<div class="card grid w-full max-w-4xl overflow-hidden lg:grid-cols-2">
    @include('auth._brand')

    <div class="p-8 sm:p-10">
        <h2 class="page-title">Buat akun</h2>
        <p class="mt-1 text-sm text-gray-500">Gratis untuk mahasiswa aktif dan alumni Unud.</p>

        @include('auth._register_tabs', ['active' => 'student'])

        <form method="POST" action="{{ route('register.store.student') }}" class="mt-6 space-y-4">
            @csrf
            @include('auth._account_fields')
            <div>
                <label for="email" class="mb-1 block text-sm font-medium text-navy-900">Email kampus</label>
                <input type="email" id="email" name="email" autocomplete="email" placeholder="nama@student.unud.ac.id" class="input @error('email') input-error @enderror" value="{{ old('email') }}" required>
                @error('email') <p class="field-error">{{ $message }}</p> @enderror
            </div>
            <button type="submit" class="btn-primary w-full">Daftar</button>
        </form>

        <p class="mt-6 text-center text-sm text-gray-600">
            Sudah punya akun? <a href="{{ route('login') }}" class="text-link">Masuk</a>
        </p>
    </div>
</div>
@endsection
