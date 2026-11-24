<?php

use App\Models\User;
use App\Models\UserInteraction;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

it('mewajibkan email saat registrasi alumni', function () {
    $this->post('/register/alumni', [
        'full_name' => 'Alumni',
        'username' => 'alumni1',
        'password' => 'rahasia123',
        'password_confirmation' => 'rahasia123',
        'verification_doc' => UploadedFile::fake()->image('ktm.jpg'),
    ])->assertSessionHasErrors('email');

    expect(User::count())->toBe(0);
});

it('menyimpan dokumen verifikasi alumni di disk privat dengan path yang benar', function () {
    Storage::fake('local');

    $this->post('/register/alumni', [
        'full_name' => 'Alumni',
        'username' => 'alumni1',
        'email' => 'alumni@example.com',
        'password' => 'rahasia123',
        'password_confirmation' => 'rahasia123',
        'verification_doc' => UploadedFile::fake()->image('ktm.jpg'),
    ])->assertRedirect(route('alumni.verification.pending'));

    $user = User::firstWhere('email', 'alumni@example.com');
    expect($user->is_verified)->toBeFalse();
    Storage::disk('local')->assertExists($user->verification_doc_path);
});

it('tidak melempar pengguna tanpa deskripsi ke setup profil saat login', function () {
    $user = User::factory()->create(['description' => null, 'password' => bcrypt('rahasia123')]);

    $this->post('/login', ['email' => $user->email, 'password' => 'rahasia123'])
        ->assertRedirect(route('home'));
});

it('hanya menampilkan kandidat dengan kategori yang sama dan belum di-swipe', function () {
    $me = User::factory()->create(['match_categories' => ['pkm', 'kkn']]);
    $match = User::factory()->create(['match_categories' => ['kkn']]);
    $swiped = User::factory()->create(['match_categories' => ['pkm']]);
    User::factory()->create(['match_categories' => ['jobs']]);
    UserInteraction::create(['user_id' => $me->id, 'target_user_id' => $swiped->id, 'action_type' => 'dislike']);

    $users = $this->actingAs($me)->get('/find-people')->assertOk()->viewData('usersToDisplay');

    expect($users->pluck('id')->all())->toBe([$match->id]);
});
