<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Post_attachment;
use Illuminate\Http\Request;
use App\Models\Post;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class PostController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    /**
     * Tujuan: Menampilkan daftar semua postingan.
     * Cara Kerja:
     * - Mengambil semua postingan dari database.
     * - Setiap postingan akan menyertakan data lampiran (attachments) dan informasi pengguna (user) yang membuat postingan.
     * - Mengembalikan data postingan dalam format JSON.
     */
    public function index(Request $request)
    {
        $perPage = $request->input('size', 10); // Default to 10 items per page
        $data = Post::with('attachments', 'user')->paginate($perPage);

        return response()->json([
            'posts' => $data->items(),
            'page' => $data->currentPage(),
            'size' => $data->perPage(),
            'total_pages' => $data->lastPage(),
            'total_posts' => $data->total(),
        ]);
    }
    /**
     * Show the form for creating a new resource.
     */
    /**
     * Tujuan: Menampilkan formulir untuk membuat sumber daya baru.
     * Catatan: Fungsi ini kosong karena mungkin tidak digunakan untuk API, atau tujuannya ditangani di tempat lain (misalnya, di frontend).
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    /**
     * Tujuan: Menyimpan postingan baru ke dalam penyimpanan.
     * Cara Kerja:
     * - Melakukan validasi data yang masuk (caption wajib, attachments opsional dengan format tertentu).
     * - Jika validasi gagal, mengembalikan pesan error.
     * - Membuat postingan baru di database dengan caption dan ID pengguna yang sedang login.
     * - Jika ada lampiran (file gambar/PDF), setiap file akan disimpan ke penyimpanan publik dan entri lampiran akan dibuat di database.
     * - Mengembalikan pesan sukses setelah postingan dan lampirannya (jika ada) berhasil disimpan.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'caption' => 'required|string',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|mimes:jpg,jpeg,png,pdf|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Invalid Fields',
                'errors' => $validator->errors(),
            ], 422);
        }

        $post = Post::create([
            'caption' => $request->caption,
            'user_id' => auth()->user()->id,
        ]);

        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('attachments', 'public');

                Post_attachment::create([
                    'post_id' => $post->id,
                    'storage_path' => $path,
                ]);
            };
        }

        return response()->json([
            'message' => 'Create Post Succes'
        ]);
    }

    /**
     * Display the specified resource.
     */
    /**
     * Tujuan: Menampilkan sumber daya yang ditentukan.
     * Catatan: Fungsi ini kosong karena mungkin tidak digunakan untuk API, atau tujuannya ditangani di tempat lain (misalnya, di frontend).
     */
    public function show(string $id) {}

    /**
     * Show the form for editing the specified resource.
     */
    /**
     * Tujuan: Menampilkan formulir untuk mengedit sumber daya yang ditentukan.
     * Catatan: Fungsi ini kosong karena mungkin tidak digunakan untuk API, atau tujuannya ditangani di tempat lain (misalnya, di frontend).
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    /**
     * Tujuan: Memperbarui sumber daya yang ditentukan dalam penyimpanan.
     * Cara Kerja:
     * - Mencari postingan berdasarkan ID. Jika tidak ditemukan, mengembalikan pesan error.
     * - Melakukan validasi data yang masuk (caption wajib).
     * - Jika validasi gagal, mengembalikan pesan error.
     * - Memperbarui caption postingan dengan data yang baru.
     * - Mengembalikan pesan sukses setelah caption berhasil diperbarui.
     */
    public function update(Request $request, string $id)
    {
        $post = Post::findOrFail($id);

        if (!$post) {
            return response()->json([
                'message' => 'Post not found.'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'caption' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Invalid Fields',
                'error' => $validator->errors(),
            ], 422);
        }

        $post->update($request->all());
        return response()->json([
            'message' => 'Caption Updated',
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    /**
     * Tujuan: Menghapus sumber daya yang ditentukan dari penyimpanan.
     * Cara Kerja:
     * - Mencari postingan berdasarkan ID. Jika tidak ditemukan, mengembalikan pesan error.
     * - Untuk setiap lampiran yang terkait dengan postingan:
     *   - Memeriksa apakah file lampiran ada di penyimpanan publik.
     *   - Jika ada, menghapus file dari penyimpanan.
     *   - Menghapus entri lampiran dari database.
     * - Menghapus postingan dari database.
     * - Mengembalikan pesan sukses setelah postingan dan lampirannya berhasil dihapus.
     */
    public function destroy(string $id)
    {
        $post = Post::find($id);

        if (!$post) {
            return response()->json([
                'message' => 'Post not found.'
            ], 404);
        }

        foreach ($post->attachments as $attachment) {
            if (Storage::disk('public')->exists($attachment->storage_path)) {
                Storage::disk('public')->delete($attachment->storage_path);
            }
            $attachment->delete();
        }

        $post->delete();

        return response()->json([
            'message' => 'Post and attachments deleted successfully.'
        ]);
    }
}
