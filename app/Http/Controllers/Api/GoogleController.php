<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Str;

class GoogleController extends Controller
{
  /**
   * Redirect to Google OAuth
   */
  public function redirect()
  {
    return Socialite::driver('google')
      ->stateless()
      ->redirect();
  }

  /**
   * Handle Google OAuth callback
   */
  public function callback()
  {
    try {
      $googleUser = Socialite::driver('google')
        ->stateless()
        ->user();

      // Find existing user by google_id or email
      $user = User::where('google_id', $googleUser->getId())
        ->orWhere('email', $googleUser->getEmail())
        ->first();

      if ($user) {
        // Update google_id if not set
        if (!$user->google_id) {
          $user->google_id = $googleUser->getId();
          $user->save();
        }
      } else {
        // Create new user
        $user = User::create([
          'username' => $this->generateUniqueUsername($googleUser->getName()),
          'email' => $googleUser->getEmail(),
          'google_id' => $googleUser->getId(),
          'password' => Hash::make(Str::random(24)),
          'thumbnail' => $googleUser->getAvatar(),
          'jenis_kelamin' => 'L', // Default, can be updated later
          'status' => 'active'
        ]);

        // Assign default role
        $user->assignRole('user');
      }

      // Check if user is active
      if ($user->status !== 'active') {
        return redirect(config('app.frontend_url', 'http://localhost:5173') . '/login?error=account_inactive');
      }

      // Generate token
      $token = $user->createToken('google_auth_token')->plainTextToken;

      // Load user roles
      $user->load('roles', 'permissions');

      // Redirect to frontend with token
      $frontendUrl = config('app.frontend_url', 'http://localhost:5173');
      return redirect("{$frontendUrl}/auth/callback?token={$token}&user=" . urlencode(json_encode($user)));

    } catch (\Exception $e) {
      $frontendUrl = config('app.frontend_url', 'http://localhost:5173');
      return redirect("{$frontendUrl}/login?error=" . urlencode($e->getMessage()));
    }
  }

  /**
   * Generate unique username from name
   */
  private function generateUniqueUsername($name)
  {
    $baseUsername = Str::slug($name, '_');
    $username = $baseUsername;
    $counter = 1;

    while (User::where('username', $username)->exists()) {
      $username = $baseUsername . '_' . $counter;
      $counter++;
    }

    return $username;
  }
}
