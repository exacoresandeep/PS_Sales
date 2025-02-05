<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\Api\ActivityController;

// Admin Authentication Routes
Route::get('/login', [AdminController::class, 'login'])->name('admin.login'); 
Route::prefix('admin')->group(function () {
    Route::get('/login', [AdminController::class, 'login'])->name('admin.login'); 
    Route::post('/doLogin', [AdminController::class, 'doLogin'])->name('admin.doLogin');

    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('admin.dashboard');
    Route::post('/logout', [AdminController::class, 'logout'])->name('admin.logout');
    Route::get('/activities', [AdminController::class, 'activities'])->name('admin.activities');
    Route::middleware('auth:sanctum')->group(function () {
    });
});
