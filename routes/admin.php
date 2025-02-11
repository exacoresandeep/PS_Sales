<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\Api\ActivityController;
use App\Http\Controllers\Api\TargetController;

// Admin Authentication Routes
Route::get('/load-content/{page}', [AdminController::class, 'loadContent'])->name('load.content');
Route::get('/login', [AdminController::class, 'login'])->name('admin.login'); 
Route::post('/logout', [AdminController::class, 'logout'])->name('logout');
Route::prefix('admin')->group(function () {
    Route::get('/login', [AdminController::class, 'login'])->name('admin.login'); 
    Route::post('/doLogin', [AdminController::class, 'doLogin'])->name('admin.doLogin');

    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('admin.dashboard');
    Route::get('/activity-management', [AdminController::class, 'activity_management'])->name('admin.activity-management');
    Route::get('/route-management', [AdminController::class, 'route_management'])->name('admin.route-management');
    Route::get('/target-management', [TargetController::class, 'index'])->name('admin.target.index');
    Route::post('/targetList', [TargetController::class, 'targetList'])->name('admin.targetList');
    Route::get('/viewTarget/{id}', [TargetController::class, 'view'])->name('admin.viewTarget');
    Route::post('/deleteTarget/{id}', [TargetController::class, 'delete'])->name('deleteTarget');

    
    Route::middleware('auth:sanctum')->group(function () {
    });
});
