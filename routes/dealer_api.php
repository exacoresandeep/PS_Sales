<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Dealer\AuthController;
use App\Http\Controllers\Dealer\ProfileController;
use App\Http\Controllers\Dealer\OrderController;
use App\Http\Controllers\Dealer\PaymentController;

Route::post('login', [AuthController::class, 'login']);
Route::prefix('v1/dealer')->group(function () {
    // Dealer Authentication
    Route::post('login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
      
    });
});


  // Route::get('profile', [ProfileController::class, 'show']);
        // Route::post('profile/update', [ProfileController::class, 'update']);

        // Dealer Orders
        // Route::prefix('orders')->group(function () {
        //     Route::get('/', [OrderController::class, 'index']); // List dealer orders
        //     Route::get('{orderId}', [OrderController::class, 'show']); // Order details
        //     Route::post('create', [OrderController::class, 'store']); // Create new order
        //     Route::post('{orderId}/update', [OrderController::class, 'update']); // Update order
        // });

        // // Dealer Payments
        // Route::prefix('payments')->group(function () {
        //     Route::get('/', [PaymentController::class, 'index']); // List payments
        //     Route::get('{paymentId}', [PaymentController::class, 'show']); // View payment details
        //     Route::post('commit', [PaymentController::class, 'commitPayment']); // Add payment commitment
        // });
