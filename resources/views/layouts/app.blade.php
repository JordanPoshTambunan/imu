<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@hasSection('title')@yield('title') · @endif I Match U</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        // Tema IMU: navy (#00296b, #003f88, #00509d) + kuning (#fdc500, #ffd500).
        // 50/100 adalah tint terang turunan untuk latar lembut.
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        navy: { 50: '#eef3fb', 100: '#d6e2f3', 700: '#00509d', 800: '#003f88', 900: '#00296b' },
                        gold: { 100: '#fff6cc', 400: '#ffd500', 500: '#fdc500' },
                    },
                    fontFamily: { sans: ['Poppins', 'sans-serif'] },
                },
            },
        };
    </script>
    <style type="text/tailwindcss">
        @layer components {
            .card { @apply bg-white rounded-2xl shadow-lg ring-1 ring-navy-900/5; }
            .input { @apply w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-gray-800 placeholder-gray-400 transition focus:border-navy-700 focus:outline-none focus:ring-2 focus:ring-navy-700/30; }
            .input-error { @apply border-red-500 focus:border-red-500 focus:ring-red-500/30; }
            .field-error { @apply mt-1 text-xs text-red-600; }
            .btn { @apply inline-flex items-center justify-center gap-2 rounded-xl px-5 py-3 font-semibold transition disabled:cursor-not-allowed disabled:opacity-50; }
            .btn-primary { @apply btn bg-navy-800 text-white hover:bg-navy-700 focus:outline-none focus:ring-2 focus:ring-gold-500 focus:ring-offset-2; }
            .btn-accent { @apply btn bg-gold-500 text-navy-900 hover:bg-gold-400 focus:outline-none focus:ring-2 focus:ring-navy-700 focus:ring-offset-2; }
            .btn-ghost { @apply btn border border-navy-100 bg-white text-navy-800 hover:bg-navy-50; }
            .chip { @apply inline-flex items-center gap-2 rounded-full border border-navy-100 bg-white px-4 py-2 text-sm font-medium text-navy-800 transition hover:border-navy-700 hover:bg-navy-50; }
            .chip.selected { @apply border-gold-500 bg-gold-500 text-navy-900 hover:bg-gold-400; }
            .tag { @apply inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold; }
            .text-link { @apply font-semibold text-navy-700 underline-offset-4 hover:underline; }
            .page-title { @apply text-2xl font-bold text-navy-900; }
            .nav-link { @apply rounded-lg px-3 py-2 text-sm font-medium text-white/80 transition hover:bg-white/10 hover:text-white; }
            .nav-link.active { @apply bg-white/10 text-gold-400; }
        }
    </style>
    <style>
        .auth-bg { background: linear-gradient(135deg, #00296b 0%, #003f88 55%, #00509d 100%); }
        .scrollbar-hide::-webkit-scrollbar { display: none; }
        .scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }
    </style>
</head>
<body class="min-h-screen flex flex-col bg-navy-50 font-sans text-gray-800 antialiased">
    @php
        // Navbar dinonaktifkan selama onboarding (samakan dengan CheckProfileCompletion).
        $user = Auth::user();
        $isOnboarding = $user
            && (request()->routeIs('profile.setup') || request()->routeIs('match.setup'))
            && (empty($user->prodi) || empty($user->fakultas) || empty($user->gender) || empty($user->interests) || empty($user->match_categories));
        $navItems = [
            ['route' => 'home', 'label' => 'Beranda', 'icon' => 'fa-house'],
            ['route' => 'find.people', 'label' => 'Cari Teman', 'icon' => 'fa-magnifying-glass'],
            ['route' => 'chat.personal', 'label' => 'Chat', 'icon' => 'fa-comments'],
            ['route' => 'community', 'label' => 'Komunitas', 'icon' => 'fa-users'],
            ['route' => 'user.profile', 'label' => 'Profil', 'icon' => 'fa-user'],
        ];
    @endphp

    <header class="sticky top-0 z-40 bg-navy-900 text-white shadow-md">
        <div class="mx-auto flex h-16 max-w-6xl items-center justify-between gap-4 px-4">
            <a href="{{ $user ? route('home') : route('login') }}" class="flex items-center gap-2 text-lg font-bold tracking-wide whitespace-nowrap">
                <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-gold-500 text-navy-900"><i class="fas fa-handshake-simple text-sm"></i></span>
                I MATCH U
            </a>

            @if ($user && ! $isOnboarding)
                <nav class="hidden md:flex items-center gap-1" aria-label="Navigasi utama">
                    @foreach ($navItems as $item)
                        <a href="{{ route($item['route']) }}" class="nav-link {{ request()->routeIs($item['route']) ? 'active' : '' }}">{{ $item['label'] }}</a>
                    @endforeach
                </nav>

                <div class="flex items-center gap-2">
                    {{-- Notifikasi --}}
                    <div class="relative">
                        <button id="notification-bell" type="button" class="relative flex h-10 w-10 items-center justify-center rounded-full hover:bg-white/10" aria-label="Notifikasi">
                            <i class="fas fa-bell text-lg"></i>
                            <span id="notification-dot" class="absolute -top-0.5 -right-0.5 hidden min-w-[1.25rem] h-5 px-1 rounded-full bg-gold-500 text-[11px] font-bold text-navy-900 flex items-center justify-center">
                                <span id="notification-count">0</span>
                            </span>
                        </button>

                        <div id="notification-dropdown" class="hidden fixed inset-x-4 top-16 sm:absolute sm:inset-x-auto sm:right-0 sm:top-auto sm:mt-2 sm:w-96 overflow-hidden rounded-2xl bg-white text-gray-800 shadow-xl ring-1 ring-navy-900/10 z-50">
                            <div class="flex items-center justify-between border-b border-gray-100 px-4 py-3">
                                <h3 class="font-semibold text-navy-900">Notifikasi</h3>
                                <button id="mark-all-read" type="button" class="text-sm font-medium text-navy-700 hover:underline">Tandai semua dibaca</button>
                            </div>
                            <div id="notifications-list" class="max-h-96 overflow-y-auto">
                                <p class="p-6 text-center text-sm text-gray-500"><i class="fas fa-spinner fa-spin mr-2"></i>Memuat…</p>
                            </div>
                            <a href="{{ route('notifications.index') }}" class="block border-t border-gray-100 px-4 py-3 text-center text-sm font-medium text-navy-700 hover:bg-navy-50">Lihat semua notifikasi</a>
                        </div>
                    </div>

                    <a href="{{ route('user.profile') }}" class="hidden sm:block" aria-label="Profil saya">
                        <x-avatar :user="$user" size="w-9 h-9 text-sm" class="ring-2 ring-gold-500" />
                    </a>
                    <a href="{{ route('logout') }}" class="hidden md:inline-flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium text-white/80 hover:bg-white/10 hover:text-white">
                        <i class="fas fa-right-from-bracket"></i> Keluar
                    </a>

                    <button id="mobile-menu-button" type="button" class="flex h-10 w-10 items-center justify-center rounded-lg hover:bg-white/10 md:hidden" aria-label="Buka menu" aria-expanded="false" aria-controls="mobile-menu">
                        <i class="fas fa-bars text-lg"></i>
                    </button>
                </div>
            @elseif ($user)
                <a href="{{ route('logout') }}" class="text-sm font-medium text-white/80 hover:text-white"><i class="fas fa-right-from-bracket mr-1"></i> Keluar</a>
            @else
                <div class="flex items-center gap-2 text-sm">
                    <a href="{{ route('login') }}" class="rounded-lg px-3 py-2 font-medium text-white/80 hover:text-white {{ request()->routeIs('login') ? 'text-gold-400' : '' }}">Masuk</a>
                    <a href="{{ route('register.student') }}" class="rounded-lg bg-gold-500 px-4 py-2 font-semibold text-navy-900 hover:bg-gold-400">Daftar</a>
                </div>
            @endif
        </div>

        @if ($user && ! $isOnboarding)
            <nav id="mobile-menu" class="hidden border-t border-white/10 px-4 pb-3 pt-2 md:hidden" aria-label="Navigasi utama">
                @foreach ($navItems as $item)
                    <a href="{{ route($item['route']) }}" class="nav-link flex items-center gap-3 {{ request()->routeIs($item['route']) ? 'active' : '' }}">
                        <i class="fas {{ $item['icon'] }} w-5 text-center"></i>{{ $item['label'] }}
                    </a>
                @endforeach
                <a href="{{ route('logout') }}" class="nav-link flex items-center gap-3"><i class="fas fa-right-from-bracket w-5 text-center"></i>Keluar</a>
            </nav>
        @endif
    </header>

    <main class="flex-grow flex flex-col px-4 py-6 sm:py-10 @yield('main_class', 'items-center')">
        @yield('content')
    </main>

    <footer class="bg-navy-900 text-white/70 text-sm">
        <div class="mx-auto flex max-w-6xl flex-col gap-2 px-4 py-4 sm:flex-row sm:items-center sm:justify-between">
            <p>Khusus untuk civitas aktif Universitas Udayana</p>
            <p>&copy; {{ date('Y') }} I Match U</p>
        </div>
    </footer>

    @stack('scripts')

    @auth
    <script>
        // Inisial nama untuk avatar yang dirender lewat JS (setara komponen Blade avatar).
        window.avatarHtml = function (name, sizeClass = 'w-10 h-10 text-sm') {
            const initial = (name || '?').trim().charAt(0).toUpperCase();
            const div = document.createElement('div');
            div.textContent = initial;
            return `<span class="${sizeClass} rounded-full flex-shrink-0 inline-flex items-center justify-center bg-navy-100 text-navy-800 font-semibold">${div.innerHTML}</span>`;
        };

        document.addEventListener('DOMContentLoaded', function () {
            const mobileButton = document.getElementById('mobile-menu-button');
            const mobileMenu = document.getElementById('mobile-menu');
            if (mobileButton && mobileMenu) {
                mobileButton.addEventListener('click', function () {
                    const open = mobileMenu.classList.toggle('hidden') === false;
                    mobileButton.setAttribute('aria-expanded', open);
                });
            }

            const notificationBell = document.getElementById('notification-bell');
            if (!notificationBell) return;

            const notificationDropdown = document.getElementById('notification-dropdown');
            const notificationDot = document.getElementById('notification-dot');
            const notificationCount = document.getElementById('notification-count');
            const notificationsList = document.getElementById('notifications-list');
            const markAllReadBtn = document.getElementById('mark-all-read');
            const csrf = document.querySelector('meta[name="csrf-token"]').content;

            let dropdownOpen = false;

            notificationBell.addEventListener('click', function (e) {
                e.stopPropagation();
                dropdownOpen = !dropdownOpen;
                notificationDropdown.classList.toggle('hidden', !dropdownOpen);
                if (dropdownOpen) loadNotifications();
            });

            document.addEventListener('click', function () {
                if (dropdownOpen) {
                    notificationDropdown.classList.add('hidden');
                    dropdownOpen = false;
                }
            });

            notificationDropdown.addEventListener('click', e => e.stopPropagation());
            markAllReadBtn.addEventListener('click', markAllNotificationsAsRead);

            updateNotificationCount();
            setInterval(updateNotificationCount, 30000);

            async function updateNotificationCount() {
                try {
                    const response = await fetch('{{ route('notifications.count') }}');
                    const data = await response.json();
                    if (data.status === 'success') {
                        const count = data.unread_count;
                        notificationDot.classList.toggle('hidden', count === 0);
                        notificationCount.textContent = count > 9 ? '9+' : count;
                    }
                } catch (error) {
                    console.error('Gagal memuat jumlah notifikasi:', error);
                }
            }

            async function loadNotifications() {
                try {
                    const response = await fetch('{{ route('notifications.api') }}');
                    const data = await response.json();
                    if (data.status === 'success') {
                        renderNotifications(data.notifications);
                        updateNotificationCount();
                    }
                } catch (error) {
                    console.error('Gagal memuat notifikasi:', error);
                    notificationsList.innerHTML = '<p class="p-6 text-center text-sm text-red-600">Gagal memuat notifikasi.</p>';
                }
            }

            // Teks notifikasi berisi nama pengguna, jadi wajib di-escape sebelum masuk innerHTML.
            function escapeHtml(text) {
                const div = document.createElement('div');
                div.textContent = text ?? '';
                return div.innerHTML.replace(/"/g, '&quot;');
            }

            function renderNotifications(notifications) {
                if (notifications.length === 0) {
                    notificationsList.innerHTML = `
                        <div class="p-8 text-center text-gray-500">
                            <i class="fas fa-bell-slash mb-2 text-2xl text-navy-100"></i>
                            <p class="text-sm">Belum ada notifikasi.</p>
                        </div>`;
                    return;
                }

                notificationsList.innerHTML = notifications.map(notification => {
                    const isUnread = !notification.is_read;
                    let actionButton = '';
                    if (notification.type === 'like_received' && isUnread && notification.from_user) {
                        actionButton = `<button type="button" onclick="likeBack(${notification.id}, ${notification.from_user.id})" class="mt-2 inline-flex items-center gap-1 rounded-full bg-gold-500 px-3 py-1 text-xs font-semibold text-navy-900 hover:bg-gold-400"><i class="fas fa-heart"></i> Suka balik</button>`;
                    } else if (notification.type === 'match_created' && notification.data?.other_user) {
                        actionButton = `<button type="button" onclick="goToChat(${notification.data.other_user.id})" class="mt-2 inline-flex items-center gap-1 rounded-full bg-navy-800 px-3 py-1 text-xs font-semibold text-white hover:bg-navy-700"><i class="fas fa-comment"></i> Mulai chat</button>`;
                    }

                    return `
                        <div class="flex items-start gap-3 border-b border-gray-50 px-4 py-3 transition hover:bg-navy-50 ${isUnread ? 'bg-gold-100/60' : ''}" onclick="markAsRead(${notification.id})">
                            ${avatarHtml(notification.from_user?.name)}
                            <div class="min-w-0 flex-1">
                                <p class="break-words text-sm font-semibold text-navy-900">${escapeHtml(notification.title)}</p>
                                <p class="mt-0.5 text-sm text-gray-600">${escapeHtml(notification.message)}</p>
                                <p class="mt-1 text-xs text-gray-400">${escapeHtml(notification.created_at)}</p>
                                ${actionButton}
                            </div>
                            ${isUnread ? '<span class="mt-1.5 h-2 w-2 flex-shrink-0 rounded-full bg-gold-500" aria-label="Belum dibaca"></span>' : ''}
                        </div>`;
                }).join('');
            }

            window.markAsRead = async function (notificationId) {
                try {
                    await fetch('{{ route('notifications.mark_read') }}', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
                        body: JSON.stringify({ notification_id: notificationId }),
                    });
                    updateNotificationCount();
                } catch (error) {
                    console.error('Gagal menandai notifikasi:', error);
                }
            };

            window.likeBack = async function (notificationId, fromUserId) {
                try {
                    const response = await fetch('{{ route('notifications.like_back') }}', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
                        body: JSON.stringify({ notification_id: notificationId, from_user_id: fromUserId }),
                    });
                    const data = await response.json();
                    if (data.status === 'success') {
                        alert(data.matched ? 'Kalian match! Yuk mulai chat.' : 'Suka balik terkirim!');
                        loadNotifications();
                    }
                } catch (error) {
                    console.error('Gagal menyukai balik:', error);
                }
            };

            window.goToChat = function (userId) {
                window.location.href = `{{ route('chat.personal') }}?with=${userId}`;
            };

            async function markAllNotificationsAsRead() {
                try {
                    await fetch('{{ route('notifications.mark_all_read') }}', {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': csrf },
                    });
                    loadNotifications();
                } catch (error) {
                    console.error('Gagal menandai semua notifikasi:', error);
                }
            }
        });
    </script>
    @endauth
</body>
</html>
