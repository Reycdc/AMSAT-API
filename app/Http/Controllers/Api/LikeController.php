<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Like;
use Illuminate\Http\Request;

class LikeController extends Controller
{
    /**
     * Toggle like on content
     */
    public function toggle($id)
    {
        $userId = auth()->id();

        $like = Like::where('user_id', $userId)
            ->where('content_id', $id)
            ->first();

        if ($like) {
            // Unlike
            $like->delete();

            return response()->json([
                'success' => true,
                'message' => 'Content unliked successfully',
                'data' => [
                    'liked' => false,
                    'likes_count' => Like::where('content_id', $id)->count()
                ]
            ], 200);
        } else {
            // Like
            Like::create([
                'user_id' => $userId,
                'content_id' => $id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Content liked successfully',
                'data' => [
                    'liked' => true,
                    'likes_count' => Like::where('content_id', $id)->count()
                ]
            ], 200);
        }
    }

    /**
     * Get likes by content ID
     */
    public function getByContent($id)
    {
        $likes = Like::with('user')
            ->where('content_id', $id)
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'likes_count' => $likes->count(),
                'likes' => $likes,
                'is_liked' => auth()->check() ? $likes->contains('user_id', auth()->id()) : false
            ]
        ], 200);
    }

    /**
     * Get current user's liked content
     */
    public function myLikes()
    {
        $likes = Like::with('content.user', 'content.menu')
            ->where('user_id', auth()->id())
            ->latest()
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $likes
        ], 200);
    }
}