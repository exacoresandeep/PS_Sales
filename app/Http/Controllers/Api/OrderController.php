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
                $orders = Order::where('created_by', $employee->id)
                    ->where('dealer_flag_order',"0")
                    ->with(['dealer:id,dealer_name,dealer_code'])
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
                                'dealer_code' => $order->dealer->dealer_code, 
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

            $validatedData = $request->validate([
                'order_type' => 'required|exists:order_types,id',
                'customer_type_id' => 'required|exists:customer_types,id',
                'order_category' => 'nullable|string',
                'lead_id' => 'required|exists:leads,id',
                'dealer_id' => 'nullable|exists:dealers,id',
                'payment_terms_id' => 'required|exists:payment_terms,id',
                'advance_amount' => 'nullable|numeric',
                'payment_date' => 'nullable|string',
                'utr_number' => 'nullable|string',
                'billing_date' => 'required|string',
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
                'attachment.*' => 'nullable|string',
            ]);

            if (!empty($validatedData['payment_date'])) {
                $validatedData['payment_date'] = Carbon::createFromFormat('d-m-Y', $validatedData['payment_date'])->format('Y-m-d');
            }
            $validatedData['billing_date'] = Carbon::createFromFormat('d-m-Y', $validatedData['billing_date'])->format('Y-m-d');
        
            $validatedData['created_by'] = $employee->id;

            $order = Order::create($validatedData);
           
            if (!empty($validatedData['order_items'])) {
                foreach ($validatedData['order_items'] as $orderItem) {
                    $totalQuantity = 0;
                    if (!empty($orderItem['product_details'])) {
                        foreach ($orderItem['product_details'] as $productDetail) {
                            $totalQuantity += $productDetail['quantity'];
                        }
                    }
            
                    $orderItem['total_quantity'] = $totalQuantity;
            
                    $order->orderItems()->create($orderItem);
                }
            }
            

            $responseData = [
                    'order_type' => $order->order_type,
                    'customer_type_id' => $order->customerType, 
                    'lead_id' => $order->lead_id,
                    'dealer_id' => $order->dealer_id,
                    'payment_terms_id' => $order->payment_terms_id,
                    // 'advance_amount' => round($order->advance_amount, 2), 
                    // 'payment_date' => $order->payment_date ? Carbon::parse($order->payment_date)->format('d-m-Y') : null,
                    // 'utr_number' => $order->utr_number,
                    'billing_date' => Carbon::parse($order->billing_date)->format('d-m-Y'),
                    'total_amount' => round($order->total_amount, 2),
                    'additional_information' => $order->additional_information,
                    'status' => $order->status,
                    'created_by' => $order->created_by,
                    'updated_at' => Carbon::parse($order->updated_at)->format('d-m-Y'),
                    'created_at' => Carbon::parse($order->created_at)->format('d-m-Y'),
                    'id' => $order->id,

            ];
            // if ($employee->id == 1) { 
            //     $responseData['admin_only_field'] = 'xs';
            // } elseif ($employee->id == 2) { 
            //     $responseData['user_only_field'] = 'sss'; 
            // }

            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Order created successfully!',
                'data' => $responseData
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
                'dealer:id,dealer_name,dealer_code', 
                'orderItems.product:id,product_name',
                'lead:id,customer_type,customer_name,phone,address',
                'lead.customerType:id,name', 
                'paymentTerm:id,name',
            ])->findOrFail($orderId);
            $order->billing_date = $order->billing_date ? Carbon::parse($order->billing_date)->format('d-m-Y') : null;
            $order->payment_date = $order->payment_date ? Carbon::parse($order->payment_date)->format('d-m-Y') : null;
            $order->created_at = Carbon::parse($order->created_at)->format('d-m-Y');
    
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
            $isDate = false; 
            $parsedDate = null;

            if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $searchKey)) {
                try {
                    $parsedDate = Carbon::createFromFormat('d/m/Y', $searchKey);
                    $isDate = true;
                } catch (\Exception $e) {
                    $isDate = false;
                }
            }
            $ordersQuery = Order::with([
                'dealer:id,dealer_name as name'  
            ])
            ->where('created_by', $employeeId)
            ->select('id', 'created_at', 'status', 'total_amount', 'dealer_id');
            
            if ($isDate) {
                $ordersQuery->whereDate('created_at', $parsedDate);
            } else {
           
                $searchKey = strtolower($searchKey);
                
                if (in_array($searchKey, ['all', 'pending', 'accepted', 'rejected'])) {
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
                $financialStartDate = Carbon::create($currentYear - 1, 4, 1);
                $financialEndDate = Carbon::create($currentYear, 3, 31); 
    
                $ordersQuery->whereBetween('created_at', [$financialStartDate, $financialEndDate]);
            }

            // $orders = $ordersQuery->get()->map(function ($order) {
            //     if ($order->created_at) {
            //         $order->created_at = Carbon::parse($order->created_at)->format('d-m-Y');
            //     }
            //     return $order;
            // });
            $orders = $ordersQuery->get()->map(function ($order) {
                return [
                    'id' => $order->id,
                    'created_at' => Carbon::parse($order->created_at)->format('d-m-Y'),
                    'status' => $order->status,
                    'total_amount' => $order->total_amount,
                    'dealer' => $order->dealer,
                ];
            });
            
    
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

    public function dealerOrderList(Request $request)
    {
        try {
            if ($request->has('search_key')) {
                return $this->orderFilter($request);
            }

            $employee = Auth::user();
            
            if($employee)
            {
                $employeeTypeId = $employee->employee_type_id;
                
                if($employeeTypeId=="1"){
                    $dealerFlagOrder = '0';    
                }
                else if($employeeTypeId=="2"){
                    $dealerFlagOrder = '1';    
                }
                else{
                    $dealerFlagOrder = '0';  
                }

                $orders = Order::join('dealers', 'orders.dealer_id', '=', 'dealers.id') 
                    ->where('dealer_flag_order',$dealerFlagOrder)
                    ->where('dealers.approver_id',$employee->id)
                    ->with(['dealer:id,dealer_name'])
                    ->select('orders.id', 'total_amount', 'orders.status', 'orders.created_at', 'orders.dealer_id',)
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

    //dealerOrderStatusUpdate
    public function dealerOrderStatusUpdate(Request $request,$orderId){
        try {
           
            $validatedData = $request->validate([
                'status' => 'required|in:Approved,Rejected',
            ]);

            $order = Order::join('dealers', 'orders.dealer_id', '=', 'dealers.id') 
            ->where('dealers.approver_id', Auth::id())->findOrFail($orderId);

            $order->update([
                'status' => $validatedData['status']
            ]);

            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Order updated successfully!',
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
}