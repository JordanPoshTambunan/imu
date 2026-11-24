<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * Pengguna IMU.
 *
 * Catatan: tabel ini memakai kolom `full_name`, BUKAN `name`.
 */
class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;

    protected $fillable = [
        'full_name',
        'username',
        'email',
        'password',
        'prodi',
        'fakultas',
        'gender',
        'description',
        'interests',
        'role',
        'profile_picture',
        'verification_doc_path',
        'is_verified',
        'match_categories',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * `interests` dan `match_categories` disimpan sebagai JSON dan ditangani
     * SEPENUHNYA oleh cast di bawah ini.
     *
     * JANGAN menambahkan accessor atau mutator yang melakukan json_encode lagi —
     * itu pernah menyebabkan double-encoding yang sulit ditelusuri.
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'interests' => 'array',
            'match_categories' => 'array',
            'is_verified' => 'boolean',
        ];
    }

    // =====================================================
    // RELASI
    // =====================================================

    /**
     * Like/dislike yang DIBERIKAN pengguna ini.
     */
    public function interactions(): HasMany
    {
        return $this->hasMany(UserInteraction::class, 'user_id');
    }

    /**
     * Like/dislike yang DITERIMA pengguna ini.
     */
    public function receivedInteractions(): HasMany
    {
        return $this->hasMany(UserInteraction::class, 'target_user_id');
    }

    /**
     * Match di mana pengguna ini menempati kolom user1_id.
     *
     * Karena konvensi user1_id < user2_id, sebuah match bisa jatuh di salah satu
     * dari dua kolom. Untuk mengambil SEMUA match pengguna, pakai matchesQuery().
     */
    public function matchesAsUser1(): HasMany
    {
        return $this->hasMany(UserMatch::class, 'user1_id');
    }

    /**
     * Match di mana pengguna ini menempati kolom user2_id.
     */
    public function matchesAsUser2(): HasMany
    {
        return $this->hasMany(UserMatch::class, 'user2_id');
    }

    /**
     * Semua match pengguna ini, dari kedua kolom sekaligus.
     *
     * Ini mengembalikan Builder, bukan relasi Eloquent, karena satu relasi
     * tidak bisa menjangkau dua kolom foreign key.
     *
     *     $user->matchesQuery()->with(['user1', 'user2'])->latest()->get();
     */
    public function matchesQuery(): Builder
    {
        return UserMatch::query()->where(function (Builder $query) {
            $query->where('user1_id', $this->id)
                  ->orWhere('user2_id', $this->id);
        });
    }

    /**
     * Pesan chat personal yang dikirim pengguna ini.
     */
    public function sentMessages(): HasMany
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    /**
     * Pesan chat personal yang diterima pengguna ini.
     */
    public function receivedMessages(): HasMany
    {
        return $this->hasMany(Message::class, 'receiver_id');
    }

    /**
     * Notifikasi IMU yang diterima pengguna ini.
     *
     * Sengaja TIDAK dinamai notifications() — nama itu sudah dipakai trait
     * Notifiable bawaan Laravel, dan menimpanya akan merusak sistem notifikasi
     * framework kalau nanti dipakai.
     */
    public function notificationsReceived(): HasMany
    {
        return $this->hasMany(Notification::class, 'user_id');
    }

    /**
     * Pesan yang dikirim pengguna ini di grup komunitas.
     */
    public function groupMessages(): HasMany
    {
        return $this->hasMany(GroupMessage::class, 'sender_id');
    }

    /**
     * Grup komunitas yang dibuat pengguna ini.
     */
    public function createdGroups(): HasMany
    {
        return $this->hasMany(ChatGroup::class, 'creator_id');
    }

    // =====================================================
    // SCOPE
    // =====================================================

    /**
     * Pengguna yang sudah menyelesaikan onboarding dan layak tampil di
     * halaman Finding People.
     */
    public function scopeDiscoverable(Builder $query): Builder
    {
        return $query->where('is_verified', true)
                     ->whereNotNull('prodi')
                     ->whereNotNull('fakultas')
                     ->whereNotNull('gender')
                     ->whereNotNull('interests')
                     ->whereNotNull('match_categories');
    }

    // =====================================================
    // HELPER
    // =====================================================

    public function hasInterest(string $interest): bool
    {
        return is_array($this->interests) && in_array($interest, $this->interests, true);
    }

    public function hasMatchCategory(string $category): bool
    {
        return is_array($this->match_categories)
            && in_array($category, $this->match_categories, true);
    }

    /**
     * Apakah pengguna ini punya setidaknya satu kategori yang sama.
     */
    public function hasAnyMatchCategory(array $categories): bool
    {
        return is_array($this->match_categories)
            && ! empty(array_intersect($this->match_categories, $categories));
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isAlumni(): bool
    {
        return $this->role === 'alumni';
    }
}
