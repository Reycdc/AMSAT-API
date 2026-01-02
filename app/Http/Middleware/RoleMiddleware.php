<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

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

            // Sanctum tokens are in format "id|token"
            // Extract the actual token part if it contains a pipe
            $tokenParts = explode('|', $token, 2);
            $actualToken = count($tokenParts) === 2 ? $tokenParts[1] : $token;

            // Hash token like Sanctum does
            $hashedToken = hash('sha256', $actualToken);

            // Get token data from database
            $tokenData = DB::table('personal_access_tokens')
                ->select('tokenable_id', 'expires_at')
                ->where('token', $hashedToken)
                ->first();

            if (!$tokenData) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated - Invalid token'
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

            // CRITICAL FIX: Load full User model and set it in Auth
            $user = User::find($userId);

            if (!$user || $user->status !== 'active') {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found or inactive'
                ], 401);
            }

            // SET AUTHENTICATED USER - This makes auth()->id() and auth()->user() work
            Auth::setUser($user);

            // CRITICAL FIX: Split pipe-separated roles
            // Laravel passes 'admin|editor' as ONE string, not two parameters
            $allRoles = [];
            foreach ($roles as $roleString) {
                // Split by pipe to handle 'admin|editor' format
                $splitRoles = explode('|', $roleString);
                $allRoles = array_merge($allRoles, $splitRoles);
            }
            // Remove duplicates and empty strings
            $allRoles = array_filter(array_unique($allRoles));

            // Check if user has ANY of the required roles
            $hasRole = false;

            foreach ($allRoles as $role) {
                $roleCount = DB::table('model_has_roles')
                    ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
                    ->where('model_has_roles.model_id', $userId)
                    ->where('roles.name', trim($role))  // Trim whitespace
                    ->where('roles.guard_name', 'api')
                    ->where(function ($query) {
                        // Accept both single and double backslash
                        $query->where('model_has_roles.model_type', 'App\Models\User')
                            ->orWhere('model_has_roles.model_type', 'App\\Models\\User');
                    })
                    ->count();

                if ($roleCount > 0) {
                    $hasRole = true;
                    break;
                }
            }

            if (!$hasRole) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized. Required role: ' . implode(' or ', $allRoles),
                    'user' => $user->username,
                    'debug' => config('app.debug') ? [
                        'user_id' => $userId,
                        'roles_checked' => $allRoles,
                        'raw_input' => $roles
                    ] : null
                ], 403);
            }

            // Update token last_used_at
            DB::table('personal_access_tokens')
                ->where('token', $hashedToken)
                ->update(['last_used_at' => now()]);

            // Store auth data for controllers (backward compatibility)
            $request->merge([
                'auth_user_id' => $userId,
                'auth_user_email' => $user->email,
                'auth_user_name' => $user->username
            ]);

            return $next($request);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Authorization failed',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
}