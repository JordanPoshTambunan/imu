@extends('layouts.app')

@section('title', 'Daftar Alumni')
@section('main_class', 'auth-bg items-center justify-center')

@section('content')
<div class="card grid w-full max-w-4xl overflow-hidden lg:grid-cols-2">
    @include('auth._brand')

    <div class="p-8 sm:p-10">
        <h2 class="page-title">Buat akun</h2>
        <p class="mt-1 text-sm text-gray-500">Akun alumni diverifikasi admin sebelum bisa dipakai.</p>

        @include('auth._register_tabs', ['active' => 'alumni'])

        <form method="POST" action="{{ route('register.store.alumni') }}" enctype="multipart/form-data" class="mt-6 space-y-4">
            @csrf
            @include('auth._account_fields')
            <div>
                <label for="email" class="mb-1 block text-sm font-medium text-navy-900">Email</label>
                <input type="email" id="email" name="email" autocomplete="email" class="input @error('email') input-error @enderror" value="{{ old('email') }}" required>
                <p class="mt-1 text-xs text-gray-500">Dipakai untuk masuk.</p>
                @error('email') <p class="field-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <span class="mb-1 block text-sm font-medium text-navy-900">Dokumen bukti alumni <span class="font-normal text-gray-500">(opsional, mempercepat verifikasi)</span></span>
                <label for="upload_doc" class="flex cursor-pointer items-center gap-3 rounded-xl border-2 border-dashed px-4 py-4 text-sm transition hover:border-navy-700 hover:bg-navy-50 @error('verification_doc') border-red-500 @else border-navy-100 @enderror">
                    <span class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-lg bg-gold-100 text-navy-800"><i class="fas fa-upload"></i></span>
                    <span>
                        <span id="upload_doc_text" class="block font-medium text-navy-900">Ijazah, KTM lama, atau sertifikat PKKMB</span>
                        <span class="text-xs text-gray-500">JPG, PNG, atau PDF · maks. 3 MB</span>
                    </span>
                    <input type="file" id="upload_doc" name="verification_doc" accept=".jpg,.jpeg,.png,.pdf" class="sr-only">
                </label>
                @error('verification_doc') <p class="field-error">{{ $message }}</p> @enderror
            </div>
            <button type="submit" class="btn-primary w-full">Daftar</button>
        </form>

        <p class="mt-6 text-center text-sm text-gray-600">
            Sudah punya akun? <a href="{{ route('login') }}" class="text-link">Masuk</a>
        </p>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.getElementById('upload_doc').addEventListener('change', function () {
        document.getElementById('upload_doc_text').textContent = this.files[0]?.name ?? 'Ijazah, KTM lama, atau sertifikat PKKMB';
    });
</script>
@endpush
