@extends('layouts.app')

@section('title', 'Menunggu Verifikasi')
@section('main_class', 'auth-bg items-center justify-center')

@section('content')
<div class="card w-full max-w-lg p-8 text-center sm:p-10">
    <span class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-gold-100 text-2xl text-navy-800"><i class="fas fa-hourglass-half"></i></span>
    <h2 class="page-title mt-6">{{ session('error') ? 'Akun belum diverifikasi' : 'Pendaftaran berhasil!' }}</h2>
    <p class="mt-3 text-gray-600">
        Akun alumnimu sedang diperiksa administrator. Proses ini biasanya memakan waktu hingga 1×24 jam.
        Kami akan mengabari lewat email setelah akunmu aktif.
    </p>
    <a href="{{ route('login') }}" class="btn-primary mt-8 w-full sm:w-auto">Kembali ke halaman masuk</a>
</div>
@endsection
