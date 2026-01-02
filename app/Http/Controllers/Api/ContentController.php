<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Content;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ContentController extends Controller
{
    /**
     * Display a listing of content (public access)
     */
    /**
     * Display a listing of content (public access)
     */
    public function index(Request $request)
    {
        try {
            $perPage = $request->get('per_page', 15);
            $search = $request->get('search');
            $menuId = $request->get('menu_id');
            $categoryId = $request->get('category_id');

            $contents = Content::with(['user', 'menu', 'categories', 'media'])
                ->whereNotNull('is_verified')
                ->when($search, function ($query, $search) {
                    return $query->where('title', 'like', "%{$search}%")
                        ->orWhere('isi', 'like', "%{$search}%");
                })
                ->when($menuId, function ($query, $menuId) {
                    return $query->where('menu_id', $menuId);
                })
                ->when($categoryId, function ($query, $categoryId) {
                    return $query->whereHas('categories', function ($q) use ($categoryId) {
                        $q->where('categories.id', $categoryId);
                    });
                })
                ->latest('date')
                ->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $contents
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve content',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred'
            ], 500);
        }
    }

    /**
     * Store a newly created content
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'menu_id' => 'required|exists:menus,id',
            'title' => 'required|string|max:255',
            'isi' => 'required|string',
            'cover' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'date' => 'required|date',
            'category_ids' => 'nullable|array',
            'category_ids.*' => 'exists:categories,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $coverPath = null;
            if ($request->hasFile('cover')) {
                $coverPath = $request->file('cover')->store('covers', 'public');
            }

            $content = Content::create([
                'user_id' => auth()->id(),
                'menu_id' => $request->menu_id,
                'title' => $request->title,
                'isi' => $request->isi,
                'cover' => $coverPath,
                'date' => $request->date,
                'has_read' => 0,
            ]);

            // Attach categories
            if ($request->has('category_ids')) {
                $content->categories()->attach($request->category_ids);
            }

            return response()->json([
                'success' => true,
                'message' => 'Content created successfully',
                'data' => $content->load(['user', 'menu', 'categories'])
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create content',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred'
            ], 500);
        }
    }

    /**
     * Display the specified content
     */
    public function show($id)
    {
        try {
            $content = Content::with(['user', 'menu', 'categories', 'media', 'comments.user'])
                ->find($id);

            if (!$content) {
                return response()->json([
                    'success' => false,
                    'message' => 'Content not found'
                ], 404);
            }

            // Increment read count
            $content->increment('has_read');

            return response()->json([
                'success' => true,
                'data' => $content
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve content',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred'
            ], 500);
        }
    }

    /**
     * Update the specified content
     */
    public function update(Request $request, $id)
    {
        try {
            $content = Content::find($id);

            if (!$content) {
                return response()->json([
                    'success' => false,
                    'message' => 'Content not found'
                ], 404);
            }

            // Check ownership
            if ($content->user_id !== auth()->id() && !auth()->user()->hasRole(['admin', 'editor'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'menu_id' => 'sometimes|exists:menus,id',
                'title' => 'sometimes|string|max:255',
                'isi' => 'sometimes|string',
                'cover' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
                'date' => 'sometimes|date',
                'category_ids' => 'nullable|array',
                'category_ids.*' => 'exists:categories,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            if ($request->hasFile('cover')) {
                if ($content->cover) {
                    \Storage::disk('public')->delete($content->cover);
                }
                $content->cover = $request->file('cover')->store('covers', 'public');
            }

            $content->fill($request->except(['cover', 'category_ids']));
            $content->save();

            // Sync categories
            if ($request->has('category_ids')) {
                $content->categories()->sync($request->category_ids);
            }

            return response()->json([
                'success' => true,
                'message' => 'Content updated successfully',
                'data' => $content->load(['user', 'menu', 'categories'])
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update content',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred'
            ], 500);
        }
    }

    /**
     * Remove the specified content
     */
    public function destroy($id)
    {
        try {
            $content = Content::find($id);

            if (!$content) {
                return response()->json([
                    'success' => false,
                    'message' => 'Content not found'
                ], 404);
            }

            // Check ownership
            if ($content->user_id !== auth()->id() && !auth()->user()->hasRole(['admin', 'editor'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 403);
            }

            if ($content->cover) {
                \Storage::disk('public')->delete($content->cover);
            }

            $content->delete();

            return response()->json([
                'success' => true,
                'message' => 'Content deleted successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete content',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred'
            ], 500);
        }
    }

    /**
     * Verify content (admin/redaktur only)
     */
    public function verify(Request $request, $id)
    {
        try {
            $content = Content::find($id);

            if (!$content) {
                return response()->json([
                    'success' => false,
                    'message' => 'Content not found'
                ], 404);
            }

            $content->is_verified = now();
            $content->redaktur_id = auth()->id();
            $content->save();

            return response()->json([
                'success' => true,
                'message' => 'Content verified successfully',
                'data' => $content->load(['user', 'redaktur'])
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to verify content',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred'
            ], 500);
        }
    }
}
