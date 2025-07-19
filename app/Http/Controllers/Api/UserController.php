<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    /**
     * Tujuan: Fungsi ini digunakan untuk mengikuti (follow) pengguna lain.
     * Cara Kerja:
     * - Mencari pengguna yang ingin diikuti berdasarkan username. Jika tidak ditemukan, akan mengembalikan pesan error.
     * - Memeriksa apakah pengguna yang sedang login mencoba mengikuti akunnya sendiri. Jika iya, akan mengembalikan pesan error.
     * - Memeriksa apakah pengguna yang sedang login sudah mengikuti pengguna tersebut. Jika iya, akan mengembalikan pesan error.
     * - Jika pengguna yang ingin diikuti memiliki akun private (pribadi), permintaan mengikuti akan disimpan dengan status requested (diminta), yang berarti perlu persetujuan dari pengguna yang diikuti.
     * - Jika pengguna yang ingin diikuti memiliki akun public (publik), pengguna yang sedang login akan langsung mengikuti dan statusnya menjadi following (mengikuti).
     */
    public function follow(Request $request, $username)
    {
        $userToFollow = User::where('username', $username)->first();

        if (!$userToFollow) {
            return response()->json(['message' => 'User not found'], 404);
        }

        /** @var \App\Models\User $user */
        $user = auth()->user();

        if ($user->id === $userToFollow->id) {
            return response()->json(['message' => 'You are not allowed to follow yourself'], 422);
        }

        $isFollowing = $user->following()->where('users.id', $userToFollow->id)->exists();

        if ($isFollowing) {
            return response()->json(['message' => 'You are already followed', 'status' => 'following'], 422);
        }

        if ($userToFollow->is_private) {
            $user->following()->attach($userToFollow->id, ['is_accepted' => 0]);
            return response()->json(['message' => 'Follow success', 'status' => 'requested']);
        }

        $user->following()->attach($userToFollow->id, ['is_accepted' => 1]);

        return response()->json(['message' => 'Follow success', 'status' => 'following']);
    }

    /**
     * Tujuan: Fungsi ini digunakan untuk berhenti mengikuti (unfollow) pengguna lain.
     * Cara Kerja:
     * - Mencari pengguna yang ingin berhenti diikuti berdasarkan username. Jika tidak ditemukan, akan mengembalikan pesan error.
     * - Memeriksa apakah pengguna yang sedang login memang sedang mengikuti pengguna tersebut. Jika tidak, akan mengembalikan pesan error.
     * - Jika pengguna yang sedang login memang mengikuti, hubungan mengikuti akan dihapus.
     * - Mengembalikan respons sukses tanpa konten (status 204 No Content).
     */
    public function unfollow(Request $request, $username)
    {
        $userToUnfollow = User::where('username', $username)->first();

        if (!$userToUnfollow) {
            return response()->json(['message' => 'User not found'], 404);
        }

        /** @var \App\Models\User $user */
        $user = auth()->user();

        $isFollowing = $user->following()->where('users.id', $userToUnfollow->id)->exists();

        if (!$isFollowing) {
            return response()->json(['message' => 'You are not following the user'], 422);
        }

        $user->following()->detach($userToUnfollow->id);

        return response()->json(null, 204);
    }

    /**
     * Tujuan: Fungsi ini untuk mendapatkan daftar pengguna yang sedang diikuti oleh pengguna yang sedang login.
     * Cara Kerja:
     * - Mengambil semua pengguna yang diikuti oleh pengguna yang sedang login.
     * - Memetakan data ini ke dalam format yang lebih rapi, termasuk id, full_name, username, bio, status private, waktu mengikuti (created_at), dan apakah permintaan mengikuti masih is_requested (status is_accepted adalah 0).
     * - Mengembalikan daftar ini sebagai respons JSON.
     */
    public function getFollowing(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();
        $following = $user->following()->get();

        $following_data = $following->map(function ($followed_user) {
            return [
                'id' => $followed_user->id,
                'full_name' => $followed_user->full_name,
                'username' => $followed_user->username,
                'bio' => $followed_user->bio,
                'is_private' => $followed_user->is_private,
                'created_at' => $followed_user->pivot->created_at->toDateTimeString(),
                'is_requested' => $followed_user->pivot->is_accepted == 0,
            ];
        });

        return response()->json(['following' => $following_data]);
    }

    /**
     * Tujuan: Fungsi ini digunakan untuk menerima permintaan mengikuti dari pengguna lain.
     * Cara Kerja:
     * - Mencari pengguna yang permintaannya ingin diterima berdasarkan username. Jika tidak ditemukan, akan mengembalikan pesan error.
     * - Memeriksa apakah pengguna tersebut benar-benar mengirim permintaan mengikuti kepada pengguna yang sedang login. Jika tidak, akan mengembalikan pesan error.
     * - Memeriksa apakah permintaan mengikuti sudah diterima sebelumnya. Jika iya, akan mengembalikan pesan error.
     * - Jika semua kondisi terpenuhi, status permintaan mengikuti akan diubah menjadi accepted (diterima).
     * - Mengembalikan pesan sukses.
     */
    public function acceptFollowRequest(Request $request, $username)
    {
        $userToAccept = User::where('username', $username)->first();

        if (!$userToAccept) {
            return response()->json(['message' => 'User not found'], 404);
        }

        /** @var \App\Models\User $user */
        $user = auth()->user();

        $follow = $user->followers()->where('users.id', $userToAccept->id)->first();

        if (!$follow) {
            return response()->json(['message' => 'The user is not following you'], 422);
        }

        if ($follow->pivot->is_accepted) {
            return response()->json(['message' => 'Follow request is already accepted'], 422);
        }

        $user->followers()->updateExistingPivot($userToAccept->id, ['is_accepted' => 1]);

        return response()->json(['message' => 'Follow request accepted']);
    }

    /**
     * Tujuan: Fungsi ini untuk mendapatkan daftar pengikut (followers) dari suatu pengguna.
     * Cara Kerja:
     * - Mencari pengguna berdasarkan username. Jika tidak ditemukan, akan mengembalikan pesan error.
     * - Mengambil semua pengikut dari pengguna tersebut.
     * - Memetakan data ini ke dalam format yang rapi, termasuk id, full_name, username, bio, status private, waktu mengikuti (created_at), dan apakah permintaan mengikuti masih is_requested (status is_accepted adalah 0).
     * - Mengembalikan daftar ini sebagai respons JSON.
     */
    public function getFollowers(Request $request, $username)
    {
        $user = User::where('username', $username)->first();

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $followers = $user->followers()->get();

        $followers_data = $followers->map(function ($follower) {
            return [
                'id' => $follower->id,
                'full_name' => $follower->full_name,
                'username' => $follower->username,
                'bio' => $follower->bio,
                'is_private' => $follower->is_private,
                'created_at' => $follower->pivot->created_at->toDateTimeString(),
                'is_requested' => $follower->pivot->is_accepted == 0,
            ];
        });

        return response()->json(['followers' => $followers_data]);
    }

    /**
     * Tujuan: Fungsi ini untuk mendapatkan daftar semua pengguna yang belum diikuti oleh pengguna yang sedang login.
     * Cara Kerja:
     * - Mengambil ID dari semua pengguna yang sedang diikuti oleh pengguna yang sedang login.
     * - Mencari semua pengguna lain yang ID-nya tidak ada dalam daftar yang sedang diikuti dan bukan akun pengguna yang sedang login itu sendiri.
     * - Memetakan data pengguna ini ke dalam format yang rapi.
     * - Mengembalikan daftar ini sebagai respons JSON.
     */
    public function getAllUsers(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();
        $followingIds = $user->following()->pluck('users.id');
        $users = User::whereNotIn('id', $followingIds)->where('id', '!=', $user->id)->get();

        $users_data = $users->map(function ($user) {
            return [
                'id' => $user->id,
                'full_name' => $user->full_name,
                'username' => $user->username,
                'bio' => $user->bio,
                'is_private' => $user->is_private,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
            ];
        });

        return response()->json(['users' => $users_data]);
    }

    /**
     * Tujuan: Fungsi ini untuk mendapatkan detail profil lengkap dari pengguna tertentu, termasuk status mengikuti dan jumlah postingan/pengikut/mengikuti.
     * Cara Kerja:
     * - Mencari pengguna berdasarkan username dan menghitung jumlah posts, followers, dan following mereka. Jika tidak ditemukan, akan mengembalikan pesan error.
     * - Menentukan status mengikuti antara pengguna yang sedang login dengan pengguna yang detailnya diminta (bisa not-following, following, atau requested).
     * - Membuat objek data pengguna yang berisi detail profil, status akun (is_your_account), status mengikuti (following_status), dan jumlah followers, following, serta posts.
     * - Menentukan apakah pengguna yang sedang login memiliki izin untuk melihat postingan pengguna yang detailnya diminta. Izin diberikan jika:
     *   - Itu adalah akun sendiri.
     *   - Akun tersebut tidak private.
     *   - Sudah following atau requested akun tersebut.
     * - Jika izin diberikan, postingan pengguna tersebut (beserta lampirannya) akan dimuat dan ditambahkan ke data pengguna.
     * - Mengembalikan data detail pengguna sebagai respons JSON.
     */
    public function getUserDetail(Request $request, $username)
    {
        $user = User::where('username', $username)->withCount(['posts', 'followers', 'following'])->first();

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        /** @var \App\Models\User $authUser */
        $authUser = auth()->user();
        $following_status = 'not-following';

        if ($authUser->following()->where('users.id', $user->id)->exists()) {
            $follow = $authUser->following()->where('users.id', $user->id)->first();
            if ($follow->pivot->is_accepted) {
                $following_status = 'following';
            } else {
                $following_status = 'requested';
            }
        }

        $user_data = [
            'id' => $user->id,
            'full_name' => $user->full_name,
            'username' => $user->username,
            'bio' => $user->bio,
            'is_private' => $user->is_private,
            'created_at' => $user->created_at,
            'is_your_account' => $user->id === $authUser->id,
            'following_status' => $following_status,
            'followers_count' => $user->followers_count,
            'following_count' => $user->following_count,
            'posts_count' => $user->posts_count,
        ];

        $canViewPosts = false;
        if ($user->id === $authUser->id) {
            $canViewPosts = true;
        } elseif (!$user->is_private) {
            $canViewPosts = true;
        } elseif ($following_status === 'following' || $following_status === 'requested') {
            $canViewPosts = true;
        }

        if ($canViewPosts) {
            $user->load('posts.attachments');
            $user_data['posts'] = $user->posts;
        }


        return response()->json($user_data);
    }
}
