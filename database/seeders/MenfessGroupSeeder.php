<?php

namespace Database\Seeders;

use App\Models\ChatGroup;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Komunitas berisi kiriman @unudfess yang dikurasi moderator.
 *
 * Aman dijalankan berulang dan di produksi (tidak membuat data contoh):
 *     php artisan db:seed --class=MenfessGroupSeeder
 */
class MenfessGroupSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('role', 'admin')->first();

        if (! $admin) {
            $this->command?->warn('Belum ada pengguna admin; komunitas Menfess Unud tidak dibuat.');

            return;
        }

        ChatGroup::firstOrCreate(
            ['name' => 'Menfess Unud'],
            [
                'description' => 'Kiriman anonim dari Instagram @unudfess, dipilih oleh moderator. Isi menfess adalah pendapat pengirim, bukan sikap IMU.',
                'creator_id' => $admin->id,
                'is_approved' => true,
            ],
        );
    }
}
