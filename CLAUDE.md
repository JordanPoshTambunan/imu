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

### SQLite tidak mendukung `whereJsonContains`

Inilah batasan terbesar project ini saat ini. `AuthController::showFindingPeoplePage`
punya jalur fallback yang **menarik seluruh kandidat pengguna ke memori PHP** lalu
memfilter dengan `array_intersect`. Jalan untuk 50 pengguna, mati untuk 5.000.

Kalau menyentuh fitur matching, pertimbangkan memperbaiki akarnya: normalisasi
`match_categories` menjadi tabel pivot `user_match_categories`, sehingga filter
menjadi satu `JOIN` terindeks. Ini pekerjaan yang sudah direncanakan.

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

2. **Tidak ada rate limiting.** Login bisa di-brute force tanpa batas.

3. **Otorisasi di endpoint community.** `addReaction`, `addComment`, dan
   `loadComments` tidak memeriksa apakah pesan berada di grup yang disetujui.

4. **Verifikasi email belum ada.** Mahasiswa otomatis `is_verified = true` hanya
   berdasarkan regex domain email. `MustVerifyEmail` sudah di-import di model
   `User` tapi tidak pernah diimplementasikan. Reset password juga belum ada
   meski tabelnya sudah dibuat.

5. **Performa matching** — lihat §5.

6. **Logika bisnis menumpuk di controller.** `CommunityController` 691 baris.
   Belum ada service layer.

7. **Belum ada test.** Hanya dua stub bawaan Laravel.

8. **Chat belum real-time.** Masih polling lewat `/chat/messages`;
   `BROADCAST_CONNECTION=log`.

9. **`deleteAttachment` belum dibuat.** Route-nya sudah dihapus sampai methodnya ada.
