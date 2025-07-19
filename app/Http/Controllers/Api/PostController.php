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
    public function index()
    {
        $data = Post::with('attachments', 'user')->get();
        return response()->json($data);
    }
    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
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
            }
            ;
        }

        return response()->json([
            'message' => 'Create Post Succes'
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {

    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $post = Post::findOrFail($id);

        if (!$post){
            return response()->json([
                'message' => 'Post not found.'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'caption' => 'required|string'
        ]);

        if($validator->fails()) {
            return response()->json([
                'message' => 'Invalid Fields',
                'error' => $validator -> errors(),
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
