<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CommentController extends Controller
{
    /**
     * Display a listing of comments
     */
    public function index()
    {
        $comments = Comment::with(['user', 'content'])
            ->latest()
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $comments
        ], 200);
    }

    /**
     * Store a newly created comment
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'news_id' => 'required|exists:content,id',
            'comment' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $comment = Comment::create([
            'user_id' => auth()->id(),
            'news_id' => $request->news_id,
            'comment' => $request->comment,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Comment created successfully',
            'data' => $comment->load('user', 'content')
        ], 201);
    }

    /**
     * Display the specified comment
     */
    public function show($id)
    {
        $comment = Comment::with(['user', 'content'])->find($id);

        if (!$comment) {
            return response()->json([
                'success' => false,
                'message' => 'Comment not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $comment
        ], 200);
    }

    /**
     * Update the specified comment
     */
    public function update(Request $request, $id)
    {
        $comment = Comment::find($id);

        if (!$comment) {
            return response()->json([
                'success' => false,
                'message' => 'Comment not found'
            ], 404);
        }

        // Check ownership
        if ($comment->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'comment' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $comment->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Comment updated successfully',
            'data' => $comment->load('user', 'content')
        ], 200);
    }

    /**
     * Remove the specified comment
     */
    public function destroy($id)
    {
        $comment = Comment::find($id);

        if (!$comment) {
            return response()->json([
                'success' => false,
                'message' => 'Comment not found'
            ], 404);
        }

        // Check ownership
        if ($comment->user_id !== auth()->id() && !auth()->user()->hasRole(['admin', 'editor'])) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $comment->delete();

        return response()->json([
            'success' => true,
            'message' => 'Comment deleted successfully'
        ], 200);
    }

    /**
     * Get comments by content ID
     */
    public function getByContent($id)
    {
        $comments = Comment::with('user')
            ->where('news_id', $id)
            ->latest()
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $comments
        ], 200);
    }
}