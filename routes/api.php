<?php

use App\Http\Controllers\Api\PostController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Rute untuk registrasi pengguna baru
Route::post('/register', [AuthController::class, 'register']);

// Rute untuk login pengguna
Route::post('/login', [AuthController::class, 'login']);

// Rute untuk menangani akses tidak terautentikasi ke endpoint login (digunakan oleh middleware 'guest')
Route::get('/login', [AuthController::class, 'index'])->name('login')->middleware('guest');

// Rute untuk logout pengguna, membutuhkan otentikasi Sanctum
Route::delete('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');

// Rute sumber daya untuk postingan (CRUD), membutuhkan otentikasi Sanctum
Route::resource('v1/post', PostController::class)->middleware('auth:sanctum');

// Grup rute yang membutuhkan otentikasi Sanctum
Route::middleware('auth:sanctum')->group(function () {
    // Mendapatkan semua pengguna yang belum diikuti oleh pengguna yang sedang login
    Route::get('v1/users', [UserController::class, 'getAllUsers']);
    // Mendapatkan detail profil pengguna berdasarkan username
    Route::get('v1/users/{username}', [UserController::class, 'getUserDetail']);
    // Mengikuti pengguna lain
    Route::post('v1/users/{username}/follow', [UserController::class, 'follow']);
    // Berhenti mengikuti pengguna lain
    Route::delete('v1/users/{username}/unfollow', [UserController::class, 'unfollow']);
    // Menerima permintaan mengikuti dari pengguna lain
    Route::put('v1/users/{username}/accept', [UserController::class, 'acceptFollowRequest']);
    // Mendapatkan daftar pengikut suatu pengguna
    Route::get('v1/users/{username}/followers', [UserController::class, 'getFollowers']);
    // Mendapatkan daftar pengguna yang sedang diikuti oleh pengguna yang sedang login
    Route::get('v1/following', [UserController::class, 'getFollowing']);
});

// Rute untuk mendapatkan informasi pengguna yang terautentikasi saat ini, membutuhkan otentikasi Sanctum
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
