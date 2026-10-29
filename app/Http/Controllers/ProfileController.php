<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use App\Models\UserInteraction;
use App\Models\UserMatch;
use App\Models\Notification; // ✅ TAMBAHAN: Import model Notification

class ProfileController extends Controller
{
    /**
     * Menangani penyimpanan/update profil dasar pengguna (dari profile_setup).
     * Metode ini sebelumnya bernama 'store', namun diubah menjadi 'storeBasicProfile'.
     */
    public function storeBasicProfile(Request $request) // Nama metode diubah dari 'store'
    {
        $user = Auth::user(); // Dapatkan user yang sedang login

        // 1. Validasi Data Input Profil Dasar
        $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'prodi' => ['required', 'string', 'max:255'],
            'fakultas' => ['required', 'string', 'max:255'],
            'gender' => ['required', Rule::in(['Laki-laki', 'Perempuan'])], // Pastikan sesuai enum di DB
            'description' => ['nullable', 'string', 'max:1000'], // Deskripsi bisa kosong
            'interests' => ['required', 'json'], // Interests wajib ada dan format JSON
            // 'match_categories' TIDAK divalidasi di sini karena ini form Profile Setup
        ], [
            'full_name.required' => 'Nama lengkap wajib diisi.',
            'prodi.required' => 'Program studi wajib diisi.',
            'fakultas.required' => 'Fakultas wajib diisi.',
            'gender.required' => 'Jenis kelamin wajib dipilih.',
            'gender.in' => 'Pilihan jenis kelamin tidak valid.',
            'description.max' => 'Deskripsi terlalu panjang (maksimal 1000 karakter).',
            'interests.required' => 'Pilih minimal satu minat Anda.',
            'interests.json' => 'Format minat tidak valid.',
        ]);

        // Decode interests dari JSON string menjadi array PHP
        $interestsArray = json_decode($request->interests, true);

        // Validasi tambahan: Pastikan array interests tidak kosong setelah decode (maksimal 3 sudah di JS)
        if (!is_array($interestsArray) || count($interestsArray) === 0 || count($interestsArray) > 3) {
            return back()->withErrors(['interests' => 'Pilih antara 1 hingga 3 minat Anda.'])->withInput();
        }

        // 2. Update Profil Pengguna
        $user->full_name = $request->full_name;
        $user->prodi = $request->prodi;
        $user->fakultas = $request->fakultas;
        $user->gender = $request->gender;
        $user->description = $request->description;
        $user->interests = $interestsArray;
        // Kolom 'match_categories' TIDAK diupdate di sini, akan diupdate di storeMatchCategories
        $user->save(); // Simpan perubahan ke database

        // 3. Redirect Pengguna Setelah Profil Dasar Lengkap, arahkan ke halaman setup matching
        return redirect()->route('match.setup')->with('success', 'Profil Anda berhasil disimpan! Sekarang pilih kategori yang Anda cari.');
    }

    /**
     * Menangani penyimpanan kategori matching pengguna (dari match_setup).
     */
    public function storeMatchCategories(Request $request)
    {
        $user = Auth::user();

        // Validasi kategori match
        $request->validate([
            'match_categories' => ['required', 'string', 'min:1'], // Ubah dari 'json' ke 'string' untuk debugging yang lebih baik
        ], [
            'match_categories.required' => 'Pilih setidaknya satu kategori yang Anda cari.',
            'match_categories.string' => 'Format kategori tidak valid.',
            'match_categories.min' => 'Pilih setidaknya satu kategori yang Anda cari.',
        ]);

        $matchCategoriesInput = $request->input('match_categories');
        
        // Validasi dan decode JSON
        $matchCategoriesArray = json_decode($matchCategoriesInput, true);
        
        // Cek apakah JSON decode berhasil
        if (json_last_error() !== JSON_ERROR_NONE) {
            \Log::error('JSON decode error in storeMatchCategories', [
                'input' => $matchCategoriesInput,
                'json_error' => json_last_error_msg()
            ]);
            
            return back()->withErrors([
                'match_categories' => 'Format kategori tidak valid. Silakan coba lagi.'
            ])->withInput();
        }

        // Validasi tambahan: Pastikan array match_categories tidak kosong setelah decode
        if (!is_array($matchCategoriesArray) || count($matchCategoriesArray) === 0) {
            \Log::warning('Empty or invalid match categories array', [
                'decoded_array' => $matchCategoriesArray,
                'is_array' => is_array($matchCategoriesArray)
            ]);
            
            return back()->withErrors([
                'match_categories' => 'Pilih setidaknya satu kategori yang Anda cari.'
            ])->withInput();
        }

        // Validasi kategori yang diizinkan (optional security check)
        $allowedCategories = ['friends', 'jobs', 'committee', 'pkm', 'kkn', 'contest'];
        $invalidCategories = array_diff($matchCategoriesArray, $allowedCategories);
        
        if (!empty($invalidCategories)) {
            \Log::warning('Invalid categories submitted', [
                'invalid_categories' => $invalidCategories,
                'submitted_categories' => $matchCategoriesArray
            ]);
            
            return back()->withErrors([
                'match_categories' => 'Kategori yang dipilih tidak valid: ' . implode(', ', $invalidCategories)
            ])->withInput();
        }

        try {
            $user->match_categories = $matchCategoriesArray;

            if (! $user->save()) {
                throw new \Exception('Failed to save user data');
            }
        } catch (\Exception $e) {
            \Log::error('Error saving match categories', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return back()->withErrors([
                'match_categories' => 'Terjadi kesalahan saat menyimpan data. Silakan coba lagi.'
            ])->withInput();
        }

        // Setelah kategori match disimpan, arahkan ke halaman home atau intended URL
        $intendedUrl = session()->pull('url.intended', route('home'));
        
        return redirect($intendedUrl)->with('success', 'Kategori pencarian Anda berhasil disimpan! Anda siap menjelajah IMU.');
    }

    /**
     * ✅ UPDATED: Menangani penyimpanan aksi like/dislike dari halaman Finding People.
     * DENGAN NOTIFICATION SYSTEM
     */
    public function storeInteraction(Request $request)
    {
        $request->validate([
            'target_user_id' => ['required', 'integer', 'exists:users,id'],
            'action_type' => ['required', Rule::in(['like', 'dislike'])],
        ]);

        $currentUser = Auth::user();
        $targetUserId = $request->target_user_id;
        $actionType = $request->action_type;
        $matched = false;
        $likeNotificationCreated = false;

        // Pastikan user tidak meng-swipe dirinya sendiri
        if ($currentUser->id == $targetUserId) {
            return response()->json(['status' => 'error', 'message' => 'Tidak bisa menyukai diri sendiri.'], 400);
        }

        // Simpan atau perbarui interaksi
        $interaction = UserInteraction::updateOrCreate(
            [
                'user_id' => $currentUser->id,
                'target_user_id' => $targetUserId
            ],
            [
                'action_type' => $actionType
            ]
        );

        // --- LOGIKA MATCHING & NOTIFICATION ---
        if ($actionType === 'like') {
            // Cek apakah target user juga sudah me-like user saat ini
            $mutualLike = UserInteraction::where('user_id', $targetUserId)
                                        ->where('target_user_id', $currentUser->id)
                                        ->where('action_type', 'like')
                                        ->first();

            if ($mutualLike) {
                // MUTUAL LIKE = MATCH!
                $user1 = min($currentUser->id, $targetUserId);
                $user2 = max($currentUser->id, $targetUserId);

                $match = UserMatch::firstOrCreate([
                    'user1_id' => $user1,
                    'user2_id' => $user2,
                ]);

                $matched = true;

                // ✅ Create match notifications for both users
                Notification::createMatchNotifications($currentUser->id, $targetUserId, $match->id);

            } else {
                // SINGLE LIKE = CREATE NOTIFICATION FOR TARGET USER
                Notification::createLikeNotification($targetUserId, $currentUser->id);
                $likeNotificationCreated = true;
            }
        }

        // --- RESPONSE BERDASARKAN AKSI ---
        if ($matched) {
            return response()->json([
                'status' => 'success',
                'message' => 'SELAMAT! TERJADI MATCH! 🎉',
                'matched' => true,
                'target_user_id' => $targetUserId,
                'action_type' => $actionType
            ], 200);
        } 
        elseif ($actionType === 'like' && $likeNotificationCreated) {
            return response()->json([
                'status' => 'success', 
                'message' => 'Like terkirim! Tunggu mereka like balik untuk match 💕',
                'matched' => false,
                'target_user_id' => $targetUserId,
                'action_type' => $actionType,
                'notification_sent' => true
            ], 200);
        }
        elseif ($actionType === 'dislike') {
            return response()->json([
                'status' => 'success',
                'message' => 'Pass. Mencari yang berikutnya...',
                'matched' => false,
                'target_user_id' => $targetUserId,
                'action_type' => $actionType
            ], 200);
        }
        else {
            // Fallback response
            return response()->json([
                'status' => 'success',
                'message' => 'Interaksi disimpan.',
                'matched' => false,
                'target_user_id' => $targetUserId,
                'action_type' => $actionType
            ], 200);
        }
    }
}