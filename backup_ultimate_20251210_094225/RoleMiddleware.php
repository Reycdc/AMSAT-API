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
            // Get token from header
            $token = $request->bearerToken();
            
            if (!$token) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated'
                ], 401);
            }
            
            // Hash token like Sanctum does
            $hashedToken = hash('sha256', $token);
            
            // Get user ID from database - NO MODEL LOADING!
            $tokenData = DB::table('personal_access_tokens')
                ->select('tokenable_id', 'expires_at')
                ->where('token', $hashedToken)
                ->first();
            
            if (!$tokenData) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid token'
                ], 401);
            }
            
            // Check expiration
            if ($tokenData->expires_at && now()->greaterThan($tokenData->expires_at)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token expired'
                ], 401);
            }
            
            $userId = $tokenData->tokenable_id;
            
            // Verify user is active
            $userExists = DB::table('users')
                ->where('id', $userId)
                ->where('status', 'active')
                ->exists();
            
            if (!$userExists) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not active'
                ], 401);
            }
            
            // Check role - DIRECT SQL QUERY
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
                    'message' => 'Unauthorized. Required role: ' . implode(' or ', $roles)
                ], 403);
            }
            
            // Update last_used_at
            DB::table('personal_access_tokens')
                ->where('token', $hashedToken)
                ->update(['last_used_at' => now()]);
            
            return $next($request);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Authorization failed'
            ], 500);
        }
    }
}