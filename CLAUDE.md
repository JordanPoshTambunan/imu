# I Match U (IMU) — Panduan Kerja

Dokumen ini dibaca Claude Code setiap sesi. Tujuannya supaya semua orang yang
mengerjakan project ini — manusia maupun AI — punya konteks yang sama dan tidak
mengulang kesalahan yang sudah pernah dibereskan.

Bahasa kode, komentar, dan pesan error untuk pengguna: **Bahasa Indonesia**.
Nama variabel, method, kolom database: **Bahasa Inggris**.

---

## 1. Apa ini

Platform matching untuk ekosistem Universitas Udayana. Bukan aplikasi kencan —
ini jaringan kampus dengan segmentasi niat: pengguna memilih apa yang mereka cari,
lalu hanya dipertemukan dengan orang yang mencari hal serupa.

**Enam kategori match** (`friends`, `jobs`, `committee`, `pkm`, `kkn`, `contest`).
Seorang pengguna bisa memilih lebih dari satu.

**Empat peran** (`mahasiswa`, `alumni`, `tenaga_pendidik`, `admin`).

### Alur pengguna

```
register (mahasiswa / alumni)
  → setup profil      (prodi, fakultas, gender, deskripsi, 1–3 interests)
  → setup kategori    (minimal 1 dari enam kategori di atas)
  → home feed
  → finding people    (like / dislike)
  → match             (terjadi saat like saling balas)
  → chat personal     (hanya terbuka setelah match)
  → community         (group chat: reaction, komentar, lampiran, moderator)
```

Notifikasi memayungi semuanya: `like_received`, `match_created`, `message_received`.

---

## 2. Tumpukan teknologi

| | |
|---|---|
| Framework | Laravel 12 |
| PHP | ^8.2 |
| Frontend | Blade + Tailwind CSS 4 via Vite 6 |
| Database | SQLite saat dev (lihat peringatan di §5) |
| Auth | Session-based. Sanctum terpasang tapi **belum dipakai** |
| Test | Pest 3 |
| Lint | Laravel Pint |

Belum ada `routes/api.php`. Semua route ada di `routes/web.php`.

---

## 3. Menjalankan secara lokal

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate
npm run dev          # terminal terpisah
php artisan serve
```

Ekstensi PHP `pdo_sqlite` harus aktif (di Laragon: Menu → PHP → Extensions).

Test (Pest, SQLite in-memory, `RefreshDatabase` aktif untuk `tests/Feature`;
`UserFactory` default = mahasiswa yang sudah selesai onboarding):

```bash
vendor/bin/pest
```

Upload file butuh symlink storage:

```bash
php artisan storage:link
```

---

## 4. Peta kode

```
app/Http/Controllers/
  AuthController.php          register, login, logout, + beberapa halaman statis
  ProfileController.php       simpan profil, simpan kategori, like/dislike + matching
  ChatController.php          chat personal (1-on-1, hanya antar match)
  CommunityController.php     group chat, reaction, komentar, upload
  NotificationController.php  notifikasi

app/Models/
  User.php              pengguna
  UserInteraction.php   satu baris per like/dislike
  UserMatch.php         tabel `matches` — lihat konvensi di §5
  Message.php           pesan chat personal
  ChatGroup.php         grup komunitas
  ChatGroupMember.php   keanggotaan grup
  GroupMessage.php      pesan di grup
  PostReaction.php      reaction pada pesan grup
  PostComment.php       komentar pada pesan grup (mendukung balasan via parent_id)
  Notification.php      notifikasi
  PushSubscription.php  langganan push — tabel ada, fitur belum dibangun
```

Catatan penting: **model `User` memetakan tabel `users` yang memakai `full_name`,
bukan `name`.** Jangan pernah menulis `$user->name`.

---

## 5. Hal yang mudah salah

Baca bagian ini sebelum menyentuh kode. Semua poin di bawah pernah menjadi bug nyata.

### Pasangan match harus dinormalisasi

Tabel `matches` punya unique constraint `(user1_id, user2_id)`. Supaya satu
pasangan tidak tersimpan dua kali, **`user1_id` selalu yang lebih kecil**:

```php
$user1 = min($currentUser->id, $targetUserId);
$user2 = max($currentUser->id, $targetUserId);
```

Melanggar ini menghasilkan match duplikat yang lolos dari constraint.

### `match_categories` dan `interests` disimpan sebagai JSON

Keduanya di-cast `array` di model. **Jangan menambahkan accessor atau mutator
yang melakukan `json_encode` lagi** — itu pernah menyebabkan double-encoding dan
butuh waktu lama untuk ditelusuri. Cast saja sudah cukup.

### `whereJsonContains` jalan di SQLite

Laravel 12 mendukung `whereJsonContains` di SQLite, MySQL, dan PostgreSQL. Jangan
membuat jalur fallback yang menarik semua pengguna ke memori PHP — dulu pernah ada
dan sudah dihapus. Filter JSON tetap full scan (tidak terindeks); kalau suatu saat
pengguna mencapai puluhan ribu, normalisasi `match_categories` menjadi tabel pivot
`user_match_categories`.

### Data pengguna di JavaScript wajib di-escape

Blade `{{ }}` aman, tetapi template string JS yang masuk ke `innerHTML` tidak.
Setiap nilai dari server (nama, judul notifikasi, isi pesan) harus lewat
`escapeHtml()`. Dropdown notifikasi pernah menjadi celah XSS lewat `full_name`.

### Upload file

Ekstensi file yang disimpan diturunkan dari isi file (`guessExtension()`), bukan
`getClientOriginalExtension()`. Dokumen identitas (verifikasi alumni) disimpan di
disk privat `local`, bukan `public`; `verification_doc_path` relatif terhadap disk itu.

### Tampilan

- **Warna** hanya dari token di `layouts/app.blade.php` (`tailwind.config`):
  `navy-900 #00296b`, `navy-800 #003f88`, `navy-700 #00509d`, `gold-500 #f0bd38`,
  `gold-400 #f6cd5c`, plus tint `navy-50/100` dan `gold-100`. Jangan pakai
  `blue-*`/`orange-*` bawaan Tailwind. Teks di atas `gold-*` selalu `text-navy-900`.
