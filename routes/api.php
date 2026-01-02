<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\MenuController;
use App\Http\Controllers\Api\GalleryController;
use App\Http\Controllers\Api\MembershipController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\BannerController;
use App\Http\Controllers\Api\ContentController;
use App\Http\Controllers\Api\MediaController;
use App\Http\Controllers\Api\CommentController;
use App\Http\Controllers\Api\LikeController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\GoogleController;
use App\Http\Controllers\Api\ChatController;

// Public Routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Google OAuth Routes
Route::get('/auth/google', [GoogleController::class, 'redirect']);
Route::get('/auth/google/callback', [GoogleController::class, 'callback']);

// Public Content Routes (Read Only)
Route::get('/content', [ContentController::class, 'index']);
Route::get('/content/{id}', [ContentController::class, 'show']);
Route::get('/galleries', [GalleryController::class, 'index']);
Route::get('/banners', [BannerController::class, 'index']);
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/menus', [MenuController::class, 'index']);
Route::get('/menus/flat', [MenuController::class, 'flat']);
Route::get('/dashboard-stats', [DashboardController::class, 'stats']);

// CRITICAL FIX: NO auth:sanctum middleware!
// RoleMiddleware handles both authentication and authorization

// Authenticated Routes (any logged in user)
Route::middleware('role:user|admin|editor|author|redaktur')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::put('/profile', [AuthController::class, 'updateProfile']);

    Route::apiResource('content', ContentController::class)->except(['index', 'show']);
    Route::post('/content/{id}/verify', [ContentController::class, 'verify']);
    Route::apiResource('media', MediaController::class);
    Route::apiResource('comments', CommentController::class);
    Route::get('/content/{id}/comments', [CommentController::class, 'getByContent']);

    Route::post('/content/{id}/like', [LikeController::class, 'toggle']);
    Route::get('/content/{id}/likes', [LikeController::class, 'getByContent']);
    Route::get('/my-likes', [LikeController::class, 'myLikes']);

    // Chat Routes
    Route::get('/chat/users', [ChatController::class, 'getUsers']);
    Route::get('/chat/conversations', [ChatController::class, 'conversations']);
    Route::post('/chat/conversations', [ChatController::class, 'startConversation']);
    Route::get('/chat/conversations/{conversationId}/messages', [ChatController::class, 'messages']);
    Route::post('/chat/conversations/{conversationId}/messages', [ChatController::class, 'sendMessage']);
    Route::post('/chat/conversations/{conversationId}/typing', [ChatController::class, 'typing']);
    Route::post('/chat/conversations/{conversationId}/read', [ChatController::class, 'markAsRead']);
});

// Admin Only Routes
Route::middleware('role:admin')->group(function () {
    Route::apiResource('users', UserController::class);
    Route::apiResource('memberships', MembershipController::class);
    Route::apiResource('banners', BannerController::class)->except(['index']);
});


// Admin/Editor Routes
Route::middleware('role:admin|editor')->group(function () {
    Route::apiResource('menus', MenuController::class)->except(['index']);
    Route::apiResource('galleries', GalleryController::class)->except(['index']);
    Route::apiResource('categories', CategoryController::class)->except(['index']);
    Route::apiResource('events', \App\Http\Controllers\Api\EventController::class);
});

// Admin/Redaktur Routes
Route::middleware('role:admin|redaktur')->group(function () {
    Route::post('/content/{id}/verify', [ContentController::class, 'verify']);
});
