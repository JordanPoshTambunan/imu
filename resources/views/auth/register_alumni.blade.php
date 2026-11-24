@extends('layouts.app') {{-- Menggunakan layout dasar yang sudah kita buat --}}

@section('content')
<div class="main-card w-full max-w-4xl flex lg:absolute lg:top-1/2 lg:-translate-y-1/2 lg:right-10">
    <div class="w-1/2 p-8 flex flex-col justify-center">
        <h2 class="text-2xl font-bold mb-6">Make real connections with <br> students and alumni from <br> our campus!</h2>
        <form method="POST" action="{{ route('register.store.alumni') }}" enctype="multipart/form-data">
            @csrf
            <div class="space-y-4">
                <div>
                    <input type="text" id="full_name" name="full_name" placeholder="Full Name" class="input-field @error('full_name') border-red-500 @enderror" value="{{ old('full_name') }}">
                    @error('full_name')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <input type="text" id="username" name="username" placeholder="UserName" class="input-field @error('username') border-red-500 @enderror" value="{{ old('username') }}">
                    @error('username')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <input type="password" id="password" name="password" placeholder="Password" class="input-field @error('password') border-red-500 @enderror">
                    @error('password')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <input type="password" id="confirm_password" name="password_confirmation" placeholder="Confirm Password" class="input-field">
                </div>
            </div>
    </div>
    <div class="w-1/2 p-8 flex flex-col justify-between">
        <div>
            <div class="flex space-x-4 mb-6">
                <a href="{{ route('register.student') }}" class="btn-outline px-6 py-2 rounded-full">Student</a>
                <button type="button" class="btn-primary px-6 py-2 rounded-full cursor-not-allowed opacity-70">Alumni</button>
            </div>

            {{-- START: TEKS INSTRUKSI BARU DITAMBAHKAN DI SINI --}}
            <p class="text-gray-300 text-sm mb-4">
                Masukkan alamat email Anda (dipakai untuk login). Untuk mempercepat verifikasi, unggah juga dokumen bukti alumni (misal: KTM lama/sertifikat PKKMB).
            </p>
            {{-- END: TEKS INSTRUKSI BARU --}}

            <div>
                <input type="email" id="email" name="email" placeholder="e-mail" required class="input-field mb-4 @error('email') border-red-500 @enderror" value="{{ old('email') }}">
                @error('email')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
                <label for="upload_doc" class="flex items-center justify-center border border-dashed border-gray-500 rounded-lg p-6 cursor-pointer text-gray-400 hover:border-blue-400 hover:text-blue-400 transition-colors @error('verification_doc') border-red-500 @enderror">
                    <i class="fas fa-upload mr-3 text-lg"></i>
                    <span class="text-center" id="upload_doc_text">degree / KTM / PKKMB certification / etc*</span>
                    <input type="file" id="upload_doc" name="verification_doc" accept=".jpg,.jpeg,.png,.pdf" class="hidden">
                </label>
                <p class="text-gray-500 text-xs mt-2">*Maximum file size: 3 MB, maximum number of files: 1</p>
                @error('verification_doc')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
        </div>
        <div class="mt-8">
            <button type="submit" class="btn-primary w-full">Register Now</button>
            <p class="text-center text-sm mt-4">
                Already have an account? <a href="{{ route('login') }}" class="text-link">Sign In</a>
            </p>
        </div>
    </div>
    </form>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const uploadDocInput = document.getElementById('upload_doc');
        const uploadDocText = document.getElementById('upload_doc_text');

        if (uploadDocInput && uploadDocText) {
            uploadDocInput.addEventListener('change', function() {
                if (this.files && this.files.length > 0) {
                    uploadDocText.textContent = this.files[0].name;
                } else {
                    uploadDocText.textContent = 'degree / KTM / PKKMB certification / etc*';
                }
            });
        }
    });
</script>
@endpush