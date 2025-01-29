<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Exception;

class OrderController extends Controller
{
    
    public function index(Request $request)
    {
        try {
            $employee = $request->user();

            if ($request->has('search_key')) {
                return $this->orderFilter($request); // Delegate to orderFilter
            }

            
           

            $orders = Order::where('created_by', $employee->id)
                ->with(['dealer:id,dealer_name'])
                ->select('id', 'total_amount', 'status', 'created_at', 'dealer_id')
                ->get();
                        
            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Orders fetched successfully',
                'data' => $orders->map(function ($order) {
                return [
                        'id' => $order->id,
                        'total_amount' => round((float) $order->total_amount, 2),
                        'status' => $order->status,
                        'created_at' => Carbon::parse($order->created_at)->format('d-m-Y'),
                        'dealer' => [
                            'name' => $order->dealer->dealer_name,
                        ],
                    ];
                }),
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    public function store(Request $request)
    {
        try {

            $employee = Auth::user();

            $validatedData = $request->validate([
                'order_type' => 'required|exists:order_types,id',
                'order_category' => 'nullable|string',
                'lead_id' => 'required|exists:leads,id',
                'dealer_id' => 'nullable|exists:dealers,id',
                'payment_terms' => 'required|in:Advance,Credit',
                'advance_amount' => 'nullable|numeric',
                'payment_date' => 'nullable|string',
                'utr_number' => 'nullable|string',
                'billing_date' => 'required|string',
                'reminder_date' => 'required|string',
                'total_amount' => 'nullable|numeric',
                'additional_information' => 'nullable|string',
                'status' => 'nullable|in:Pending,Dispatched,Delivered',
                'vehicle_category' => 'nullable|string',
                'vehicle_type' => 'nullable|string',
                'vehicle_number' => 'nullable|string',
                'driver_name' => 'nullable|string',
                'driver_phone' => 'nullable|string',
                'order_items' => 'required|array',
                'order_items.*.product_id' => 'required|exists:products,id',
                // 'order_items.*.total_quantity' => 'required|numeric',
                // 'order_items.*.priority_quantity' => 'nullable|string',
                'order_items.*.product_details' => 'nullable|array',
                'attachment' => 'nullable|array',
                'attachment.*' => 'nullable|string',
            ]);
            if (isset($validatedData['payment_date'])) {
                $validatedData['payment_date'] = Carbon::createFromFormat('d-m-Y', $validatedData['payment_date'])->format('Y-m-d');
            }

            $validatedData['billing_date'] = Carbon::createFromFormat('d-m-Y', $validatedData['billing_date'])->format('Y-m-d');
            $validatedData['reminder_date'] = Carbon::createFromFormat('d-m-Y', $validatedData['reminder_date'])->format('Y-m-d');
           
            $validatedData['created_by'] = $employee->id;

            if ($request->hasFile('attachment')) {
                $attachments = [];
                foreach ($validatedData['attachment'] as $fileName) {
                    $file = $request->file('attachment.' . $fileName);
                    if ($file) {
                        $filePath = $file->storeAs('orders', $fileName, 'public');
                        $attachments[] = $filePath;  
                    }
                }
                $validatedData['attachment'] = json_encode($attachments); 
            }
    

            $order = Order::create($validatedData);

         
            foreach ($validatedData['order_items'] as $orderItem) {
                if (isset($orderItem['product_details']) && is_array($orderItem['product_details'])) {
                    $orderItem['total_quantity'] = collect($orderItem['product_details'])
                        ->sum(function ($detail) {
                            return $detail['quantity'] ?? 0;
                        });
                   
                }
                $order->orderItems()->create($orderItem);
            }
           

            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Order created successfully!',
                'data' => $order,
            ], 200);

        } catch (Exception $e) {

            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    public function show($orderId)
    {
        try {

            $user = Auth::user();

            if ($user === null) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 400,
                    'message' => 'You must be logged in to view this order.'
                ], 400);
            }
            
            $order = Order::with([
                'orderType:id,name',
                'dealer:id,dealer_name,phone,email', 
                'orderItems.product:id,product_name',
                'lead:id,customer_type,customer_name,email,phone,address,instructions,record_details,status',
                'lead.customerType:id,name', 
            ])->findOrFail($orderId);

            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Orders details fetched successfully',
                'data' => $order,
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    public function orderFilter(Request $request)
    {
        try {
            $employeeId = Auth::id();

            if (!$employeeId) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 401,
                    'message' => 'Unauthorized user.',
                ], 401);
            }
            $searchKey = $request->input('search_key', '');
            $parsedDate = Carbon::createFromFormat('d/m/Y', $searchKey);
            $isDate = $parsedDate && $parsedDate->format('d/m/Y') === $searchKey;
            $ordersQuery = Order::with([
                'orderType:id,name',
                'dealer:id,dealer_name,phone,email',
                'orderItems.product:id,product_name',
                'lead:id,customer_type,customer_name,email,phone,address,instructions,record_details,status',
                'lead.customerType:id,name'
            ])
            ->where('created_by', $employeeId);
            if ($isDate) {
                $ordersQuery->whereDate('created_at', $parsedDate);
            } else {
           
                $searchKey = strtolower($searchKey);
                
                if (in_array($searchKey, ['all', 'pending', 'accepted'])) {
                    if ($searchKey !== 'all') {
                        $ordersQuery->where('status', ucfirst($searchKey));
                    }
                }
    
                if (in_array($searchKey, ['today', 'weekly', 'monthly'])) {
                    $startDate = null;
                    $endDate = null;
                    
                    if ($searchKey == 'today') {
                        $startDate = Carbon::today()->startOfDay();
                        $endDate = Carbon::today()->endOfDay();
                    } elseif ($searchKey == 'weekly') {
                        $startDate = Carbon::now()->startOfWeek();
                        $endDate = Carbon::now()->endOfWeek();
                    } elseif ($searchKey == 'monthly') {
                        $startDate = Carbon::now()->startOfMonth();
                        $endDate = Carbon::now()->endOfMonth();
                    }
    
                    if ($startDate && $endDate) {
                        $ordersQuery->whereBetween('created_at', [$startDate, $endDate]);
                    }
                }
    
                $currentYear = Carbon::now()->year;
                $financialStartDate = Carbon::create($currentYear - 1, 4, 1); // Starting from April 1st of last year
                $financialEndDate = Carbon::create($currentYear, 3, 31); // Ending on March 31st of current year
    
                $ordersQuery->whereBetween('created_at', [$financialStartDate, $financialEndDate]);
            }
    
            // Fetch filtered orders
            $orders = $ordersQuery->get();
    
            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Orders filtered successfully',
                'data' => $orders,
            ], 200);
    
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}