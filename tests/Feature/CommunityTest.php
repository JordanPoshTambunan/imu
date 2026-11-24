<?php

use App\Models\ChatGroup;
use App\Models\GroupMessage;
use App\Models\PostComment;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

function makeGroupMessage(bool $approved = true): GroupMessage
{
    $creator = User::factory()->create();
    $group = ChatGroup::create([
        'name' => 'Grup '.uniqid(),
        'creator_id' => $creator->id,
        'is_approved' => $approved,
    ]);

    return GroupMessage::create([
        'group_id' => $group->id,
        'sender_id' => $creator->id,
        'message_content' => 'Halo',
    ]);
}

it('menolak reaction, komentar, dan load komentar pada grup yang belum disetujui', function () {
    $message = makeGroupMessage(approved: false);
    $this->actingAs(User::factory()->create());

    $this->postJson('/community/reactions', ['message_id' => $message->id, 'reaction_type' => 'like'])->assertNotFound();
    $this->postJson('/community/comments', ['message_id' => $message->id, 'comment_content' => 'hai'])->assertNotFound();
    $this->getJson('/community/comments?message_id='.$message->id)->assertNotFound();

    expect(PostComment::count())->toBe(0);
});

it('mengizinkan reaction dan komentar pada grup yang disetujui', function () {
    $message = makeGroupMessage();
    $this->actingAs(User::factory()->create());

    $this->postJson('/community/reactions', ['message_id' => $message->id, 'reaction_type' => 'like'])->assertOk();
    $this->postJson('/community/comments', ['message_id' => $message->id, 'comment_content' => 'hai'])->assertOk();
});

it('menolak balasan ke komentar milik pesan lain', function () {
    $message = makeGroupMessage();
    $other = makeGroupMessage();
    $user = User::factory()->create();
    $foreignComment = PostComment::create(['message_id' => $other->id, 'user_id' => $user->id, 'comment_content' => 'x']);

    $this->actingAs($user)
        ->postJson('/community/comments', ['message_id' => $message->id, 'comment_content' => 'balas', 'parent_id' => $foreignComment->id])
        ->assertStatus(422);
});

it('mengembalikan 422, bukan 500, untuk input tidak valid', function () {
    $this->actingAs(User::factory()->create())
        ->postJson('/community/reactions', ['message_id' => 999, 'reaction_type' => 'bom'])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['message_id', 'reaction_type']);
});

it('menyimpan pesan apa adanya tanpa escape HTML dan tetap mempertahankan baris baru', function () {
    $message = makeGroupMessage();
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->postJson('/community/send-message', ['group_id' => $message->group_id, 'message_content' => "rapat \"besok\"\nbaris dua"])
        ->assertOk();

    expect(GroupMessage::latest('id')->first()->message_content)->toBe("rapat \"besok\"\nbaris dua");
});

it('menentukan ekstensi lampiran dari isi file, bukan dari nama kiriman', function () {
    Storage::fake('public');
    $message = makeGroupMessage();
    $admin = User::factory()->create(['role' => 'admin']);

    // Gambar PNG asli yang diberi nama .html (polyglot PNG+HTML dulu tersimpan sebagai .html).
    // UploadedFile::fake() tidak bisa dipakai langsung karena ia menebak MIME dari nama file.
    $png = UploadedFile::fake()->image('a.png'); // simpan referensi, file tmp-nya hilang saat objek ini dibuang
    $send = fn (string $name) => $this->actingAs($admin)->post(
        '/community/send-message',
        ['group_id' => $message->group_id, 'attachment' => new UploadedFile($png->getPathname(), $name, null, null, true)],
        ['Accept' => 'application/json'],
    );

    // Nama .php sudah ditolak oleh validasi bawaan Laravel.
    $send('shell.php')->assertStatus(422);
    $send('halaman.html')->assertOk();

    $stored = Storage::disk('public')->allFiles('community_uploads');
    expect($stored)->toHaveCount(1)
        ->and($stored[0])->toEndWith('.png');
});

it('menolak lampiran dengan tipe yang tidak diizinkan', function () {
    $message = makeGroupMessage();
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->post('/community/send-message', [
            'group_id' => $message->group_id,
            'attachment' => UploadedFile::fake()->createWithContent('x.html', '<script>alert(1)</script>'),
        ], ['Accept' => 'application/json'])
        ->assertStatus(422);
});

it('mengenali link post Instagram dan menormalkannya', function (string $content, ?string $expected) {
    expect((new GroupMessage(['message_content' => $content]))->instagramUrl())->toBe($expected);
})->with([
    ['Info terbaru https://www.instagram.com/p/DAbc123_-x/?igsh=xyz', 'https://www.instagram.com/p/DAbc123_-x/'],
    ['http://instagram.com/reel/Cq9Z', 'https://www.instagram.com/reel/Cq9Z/'],
    ['https://www.instagram.com/univ.udayana/p/DgERsxVk4LS/', 'https://www.instagram.com/p/DgERsxVk4LS/'],
    ['Profil saja https://www.instagram.com/univ.udayana/', null],
    ['https://evil.com/instagram.com/p/abc', null],
    ['Tanpa link', null],
]);

it('menampilkan embed Instagram di halaman komunitas', function () {
    $message = makeGroupMessage();
    $message->update(['message_content' => 'Cek https://www.instagram.com/p/DAbc123/']);

    $this->actingAs(User::factory()->create())
        ->get('/community?group='.$message->group_id)
        ->assertSee('data-instgrm-permalink="https://www.instagram.com/p/DAbc123/"', false)
        ->assertSee('instagram.com/embed.js', false);
});

it('menyembunyikan link Instagram dari teks saat ditampilkan sebagai embed', function (string $content, string $expected) {
    expect((new GroupMessage(['message_content' => $content]))->contentWithoutInstagramUrl())->toBe($expected);
})->with([
    ["Info terbaru dari kampus\nhttps://www.instagram.com/univ.udayana/p/DgERsxVk4LS/", 'Info terbaru dari kampus'],
    ['https://www.instagram.com/p/DAbc123/?igsh=xyz', ''],
    ['Tanpa link Instagram https://unud.ac.id', 'Tanpa link Instagram https://unud.ac.id'],
]);

it('membuat komunitas Menfess Unud sekali saja', function () {
    User::factory()->create(['role' => 'admin']);

    $this->seed(Database\Seeders\MenfessGroupSeeder::class);
    $this->seed(Database\Seeders\MenfessGroupSeeder::class);

    expect(ChatGroup::where('name', 'Menfess Unud')->where('is_approved', true)->count())->toBe(1);
});
