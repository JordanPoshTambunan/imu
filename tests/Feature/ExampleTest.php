<?php

use App\Models\User;

it('mengarahkan tamu dari / ke halaman masuk', function () {
    $this->get('/')->assertRedirect(route('login'));
});

it('mengarahkan pengguna yang login dari / ke beranda', function () {
    $this->actingAs(User::factory()->create())->get('/')->assertRedirect(route('home'));
});
