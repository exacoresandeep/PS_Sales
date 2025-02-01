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
            if ($request->has('search_key')) {
                return $this->orderFilter($request); 
            }


            $employee = Auth::user();
            if($employee)
            {
                // $employeeTypeId = $employee->employee_type_id;
                // if($employeeTypeId) {
                //     $dealerFlagOrder = 0;
                // }
                // else{
                //     $dealerFlagOrder = 1;
                // }

                $orders = Order::
                    where('created_by', $employee->id)
                    ->where('dealer_flag_order',0)
                    ->with(['dealer:id,dealer_name'])
                    ->select('id', 'total_amount', 'status', 'created_at', 'dealer_id')
                    ->get()
                    ->map(function ($order) {
                
                    $order->total_amount = (float) sprintf("%.2f", $order->total_amount);            
                        return $order;
                    });     
                return response()->json([
                    'success' => true,
                    'statusCode' => 200,
                    'message' => 'Orders fetched successfully',
                    'data' => $orders->map(function ($order) {
                    return [
                            'id' => $order->id,
                            'total_amount' => $order->total_amount,
                            'status' => $order->status,
                            'created_at' => $order->created_at->format('d-m-Y'),
                            'dealer' => [
                                'name' => $order->dealer->dealer_name,
                            ],
                        ];
                    }),
                ], 200);
            }else{
                return response()->json([
                    'success' => false,
                    'statusCode' => 401,
                    'message' => "User not Authenticated",
                ], 401);
            }

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    public function store1(Request $request)
    {
        try {

            $employee = Auth::user();
            if($employee)
            {
                $employeeTypeId = $employee->employee_type_id;
                // 
                if($employeeTypeId=="1"){
                    $dealerFlagOrder = '0';    
                }
                else if($employeeTypeId=="2"){
                    $dealerFlagOrder = '1';    
                }else{
                    $dealerFlagOrder = '0';  
                }
                
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
                $validatedData['payment_date'] = Carbon::createFromFormat('d-m-Y', $validatedData['payment_date'])->format('Y-m-d');

                $validatedData['billing_date'] = Carbon::createFromFormat('d-m-Y', $validatedData['billing_date'])->format('Y-m-d');
                $validatedData['reminder_date'] = Carbon::createFromFormat('d-m-Y', $validatedData['reminder_date'])->format('Y-m-d');
            
                $validatedData['created_by'] = $employee->id;
                // $validatedData['dealer_flag_order'] = $dealerFlagOrder;

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
            }else{
                return response()->json([
                    'success' => false,
                    'statusCode' => 401,
                    'message' => "User not Authenticated",
                ], 401);
            }

            
           


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
            if (!$employee) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 401,
                    'message' => "User not Authenticated",
                ], 401);
            }

            // $employeeTypeId = $employee->employee_type_id;
            // if($employeeTypeId) {
            //     $dealerFlagOrder = 0;
            // }
            // else{
            //     $dealerFlagOrder = 1;
            // }

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
                'order_items.*.product_details' => 'nullable|array',
                'attachment' => 'nullable|array',
                'attachment.*' => 'nullable|file|mimes:jpg,png,pdf|max:2048',
            ]);

            if (!empty($validatedData['payment_date'])) {
                $validatedData['payment_date'] = Carbon::createFromFormat('d-m-Y', $validatedData['payment_date'])->format('Y-m-d');
            }
            $validatedData['billing_date'] = Carbon::createFromFormat('d-m-Y', $validatedData['billing_date'])->format('Y-m-d');
            $validatedData['reminder_date'] = Carbon::createFromFormat('d-m-Y', $validatedData['reminder_date'])->format('Y-m-d');
        
            $validatedData['created_by'] = $employee->id;
            // $validatedData['dealer_flag_order'] = $dealerFlagOrder;

            if ($request->hasFile('attachment')) {
                $attachments = [];
                foreach ($request->file('attachment') as $file) {
                    $filePath = $file->storeAs('orders', $file->getClientOriginalName(), 'public');
                    $attachments[] = $filePath;
                }
                $validatedData['attachment'] = json_encode(array_map('strval', $attachments), JSON_UNESCAPED_UNICODE);
            }

            $order = Order::create($validatedData);

            if (!empty($validatedData['order_items'])) {
                foreach ($validatedData['order_items'] as $orderItem) {
                    if (isset($orderItem['product_details']) && is_array($orderItem['product_details'])) {
                        $orderItem['total_quantity'] = collect($orderItem['product_details'])
                            ->sum(fn ($detail) => $detail['quantity'] ?? 0);
                    }
                    $order->orderItems()->create($orderItem);
                }
            }

            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Order created successfully!',
                'data' => [
                    'order_type' => $order->order_type,
                    'order_category' => $order->order_category,
                    'lead_id' => $order->lead_id,
                    'dealer_id' => $order->dealer_id,
                    'payment_terms' => $order->payment_terms,
                    'advance_amount' => round($order->advance_amount, 2), 
                    'payment_date' => $order->payment_date ? Carbon::parse($order->payment_date)->format('d-m-Y') : null,
                    'utr_number' => $order->utr_number,
                    'billing_date' => Carbon::parse($order->billing_date)->format('d-m-Y'),
                    'reminder_date' => Carbon::parse($order->reminder_date)->format('d-m-Y'),
                    'total_amount' => round($order->total_amount, 2),
                    'additional_information' => $order->additional_information,
                    'status' => $order->status,
                    'created_by' => $order->created_by,
                    'updated_at' => Carbon::parse($order->updated_at)->format('d-m-Y H:i:s'),
                    'created_at' => Carbon::parse($order->created_at)->format('d-m-Y H:i:s'),
                    'id' => $order->id,
                ],
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
            $order->billing_date = $order->billing_date ? Carbon::parse($order->billing_date)->format('d-m-Y') : null;
            $order->reminder_date = $order->reminder_date ? Carbon::parse($order->reminder_date)->format('d-m-Y') : null;
            $order->payment_date = $order->payment_date ? Carbon::parse($order->payment_date)->format('d-m-Y') : null;
            $order->created_at = Carbon::parse($order->created_at)->format('d-m-Y');
            $order->updated_at = Carbon::parse($order->updated_at)->format('d-m-Y');
    
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


    public function dealerOrderList()
    {
        $employeeId = Auth::id();

        if (!$employeeId) {
            return response()->json([
                'success' => false,
                'statusCode' => 401,
                'message' => 'Unauthorized user.',
            ], 401);
        }

        $orders = Order::join('dealers', 'dealers.id', '=', 'orders.created_by_dealer')
                    //    ->where('orders.dealer_flag_order', 1)
                       ->where('dealers.approver_id', $employeeId)
                       ->select('orders.*') 
                       ->get();
        // dd($employeeId);
        if ($orders->isEmpty()) {
            return response()->json([
                'success' => false,
                'statusCode' => 404,
                'message' => 'No orders found for the dealer.'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'statusCode' => 404,
            'message' => 'Orders fetched successfully',
            'data' => $orders
        ]);
    }

    public function dealerOrderDetails($orderId)
    {
        $order = Order::where('id', $orderId)->first();

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $order
        ]);
    }

    public function dealerOrderStatusUpdate(Request $request, $orderId)
    {
        $request->validate([
            'status' => 'required|string'
        ]);

        $order = Order::where('id', $orderId)->first();

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found'
            ], 404);
        }

        $order->status = $request->status;
        $order->save();

        return response()->json([
            'success' => true,
            'message' => 'Order status updated successfully',
            'data' => $order
        ]);
    }
}