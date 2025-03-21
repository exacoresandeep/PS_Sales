<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GreytHRController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\EmployeeController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\LeadController;
use App\Http\Controllers\Api\LeaveController;
use App\Http\Controllers\Api\ActivityController;
use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\TargetController;
use App\Http\Controllers\Api\RouteController;
use App\Http\Controllers\Api\StockController;
use App\Http\Controllers\Dealer\DealerController;
use App\Http\Controllers\Dealer\DealerOrderController;

Route::prefix('v1')->group(function () {

    Route::prefix('dealer')->group(function () {
        Route::post('login', [DealerController::class, 'login']);

        Route::middleware('auth:sanctum')->group(function () {
            Route::post('store', [DealerController::class, 'store']);
            Route::get('profile', [DealerController::class, 'getDealerProfile']);

            Route::get('order-types', [AuthController::class, 'getOrderTypes']);
            Route::get('payment-terms', [AuthController::class, 'getPaymentTerms']);
            Route::get('products', [AuthController::class, 'getProducts']);
            Route::post('product-types', [AuthController::class, 'getProductTypes']);
            Route::get('getVehicleCategory', [AuthController::class, 'getVehicleCategory']);
            Route::post('getVehicleTypeByCategory', [AuthController::class, 'getVehicleTypeByCategory']);

            Route::prefix('orders')->group(function () {
                Route::post('/', [DealerOrderController::class, 'store']); // Store new order
                Route::get('/', [DealerOrderController::class, 'index']); // List orders by current user ID
                Route::get('/{orderId}', [DealerOrderController::class, 'show']); // order details
                // Route::post('/filter', [DealerOrderController::class, 'orderFilter']);
                Route::post('/track-order', [DealerOrderController::class, 'trackOrder']);
                Route::post('/monthly-transaction', [DealerOrderController::class, 'monthlySalesTransaction']);
                Route::post('/outstanding-payments', [DealerOrderController::class, 'outstandingPaymentsList']);
                Route::get('/outstanding-payments/{orderId}', [DealerOrderController::class, 'opDetails']);
                Route::post('/order-request-list', [DealerOrderController::class, 'orderRequestList']);
                Route::get('/order-request/{orderId}', [DealerOrderController::class, 'orderRequestDetails']);
                Route::post('/order-request/{orderId}', [DealerOrderController::class, 'orderRequestStatusUpdate']);
            });

            Route::get('/payment-history', [DealerOrderController::class, 'paymentHistoryList']);
            Route::get('/payment-history/{orderId}', [DealerOrderController::class, 'paymentHistoryOrderDetails']);
            Route::get('/credit-notes', [DealerOrderController::class, 'creditNoteList']);
            Route::get('/credit-notes/{orderId}', [DealerOrderController::class, 'creditNoteDetails']);
            Route::get('support', [DealerOrderController::class, 'getSupport']);
            Route::post('logout', [DealerController::class, 'logout']);

        });
    });

    Route::post('login', [AuthController::class, 'login']);
    // Route::post('login', [GreytHRController::class, 'login']);
    Route::middleware('auth:sanctum')->group(function () {

        Route::post('employees', [EmployeeController::class, 'store']);
        Route::get('employee', [EmployeeController::class, 'show']);
        Route::get('employees/filter', [EmployeeController::class, 'filterEmployeesByType']);
        Route::post('/fileUpload', [AuthController::class, 'fileUpload']);
        Route::get('/filter', [AuthController::class, 'getFilteredOrders']);

        Route::prefix('orders')->group(function () {
            Route::post('/', [OrderController::class, 'store']); // Store new order
            Route::get('/', [OrderController::class, 'index']); // List orders by current user ID
            Route::get('{orderId}', [OrderController::class, 'show']); // order details
            Route::post('/filter', [OrderController::class, 'orderFilter']);

            Route::get('/dealer/outstanding-payments', [OrderController::class, 'outstandingPaymentsList']); 
            Route::get('/dealer/view-outstanding-payment/{orderId}', [OrderController::class, 'viewOutstandingPaymentOrderDetails']); 
            Route::post('/dealer/outstanding-payment/{id}/add-commitment', [OrderController::class, 'addOutstandingPaymentCommitment']);

            Route::post('/send-for-approval/{orderId}', [OrderController::class, 'sendForApproval']);
            Route::post('/order-approval-list', [OrderController::class, 'orderApprovalList']);
            Route::get('/order-approval/{orderId}', [OrderController::class, 'orderApprovalDetails']);
         
        });

        Route::prefix('leads')->group(function () {
            Route::post('/', [LeadController::class, 'store']); // Create new lead
            Route::get('/', [LeadController::class, 'index']); // List Leads by current user ID
            Route::get('/{customer_type_id}/filter', [LeadController::class, 'getleadsFilter']);
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
            // Route::post('/list', [TargetController::class, 'indexList']);
            Route::post('/', [TargetController::class, 'getTargets']);
        });
        Route::prefix('route')->group(function () {
            Route::get('/todays-routes', [RouteController::class, 'getTodaysTrip']);
            Route::post('/{dealerId}/update-activity', [RouteController::class, 'updateDealerTripActivity']);
            Route::get('/{dealerId}/view-trip-details', [RouteController::class, 'viewTripDetails']);
            Route::post('/{tripRouteId}/add-dealer', [RouteController::class, 'addDealerToRoute']);
            
            Route::get('/routeList', [RouteController::class, 'routeList']);
            Route::post('/routeReschedule', [RouteController::class, 'routeReschedule']);
            Route::get('/todaysRouteSchedule', [RouteController::class, 'todaysRouteSchedule']);
            Route::get('/currentWeekRoutes', [RouteController::class, 'currentWeekRoutes']);
            Route::post('/changeRouteStatus', [RouteController::class, 'changeRouteStatus']);
            Route::get('/{district_id}', [RouteController::class, 'getRoutesByDistrict']);

        });
        Route::prefix('attendance')->group(function () {
            Route::post('/punch-in', [AttendanceController::class, 'punchIn']);
            Route::post('/punch-out', [AttendanceController::class, 'punchOut']);
            // Route::get('/auto-punch-out', [AttendanceController::class, 'autoPunchOut']);
            Route::get('/today', [AttendanceController::class, 'getTodayAttendance']);
        });
        Route::prefix('stock')->group(function () {
            Route::get('/stock-insights', [StockController::class, 'stockList']);
            Route::get('/product-stock/{product_details_id}', [StockController::class, 'getProductStockDetails']);
            Route::get('/stock-filter', [StockController::class, 'stockFilter']);
        });
        Route::prefix('report')->group(function () {
            //Sales Report
            Route::get('/sereport', [OrderController::class, 'salesExecutiveSalesReport']);
            Route::get('/sales-executive/{employee_id}/sales-report', [OrderController::class, 'salesReportDetails']);

            //Order Report
            Route::get('/order-report-listing', [OrderController::class, 'orderReportListing']);
            Route::get('/sales-executive/{employee_id}/order-report', [OrderController::class, 'orderReportDetails']);

            //Leads Report
            Route::get('/lead-report-listing', [OrderController::class, 'leadReportListing']);
            Route::get('/sales-executive/{employee_id}/lead-report', [OrderController::class, 'leadReportDetails']);

            //Activity Report
            Route::get('/activity-report-listing', [ActivityController::class, 'activityReportListing']);
            Route::get('/sales-executive/{employee_id}/activity-report', [ActivityController::class, 'activityReportDetails']);

            Route::get('/routes-report', [RouteController::class, 'getRoutesReport']);
            Route::get('/routes-details/{routeId}', [RouteController::class, 'getRouteDetails']);


        });

        Route::get('customer-types', [AuthController::class, 'getCustomerTypes']);
        Route::get('order-types', [AuthController::class, 'getOrderTypes']);
        Route::get('dealers', [AuthController::class, 'getDealers']);
        Route::get('products', [AuthController::class, 'getProducts']);
        Route::post('product-types', [AuthController::class, 'getProductTypes']);
        Route::get('product-rate', [AuthController::class, 'getProductRate']);
        Route::get('leave-types', [AuthController::class, 'getLeaveTypes']);
        Route::get('payment-terms', [AuthController::class, 'getPaymentTerms']);
        
        Route::get('districts', [AuthController::class, 'getDistricts']);

        Route::post('logout', [AuthController::class, 'logout']);
        
        Route::get('getVehicleCategory', [AuthController::class, 'getVehicleCategory']);
        Route::post('getVehicleTypeByCategory', [AuthController::class, 'getVehicleTypeByCategory']);

        Route::get('trackOrder', [AuthController::class, 'trackOrder']);
        Route::get('dealerOrderList', [OrderController::class, 'dealerOrderList']);
        Route::get('dealerOrderDetails/{orderId}', [OrderController::class, 'dealerOrderDetails']); 
        Route::post('dealerOrderStatusUpdate/{orderId}', [OrderController::class, 'dealerOrderStatusUpdate']); 
        Route::get('dealerOrderFilter', [OrderController::class, 'dealerOrderFilter']); 

        // Route::post('/fileUpload', [AuthController::class, 'uploadFile']);
        

    });

});


Route::get('/greythr/access-token', [GreytHRController::class, 'getAccessToken']);
