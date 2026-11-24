<?php

use App\Models\Notification;
use App\Models\User;

it('menampilkan halaman notifikasi dengan nama yang di-escape', function () {
    $me = User::factory()->create();
    $attacker = User::factory()->create(['full_name' => '<img src=x onerror=alert(1)>']);
    Notification::createLikeNotification($me->id, $attacker->id);

    $this->actingAs($me)
        ->get('/notifications/page')
        ->assertOk()
        ->assertDontSee('<img src=x onerror', false)
        ->assertSee('&lt;img src=x onerror', false);
});
