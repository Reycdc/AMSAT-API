<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Membership;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MembershipController extends Controller
{
    /**
     * Display a listing of memberships
     */
    public function index()
    {
        $memberships = Membership::with('user')->latest()->get();

        return response()->json([
            'success' => true,
            'data' => $memberships
        ], 200);
    }

    /**
     * Store a newly created membership
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id|unique:memberships',
            'type' => 'required|in:free,premium,vip',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $membership = Membership::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Membership created successfully',
            'data' => $membership->load('user')
        ], 201);
    }

    /**
     * Display the specified membership
     */
    public function show($id)
    {
        $membership = Membership::with('user')->find($id);

        if (!$membership) {
            return response()->json([
                'success' => false,
                'message' => 'Membership not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $membership
        ], 200);
    }

    /**
     * Update the specified membership
     */
    public function update(Request $request, $id)
    {
        $membership = Membership::find($id);

        if (!$membership) {
            return response()->json([
                'success' => false,
                'message' => 'Membership not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'type' => 'required|in:free,premium,vip',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $membership->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Membership updated successfully',
            'data' => $membership->load('user')
        ], 200);
    }

    /**
     * Remove the specified membership
     */
    public function destroy($id)
    {
        $membership = Membership::find($id);

        if (!$membership) {
            return response()->json([
                'success' => false,
                'message' => 'Membership not found'
            ], 404);
        }

        $membership->delete();

        return response()->json([
            'success' => true,
            'message' => 'Membership deleted successfully'
        ], 200);
    }
}