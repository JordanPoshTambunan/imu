{{-- Pilihan jenis pendaftaran. $active: 'student' atau 'alumni'. --}}
<div class="mt-6 grid grid-cols-2 gap-1 rounded-xl bg-navy-50 p-1 text-sm font-semibold" role="tablist">
    <a href="{{ route('register.student') }}" role="tab" aria-selected="{{ $active === 'student' ? 'true' : 'false' }}"
       class="rounded-lg px-4 py-2 text-center transition {{ $active === 'student' ? 'bg-navy-800 text-white shadow' : 'text-navy-800 hover:bg-white' }}">Mahasiswa</a>
    <a href="{{ route('register.alumni') }}" role="tab" aria-selected="{{ $active === 'alumni' ? 'true' : 'false' }}"
       class="rounded-lg px-4 py-2 text-center transition {{ $active === 'alumni' ? 'bg-navy-800 text-white shadow' : 'text-navy-800 hover:bg-white' }}">Alumni</a>
</div>
