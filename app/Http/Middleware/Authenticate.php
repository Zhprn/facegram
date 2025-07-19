<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    /**
     * Tujuan: Mendapatkan path ke mana pengguna harus dialihkan ketika mereka tidak terautentikasi.
     * Cara Kerja:
     * - Jika permintaan (request) adalah permintaan JSON (misalnya dari API), maka akan menghentikan eksekusi dan mengembalikan respons JSON dengan pesan "Unauthenticated." dan status kode 401. Ini penting untuk API agar tidak mengalihkan pengguna ke halaman login, melainkan memberikan respons error yang jelas.
     * - Jika bukan permintaan JSON (misalnya permintaan web biasa), maka akan mengembalikan null, yang berarti Laravel akan melanjutkan dengan perilaku default-nya untuk mengalihkan pengguna ke halaman login yang ditentukan.
     */
    protected function redirectTo(Request $request): ?string
    {
        if ($request->expectsJson()) {
            abort(response()->json([
                'message' => 'Anauthenticated.',
            ], 401));
        }

        return null;
    }
}
