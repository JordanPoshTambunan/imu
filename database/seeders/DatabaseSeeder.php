<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        // ❌ COMMENTED OUT: Ini menggunakan factory yang tidak sesuai dengan struktur User model Anda
        // User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);

        // ✅ TAMBAHAN: Call ChatGroupSeeder
        $this->call([
            ChatGroupSeeder::class,
            MenfessGroupSeeder::class, // setelah ChatGroupSeeder, yang membuat admin
        ]);

        // ℹ️ INFO: ChatGroupSeeder akan otomatis membuat admin user jika belum ada
        // Jadi tidak perlu factory di sini
    }
}