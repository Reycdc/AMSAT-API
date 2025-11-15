<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    /**
     * Display a listing of users
     */
    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 15);
        $search = $request->get('search');

        $users = User::with('roles')
            ->when($search, function ($query, $search) {
                return $query->where('username', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $users
        ], 200);
    }

    /**
     * Store a newly created user
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required|string|max:255|unique:users',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'jenis_kelamin' => 'required|in:Laki-laki,Perempuan',
            'alamat' => 'nullable|string',
            'thumbnail' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'role' => 'required|in:admin,editor,author,redaktur,user',
            'status' => 'sometimes|in:active,inactive,banned',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $thumbnailPath = null;
        if ($request->hasFile('thumbnail')) {
            $thumbnailPath = $request->file('thumbnail')->store('thumbnails', 'public');
        }

        $user = User::create([
            'username' => $request->username,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'jenis_kelamin' => $request->jenis_kelamin,
            'alamat' => $request->alamat,
            'thumbnail' => $thumbnailPath,
            'status' => $request->get('status', 'active'),
            'is_verified' => $request->get('is_verified', false),
        ]);

        $user->assignRole($request->role);

        return response()->json([
            'success' => true,
            'message' => 'User created successfully',
            'data' => $user->load('roles')
        ], 201);
    }

    /**
     * Display the specified user
     */
    public function show($id)
    {
        $user = User::with('roles', 'permissions')->find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $user
        ], 200);
    }

    /**
     * Update the specified user
     */
    public function update(Request $request, $id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'username' => 'sometimes|string|max:255|unique:users,username,' . $id,
            'email' => 'sometimes|string|email|max:255|unique:users,email,' . $id,
            'password' => 'sometimes|string|min:8',
            'jenis_kelamin' => 'sometimes|in:Laki-laki,Perempuan',
            'alamat' => 'nullable|string',
            'thumbnail' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'role' => 'sometimes|in:admin,editor,author,redaktur,user',
            'status' => 'sometimes|in:active,inactive,banned',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        if ($request->hasFile('thumbnail')) {
            if ($user->thumbnail) {
                \Storage::disk('public')->delete($user->thumbnail);
            }
            $user->thumbnail = $request->file('thumbnail')->store('thumbnails', 'public');
        }

        if ($request->has('password')) {
            $user->password = Hash::make($request->password);
        }

        $user->fill($request->except(['password', 'thumbnail', 'role']));
        $user->save();

        if ($request->has('role')) {
            $user->syncRoles([$request->role]);
        }

        return response()->json([
            'success' => true,
            'message' => 'User updated successfully',
            'data' => $user->load('roles')
        ], 200);
    }

    /**
     * Remove the specified user
     */
    public function destroy($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        if ($user->thumbnail) {
            \Storage::disk('public')->delete($user->thumbnail);
        }

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'User deleted successfully'
        ], 200);
    }
}