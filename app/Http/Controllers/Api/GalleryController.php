<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Gallery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class GalleryController extends Controller
{
    /**
     * Display a listing of galleries
     */
    public function index()
    {
        $galleries = Gallery::latest()->get();

        return response()->json([
            'success' => true,
            'data' => $galleries
        ], 200);
    }

    /**
     * Store a newly created gallery
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'image' => 'required|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $imagePath = $request->file('image')->store('galleries', 'public');

        $gallery = Gallery::create([
            'title' => $request->title,
            'description' => $request->description,
            'image' => $imagePath,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Gallery created successfully',
            'data' => $gallery
        ], 201);
    }

    /**
     * Display the specified gallery
     */
    public function show($id)
    {
        $gallery = Gallery::find($id);

        if (!$gallery) {
            return response()->json([
                'success' => false,
                'message' => 'Gallery not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $gallery
        ], 200);
    }

    /**
     * Update the specified gallery
     */
    public function update(Request $request, $id)
    {
        $gallery = Gallery::find($id);

        if (!$gallery) {
            return response()->json([
                'success' => false,
                'message' => 'Gallery not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        if ($request->hasFile('image')) {
            if ($gallery->image) {
                \Storage::disk('public')->delete($gallery->image);
            }
            $gallery->image = $request->file('image')->store('galleries', 'public');
        }

        $gallery->fill($request->except('image'));
        $gallery->save();

        return response()->json([
            'success' => true,
            'message' => 'Gallery updated successfully',
            'data' => $gallery
        ], 200);
    }

    /**
     * Remove the specified gallery
     */
    public function destroy($id)
    {
        $gallery = Gallery::find($id);

        if (!$gallery) {
            return response()->json([
                'success' => false,
                'message' => 'Gallery not found'
            ], 404);
        }

        if ($gallery->image) {
            \Storage::disk('public')->delete($gallery->image);
        }

        $gallery->delete();

        return response()->json([
            'success' => true,
            'message' => 'Gallery deleted successfully'
        ], 200);
    }
}