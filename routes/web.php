<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\CommunityController;
use App\Http\Controllers\NotificationController;

/*
|--------------------------------------------------------------------------
| Halaman Publik
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return view('welcome');
})->name('welcome');

/*
|--------------------------------------------------------------------------
| Autentikasi
|--------------------------------------------------------------------------
| Rate limit dipasang pada endpoint yang menerima kredensial atau membuat
| akun, untuk mencegah brute force dan pendaftaran massal.
*/

// Registrasi mahasiswa
Route::get('/register/student', [AuthController::class, 'showRegisterStudentForm'])
    ->name('register.student');
Route::post('/register/student', [AuthController::class, 'registerStudent'])
    ->middleware('throttle:6,1')
    ->name('register.store.student');

// Registrasi alumni
Route::get('/register/alumni', [AuthController::class, 'showRegisterAlumniForm'])
    ->name('register.alumni');
Route::post('/register/alumni', [AuthController::class, 'registerAlumni'])
    ->middleware('throttle:6,1')
    ->name('register.store.alumni');

// Login
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login'])
    ->middleware('throttle:5,1')
    ->name('login.store');

// Logout
Route::get('/logout', [AuthController::class, 'logout'])->name('logout');

// Halaman tunggu verifikasi alumni (landing setelah mendaftar, tidak perlu login)
Route::get('/alumni/verification-pending', [AuthController::class, 'showAlumniVerificationPendingPage'])
    ->name('alumni.verification.pending');

/*
|--------------------------------------------------------------------------
| Onboarding
|--------------------------------------------------------------------------
| Harus dapat diakses meski profil belum lengkap, karena justru di sinilah
| profil dilengkapi. Karena itu tidak memakai middleware 'profile_complete'.
*/

Route::middleware('auth')->group(function () {
    Route::get('/profile/setup', [AuthController::class, 'showProfileSetupForm'])
        ->name('profile.setup');
    Route::post('/profile/store', [ProfileController::class, 'storeBasicProfile'])
        ->name('profile.store');

    Route::get('/match/setup', [AuthController::class, 'showMatchSetupForm'])
        ->name('match.setup');
    Route::post('/match/store-categories', [ProfileController::class, 'storeMatchCategories'])
        ->name('profile.store_match_categories');
});

/*
|--------------------------------------------------------------------------
| Aplikasi Utama
|--------------------------------------------------------------------------
| Memerlukan login DAN profil yang sudah lengkap.
*/

Route::middleware(['auth', 'profile_complete'])->group(function () {

    Route::get('/home', [AuthController::class, 'showHomePage'])->name('home');
    Route::get('/find-people', [AuthController::class, 'showFindingPeoplePage'])->name('find.people');
    Route::get('/profile', [AuthController::class, 'showUserProfilePage'])->name('user.profile');

    // Like / dislike
    Route::post('/user/interact', [ProfileController::class, 'storeInteraction'])
        ->middleware('throttle:60,1')
        ->name('user.interact');

    /*
    | Chat personal — hanya terbuka antar pengguna yang sudah match.
    */
    Route::get('/chat/personal', [ChatController::class, 'showPersonalChatPage'])->name('chat.personal');
    Route::get('/chat/messages', [ChatController::class, 'getMessages'])->name('chat.get_messages');
    Route::post('/chat/send-message', [ChatController::class, 'sendMessage'])
        ->middleware('throttle:30,1')
        ->name('chat.send_message');

    /*
    | Community — group chat beserta reaction dan komentar.
    */
    Route::get('/community', [CommunityController::class, 'showCommunityChatPage'])->name('community');
    Route::get('/community/messages', [CommunityController::class, 'getMessages'])->name('community.get_messages');
    Route::get('/community/stats', [CommunityController::class, 'getStats'])->name('community.stats');
    Route::get('/community/comments', [CommunityController::class, 'loadComments'])->name('community.load_comments');

    Route::post('/community/send-message', [CommunityController::class, 'sendGroupMessage'])
        ->middleware('throttle:30,1')
        ->name('community.send_message');
    Route::post('/community/reactions', [CommunityController::class, 'addReaction'])
        ->middleware('throttle:60,1')
        ->name('community.add_reaction');
    Route::post('/community/comments', [CommunityController::class, 'addComment'])
        ->middleware('throttle:30,1')
        ->name('community.add_comment');

    // TODO: route DELETE /community/attachments/{messageId} dihapus sampai
    // CommunityController::deleteAttachment benar-benar dibuat.

    /*
    | Notifikasi
    */
    Route::get('/notifications/page', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications', [NotificationController::class, 'getNotifications'])->name('notifications.api');
    Route::get('/notifications/count', [NotificationController::class, 'getUnreadCount'])->name('notifications.count');

    Route::post('/notifications/mark-read', [NotificationController::class, 'markAsRead'])
        ->name('notifications.mark_read');
    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllAsRead'])
        ->name('notifications.mark_all_read');
    Route::post('/notifications/like-back', [NotificationController::class, 'likeBack'])
        ->middleware('throttle:60,1')
        ->name('notifications.like_back');
});
