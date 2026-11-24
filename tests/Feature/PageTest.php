<?php

use App\Models\ChatGroup;
use App\Models\Message;
use App\Models\User;
use App\Models\UserMatch;

it('merender setiap halaman di dalam layout', function (string $uri, bool $auth) {
    if ($auth) {
        $this->actingAs(User::factory()->create());
    }

    $html = $this->get($uri)->assertOk()->getContent();

    // Section yang lupa ditutup @endsection tercetak sebelum <!DOCTYPE>.
    expect(ltrim($html))->toStartWith('<!DOCTYPE html>');
})->with([
    ['/login', false],
    ['/register/student', false],
    ['/register/alumni', false],
    ['/alumni/verification-pending', false],
    ['/home', true],
    ['/find-people', true],
    ['/chat/personal', true],
    ['/community', true],
    ['/profile', true],
    ['/profile/setup', true],
    ['/match/setup', true],
    ['/notifications/page', true],
]);

it('menandai pesan masuk sebagai dibaca saat di-poll', function () {
    [$me, $other] = User::factory()->count(2)->create();
    $match = UserMatch::create(['user1_id' => min($me->id, $other->id), 'user2_id' => max($me->id, $other->id)]);
    $message = Message::create(['sender_id' => $other->id, 'receiver_id' => $me->id, 'match_id' => $match->id, 'message_content' => 'Halo']);

    $this->actingAs($me)
        ->getJson('/chat/messages?match_id='.$match->id)
        ->assertOk()
        ->assertJsonPath('messages.0.timestamp', $message->created_at->format('H:i'));

    expect($message->fresh()->read_at)->not->toBeNull();
});

it('menampilkan form posting untuk moderator grup', function () {
    $creator = User::factory()->create();
    $moderator = User::factory()->create();
    $group = ChatGroup::create(['name' => 'Info', 'creator_id' => $creator->id, 'moderator_id' => $moderator->id, 'is_approved' => true]);

    $this->actingAs($moderator)->get('/community?group='.$group->id)->assertSee('id="post-form"', false);
    $this->actingAs(User::factory()->create())->get('/community?group='.$group->id)->assertDontSee('id="post-form"', false);
});
