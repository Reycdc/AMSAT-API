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
    public function index()
    {
        $menus = Menu::with('children', 'parent')
            ->whereNull('parent_id')
            ->orderBy('urutan')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $menus
        ], 200);
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

        $menu = Menu::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Menu created successfully',
            'data' => $menu->load('parent')
        ], 201);
    }

    /**
     * Display the specified menu
     */
    public function show($id)
    {
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
    }

    /**
     * Update the specified menu
     */
    public function update(Request $request, $id)
    {
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
    }

    /**
     * Remove the specified menu
     */
    public function destroy($id)
    {
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
    }
}