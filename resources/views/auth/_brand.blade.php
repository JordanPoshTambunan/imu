{{-- Panel kiri halaman autentikasi. Disembunyikan di layar kecil. --}}
<div class="relative hidden lg:flex flex-col justify-between overflow-hidden bg-navy-900 p-10 text-white">
    <div class="absolute -right-16 -top-16 h-56 w-56 rounded-full bg-gold-500/20" aria-hidden="true"></div>
    <div class="absolute -bottom-20 -left-10 h-64 w-64 rounded-full bg-navy-700/60" aria-hidden="true"></div>

    <div class="relative">
        <img src="{{ asset('images/logo.png') }}" alt="I Match U" class="mb-8 h-24 w-auto">
        <span class="tag bg-gold-500 text-navy-900">Universitas Udayana</span>
        <h1 class="mt-6 text-3xl font-bold leading-tight">Temukan rekan yang <span class="text-gold-400">sefrekuensi</span> di kampusmu.</h1>
        <p class="mt-3 text-white/70">Bukan aplikasi kencan. Pilih apa yang kamu cari, dan kami pertemukan dengan yang mencari hal serupa.</p>
    </div>

    <ul class="relative mt-10 space-y-4 text-sm">
        <li class="flex items-center gap-3"><span class="flex h-9 w-9 items-center justify-center rounded-lg bg-white/10 text-gold-400"><i class="fas fa-lightbulb"></i></span>Tim PKM, lomba, dan KKN</li>
        <li class="flex items-center gap-3"><span class="flex h-9 w-9 items-center justify-center rounded-lg bg-white/10 text-gold-400"><i class="fas fa-calendar-check"></i></span>Kepanitiaan dan organisasi</li>
        <li class="flex items-center gap-3"><span class="flex h-9 w-9 items-center justify-center rounded-lg bg-white/10 text-gold-400"><i class="fas fa-briefcase"></i></span>Info kerja dari alumni</li>
    </ul>
</div>
