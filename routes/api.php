<?php

// routes/api.php

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

// Public Routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Public Content Routes (Read Only)
Route::get('/content', [ContentController::class, 'index']);
Route::get('/content/{id}', [ContentController::class, 'show']);
Route::get('/galleries', [GalleryController::class, 'index']);
Route::get('/banners', [BannerController::class, 'index']);
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/menus', [MenuController::class, 'index']);

// Protected Routes
Route::middleware('auth:sanctum')->group(function () {
    
    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::put('/profile', [AuthController::class, 'updateProfile']);
    
    // Users (Admin Only)
    Route::middleware('role:admin')->group(function () {
        Route::apiResource('users', UserController::class);
    });
    
    // Menus (Admin/Editor)
    Route::middleware('role:admin|editor')->group(function () {
        Route::apiResource('menus', MenuController::class)->except(['index']);
    });
    
    // Galleries (Admin/Editor)
    Route::middleware('role:admin|editor')->group(function () {
        Route::apiResource('galleries', GalleryController::class)->except(['index']);
    });
    
    // Memberships (Admin Only)
    Route::middleware('role:admin')->group(function () {
        Route::apiResource('memberships', MembershipController::class);
    });
    
    // Categories (Admin/Editor)
    Route::middleware('role:admin|editor')->group(function () {
        Route::apiResource('categories', CategoryController::class)->except(['index']);
    });
    
    // Banners (Admin Only)
    Route::middleware('role:admin')->group(function () {
        Route::apiResource('banners', BannerController::class)->except(['index']);
    });
    
    // Content (Authenticated Users)
    Route::apiResource('content', ContentController::class)->except(['index', 'show']);
    Route::post('/content/{id}/verify', [ContentController::class, 'verify'])->middleware('role:admin|redaktur');
    
    // Media (Content Owner or Admin)
    Route::apiResource('media', MediaController::class);
    
    // Comments (Authenticated Users)
    Route::apiResource('comments', CommentController::class);
    Route::get('/content/{id}/comments', [CommentController::class, 'getByContent']);
    
    // Likes (Authenticated Users)
    Route::post('/content/{id}/like', [LikeController::class, 'toggle']);
    Route::get('/content/{id}/likes', [LikeController::class, 'getByContent']);
    Route::get('/my-likes', [LikeController::class, 'myLikes']);
});