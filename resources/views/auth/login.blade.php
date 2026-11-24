@extends('layouts.app')

@section('title', 'Masuk')
@section('main_class', 'auth-bg items-center justify-center')

@section('content')
<div class="card grid w-full max-w-4xl overflow-hidden lg:grid-cols-2">
    @include('auth._brand')

    <div class="p-8 sm:p-10">
        <h2 class="page-title">Masuk</h2>
        <p class="mt-1 text-sm text-gray-500">Selamat datang kembali! Masuk dengan email kampusmu.</p>

        @if (session('success'))
            <div class="mt-6 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800" role="status">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="mt-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700" role="alert">{{ session('error') }}</div>
        @endif

        <form method="POST" action="{{ route('login.store') }}" class="mt-6 space-y-5">
            @csrf
            <div>
                <label for="email" class="mb-1 block text-sm font-medium text-navy-900">Email</label>
                <input type="email" id="email" name="email" autocomplete="email" placeholder="nama@student.unud.ac.id" class="input @error('email') input-error @enderror" value="{{ old('email') }}" required autofocus>
                @error('email') <p class="field-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="password" class="mb-1 block text-sm font-medium text-navy-900">Kata sandi</label>
                <input type="password" id="password" name="password" autocomplete="current-password" class="input @error('password') input-error @enderror" required>
                @error('password') <p class="field-error">{{ $message }}</p> @enderror
            </div>
            <button type="submit" class="btn-primary w-full">Masuk</button>
        </form>

        <p class="mt-6 text-center text-sm text-gray-600">
            Belum punya akun? <a href="{{ route('register.student') }}" class="text-link">Daftar sekarang</a>
        </p>
    </div>
</div>
@endsection
