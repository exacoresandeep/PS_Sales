<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Target;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class TargetController extends Controller
{
    public function getMonthlyTarget($month)
    {
        try {
            $currentYear = Carbon::now()->year;
            $employeeId = Auth::id();

            $targets = Target::where('month', $month)
                ->where('year', $currentYear)
                ->where('created_by', $employeeId)
                ->get();

            if ($targets->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 404,
                    'message' => 'No targets found for the selected month.',
                    'data' => null,
                ], 404);
            }

            $orders = Order::where('created_by', $employeeId)
            ->whereYear('created_at', $currentYear)
            ->whereMonth('created_at', $month)
            ->with('orderItems')
            ->get();

            $achievedTarget = $orders->flatMap(function ($order) {
                return $order->orderItems;
            })->sum('total_quantity');

            $response = [
                'targets' => $targets,
                'achieved_quantity' => $achievedTarget,
            ];

            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Target data retrieved successfully.',
                'data' => $response,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => 'An error occurred while retrieving target data.',
                'data' => $e->getMessage(), 
            ], 500);
        }
    }
}
