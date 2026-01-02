<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MenuController extends Controller
{
    /**
     * Display a listing of menus
     */
    /**
     * Display a listing of menus
     */
    public function index()
    {
        try {
            $menus = Menu::with('children', 'parent')
                ->whereNull('parent_id')
                ->orderBy('urutan')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $menus
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve menus',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred'
            ], 500);
        }
    }

    /**
     * Get flat list of all menus for dropdown selection
     */
    public function flat()
    {
        try {
            $menus = Menu::withCount('contents')
                ->orderBy('urutan')
                ->get();

            // Flatten with hierarchy labels
            $flatMenus = [];
            $this->flattenMenus($menus->whereNull('parent_id'), $flatMenus, $menus);

            return response()->json([
                'success' => true,
                'data' => $flatMenus
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve menus',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred'
            ], 500);
        }
    }

    /**
     * Helper to flatten menus with hierarchy
     */
    private function flattenMenus($items, &$result, $allMenus, $prefix = '')
    {
        foreach ($items as $item) {
            $result[] = [
                'id' => $item->id,
                'title' => $item->title,
                'label' => $prefix . $item->title,
                'parent_id' => $item->parent_id,
                'contents_count' => $item->contents_count,
            ];

            $children = $allMenus->where('parent_id', $item->id);
            if ($children->count() > 0) {
                $this->flattenMenus($children, $result, $allMenus, $prefix . $item->title . ' > ');
            }
        }
    }

    /**
     * Store a newly created menu
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'parent_id' => 'nullable|exists:menus,id',
            'urutan' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $menu = Menu::create($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Menu created successfully',
                'data' => $menu->load('parent')
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create menu',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred'
            ], 500);
        }
    }

    /**
     * Display the specified menu
     */
    public function show($id)
    {
        try {
            $menu = Menu::with('children', 'parent')->find($id);

            if (!$menu) {
                return response()->json([
                    'success' => false,
                    'message' => 'Menu not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $menu
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve menu',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred'
            ], 500);
        }
    }

    /**
     * Update the specified menu
     */
    public function update(Request $request, $id)
    {
        try {
            $menu = Menu::find($id);

            if (!$menu) {
                return response()->json([
                    'success' => false,
                    'message' => 'Menu not found'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'title' => 'sometimes|string|max:255',
                'parent_id' => 'nullable|exists:menus,id',
                'urutan' => 'nullable|integer',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Prevent menu from being its own parent
            if ($request->parent_id == $id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Menu cannot be its own parent'
                ], 422);
            }

            $menu->update($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Menu updated successfully',
                'data' => $menu->load('parent', 'children')
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update menu',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred'
            ], 500);
        }
    }

    /**
     * Remove the specified menu
     */
    public function destroy($id)
    {
        try {
            $menu = Menu::find($id);

            if (!$menu) {
                return response()->json([
                    'success' => false,
                    'message' => 'Menu not found'
                ], 404);
            }

            $menu->delete();

            return response()->json([
                'success' => true,
                'message' => 'Menu deleted successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete menu',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred'
            ], 500);
        }
    }
}