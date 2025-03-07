<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\Api\ActivityController;
use App\Http\Controllers\Api\TargetController;
use App\Http\Controllers\Api\RouteController;
use App\Http\Controllers\Api\EmployeeController;
use App\Http\Controllers\Api\DealerController;

// Admin Authentication Routes
Route::get('/load-content/{page}', [AdminController::class, 'loadContent'])->name('load.content');
Route::get('/login', [AdminController::class, 'login'])->name('admin.login'); 
Route::post('/logout', [AdminController::class, 'logout'])->name('logout');
Route::prefix('admin')->group(function () {
    Route::get('/login', [AdminController::class, 'login'])->name('admin.login'); 
    Route::post('/doLogin', [AdminController::class, 'doLogin'])->name('admin.doLogin');
    Route::get('/employees-by-dealer/{dealer_id}', [ActivityController::class, 'getEmployeesByDealer']);
    Route::get('/dealers-by-district/{district_id}', [ActivityController::class, 'getDealersByDistrict']);
    Route::get('/get-districts', [RouteController::class, 'getDistricts'])->name('admin.get-districts');
    Route::get('/get-employees', [RouteController::class, 'getEmployees'])->name('admin.get-employees');
    Route::get('/get-locations', [RouteController::class, 'getLocations'])->name('admin.get-locations');

    
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('admin.dashboard');
   
    Route::prefix('activity')->group(function () {
        Route::get('/activity-type-index', [ActivityController::class, 'activityTypeIndex'])->name('admin.activity.activity-type-index');
        Route::get('/activity-type-list', [ActivityController::class, 'getActivityTypes'])->name('admin.activity.activity-type-list');
        Route::post('/activity-type-store', [ActivityController::class, 'activityTypeStore'])->name('admin.activity.activity-type-store');
        Route::get('/activity-type-edit/{activity_type}', [ActivityController::class, 'editActivityType'])->name('admin.activity.activity-type-edit');
        Route::put('/activity-type-update/{activity_type}', [ActivityController::class, 'updateActivityType'])->name('admin.activity.activity-type-update');
        Route::delete('/activity-type-delete/{activity_type}', [ActivityController::class, 'deleteActivityType'])->name('admin.activity.activity-type-delete');
    
        Route::get('/', [ActivityController::class, 'activityIndex'])->name('admin.activity.index');
        Route::get('/list', [ActivityController::class, 'list'])->name('admin.activity.list');
        Route::post('/store', [ActivityController::class, 'store'])->name('admin.activity.store');
        Route::get('/view/{activity}', [ActivityController::class, 'view'])->name('admin.activity.view');
        Route::get('/edit/{activity}', [ActivityController::class, 'edit'])->name('admin.activity.edit');
        Route::put('/update/{activity}', [ActivityController::class, 'update'])->name('admin.activity.update');
        Route::delete('/delete/{activity}', [ActivityController::class, 'delete'])->name('admin.activity.delete');
    
    });

    Route::prefix('routes')->group(function () {
        Route::get('/route-index', [RouteController::class, 'routeIndex'])->name('admin.route.route-index');
        Route::get('/route-list', [RouteController::class, 'routeList'])->name('admin.route.route-list');
        Route::post('/route-store', [RouteController::class, 'routeStore'])->name('admin.route.route-store');
        Route::get('/route-edit/{route_id}', [RouteController::class, 'editRoute'])->name('admin.route.route-edit');
        Route::put('/route-update/{route_id}', [RouteController::class, 'updateRoute'])->name('admin.route.route-update');
        Route::delete('/route-delete/{route_id}', [RouteController::class, 'deleteRoute'])->name('admin.route.route-delete');


        Route::get('/', [RouteController::class, 'assignedIndex'])->name('admin.route.index');
        Route::get('/assigned-list', [RouteController::class, 'assignedList'])->name('admin.route.assigned-list');
        Route::post('/store', [RouteController::class, 'storeAssignedRoute'])->name('admin.route.assigned-store');
        Route::get('/edit/{id}', [RouteController::class, 'editAssignedRoute'])->name('admin.route.assigned-edit');
        Route::put('/update/{id}', [RouteController::class, 'updateAssignedRoute'])->name('admin.route.assigned-update');
        Route::delete('/delete/{id}', [RouteController::class, 'deleteAssignedRoute'])->name('admin.route.assigned-delete');

    });


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
