<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Categories\CategoryController;
use App\Http\Controllers\Reports\ImageController;
use App\Http\Controllers\Reports\ReportController;
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
});

