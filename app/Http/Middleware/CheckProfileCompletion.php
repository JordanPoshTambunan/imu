<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Memaksa pengguna menyelesaikan onboarding sebelum mengakses aplikasi.
 *
 * Alur: profil dasar -> kategori match -> aplikasi.
 *
 * Didaftarkan sebagai alias 'profile_complete' di bootstrap/app.php.
 */
class CheckProfileCompletion
{
    /**
     * Field profil yang wajib diisi.
     *
     * PENTING: 'description' sengaja TIDAK ada di daftar ini. ProfileController
     * memvalidasinya sebagai 'nullable', jadi mewajibkannya di sini akan membuat
     * pengguna yang mengosongkan deskripsi terjebak dalam loop redirect.
     */
    private const REQUIRED_PROFILE_FIELDS = ['prodi', 'fakultas', 'gender'];

    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check()) {
            return $next($request);
        }

        $user = Auth::user();

        // Tahap 1: profil dasar
        if (! $this->hasCompleteBasicProfile($user) && ! $request->routeIs('profile.setup')) {
            session()->put('url.intended', $request->url());

            return redirect()
                ->route('profile.setup')
                ->with('info', 'Mohon lengkapi profil Anda untuk dapat mengakses fitur lainnya.');
        }

        // Tahap 2: kategori match
        if ($this->hasCompleteBasicProfile($user)
            && ! $this->hasMatchCategories($user)
            && ! $request->routeIs('match.setup')) {
            session()->put('url.intended', $request->url());

            return redirect()
                ->route('match.setup')
                ->with('info', 'Mohon pilih kategori yang Anda cari untuk dapat mengakses fitur lainnya.');
        }

        return $next($request);
    }

    private function hasCompleteBasicProfile($user): bool
    {
        foreach (self::REQUIRED_PROFILE_FIELDS as $field) {
            if (empty($user->$field)) {
                return false;
            }
        }

        return ! empty($user->interests) && is_array($user->interests);
    }

    private function hasMatchCategories($user): bool
    {
        return ! empty($user->match_categories) && is_array($user->match_categories);
    }
}
