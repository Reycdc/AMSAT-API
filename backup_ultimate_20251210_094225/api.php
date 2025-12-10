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
use App\Http\Controllers\Api\SuratKeluarController;
use App\Http\Controllers\Api\SuratMasukController;
use App\Http\Controllers\Api\DisposisiSuratController;

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




    Route::get('/surat-keluar', [SuratKeluarController::class, 'index']);
    
    // Create surat keluar (All authenticated users)
    Route::post('/surat-keluar', [SuratKeluarController::class, 'store']);
    
    // Show surat keluar detail
    Route::get('/surat-keluar/{id}', [SuratKeluarController::class, 'show']);
    
    // Update surat keluar (Owner or Admin)
    Route::put('/surat-keluar/{id}', [SuratKeluarController::class, 'update']);
    
    // Delete surat keluar (Owner or Admin)
    Route::delete('/surat-keluar/{id}', [SuratKeluarController::class, 'destroy']);
    
    // Submit surat keluar for approval (Owner)
    Route::post('/surat-keluar/{id}/submit', [SuratKeluarController::class, 'submit']);
    
    // Approve surat keluar (Admin only)
    Route::post('/surat-keluar/{id}/approve', [SuratKeluarController::class, 'approve'])
        ->middleware('role:admin');
    
    // Reject surat keluar (Admin only)
    Route::post('/surat-keluar/{id}/reject', [SuratKeluarController::class, 'reject'])
        ->middleware('role:admin');
    
    
    // ========================================
    // SURAT MASUK ROUTES
    // ========================================
    
    // List surat masuk for current user
    Route::get('/surat-masuk', [SuratMasukController::class, 'index']);
    
    // List all surat masuk (Admin/Editor only)
    Route::get('/surat-masuk/all', [SuratMasukController::class, 'all'])
        ->middleware('role:admin|editor');
    
    // Get unread count
    Route::get('/surat-masuk/unread-count', [SuratMasukController::class, 'unreadCount']);
    
    // Create surat masuk (Admin/Editor only)
    Route::post('/surat-masuk', [SuratMasukController::class, 'store'])
        ->middleware('role:admin|editor');
    
    // Show surat masuk detail
    Route::get('/surat-masuk/{id}', [SuratMasukController::class, 'show']);
    
    // Update surat masuk (Admin/Editor only)
    Route::put('/surat-masuk/{id}', [SuratMasukController::class, 'update'])
        ->middleware('role:admin|editor');
    
    // Delete surat masuk (Admin/Editor only)
    Route::delete('/surat-masuk/{id}', [SuratMasukController::class, 'destroy'])
        ->middleware('role:admin|editor');
    
    // Update status surat masuk (Owner or Admin/Editor)
    Route::patch('/surat-masuk/{id}/status', [SuratMasukController::class, 'updateStatus']);
    
    
    // ========================================
    // DISPOSISI SURAT ROUTES
    // ========================================
    
    // List dispositions for current user (received)
    Route::get('/disposisi', [DisposisiSuratController::class, 'index']);
    
    // List dispositions sent by current user
    Route::get('/disposisi/sent', [DisposisiSuratController::class, 'sent']);
    
    // Get dispositions for specific surat masuk
    Route::get('/disposisi/surat/{suratMasukId}', [DisposisiSuratController::class, 'getBySurat']);
    
    // Create new disposition
    Route::post('/disposisi', [DisposisiSuratController::class, 'store']);
    
    // Show disposition detail
    Route::get('/disposisi/{id}', [DisposisiSuratController::class, 'show']);
    
    // Update disposition (Sender or Admin/Editor)
    Route::put('/disposisi/{id}', [DisposisiSuratController::class, 'update']);
    
    // Accept disposition (Receiver)
    Route::post('/disposisi/{id}/accept', [DisposisiSuratController::class, 'accept']);
    
    // Start processing disposition (Receiver)
    Route::post('/disposisi/{id}/process', [DisposisiSuratController::class, 'process']);
    
    // Complete disposition (Receiver)
    Route::post('/disposisi/{id}/complete', [DisposisiSuratController::class, 'complete']);
    
    // Delete disposition (Sender or Admin/Editor)
    Route::delete('/disposisi/{id}', [DisposisiSuratController::class, 'destroy']);
});