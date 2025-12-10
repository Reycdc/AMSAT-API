<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\DB;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        try {
            // Get token from Authorization header
            $token = $request->bearerToken();
            
            if (!$token) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated - No token provided'
                ], 401);
            }
            
            // Hash token like Sanctum does
            $hashedToken = hash('sha256', $token);
            
            // Get token data from database - NO ELOQUENT MODEL LOADING!
            $tokenData = DB::table('personal_access_tokens')
                ->select('tokenable_id', 'expires_at', 'last_used_at')
                ->where('token', $hashedToken)
                ->first();
            
            if (!$tokenData) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated - Invalid token'
                ], 401);
            }
            
            // Check if token is expired
            if ($tokenData->expires_at && now()->greaterThan($tokenData->expires_at)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated - Token expired'
                ], 401);
            }
            
            $userId = $tokenData->tokenable_id;
            
            // Verify user exists and is active
            $user = DB::table('users')
                ->select('id', 'username', 'email', 'status')
                ->where('id', $userId)
                ->first();
            
            if (!$user || $user->status !== 'active') {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated - User not found or inactive'
                ], 401);
            }
            
            // Check if user has any of the required roles - DIRECT SQL
            $hasRole = false;
            
            foreach ($roles as $role) {
                $roleExists = DB::table('model_has_roles')
                    ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
                    ->where('model_has_roles.model_type', 'App\\Models\\User')
                    ->where('model_has_roles.model_id', $userId)
                    ->where('roles.name', $role)
                    ->where('roles.guard_name', 'api')
                    ->exists();
                
                if ($roleExists) {
                    $hasRole = true;
                    break;
                }
            }
            
            if (!$hasRole) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized - Required role: ' . implode(' or ', $roles),
                    'user' => $user->username
                ], 403);
            }
            
            // Update token last_used_at
            DB::table('personal_access_tokens')
                ->where('token', $hashedToken)
                ->update(['last_used_at' => now()]);
            
            // Store authenticated user data in request for controllers
            $request->merge([
                'auth_user_id' => $userId,
                'auth_user_email' => $user->email,
                'auth_user_name' => $user->username
            ]);
            
            return $next($request);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Authorization check failed',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
}
