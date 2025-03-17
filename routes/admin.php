<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\Api\ActivityController;
use App\Http\Controllers\Api\TargetController;
use App\Http\Controllers\Api\RouteController;
use App\Http\Controllers\Api\EmployeeController;
use App\Http\Controllers\Api\DealerController;
use App\Http\Controllers\AccountsController;

    Route::get('/', [AdminController::class, 'login'])->name('login');
    Route::post('/doLogin', [AdminController::class, 'doLogin'])->name('doLogin');


Route::post('/logout', [AdminController::class, 'logout'])->name('logout')->middleware('auth');

Route::prefix('sales')->middleware('auth')->group(function () {
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('sales.dashboard');

    Route::prefix('activity')->group(function () {
        Route::get('/type', [ActivityController::class, 'activityTypeIndex'])->name('sales.activity.activity-type-index');
        Route::get('/type/list', [ActivityController::class, 'getActivityTypes'])->name('sales.activity.activity-type-list');
        Route::post('/type/store', [ActivityController::class, 'activityTypeStore'])->name('sales.activity.type.store');
        Route::get('/type/edit/{activity_type}', [ActivityController::class, 'editActivityType'])->name('sales.activity.type.edit');
        Route::put('/type/update/{activity_type}', [ActivityController::class, 'updateActivityType'])->name('sales.activity.type.update');
        Route::delete('/type/delete/{activity_type}', [ActivityController::class, 'deleteActivityType'])->name('sales.activity.type.delete');

        Route::get('/', [ActivityController::class, 'activityIndex'])->name('sales.activity.index');
        Route::get('/list', [ActivityController::class, 'list'])->name('sales.activity.list');
        Route::post('/store', [ActivityController::class, 'store'])->name('sales.activity.store');
        Route::get('/view/{activity}', [ActivityController::class, 'view'])->name('sales.activity.view');
        Route::get('/edit/{activity}', [ActivityController::class, 'edit'])->name('sales.activity.edit');
        Route::put('/update/{activity}', [ActivityController::class, 'update'])->name('sales.activity.update');
        Route::delete('/delete/{activity}', [ActivityController::class, 'delete'])->name('sales.activity.delete');
    });

    Route::prefix('routes')->group(function () {
        Route::get('/', [RouteController::class, 'assignedIndex'])->name('sales.route.index');
        Route::get('/list', [RouteController::class, 'assignedList'])->name('sales.route.assigned.list');
        Route::post('/store', [RouteController::class, 'storeAssignedRoute'])->name('sales.route.assigned.store');
        Route::get('/edit/{id}', [RouteController::class, 'editAssignedRoute'])->name('sales.route.assigned.edit');
        Route::put('/update/{id}', [RouteController::class, 'updateAssignedRoute'])->name('sales.route.assigned.update');
        Route::delete('/delete/{id}', [RouteController::class, 'deleteAssignedRoute'])->name('sales.route.assigned.delete');

        // Sub-routes
        Route::get('/type', [RouteController::class, 'routeIndex'])->name('sales.route.type.index');
        Route::get('/type/list', [RouteController::class, 'routeList'])->name('sales.route.type.list');
        Route::post('/type/store', [RouteController::class, 'routeStore'])->name('sales.route.type.store');
        Route::get('/type/edit/{route_id}', [RouteController::class, 'editRoute'])->name('sales.route.type.edit');
        Route::put('/type/update/{route_id}', [RouteController::class, 'updateRoute'])->name('sales.route.type.update');
        Route::delete('/type/delete/{route_id}', [RouteController::class, 'deleteRoute'])->name('sales.route.type.delete');
    });

    Route::prefix('targets')->group(function () {
        Route::get('/', [TargetController::class, 'index'])->name('sales.target.index');
        Route::post('/list', [TargetController::class, 'targetList'])->name('sales.target.list');
        Route::post('/store', [TargetController::class, 'store'])->name('sales.target.store');
        Route::post('/update', [TargetController::class, 'update'])->name('sales.target.update');
        Route::get('/view/{id}', [TargetController::class, 'viewTargets'])->name('sales.target.view');
        Route::delete('/delete/{id}', [TargetController::class, 'destroy'])->name('sales.target.delete');
        Route::get('/{id}', [TargetController::class, 'getTargetDetails'])->name('sales.target.details');
        Route::get('/getVisitCount/{employeeType}/employee/{employee}', [TargetController::class, 'getVisitCount'])->name('sales.getVisitCount');
    });

    Route::get('/get-employees/{employeeTypeId}', [EmployeeController::class, 'getEmployeesByType'])->name('sales.getEmployees');
    Route::get('/employees-by-dealer/{dealer_id}', [ActivityController::class, 'getEmployeesByDealer']);
    Route::get('/dealers-by-district/{district_id}', [ActivityController::class, 'getDealersByDistrict']);
    Route::get('/get-districts', [RouteController::class, 'getDistricts'])->name('sales.get-districts');
    Route::get('/get-employees', [RouteController::class, 'getEmployees'])->name('sales.get-employees');
    Route::get('/get-locations', [RouteController::class, 'getLocations'])->name('sales.get-locations');

});

Route::prefix('accounts')->middleware('auth')->group(function () {
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('accounts.dashboard');
    Route::prefix('orders')->group(function () {
        Route::get('/', [AccountsController::class, 'index'])->name('accounts.orders.index');
        Route::get('/list', [AccountsController::class, 'orderList'])->name('accounts.orders.list');
        Route::get('/view/{id}', [AccountsController::class, 'viewOrder'])->name('view'); 
        Route::post('/approve/{id}', [AccountsController::class, 'approveOrder'])->name('accounts.orders.approve');
        Route::post('/reject/{id}', [AccountsController::class, 'rejectOrder'])->name('accounts.orders.reject');


    });
});

Route::prefix('admin')->middleware('auth')->group(function () {
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('admin.dashboard');
    Route::prefix('users')->group(function () {
        Route::get('/', [TargetController::class, 'usersIndex'])->name('admin.users.index');
        Route::post('/list', [TargetController::class, 'usersList'])->name('admin.users.list');
        Route::post('/store', [TargetController::class, 'usersStore'])->name('admin.users.store');
        Route::post('/update', [TargetController::class, 'usersUpdate'])->name('admin.users.update');
        Route::delete('/delete/{id}', [TargetController::class, 'usersDestroy'])->name('admin.users.delete');
    });
});




