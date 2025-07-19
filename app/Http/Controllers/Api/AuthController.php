<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Laravel\Sanctum\PersonalAccessToken;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    // Register
    /**
     * Tujuan: Mendaftarkan pengguna baru ke sistem.
     * Cara Kerja:
     * - Melakukan validasi data yang masuk (nama lengkap, username, password, bio wajib; username harus unik; is_private adalah boolean).
     * - Jika validasi gagal, mengembalikan pesan error.
     * - Membuat pengguna baru di database dengan data yang diberikan, password di-hash.
     * - Membuat token otentikasi untuk pengguna yang baru terdaftar.
     * - Mengembalikan pesan sukses, data pengguna, dan token otentikasi dalam format JSON.
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'full_name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users',
            'password' => 'required|string|min:6',
            'bio' => 'required|string',
            'is_private' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Invalid Fields',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = User::create([
            'full_name' => $request->full_name,
            'username' => $request->username,
            'password' => Hash::make($request->password),
            'bio' => $request->bio,
            'is_private' => $request->is_private ?? 0,
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Registrasi berhasil',
            'data' => $user,
            'token' => $token,
        ], 201);
    }

    // Login
    /**
     * Tujuan: Mengautentikasi pengguna dan memberikan token akses.
     * Cara Kerja:
     * - Melakukan validasi data yang masuk (username dan password wajib).
     * - Jika validasi gagal, mengembalikan pesan error.
     * - Mencari pengguna berdasarkan username.
     * - Memeriksa apakah pengguna ditemukan dan password yang dimasukkan cocok dengan hash password di database. Jika tidak cocok, mengembalikan pesan error.
     * - Membuat token otentikasi baru untuk pengguna.
     * - Mengembalikan pesan sukses, token otentikasi, dan data pengguna dalam format JSON.
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = User::where('username', $request->username)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Username atau password salah',
            ], 401);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login Succes',
            'token' => $token,
            'data' => $user,
        ], 200);
    }

    /**
     * Tujuan: Melakukan logout pengguna dengan mencabut token akses saat ini.
     * Cara Kerja:
     * - Mengambil token akses saat ini dari pengguna yang terautentikasi.
     * - Menghapus token akses tersebut, sehingga pengguna tidak lagi terautentikasi.
     * - Mengembalikan pesan sukses.
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logout success']);
    }

    /**
     * Tujuan: Mengembalikan pesan "Unauthenticated" jika pengguna mencoba mengakses endpoint tanpa otentikasi.
     * Cara Kerja:
     * - Fungsi ini biasanya dipanggil ketika rute dilindungi oleh middleware otentikasi, dan pengguna mencoba mengaksesnya tanpa token yang valid.
     * - Mengembalikan pesan error "Unauthenticated" dengan status kode 401.
     */
    public function index()
    {
        return response()->json([
            'message' => 'Anauthenticated.'
        ], 401);
    }
}
