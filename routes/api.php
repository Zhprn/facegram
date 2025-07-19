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

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('/login', [AuthController::class, 'index'])->name('login')->middleware('guest');
Route::delete('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
Route::resource('v1/post', PostController::class)->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('v1/users', [UserController::class, 'getAllUsers']);
    Route::get('v1/users/{username}', [UserController::class, 'getUserDetail']);
    Route::post('v1/users/{username}/follow', [UserController::class, 'follow']);
    Route::delete('v1/users/{username}/unfollow', [UserController::class, 'unfollow']);
    Route::put('v1/users/{username}/accept', [UserController::class, 'acceptFollowRequest']);
    Route::get('v1/users/{username}/followers', [UserController::class, 'getFollowers']);
    Route::get('v1/following', [UserController::class, 'getFollowing']);
});


Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
