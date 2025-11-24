<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\ProfileController;
use App\Http\Controllers\Categories\CategoryController;
use App\Http\Controllers\Dashboard\DashboardController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\Reports\CommentController;
use App\Http\Controllers\Reports\ImageController;
use App\Http\Controllers\Reports\LikeController;
use App\Http\Controllers\Reports\ReportController;
use App\Http\Controllers\Stats\StatsController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register'])->name('auth.register');
    Route::post('/login', [AuthController::class, 'login'])->name('auth.login');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [AuthController::class, 'me'])->name('auth.me');
        Route::post('/logout', [AuthController::class, 'logout'])->name('auth.logout');
    });
});

// Profile routes - User manages their own profile
Route::middleware('auth:sanctum')->group(function () {
    Route::patch('/profile', [ProfileController::class, 'updateProfile'])->name('profile.update');
});

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Reports
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/{id}', [ReportController::class, 'show'])->name('reports.show');
    Route::post('/reports', [ReportController::class, 'store'])->name('reports.store');
    Route::patch('/reports/{id}', [ReportController::class, 'update'])->name('reports.update');
    Route::delete('/reports/{id}', [ReportController::class, 'destroy'])->name('reports.destroy');
    Route::patch('/reports/{id}/status', [ReportController::class, 'changeStatus'])->name('reports.changeStatus');

    // Categories
    Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
    Route::get('/categories/{id}', [CategoryController::class, 'show'])->name('categories.show');
    Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
    Route::patch('/categories/{id}', [CategoryController::class, 'update'])->name('categories.update');
    Route::delete('/categories/{id}', [CategoryController::class, 'destroy'])->name('categories.destroy');

    // Images
    Route::post('/reports/{reportId}/images', [ImageController::class, 'store'])->name('images.store');
    Route::delete('/images/{id}', [ImageController::class, 'destroy'])->name('images.destroy');
    Route::get('/reports/{reportId}/images', [ImageController::class, 'getByReport'])->name('images.getByReport');

    // Comments
    Route::get('/reports/{reportId}/comments', [CommentController::class, 'index'])->name('comments.index');
    Route::post('/reports/{reportId}/comments', [CommentController::class, 'store'])->name('comments.store');
    Route::delete('/comments/{id}', [CommentController::class, 'destroy'])->name('comments.destroy');

    // Likes
    Route::get('/reports/{reportId}/likes/check', [LikeController::class, 'check'])->name('likes.check');
    Route::get('/reports/{reportId}/likes', [LikeController::class, 'index'])->name('likes.index');
    Route::post('/reports/{reportId}/likes', [LikeController::class, 'store'])->name('likes.store');
    Route::delete('/reports/{reportId}/likes', [LikeController::class, 'destroy'])->name('likes.destroy');
    Route::delete('/likes/{id}', [LikeController::class, 'destroyById'])->name('likes.destroyById');

    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount'])->name('notifications.unreadCount');
    Route::patch('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.markAsRead');
    Route::patch('/notifications/mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('notifications.markAllAsRead');
    Route::delete('/notifications/{id}', [NotificationController::class, 'destroy'])->name('notifications.destroy');
    Route::delete('/notifications', [NotificationController::class, 'deleteAll'])->name('notifications.deleteAll');

    // Statistics
    Route::prefix('stats')->group(function () {
        Route::get('/global', [StatsController::class, 'global'])->name('stats.global');
        Route::get('/user', [StatsController::class, 'user'])->name('stats.user');
        Route::get('/user/{userId}', [StatsController::class, 'user'])->name('stats.userById');
        Route::get('/category/{categoryId}', [StatsController::class, 'category'])->name('stats.category');
        Route::get('/timeline', [StatsController::class, 'timeline'])->name('stats.timeline');
    });

    // Dashboard
    Route::prefix('dashboard')->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard.index');
    });
});