- **Kelas dasar** juga di layout: `card`, `input`, `btn-primary`, `btn-accent`,
  `btn-ghost`, `chip`, `tag`, `page-title`. Pakai ini sebelum menulis kelas baru.
- **Avatar**: `<x-avatar :user="$user" />`; di JS pakai `avatarHtml(name)`.
  `via.placeholder.com` sudah mati — jangan dipakai.
- Setiap view yang memakai `@section('content')` **wajib** ditutup `@endsection`.
  Tanpa itu isinya tercetak sebelum `<!DOCTYPE>`. `tests/Feature/PageTest.php`
  menjaga ini; tambahkan halaman baru ke daftar di sana.
- Jangan menulis `<x-...>` di dalam komentar JS/HTML: Blade tetap mengompilasinya.
- Teks UI dalam Bahasa Indonesia. Zona waktu app `Asia/Makassar` (WITA), locale `id`.

### Jangan escape HTML saat menyimpan

Blade `{{ }}` sudah meng-escape otomatis saat render. Meng-escape lagi sebelum
menyimpan ke database menghasilkan double-escaping — pengguna mengetik
`rapat "besok"` dan yang tampil `rapat &quot;besok&quot;`. Simpan apa adanya.

### Otorisasi tidak boleh hanya mengandalkan `exists:`

`'message_id' => ['exists:group_messages,id']` hanya membuktikan baris itu ada,
**bukan** bahwa pengguna berhak mengaksesnya. Selalu verifikasi kepemilikan atau
keanggotaan secara eksplisit.

Pola yang benar sudah ada di `ChatController::sendMessage` — dia memastikan
pengguna adalah salah satu pihak dalam match sebelum mengizinkan apa pun. Tiru itu.

### Middleware didaftarkan di `bootstrap/app.php`

Laravel 12 tidak membaca `app/Http/Kernel.php`. File itu sudah dihapus dari project
ini. Semua alias dan konfigurasi middleware masuk ke `bootstrap/app.php`.

### Jangan memanggil controller dari controller

Kalau dua endpoint butuh logika yang sama, tarik logikanya keluar menjadi service
atau action class. Mengkonstruksi controller lain secara manual dan memalsukan
objek `Request` adalah tanda logika itu salah tempat.

---

## 6. Konvensi

**Route debug dilarang masuk ke `routes/web.php`.** Project ini pernah punya route
yang bisa membaca dan mengubah data pengguna mana pun tanpa penjagaan environment.
Kalau butuh alat debug, buat Artisan command di `app/Console/Commands/`.

**Jangan menaruh method debug di model.** Model `User` pernah berisi 270 baris
method `echo` untuk Tinker. Gunakan Tinker langsung, atau tulis test.

**Validasi di setiap endpoint**, dengan pesan error Bahasa Indonesia. Ini sudah
konsisten di seluruh project — pertahankan.

**Endpoint yang mengubah data harus punya rate limit.** Gunakan middleware
`throttle`.

**Migrasi harus punya `down()` yang benar.** Sejauh ini sudah konsisten.

---

## 7. Pekerjaan yang belum selesai

Diurutkan dari yang paling memblokir.

1. **Verifikasi alumni tidak punya jalan keluar.** Alumni mendaftar dengan
   `is_verified = false` dan diarahkan ke halaman "tunggu 1x24 jam", tetapi tidak
   ada halaman admin, Artisan command, atau endpoint mana pun untuk menyetujui
   mereka. Secara praktis seluruh peran `alumni` tidak bisa login selamanya.

2. **Verifikasi email belum ada.** Mahasiswa otomatis `is_verified = true` hanya
   berdasarkan regex domain email. `MustVerifyEmail` sudah di-import di model
   `User` tapi tidak pernah diimplementasikan. Reset password juga belum ada
   meski tabelnya sudah dibuat.

3. **Logika bisnis menumpuk di controller.** `CommunityController` ~700 baris.
   Belum ada service layer. `NotificationController::likeBack` masih memanggil
   `ProfileController` secara manual (melanggar §5).

4. **Spam notifikasi.** Like → dislike → like membuat notifikasi `like_received`
   baru setiap kali; match notification dibuat ulang setiap like setelah match.

5. **Logout lewat GET.** Seharusnya POST agar tidak bisa dipicu dari link luar.

6. **Test masih sedikit.** Ada test fitur untuk community, registrasi alumni,
   login, finding people, dan halaman notifikasi (`tests/Feature`). Chat dan
   like/match belum diuji.

7. **Chat belum real-time.** Halaman chat polling `/chat/messages` tiap 5 detik;
   `BROADCAST_CONNECTION=log`. Komentar community hanya memuat 10 komentar pertama
   (belum ada tombol "muat lagi") dan balasan bertingkat belum bisa dibuat dari UI.

10. **Belum ada upload foto profil.** Kolom `profile_picture` ada, tapi tidak ada
    form untuk mengisinya; semua avatar menampilkan inisial.

8. **`deleteAttachment` belum dibuat.** Route-nya sudah dihapus sampai methodnya ada.

9. **Format kode belum rapi.** `vendor/bin/pint --test` gagal di hampir semua file
   lama. Jalankan Pint seluruh project dalam satu commit tersendiri, jangan dicampur
   dengan perubahan logika.
