{{-- Field akun yang sama untuk pendaftaran mahasiswa dan alumni. --}}
<div>
    <label for="full_name" class="mb-1 block text-sm font-medium text-navy-900">Nama lengkap</label>
    <input type="text" id="full_name" name="full_name" autocomplete="name" class="input @error('full_name') input-error @enderror" value="{{ old('full_name') }}" required>
    @error('full_name') <p class="field-error">{{ $message }}</p> @enderror
</div>
<div>
    <label for="username" class="mb-1 block text-sm font-medium text-navy-900">Username</label>
    <input type="text" id="username" name="username" autocomplete="username" class="input @error('username') input-error @enderror" value="{{ old('username') }}" required>
    @error('username') <p class="field-error">{{ $message }}</p> @enderror
</div>
<div class="grid gap-4 sm:grid-cols-2">
    <div>
        <label for="password" class="mb-1 block text-sm font-medium text-navy-900">Kata sandi</label>
        <input type="password" id="password" name="password" autocomplete="new-password" minlength="8" class="input @error('password') input-error @enderror" required>
    </div>
    <div>
        <label for="password_confirmation" class="mb-1 block text-sm font-medium text-navy-900">Ulangi kata sandi</label>
        <input type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password" class="input" required>
    </div>
</div>
@error('password') <p class="field-error -mt-3">{{ $message }}</p> @enderror
