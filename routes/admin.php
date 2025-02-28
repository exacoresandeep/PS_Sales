<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\Api\ActivityController;
use App\Http\Controllers\Api\TargetController;
use App\Http\Controllers\Api\RouteController;
use App\Http\Controllers\Api\EmployeeController;

// Admin Authentication Routes
Route::get('/load-content/{page}', [AdminController::class, 'loadContent'])->name('load.content');
Route::get('/login', [AdminController::class, 'login'])->name('admin.login'); 
Route::post('/logout', [AdminController::class, 'logout'])->name('logout');
Route::prefix('admin')->group(function () {
    Route::get('/login', [AdminController::class, 'login'])->name('admin.login'); 
    Route::post('/doLogin', [AdminController::class, 'doLogin'])->name('admin.doLogin');

    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('admin.dashboard');
    Route::get('/activity-management', [AdminController::class, 'activity_management'])->name('admin.activity-management');
    // Route::get('/route-management', [AdminController::class, 'route_management'])->name('admin.route-management');
    
    Route::get('/routes', [RouteController::class, 'index'])->name('admin.route.index');
    Route::post('/routes/store', [RouteController::class, 'store'])->name('admin.route.store');
    Route::post('/routes/list', [RouteController::class, 'routesListing'])->name('admin.route.list');
    Route::post('/routes/update', [RouteController::class, 'update'])->name('admin.route.update');
    Route::get('/routes/getAllRoutesByDistrict/{district}', [RouteController::class, 'getAllRoutesByDistrict'])->name('admin.route.getAllRoutesByDistrict');


    Route::post('/targets/store', [TargetController::class, 'store'])->name('admin.target.store');
    Route::get('/targets', [TargetController::class, 'index'])->name('admin.target.index');
    Route::post('/targets/list', [TargetController::class, 'targetList'])->name('admin.target.list');
    Route::post('/targets/update', [TargetController::class, 'update'])->name('admin.target.update');
    Route::get('/targets/get/{id}', [TargetController::class, 'viewTargets'])->name('admin.target.get');
    Route::delete('/targets/delete/{id}', [TargetController::class, 'destroy'])->name('admin.target.delete');
    
    Route::get('/get-employees/{employeeTypeId}', [EmployeeController::class, 'getEmployeesByType'])->name('admin.getEmployees');
    Route::post('/targetList', [TargetController::class, 'targetList'])->name('admin.targetList');
    Route::get('/viewTarget/{id}', [TargetController::class, 'view'])->name('admin.viewTarget');
    // Route::post('/deleteTarget/{id}', [TargetController::class, 'delete'])->name('deleteTarget');

    
    Route::middleware('auth:sanctum')->group(function () {
    });
});
