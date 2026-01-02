<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MediaController extends Controller
{
    /**
     * Display a listing of media
     */
    /**
     * Display a listing of media
     */
    public function index(Request $request)
    {
        try {
            $contentId = $request->get('content_id');

            $media = Media::with('content')
                ->when($contentId, function ($query, $contentId) {
                    return $query->where('content_id', $contentId);
                })
                ->latest()
                ->get();

            return response()->json([
                'success' => true,
                'data' => $media
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve media',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred'
            ], 500);
        }
    }

    /**
     * Store a newly created media
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'content_id' => 'required|exists:content,id',
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'document' => 'nullable|file|mimes:pdf,doc,docx|max:5120',
            'video' => 'nullable|file|mimes:mp4,avi,mov|max:51200',
            'link' => 'nullable|url',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $mediaData = ['content_id' => $request->content_id];

            if ($request->hasFile('image')) {
                $mediaData['image'] = $request->file('image')->store('media/images', 'public');
            }

            if ($request->hasFile('document')) {
                $mediaData['document'] = $request->file('document')->store('media/documents', 'public');
            }

            if ($request->hasFile('video')) {
                $mediaData['video'] = $request->file('video')->store('media/videos', 'public');
            }

            if ($request->has('link')) {
                $mediaData['link'] = $request->link;
            }

            $media = Media::create($mediaData);

            return response()->json([
                'success' => true,
                'message' => 'Media created successfully',
                'data' => $media->load('content')
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create media',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred'
            ], 500);
        }
    }

    /**
     * Display the specified media
     */
    public function show($id)
    {
        try {
            $media = Media::with('content')->find($id);

            if (!$media) {
                return response()->json([
                    'success' => false,
                    'message' => 'Media not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $media
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve media',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred'
            ], 500);
        }
    }

    /**
     * Update the specified media
     */
    public function update(Request $request, $id)
    {
        try {
            $media = Media::find($id);

            if (!$media) {
                return response()->json([
                    'success' => false,
                    'message' => 'Media not found'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
                'document' => 'nullable|file|mimes:pdf,doc,docx|max:5120',
                'video' => 'nullable|file|mimes:mp4,avi,mov|max:51200',
                'link' => 'nullable|url',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            if ($request->hasFile('image')) {
                if ($media->image) {
                    \Storage::disk('public')->delete($media->image);
                }
                $media->image = $request->file('image')->store('media/images', 'public');
            }

            if ($request->hasFile('document')) {
                if ($media->document) {
                    \Storage::disk('public')->delete($media->document);
                }
                $media->document = $request->file('document')->store('media/documents', 'public');
            }

            if ($request->hasFile('video')) {
                if ($media->video) {
                    \Storage::disk('public')->delete($media->video);
                }
                $media->video = $request->file('video')->store('media/videos', 'public');
            }

            if ($request->has('link')) {
                $media->link = $request->link;
            }

            $media->save();

            return response()->json([
                'success' => true,
                'message' => 'Media updated successfully',
                'data' => $media->load('content')
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update media',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred'
            ], 500);
        }
    }

    /**
     * Remove the specified media
     */
    public function destroy($id)
    {
        try {
            $media = Media::find($id);

            if (!$media) {
                return response()->json([
                    'success' => false,
                    'message' => 'Media not found'
                ], 404);
            }

            // Delete files
            if ($media->image) {
                \Storage::disk('public')->delete($media->image);
            }
            if ($media->document) {
                \Storage::disk('public')->delete($media->document);
            }
            if ($media->video) {
                \Storage::disk('public')->delete($media->video);
            }

            $media->delete();

            return response()->json([
                'success' => true,
                'message' => 'Media deleted successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete media',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred'
            ], 500);
        }
    }
}