<?php

namespace App\Http\Controllers\Api;

use App\Models\Banner;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class BannerController extends Controller
{
    /**
     * Display a listing of banners
     */
    public function index(Request $request)
    {
        $status = $request->get('status');

        $banners = Banner::when($status !== null, function ($query) use ($status) {
                return $query->where('status', $status);
            })
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $banners
        ], 200);
    }

    /**
     * Store a newly created banner
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'gambar' => 'required|image|mimes:jpeg,png,jpg|max:2048',
            'status' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $gambarPath = $request->file('gambar')->store('banners', 'public');

        $banner = Banner::create([
            'gambar' => $gambarPath,
            'status' => $request->get('status', true),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Banner created successfully',
            'data' => $banner
        ], 201);
    }

    /**
     * Display the specified banner
     */
    public function show($id)
    {
        $banner = Banner::find($id);

        if (!$banner) {
            return response()->json([
                'success' => false,
                'message' => 'Banner not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $banner
        ], 200);
    }

    /**
     * Update the specified banner
     */
    public function update(Request $request, $id)
    {
        $banner = Banner::find($id);

        if (!$banner) {
            return response()->json([
                'success' => false,
                'message' => 'Banner not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'gambar' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'status' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        if ($request->hasFile('gambar')) {
            // Delete old image
            Storage::disk('public')->delete($banner->gambar);
            $banner->gambar = $request->file('gambar')->store('banners', 'public');
        }

        if ($request->has('status')) {
            $banner->status = $request->status;
        }

        $banner->save();

        return response()->json([
            'success' => true,
            'message' => 'Banner updated successfully',
            'data' => $banner
        ], 200);
    }

    /**
     * Remove the specified banner
     */
    public function destroy($id)
    {
        $banner = Banner::find($id);

        if (!$banner) {
            return response()->json([
                'success' => false,
                'message' => 'Banner not found'
            ], 404);
        }

        Storage::disk('public')->delete($banner->gambar);
        $banner->delete();

        return response()->json([
            'success' => true,
            'message' => 'Banner deleted successfully'
        ], 200);
    }
}