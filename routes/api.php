<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\EmployeeController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\LeadController;
use App\Http\Controllers\Api\LeaveController;
use App\Http\Controllers\Api\ActivityController;
use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\TargetController;
use App\Http\Controllers\Api\RouteController;

Route::prefix('v1')->group(function () {
    Route::post('login', [AuthController::class, 'login']);
    Route::middleware('auth:sanctum')->group(function () {

        Route::post('employees', [EmployeeController::class, 'store']);
        Route::get('employee', [EmployeeController::class, 'show']);
        Route::post('/fileUpload', [AuthController::class, 'fileUpload']);

        Route::prefix('orders')->group(function () {
            Route::post('/', [OrderController::class, 'store']); // Store new order
            Route::get('/', [OrderController::class, 'index']); // List orders by current user ID
            Route::get('{orderId}', [OrderController::class, 'show']); // order details

            Route::get('filter', [OrderController::class, 'orderFilter']);

        
        });

        Route::prefix('leads')->group(function () {
            Route::post('/', [LeadController::class, 'store']); // Create new lead
            Route::get('/', [LeadController::class, 'index']); // List Leads by current user ID
            Route::get('{leadId}', [LeadController::class, 'show']); // Leads details
            Route::post('{leadId}/update', [LeadController::class, 'updateLead']); // Update lead status
        });
        Route::prefix('leave')->group(function () {
            Route::post('/', [LeaveController::class, 'store']); // Create a new leave entry
            Route::get('/', [LeaveController::class, 'index']); // List leave entries
            Route::get('{month}', [LeaveController::class, 'leaveByMonth']); // Leave entries for the selected month
            Route::post('/claim/{id}', [LeaveController::class, 'updateClaim']);
        });
        Route::prefix('activities')->group(function () {
            Route::get('/', [ActivityController::class, 'index']); // List activities for the current employee
            Route::get('{activityId}', [ActivityController::class, 'viewActivity']); // View Activity
            Route::post('{activityId}/update', [ActivityController::class, 'updateActivity']); // Update activity
        });
        Route::prefix('target')->group(function () {
            Route::get('/{month}', [TargetController::class, 'getMonthlyTarget']);
        });
        Route::prefix('route')->group(function () {
            Route::get('/todays-routes', [RouteController::class, 'getTodaysTrip']);
            Route::post('/{dealerId}/update-activity', [RouteController::class, 'updateDealerTripActivity']);
            Route::get('/{dealerId}/view-trip-details', [RouteController::class, 'viewTripDetails']);
            Route::post('/{tripRouteId}/add-dealer', [RouteController::class, 'addDealerToRoute']);
        });
        Route::prefix('attendance')->group(function () {
            Route::post('/punch-in', [AttendanceController::class, 'punchIn']);
            Route::post('/punch-out', [AttendanceController::class, 'punchOut']);
            Route::get('/auto-punch-out', [AttendanceController::class, 'autoPunchOut']);
            Route::get('/today', [AttendanceController::class, 'getTodayAttendance']);
        });

        Route::get('customer-types', [AuthController::class, 'getCustomerTypes']);
        Route::get('order-types', [AuthController::class, 'getOrderTypes']);
        Route::get('dealers', [AuthController::class, 'getDealers']);
        Route::get('products', [AuthController::class, 'getProducts']);
        Route::post('product-types', [AuthController::class, 'getProductTypes']);
        Route::get('product-rate', [AuthController::class, 'getProductRate']);
        Route::get('leave-types', [AuthController::class, 'getLeaveTypes']);

        
        Route::post('logout', [AuthController::class, 'logout']);

        
        
        
        Route::get('getVehicleCategory', [AuthController::class, 'getVehicleCategory']);
        Route::post('getVehicleTypeByCategory', [AuthController::class, 'getVehicleTypeByCategory']);

        Route::get('trackOrder', [AuthController::class, 'trackOrder']);
        Route::get('dealerOrderList', [AuthController::class, 'dealerOrderList']);



        Route::post('/fileUpload', [AuthController::class, 'uploadFile']);
        

    });
});
