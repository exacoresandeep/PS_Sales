<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\AssignRoute;
use App\Models\Dealer;
use App\Models\Lead;
use App\Models\District;
use App\Models\Regions;
use App\Models\Payment;
use App\Models\Employee;
use App\Models\OutstandingPaymentCommitment;
use App\Models\OutstandingPayment;
use App\Models\ProductType;
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
                    ->orderBy('id','desc')
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
                'order_type' => 'nullable|exists:order_types,id',
                'customer_type_id' => 'nullable|exists:customer_types,id',
                'order_category' => 'nullable|string',
                'lead_id' => 'nullable|exists:leads,id',
                'dealer_id' => 'nullable|exists:dealers,id',
                'payment_terms_id' => 'required|exists:payment_terms,id',
                'advance_amount' => 'nullable|numeric',
                'payment_date' => 'nullable|string',
                'utr_number' => 'nullable|string',
                'billing_date' => 'required|string',
                'total_amount' => 'nullable|numeric',
                'additional_information' => 'nullable|string',
                'status' => 'nullable|in:Pending,Dispatched,Delivered',
                'vehicle_category_id' => 'nullable|integer',
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
                'vehicleCategory:id,vehicle_category_name' 
            ])->findOrFail($orderId);
            $order->billing_date = $order->billing_date ? Carbon::parse($order->billing_date)->format('d-m-Y') : null;
            $order->payment_date = $order->payment_date ? Carbon::parse($order->payment_date)->format('d-m-Y') : null;
            $order->created_at = Carbon::parse($order->created_at)->format('d-m-Y');
            $order->vehicle_category_name = $order->vehicleCategory->vehicle_category_name ?? null;
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

    // public function dealerOrderDetails($orderId)
    // {
    //     try {
    //         $user = Auth::user();

    //         if ($user === null) {
    //             return response()->json([
    //                 'success' => false,
    //                 'statusCode' => 400,
    //                 'message' => 'You must be logged in to view this order.'
    //             ], 400);
    //         }

    //         $order = Order::with([
    //             'orderType:id,name',
    //             'dealer:id,dealer_name,dealer_code,assigned_route_id', 
    //             'orderItems.product:id,product_name',
    //             'paymentTerm:id,name',
    //             'vehicleCategory:id,vehicle_category_name' 
    //         ])->findOrFail($orderId);
            
    //         $dealerId = $order->created_by_dealer;
    //         $totalOutstandingPayments = OutstandingPayment::where('dealer_id', $dealerId)->sum('outstanding_amount');
    //         $billingDate = $order->billing_date ? Carbon::parse($order->billing_date)->format('d/m/Y') : null;
    //         $createdAt = Carbon::parse($order->created_at)->format('d/m/Y');

    //         $orderItems = $order->orderItems->map(function ($item) {
    //             $productDetails = collect($item->product_details)->map(function ($detail) {
    //                 $productType = ProductType::find($detail['product_type_id']);
    //                 return [
    //                     'product_type_id' => $detail['product_type_id'],
    //                     'type_name' => $productType->type_name ?? null, 
    //                     'quantity' => (int) $detail['quantity'],
    //                     'rate' => $detail['rate']
    //                 ];
    //             });
    
    //             return [
    //                 'product_id' => $item->product_id,
    //                 'product_name' => $item->product->product_name ?? null,
    //                 'total_quantity' => (int) $item->total_quantity,
    //                 'product_details' => $productDetails,
    //             ];
    //         });

    //         $response = [
    //             'id' => $order->id,
    //             'order_type' => $order->orderType->name ?? null,
    //             'payment_term' => $order->paymentTerm->name ?? null,
    //             'billing_date' => $billingDate,
    //             'attachment' => $order->attachment,
    //             'total_amount' => $order->total_amount,
    //             'additional_information' => $order->additional_information,
    //             'created_at' => $createdAt,
    //             'order_items' => $orderItems,
    //             'vehicle_category' => $order->vehicleCategory->vehicle_category_name ?? null,
    //             'vehicle_number' => $order->vehicle_number,
    //             'driver_name' => $order->driver_name,
    //             'driver_phone' => $order->driver_phone,
    //             'total_outstanding_payments' => $totalOutstandingPayments
    //         ];

    //         return response()->json([
    //             'success' => true,
    //             'statusCode' => 200,
    //             'message' => 'Order details fetched successfully',
    //             'data' => $response,
    //         ], 200);

    //     } catch (Exception $e) {
    //         return response()->json([
    //             'success' => false,
    //             'statusCode' => 500,
    //             'message' => $e->getMessage(),
    //         ], 500);
    //     }
    // }
    public function dealerOrderDetails($orderId)
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 400,
                    'message' => 'You must be logged in to view this order.'
                ], 400);
            }

            $order = Order::with([
                'orderType:id,name',
                'dealers:id,dealer_name,dealer_code,assigned_route_id', 
                'orderItems.product:id,product_name',
                'paymentTerm:id,name',
                'vehicleCategory:id,vehicle_category_name' 
            ])->findOrFail($orderId);

            $dealer = $order->dealers;
            if (!$dealer) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 404,
                    'message' => 'Dealer not found for this order.'
                ], 404);
            }

            $allowedRoutes = AssignRoute::where('parent_id', $user->id)->pluck('id')->toArray();

            if (!in_array($dealer->assigned_route_id, $allowedRoutes)) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 403,
                    'message' => 'You are not authorized to view this order.'
                ], 403);
            }

            $totalOutstandingPayments = OutstandingPayment::where('dealer_id', $dealer->id)->sum('outstanding_amount');
            $billingDate = $order->billing_date ? Carbon::parse($order->billing_date)->format('d/m/Y') : null;
            $createdAt = Carbon::parse($order->created_at)->format('d/m/Y');

            $orderItems = $order->orderItems->map(function ($item) {
                $productDetails = collect($item->product_details)->map(function ($detail) {
                    $productType = ProductType::find($detail['product_type_id']);
                    return [
                        'product_type_id' => $detail['product_type_id'],
                        'type_name' => $productType->type_name ?? null, 
                        'quantity' => (int) $detail['quantity'],
                        'rate' => $detail['rate']
                    ];
                });

                return [
                    'product_id' => $item->product_id,
                    'product_name' => $item->product->product_name ?? null,
                    'total_quantity' => (int) $item->total_quantity,
                    'product_details' => $productDetails,
                ];
            });

            // Build the response
            $response = [
                'id' => $order->id,
                'order_type' => $order->orderType->name ?? null,
                'payment_term' => $order->paymentTerm->name ?? null,
                'billing_date' => $billingDate,
                'attachment' => $order->attachment,
                'total_amount' => $order->total_amount,
                'additional_information' => $order->additional_information,
                'created_at' => $createdAt,
                'order_items' => $orderItems,
                'vehicle_category' => $order->vehicleCategory->vehicle_category_name ?? null,
                'vehicle_number' => $order->vehicle_number,
                'driver_name' => $order->driver_name,
                'driver_phone' => $order->driver_phone,
                'total_outstanding_payments' => $totalOutstandingPayments,
                'dealer_code' => $dealer->dealer_code,
                'dealer_name' => $dealer->dealer_name
            ];

            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Order details fetched successfully',
                'data' => $response,
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
    
            if (!$employee) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 401,
                    'message' => "User not Authenticated",
                ], 401);
            }
    
            $assignedRoutes = AssignRoute::where('parent_id', $employee->id)->pluck('id')->toArray();

            if (empty($assignedRoutes)) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 404,
                    'message' => "No assigned routes found for the employee.",
                    'data' => []
                ], 404);
            }

            // Get dealers linked to these routes
            $dealers = Dealer::whereIn('assigned_route_id', $assignedRoutes)->pluck('id')->toArray();
    
            if (empty($dealers)) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 404,
                    'message' => "No dealers found for the assigned routes.",
                    'data' => []
                ], 404);
            }
    
            $orders = Order::join('dealers', 'orders.created_by_dealer', '=', 'dealers.id')
                ->where('orders.dealer_flag_order', '1')
                ->whereIn('orders.created_by_dealer', $dealers)
                ->select([
                    'orders.id',
                    'orders.total_amount',
                    'orders.status',
                    'orders.created_at',
                    'dealers.id as dealer_id',
                    'dealers.dealer_name',
                    'dealers.dealer_code'
                ])
                ->get() 
                ->map(function ($order) {
                    $order->total_amount = (float) sprintf("%.2f", $order->total_amount);
                    return $order;
                });
    
            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Dealer-created orders fetched successfully',
                'data' => $orders->map(function ($order) {
                    return [
                        'id' => $order->id,
                        'total_amount' => $order->total_amount,
                        'status' => $order->status,
                        'created_at' => $order->created_at->format('d/m/Y'),
                        'dealer' => [
                            'id' => $order->dealer_id,
                            'name' => $order->dealer_name,
                            'code' => $order->dealer_code,
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
    
    // public function dealerOrderStatusUpdate(Request $request, $orderId)
    // {
    //     try {
    //         $validatedData = $request->validate([
    //             'status' => 'required|in:Accepted,Rejected',
    //             'reason_for_rejection' => 'required_if:status,Rejected|nullable|string|max:255',
    //         ]);

    //         $employee = Auth::user();

    //         if (!$employee) {
    //             return response()->json([
    //                 'success' => false,
    //                 'statusCode' => 401,
    //                 'message' => "User not Authenticated",
    //             ], 401);
    //         }

    //         if ($employee->employee_type_id == 5) {
    //             $order = Order::where('id', $orderId)
    //                 ->where(function ($query) {
    //                     $query->whereIn('created_by', Employee::whereIn('employee_type_id', [2, 3, 4])->pluck('id'))
    //                         ->orWhereNotNull('created_by_dealer');
    //                 })
    //                 ->first();
    //         } else {
    //             $assignedRoutes = AssignRoute::where('employee_id', $employee->id)->pluck('id')->toArray();

    //             if (empty($assignedRoutes)) {
    //                 return response()->json([
    //                     'success' => false,
    //                     'statusCode' => 404,
    //                     'message' => "No assigned routes found for the employee.",
    //                 ], 404);
    //             }

    //             // Get dealers linked to those routes
    //             $dealers = Dealer::whereIn('assigned_route_id', $assignedRoutes)->pluck('id')->toArray();

    //             if (empty($dealers)) {
    //                 return response()->json([
    //                     'success' => false,
    //                     'statusCode' => 404,
    //                     'message' => "No dealers found for the assigned routes.",
    //                 ], 404);
    //             }

    //             // Fetch order created by dealers in assigned routes
    //             $order = Order::where('id', $orderId)
    //                 ->whereIn('created_by_dealer', $dealers)
    //                 ->first();
    //         }

    //         if (!$order) {
    //             return response()->json([
    //                 'success' => false,
    //                 'statusCode' => 404,
    //                 'message' => "Order with ID $orderId not found or not accessible.",
    //             ], 404);
    //         }

    //         // Prepare update data
    //         $updateData = ['status' => $validatedData['status']];
    //         if ($validatedData['status'] === 'Rejected') {
    //             $updateData['reason_for_rejection'] = $validatedData['reason_for_rejection'];
    //         } else {
    //             $updateData['reason_for_rejection'] = null;
    //         }

    //         // Update the order status
    //         $order->update($updateData);

    //         return response()->json([
    //             'success' => true,
    //             'statusCode' => 200,
    //             'message' => 'Order status updated successfully!',
    //             'data' => [
    //                 'id' => $order->id,
    //                 'status' => $order->status,
    //                 'reason_for_rejection' => $order->reason_for_rejection,
    //             ],
    //         ], 200);

    //     } catch (\Illuminate\Validation\ValidationException $e) {
    //         return response()->json([
    //             'success' => false,
    //             'statusCode' => 400,
    //             'message' => "Validation error",
    //         ], 400);
    //     } catch (Exception $e) {
    //         return response()->json([
    //             'success' => false,
    //             'statusCode' => 500,
    //             'message' => $e->getMessage(),
    //         ], 500);
    //     }
    // }
    public function dealerOrderStatusUpdate(Request $request, $orderId)
    {
        try {
            $validatedData = $request->validate([
                'status' => 'required|in:Accepted,Rejected',
                'reason_for_rejection' => 'required_if:status,Rejected|nullable|string|max:255',
            ]);

            $user = Auth::user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 401,
                    'message' => "User not Authenticated",
                ], 401);
            }

            // Fetch order with necessary relationships
            $order = Order::with('dealers:id,dealer_name,dealer_code,assigned_route_id')->findOrFail($orderId);
            $dealer = $order->dealers;

            if (!$dealer) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 404,
                    'message' => 'Dealer not found for this order.',
                ], 404);
            }

            // Check if the user has access to this dealer's order
            $allowedRoutes = AssignRoute::where('parent_id', $user->id)->pluck('id')->toArray();

            if (!in_array($dealer->assigned_route_id, $allowedRoutes)) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 403,
                    'message' => 'You are not authorized to update this order status.',
                ], 403);
            }

            // Update order status and reason for rejection
            $order->update([
                'status' => $validatedData['status'],
                'reason_for_rejection' => $validatedData['status'] === 'Rejected' ? $validatedData['reason_for_rejection'] : null,
            ]);

            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Order status updated successfully!',
                'data' => [
                    'id' => $order->id,
                    'status' => $order->status,
                    'reason_for_rejection' => $order->reason_for_rejection,
                ],
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 400,
                'message' => "Validation error",
                'errors' => $e->errors(),
            ], 400);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => $e->getMessage(),
            ], 500);
        }
    }


    public function dealerOrderFilter(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'search_key' => 'required|string|in:All,Pending,Accepted,Rejected',
            ]);

            $searchKey = $validatedData['search_key'];
            $employee = Auth::user();

            if (!$employee) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 401,
                    'message' => "User not Authenticated",
                ], 401);
            }

            $assignedRoutes = AssignRoute::where('employee_id', $employee->id)->pluck('id')->toArray();

            if (empty($assignedRoutes)) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 404,
                    'message' => "No assigned routes found for the employee.",
                    'data' => []
                ], 404);
            }

            $dealers = Dealer::whereIn('assigned_route_id', $assignedRoutes)->pluck('id')->toArray();

            if (empty($dealers)) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 404,
                    'message' => "No dealers found for the assigned routes.",
                    'data' => []
                ], 404);
            }

            $ordersQuery = Order::join('dealers', 'orders.created_by_dealer', '=', 'dealers.id')
                ->where('orders.dealer_flag_order', '1')
                ->whereIn('orders.created_by_dealer', $dealers)
                ->select([
                    'orders.id',
                    'orders.total_amount',
                    'orders.status',
                    'orders.created_at',
                    'dealers.id as dealer_id',
                    'dealers.dealer_name',
                    'dealers.dealer_code'
                ]);

            if ($searchKey !== 'All') {
                $ordersQuery->where('orders.status', $searchKey);
            }

            $orders = $ordersQuery->get()->map(function ($order) {
                return [
                    'id' => $order->id,
                    'total_amount' => (float) sprintf("%.2f", $order->total_amount),
                    'status' => $order->status,
                    'created_at' => $order->created_at->format('d/m/Y'),
                    'dealer' => [
                        'id' => $order->dealer_id,
                        'name' => $order->dealer_name,
                        'code' => $order->dealer_code,
                    ],
                ];
            });

            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => "Filtered dealer-created orders fetched successfully",
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

   
    // public function outstandingPaymentsList()
    // {
    //     try {
    //         $employee = Auth::user();

    //         if (!$employee) {
    //             return response()->json([
    //                 'success' => false,
    //                 'statusCode' => 401,
    //                 'message' => "User not Authenticated",
    //             ], 401);
    //         }

    //         $dealers = [];

    //         if ($employee->employee_type_id == 2) { 
    //             $assignedRoutes = AssignRoute::where('employee_id', $employee->id)->pluck('id')->toArray();

    //             if (empty($assignedRoutes)) {
    //                 return response()->json([
    //                     'success' => false,
    //                     'statusCode' => 404,
    //                     'message' => "No assigned routes found for the employee.",
    //                 ], 404);
    //             }

    //             $dealers = Dealer::whereIn('assigned_route_id', $assignedRoutes)->pluck('id')->toArray();

    //         } elseif ($employee->employee_type_id == 3) { 
    //             $dealers = Dealer::where('district_id', $employee->district_id)->pluck('id')->toArray();

    //         } elseif ($employee->employee_type_id == 4) { 
    //             $region = Regions::whereHas('districts', function ($query) use ($employee) {
    //                 $query->where('id', $employee->district_id);
    //             })->first();

    //             if (!$region) {
    //                 return response()->json([
    //                     'success' => false,
    //                     'statusCode' => 404,
    //                     'message' => "Region not found for the employee's district.",
    //                 ], 404);
    //             }

    //             $districtsInRegion = District::where('regions_id', $region->id)->pluck('id')->toArray();

    //             $dealers = Dealer::whereIn('district_id', $districtsInRegion)->pluck('id')->toArray();

    //         } elseif ($employee->employee_type_id == 5) {
    //             $dealers = Dealer::pluck('id')->toArray();
    //         }

    //         if (empty($dealers)) {
    //             return response()->json([
    //                 'success' => false,
    //                 'statusCode' => 404,
    //                 'message' => "No dealers found for the given criteria.",
    //             ], 404);
    //         }

    //         $outstandingPayments = OutstandingPayment::whereIn('dealer_id', $dealers)
    //             ->where('outstanding_amount', '>', 0) 
    //             ->with('dealer:id,dealer_name,dealer_code') 
    //             ->orderBy('due_date', 'asc')
    //             ->get();

    //         $paymentsData = $outstandingPayments->map(function ($payment) {
    //             return [
    //                 'order_id' => $payment->order_id,
    //                 'invoice_number' => $payment->invoice_number,
    //                 'invoice_date' => $payment->invoice_date ? \Carbon\Carbon::parse($payment->invoice_date)->format('d/m/Y') : null,
    //                 'due_date' => $payment->due_date ? \Carbon\Carbon::parse($payment->due_date)->format('d/m/Y') : null,
    //                 'invoice_total' => (float) $payment->invoice_total,
    //                 'paid_amount' => (float) $payment->paid_amount,
    //                 'outstanding_amount' => (float) $payment->outstanding_amount,
    //                 'payment_doc_number' => $payment->payment_doc_number,
    //                 'status' => $payment->status,
    //                 'dealer' => [
    //                     'id' => $payment->dealer->id,
    //                     'name' => $payment->dealer->dealer_name,
    //                     'code' => $payment->dealer->dealer_code,
    //                 ]
    //             ];
    //         });

    //         return response()->json([
    //             'success' => true,
    //             'statusCode' => 200,
    //             'message' => 'Outstanding payments fetched successfully',
    //             'data' => $paymentsData,
    //         ], 200);

    //     } catch (Exception $e) {
    //         return response()->json([
    //             'success' => false,
    //             'statusCode' => 500,
    //             'message' => $e->getMessage(),
    //         ], 500);
    //     }
    // }
    // public function viewOutstandingPaymentOrderDetails($orderId)
    // {
    //     try {
    //         $employee = Auth::user();

    //         if (!$employee) {
    //             return response()->json([
    //                 'success' => false,
    //                 'statusCode' => 401,
    //                 'message' => "User not Authenticated",
    //             ], 401);
    //         }

    //         $dealers = [];

    //         if ($employee->employee_type_id == 2) { 
    //             $assignedRoutes = AssignRoute::where('employee_id', $employee->id)->pluck('id')->toArray();

    //             if (empty($assignedRoutes)) {
    //                 return response()->json([
    //                     'success' => false,
    //                     'statusCode' => 404,
    //                     'message' => "No assigned routes found for the employee.",
    //                 ], 404);
    //             }

    //             $dealers = Dealer::whereIn('assigned_route_id', $assignedRoutes)->pluck('id')->toArray();

    //         } elseif ($employee->employee_type_id == 3) { 
    //             $dealers = Dealer::where('district_id', $employee->district_id)->pluck('id')->toArray();

    //         } elseif ($employee->employee_type_id == 4) { 
    //             $region = Regions::whereHas('districts', function ($query) use ($employee) {
    //                 $query->where('id', $employee->district_id);
    //             })->first();

    //             if (!$region) {
    //                 return response()->json([
    //                     'success' => false,
    //                     'statusCode' => 404,
    //                     'message' => "Region not found for the employee's district.",
    //                 ], 404);
    //             }

    //             $districtsInRegion = District::where('regions_id', $region->id)->pluck('id')->toArray();
    //             $dealers = Dealer::whereIn('district_id', $districtsInRegion)->pluck('id')->toArray();

    //         } elseif ($employee->employee_type_id == 5) { 
    //             $dealers = Dealer::pluck('id')->toArray();
    //         }

    //         if (empty($dealers)) {
    //             return response()->json([
    //                 'success' => false,
    //                 'statusCode' => 404,
    //                 'message' => "No dealers found for the given criteria.",
    //             ], 404);
    //         }

    //         $outstandingPayment = OutstandingPayment::with([
    //             'dealer:id,dealer_name,dealer_code,district_id',
    //             'order.orderType:id,name',
    //             'order.orderItems.product:id,product_name',
    //             'order.paymentTerm:id,name',
    //             'order.vehicleCategory:id,vehicle_category_name'
    //         ])->where('order_id', $orderId)->first();

    //         if (!$outstandingPayment) {
    //             return response()->json([
    //                 'success' => false,
    //                 'statusCode' => 404,
    //                 'message' => "No outstanding payment found for this order.",
    //                 'data' => []
    //             ], 404);
    //         }

    //         if (!in_array($outstandingPayment->dealer->id, $dealers)) {
    //             return response()->json([
    //                 'success' => false,
    //                 'statusCode' => 403,
    //                 'message' => "You do not have permission to view this order.",
    //             ], 403);
    //         }

    //         $order = $outstandingPayment->order;

    //         $orderItems = $order->orderItems->map(function ($item) {
    //             $productDetails = collect($item->product_details)->map(function ($detail) {
    //                 $productType = ProductType::find($detail['product_type_id']);
    //                 return [
    //                     'product_type_id' => $detail['product_type_id'],
    //                     'type_name' => $productType->type_name ?? null,
    //                     'quantity' => (int) $detail['quantity'],
    //                     'rate' => $detail['rate']
    //                 ];
    //             });

    //             return [
    //                 'product_id' => $item->product_id,
    //                 'product_name' => $item->product->product_name ?? null,
    //                 'total_quantity' => (int) $item->total_quantity,
    //                 'product_details' => $productDetails,
    //             ];
    //         });

    //         $response = [
    //             'order_id' => $order->id,
    //             'order_type' => $order->orderType->name ?? null,
    //             'payment_term' => $order->paymentTerm->name ?? null,
    //             'billing_date' => $order->billing_date ? \Carbon\Carbon::parse($order->billing_date)->format('d/m/Y') : null,
    //             'attachment' => $order->attachment,
    //             'total_amount' => $order->total_amount,
    //             'additional_information' => $order->additional_information,
    //             'created_at' => \Carbon\Carbon::parse($order->created_at)->format('d/m/Y'),
    //             'order_items' => $orderItems,
    //             'vehicle_category' => $order->vehicleCategory->vehicle_category_name ?? null,
    //             'vehicle_number' => $order->vehicle_number,
    //             'driver_name' => $order->driver_name,
    //             'driver_phone' => $order->driver_phone,
    //             'outstanding_payment' => [
    //                 'outstanding_payment_id' =>  $outstandingPayment->id,
    //                 'invoice_number' => $outstandingPayment->invoice_number,
    //                 'invoice_date' => $outstandingPayment->invoice_date ? \Carbon\Carbon::parse($outstandingPayment->invoice_date)->format('d/m/Y') : null,
    //                 'due_date' => $outstandingPayment->due_date ? \Carbon\Carbon::parse($outstandingPayment->due_date)->format('d/m/Y') : null,
    //                 'invoice_total' => (float) $outstandingPayment->invoice_total,
    //                 'paid_amount' => (float) $outstandingPayment->paid_amount,
    //                 'outstanding_amount' => (float) $outstandingPayment->outstanding_amount,
    //                 'payment_doc_number' => $outstandingPayment->payment_doc_number,
    //                 'payment_date' => $outstandingPayment->payment_date ? \Carbon\Carbon::parse($outstandingPayment->payment_date)->format('d/m/Y') : null,
    //                 'payment_amount_applied' => (float) $outstandingPayment->payment_amount_applied,
    //                 'status' => $outstandingPayment->status,
    //             ],
    //             'dealer' => [
    //                 'id' => $outstandingPayment->dealer->id,
    //                 'name' => $outstandingPayment->dealer->dealer_name,
    //                 'code' => $outstandingPayment->dealer->dealer_code,
    //             ]
    //         ];

    //         return response()->json([
    //             'success' => true,
    //             'statusCode' => 200,
    //             'message' => 'Outstanding Payment Order details fetched successfully',
    //             'data' => $response,
    //         ], 200);

    //     } catch (Exception $e) {
    //         return response()->json([
    //             'success' => false,
    //             'statusCode' => 500,
    //             'message' => $e->getMessage(),
    //         ], 500);
    //     }
    // }
    // public function addOutstandingPaymentCommitment(Request $request, $outstandingPaymentId)
    // {
    //     try {
    //         $employee = Auth::user();

    //         if (!$employee) {
    //             return response()->json([
    //                 'success' => false,
    //                 'statusCode' => 401,
    //                 'message' => "User not Authenticated",
    //             ], 401);
    //         }

    //         // Validate request data
    //         $validatedData = $request->validate([
    //             'commitments' => 'required|array|min:1',
    //             'commitments.*.committed_date' => 'required|date|after_or_equal:today',
    //             'commitments.*.committed_amount' => 'required|numeric|min:1',
    //         ]);

    //         // Fetch outstanding payment with dealer details
    //         $outstandingPayment = OutstandingPayment::with('dealer')->findOrFail($outstandingPaymentId);
    //         $dealerDistrictId = $outstandingPayment->dealer->district_id;
    //         $dealerId = $outstandingPayment->dealer->id;

    //         // Role-based access control
    //         if ($employee->employee_type_id == 2) { // ASO - Check assigned routes
    //             $assignedDealerIds = AssignRoute::where('employee_id', $employee->id)
    //                 ->pluck('dealer_id')
    //                 ->toArray();

    //             if (!in_array($dealerId, $assignedDealerIds)) {
    //                 return response()->json([
    //                     'success' => false,
    //                     'statusCode' => 403,
    //                     'message' => "You do not have permission to add commitments for this order.",
    //                 ], 403);
    //             }
    //         } elseif ($employee->employee_type_id == 3) { // DSM - Check district
    //             if ($dealerDistrictId != $employee->district_id) {
    //                 return response()->json([
    //                     'success' => false,
    //                     'statusCode' => 403,
    //                     'message' => "You do not have permission to add commitments for this order.",
    //                 ], 403);
    //             }
    //         } elseif ($employee->employee_type_id == 4) { // RSM - Check region
    //             $region = Regions::whereHas('districts', function ($query) use ($employee) {
    //                 $query->where('id', $employee->district_id);
    //             })->first();

    //             if (!$region) {
    //                 return response()->json([
    //                     'success' => false,
    //                     'statusCode' => 404,
    //                     'message' => "Region not found for the employee's district.",
    //                 ], 404);
    //             }

    //             $districtsInRegion = District::where('regions_id', $region->id)->pluck('id')->toArray();
    //             if (!in_array($dealerDistrictId, $districtsInRegion)) {
    //                 return response()->json([
    //                     'success' => false,
    //                     'statusCode' => 403,
    //                     'message' => "You do not have permission to add commitments for this order.",
    //                 ], 403);
    //             }
    //         }
    //         // SM (employee_type_id == 5) has access to all orders, so no restriction needed.

    //         // Calculate remaining outstanding amount
    //         $totalCommitted = OutstandingPaymentCommitment::where('outstanding_payment_id', $outstandingPaymentId)->sum('committed_amount');
    //         $remainingOutstanding = $outstandingPayment->outstanding_amount - $totalCommitted;

    //         $commitmentsToInsert = [];
    //         $totalNewCommitments = 0;

    //         foreach ($validatedData['commitments'] as $commitment) {
    //             $totalNewCommitments += $commitment['committed_amount'];

    //             if ($totalNewCommitments > $remainingOutstanding) {
    //                 return response()->json([
    //                     'success' => false,
    //                     'statusCode' => 400,
    //                     'message' => "Total committed amount exceeds remaining outstanding balance of $remainingOutstanding.",
    //                 ], 400);
    //             }

    //             $commitmentsToInsert[] = [
    //                 'outstanding_payment_id' => $outstandingPaymentId,
    //                 'committed_date' => $commitment['committed_date'],
    //                 'committed_amount' => $commitment['committed_amount'],
    //                 'created_at' => now(),
    //                 'updated_at' => now(),
    //             ];
    //         }

    //         // Bulk insert commitments
    //         OutstandingPaymentCommitment::insert($commitmentsToInsert);

    //         return response()->json([
    //             'success' => true,
    //             'statusCode' => 200,
    //             'message' => 'Commitments added successfully!',
    //             'data' => $commitmentsToInsert,
    //         ], 200);

    //     } catch (Exception $e) {
    //         return response()->json([
    //             'success' => false,
    //             'statusCode' => 500,
    //             'message' => $e->getMessage(),
    //         ], 500);
    //     }
    // }
    public function outstandingPaymentsList()
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

            $dealers = [];

            switch ($employee->employee_type_id) {
                case 2: // ASO
                    $assignedRoutes = AssignRoute::where('parent_id', $employee->id)->pluck('id')->toArray();
                    if (empty($assignedRoutes)) {
                        return response()->json([
                            'success' => false,
                            'statusCode' => 404,
                            'message' => "No assigned routes found for the employee.",
                        ], 404);
                    }
                    $dealers = Dealer::whereIn('assigned_route_id', $assignedRoutes)->pluck('id')->toArray();
                    break;

                case 3: // DSM
                    $dealers = Dealer::where('district_id', $employee->district_id)->pluck('id')->toArray();
                    break;

                case 4: // RSM
                    $region = Regions::whereHas('districts', function ($query) use ($employee) {
                        $query->where('id', $employee->district_id);
                    })->first();

                    if (!$region) {
                        return response()->json([
                            'success' => false,
                            'statusCode' => 404,
                            'message' => "Region not found for the employee's district.",
                        ], 404);
                    }

                    $districtsInRegion = District::where('regions_id', $region->id)->pluck('id')->toArray();
                    $dealers = Dealer::whereIn('district_id', $districtsInRegion)->pluck('id')->toArray();
                    break;

                case 5: // SM
                    $dealers = Dealer::pluck('id')->toArray();
                    break;
            }

            if (empty($dealers)) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 404,
                    'message' => "No dealers found for the given criteria.",
                ], 404);
            }

            $outstandingPayments = OutstandingPayment::whereIn('dealer_id', $dealers)
                ->where('outstanding_amount', '>', 0)
                ->with('dealer:id,dealer_name,dealer_code')
                ->orderBy('due_date', 'asc')
                ->get();

            $paymentsData = $outstandingPayments->map(function ($payment) {
                return [
                    'id' => $payment->id,
                    'order_id' => $payment->order_id,
                    'invoice_number' => $payment->invoice_number,
                    'invoice_date' => optional($payment->invoice_date)->format('d/m/Y'),
                    'due_date' => optional($payment->due_date)->format('d/m/Y'),
                    'invoice_total' => (float) $payment->invoice_total,
                    'paid_amount' => (float) $payment->paid_amount,
                    'outstanding_amount' => (float) $payment->outstanding_amount,
                    'payment_doc_number' => $payment->payment_doc_number,
                    'status' => $payment->status,
                    'dealer' => [
                        'id' => $payment->dealer->id,
                        'name' => $payment->dealer->dealer_name,
                        'code' => $payment->dealer->dealer_code,
                    ]
                ];
            });

            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Outstanding payments fetched successfully',
                'data' => $paymentsData,
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    public function viewOutstandingPaymentOrderDetails($orderId)
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
    
            $dealers = [];
    
            if ($employee->employee_type_id == 2) { // ASO
                // $assignedRoutes = AssignRoute::where('employee_id', $employee->id)->pluck('id')->toArray();
                $assignedRoutes = AssignRoute::where('parent_id', $employee->id)->pluck('id')->toArray();
    
                if (empty($assignedRoutes)) {
                    return response()->json([
                        'success' => false,
                        'statusCode' => 404,
                        'message' => "No assigned routes found for the employee.",
                    ], 404);
                }
    
                // Get dealers assigned to ASO via parent_id relationship
                // $dealerRoutes = AssignRoute::whereIn('parent_id', $assignedRoutes)->pluck('id')->toArray();
                // $dealers = Dealer::whereIn('assigned_route_id', $dealerRoutes)->pluck('id')->toArray();
                $dealers = Dealer::whereIn('assigned_route_id', $assignedRoutes)->pluck('id')->toArray();
    
            } elseif ($employee->employee_type_id == 3) { // DSM
                $dealers = Dealer::where('district_id', $employee->district_id)->pluck('id')->toArray();
    
            } elseif ($employee->employee_type_id == 4) { // RSM
                $region = Regions::whereHas('districts', function ($query) use ($employee) {
                    $query->where('id', $employee->district_id);
                })->first();
    
                if (!$region) {
                    return response()->json([
                        'success' => false,
                        'statusCode' => 404,
                        'message' => "Region not found for the employee's district.",
                    ], 404);
                }
    
                $districtsInRegion = District::where('regions_id', $region->id)->pluck('id')->toArray();
                $dealers = Dealer::whereIn('district_id', $districtsInRegion)->pluck('id')->toArray();
    
            } elseif ($employee->employee_type_id == 5) { // SM
                $dealers = Dealer::pluck('id')->toArray();
            }
    
            if (empty($dealers)) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 404,
                    'message' => "No dealers found for the given criteria.",
                ], 404);
            }
    
            $outstandingPayment = OutstandingPayment::with([
                'dealer:id,dealer_name,dealer_code,district_id',
                'order.orderType:id,name',
                'order.orderItems.product:id,product_name',
                'order.paymentTerm:id,name',
                'order.vehicleCategory:id,vehicle_category_name'
            ])->where('order_id', $orderId)->first();
    
            if (!$outstandingPayment) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 404,
                    'message' => "No outstanding payment found for this order.",
                    'data' => []
                ], 404);
            }
    
            if (!in_array($outstandingPayment->dealer->id, $dealers)) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 403,
                    'message' => "You do not have permission to view this order.",
                ], 403);
            }
            
    
            $order = $outstandingPayment->order;
            $payments = Payment::where('order_id', $order->id)
                ->where('dealer_id', $outstandingPayment->dealer->id)
                ->select('payment_date', 'payment_amount', 'payment_document_no')
                ->orderBy('payment_date', 'asc')
                ->get();

            $totalPaidAmount = $payments->sum('payment_amount');
            $totalOutstandingAmount = $order->invoice_total - $totalPaidAmount;
    
            $orderItems = $order->orderItems->map(function ($item) {
                $productDetails = collect($item->product_details)->map(function ($detail) {
                    $productType = ProductType::find($detail['product_type_id']);
                    return [
                        'product_type_id' => $detail['product_type_id'],
                        'type_name' => $productType->type_name ?? null,
                        'quantity' => (int) $detail['quantity'],
                        'rate' => $detail['rate']
                    ];
                });
    
                return [
                    'product_id' => $item->product_id,
                    'product_name' => $item->product->product_name ?? null,
                    'total_quantity' => (int) $item->total_quantity,
                    'product_details' => $productDetails,
                ];
            });
    
            $response = [
                'order_id' => $order->id,
                'order_type' => $order->orderType->name ?? null,
                'payment_term' => $order->paymentTerm->name ?? null,
                'billing_date' => $order->billing_date ? \Carbon\Carbon::parse($order->billing_date)->format('d/m/Y') : null,
                'attachment' => $order->attachment,
                'total_amount' => $order->total_amount,
                'additional_information' => $order->additional_information,
                'created_at' => \Carbon\Carbon::parse($order->created_at)->format('d/m/Y'),
                'order_items' => $orderItems,
                'vehicle_category' => $order->vehicleCategory->vehicle_category_name ?? null,
                'vehicle_number' => $order->vehicle_number,
                'driver_name' => $order->driver_name,
                'driver_phone' => $order->driver_phone,
                'outstanding_payment' => [
                    'outstanding_payment_id' =>  $outstandingPayment->id,
                    'invoice_number' => $outstandingPayment->invoice_number,
                    'invoice_date' => $outstandingPayment->invoice_date ? \Carbon\Carbon::parse($outstandingPayment->invoice_date)->format('d/m/Y') : null,
                    'due_date' => $outstandingPayment->due_date ? \Carbon\Carbon::parse($outstandingPayment->due_date)->format('d/m/Y') : null,
                    'invoice_total' => (float) $outstandingPayment->invoice_total,
                    'paid_amount' => (float) $totalPaidAmount,
                    'outstanding_amount' => (float) $totalOutstandingAmount,
                    'payment_doc_number' => $outstandingPayment->payment_doc_number,
                    'payment_date' => $outstandingPayment->payment_date ? \Carbon\Carbon::parse($outstandingPayment->payment_date)->format('d/m/Y') : null,
                    'payment_amount_applied' => (float) $outstandingPayment->payment_amount_applied,
                    'status' => $outstandingPayment->status,
                ],
                'dealer' => [
                    'id' => $outstandingPayment->dealer->id,
                    'name' => $outstandingPayment->dealer->dealer_name,
                    'code' => $outstandingPayment->dealer->dealer_code,
                ]
            ];
    
            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Outstanding Payment Order details fetched successfully',
                'data' => $response,
            ], 200);
    
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    public function addOutstandingPaymentCommitment(Request $request, $outstandingPaymentId)
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
                'commitments' => 'required|array|min:1',
                'commitments.*.committed_date' => 'required|date|after_or_equal:today',
                'commitments.*.committed_amount' => 'required|numeric|min:1',
            ]);

            $outstandingPayment = OutstandingPayment::with('dealer')->findOrFail($outstandingPaymentId);
            $dealer = $outstandingPayment->dealer;

            $allowed = false;

            switch ($employee->employee_type_id) {
                case 2: 
                    $assignedRoutes = AssignRoute::where('employee_id', $employee->id)->pluck('id')->toArray();
                    $allowed = in_array($dealer->assigned_route_id, $assignedRoutes);
                    break;

                case 3: 
                    $allowed = ($dealer->district_id == $employee->district_id);
                    break;

                case 4: // RSM - Check region
                    $region = Regions::whereHas('districts', function ($query) use ($employee) {
                        $query->where('id', $employee->district_id);
                    })->first();

                    if ($region) {
                        $districtsInRegion = District::where('regions_id', $region->id)->pluck('id')->toArray();
                        $allowed = in_array($dealer->district_id, $districtsInRegion);
                    }
                    break;

                case 5: // SM - Unrestricted access
                    $allowed = true;
                    break;
            }

            if (!$allowed) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 403,
                    'message' => "You do not have permission to add commitments for this order.",
                ], 403);
            }

            // Calculate remaining outstanding amount
            $totalCommitted = OutstandingPaymentCommitment::where('outstanding_payment_id', $outstandingPaymentId)->sum('committed_amount');
            $remainingOutstanding = $outstandingPayment->outstanding_amount - $totalCommitted;

            $commitmentsToInsert = [];
            $totalNewCommitments = 0;

            foreach ($validatedData['commitments'] as $commitment) {
                $totalNewCommitments += $commitment['committed_amount'];

                if ($totalNewCommitments > $remainingOutstanding) {
                    return response()->json([
                        'success' => false,
                        'statusCode' => 400,
                        'message' => "Total committed amount exceeds remaining outstanding balance of $remainingOutstanding.",
                    ], 400);
                }

                $commitmentsToInsert[] = [
                    'outstanding_payment_id' => $outstandingPaymentId,
                    'committed_date' => $commitment['committed_date'],
                    'committed_amount' => $commitment['committed_amount'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            // Bulk insert commitments
            OutstandingPaymentCommitment::insert($commitmentsToInsert);

            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Commitments added successfully!',
                'data' => $commitmentsToInsert,
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => $e->getMessage(),
            ], 500);
        }
    }



   
    // public function salesExecutiveSalesReport(Request $request)
    // {
    //     try {
    //         $employee = Auth::user();

    //         if (!$employee) {
    //             return response()->json([
    //                 'success' => false,
    //                 'statusCode' => 401,
    //                 'message' => "User not authenticated.",
    //             ], 401);
    //         }

    //         $salesExecutives = Employee::where('district', $employee->district)
    //             ->where('employee_type_id', 1) 
    //             ->get();
    //         if ($salesExecutives->isEmpty()) {
    //             return response()->json([
    //                 'success' => false,
    //                 'statusCode' => 404,
    //                 'message' => "No Sales Executives found in this district.",
    //             ], 404);
    //         }

    //         $month = $request->input('month', date('m'));
    //         $year = $request->input('year', date('Y'));

    //         $totalSalesForPeriod = 0;

    //         $salesReport = $salesExecutives->map(function ($se) use ($month, $year, &$totalSalesForPeriod) {
    //             $orders = Order::where('created_by', $se->id)
    //                 ->where('status', 'Delivered')
    //                 ->whereYear('created_at', $year)
    //                 ->whereMonth('created_at', $month)
    //                 ->get();

    //             $totalSales = $orders->sum('invoice_total');
    //             $totalSalesForPeriod += $totalSales; 

    //             return [
    //                 'employee_id' => $se->id,
    //                 'employee_name' => $se->name,
    //                 'employee_code' => $se->employee_code,
    //                 'total_sales_report' => (float) $totalSales,
    //                 'orders' => $orders->map(function ($order) {
    //                     return [
    //                         'order_id' => $order->id,
    //                         'created_at' =>  $order->created_at ? $order->created_at->format('d/m/Y') : null,
    //                         'invoice_total' => (float) $order->invoice_total,
    //                     ];
    //                 }),
    //             ];
    //         });

    //         return response()->json([
    //             'success' => true,
    //             'statusCode' => 200,
    //             'message' => "Sales report fetched successfully for $month/$year.",
    //             'data' => [
    //                 'total_sales_for_period' => (float) $totalSalesForPeriod,
    //                 'sales_report' => $salesReport,
    //             ],
    //         ], 200);

    //     } catch (Exception $e) {
    //         return response()->json([
    //             'success' => false,
    //             'statusCode' => 500,
    //             'message' => $e->getMessage(),
    //         ], 500);
    //     }
    // }
    // public function salesReportDetails(Request $request, $employee_id)
    // {
    //     try {
    //         // Get logged-in employee
    //         $employee = Auth::user();
    //         if ($employee->employee_type_id == 3) { 
    //             // DSM can only see Sales Executives (SE)
    //             $allowedEmployeeTypes = [1]; 
    //         } elseif ($employee->employee_type_id == 4) { 
    //             // RSM can see both ASOs and DSM
    //             $allowedEmployeeTypes = [2, 3]; 
    //         } else {
    //             return response()->json([
    //                 'success' => false,
    //                 'statusCode' => 403,
    //                 'message' => "Unauthorized access.",
    //             ], 403);
    //         }

    //         // Find the Sales Executive
    //         $salesEmployee = Employee::where('id', $employee_id)
    //         ->whereIn('employee_type_id', $allowedEmployeeTypes)
    //         ->first();

    //         if (!$salesEmployee) {
    //             return response()->json([
    //                 'success' => false,
    //                 'statusCode' => 404,
    //                 'message' => "Sales Executive not found.",
    //             ], 404);
    //         }

    //         // Get month & year from request, default to current month & year
    //         $month = $request->input('month', date('m'));
    //         $year = $request->input('year', date('Y'));

    //         // Fetch delivered orders for the selected Sales Executive
    //         $orders = Order::where('created_by', $salesEmployee->id)
    //         ->where('status', 'Delivered')
    //         ->whereYear('created_at', $year)
    //         ->whereMonth('created_at', $month)
    //         ->with('dealer:id,dealer_name') // Load dealer details
    //         ->get();

    //         // Calculate total sales amount for the filtered period
    //         $totalSalesAmount = $orders->sum('invoice_total');

    //         // Format orders data
    //         $ordersData = $orders->map(function ($order) {
    //             return [
    //                 'order_id' => $order->id,
    //                 'created_at' => $order->created_at ? $order->created_at->format('d/m/Y') : null,
    //                 'dealer_name' => $order->dealer ? $order->dealer->dealer_name : 'N/A',
    //                 'invoice_total' => (float) $order->invoice_total,
    //             ];
    //         });
    //         return response()->json([
    //             'success' => true,
    //             'statusCode' => 200,
    //             'message' => "Sales report details fetched successfully for $month/$year.",
    //             'data' => [
    //                 'employee_details' => [
    //                     'employee_id' => $salesEmployee->id,
    //                     'employee_code' => $salesEmployee->employee_code,
    //                     'employee_name' => $salesEmployee->name,
    //                     'email' => $salesEmployee->email,
    //                     'phone' => $salesEmployee->phone,
    //                     'total_sales_amount' => (float) $totalSalesAmount,
    //                 ],
    //                 'orders' => $ordersData,
    //             ],
    //         ], 200);

    //     } catch (Exception $e) {
    //         return response()->json([
    //             'success' => false,
    //             'statusCode' => 500,
    //             'message' => $e->getMessage(),
    //         ], 500);
    //     }
    // }

    public function salesExecutiveSalesReport(Request $request)
    {
        try {
            $employee = Auth::user();
    
            if (!$employee) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 401,
                    'message' => "User not authenticated.",
                ], 401);
            }
    
            $month = $request->input('month', date('m'));
            $year = $request->input('year', date('Y'));
    
            $totalSalesForPeriod = 0;
            if ($employee->employee_type_id == 3) { 
                $salesExecutives = Employee::where('district_id', $employee->district_id)
                    ->where('employee_type_id', 1)
                    ->get();
    
                if ($salesExecutives->isEmpty()) {
                    return response()->json([
                        'success' => false,
                        'statusCode' => 404,
                        'message' => "No Sales Executives found in this district.",
                    ], 404);
                }
    
                $salesReport = $salesExecutives->map(function ($se) use ($month, $year, &$totalSalesForPeriod) {
                    $orders = Order::where('created_by', $se->id)
                        ->where('status', 'Delivered')
                        ->whereYear('created_at', $year)
                        ->whereMonth('created_at', $month)
                        ->get();
    
                    $totalSales = $orders->sum('invoice_total');
                    $totalSalesForPeriod += $totalSales;
    
                    return [
                        'employee_id' => $se->id,
                        'employee_name' => $se->name,
                        'employee_code' => $se->employee_code,
                        'total_sales_report' => (float) $totalSales,
                        'orders' => $orders->map(function ($order) {
                            return [
                                'order_id' => $order->id,
                                'created_at' => $order->created_at ? $order->created_at->format('d/m/Y') : null,
                                'invoice_total' => (float) $order->invoice_total,
                            ];
                        }),
                    ];
                });
    
            } elseif ($employee->employee_type_id == 4) { 
                $region = Regions::whereHas('districts', function ($query) use ($employee) {
                    $query->where('id', $employee->district_id);
                })->first();
    
                if (!$region) {
                    return response()->json([
                        'success' => false,
                        'statusCode' => 404,
                        'message' => "Region not found for the employee's district.",
                    ], 404);
                }
    
                $districtsInRegion = District::where('regions_id', $region->id)->pluck('id')->toArray();
                $employees = Employee::whereIn('district_id', $districtsInRegion)
                    ->whereIn('employee_type_id', [2, 3])
                    ->get();
    
                if ($employees->isEmpty()) {
                    return response()->json([
                        'success' => false,
                        'statusCode' => 404,
                        'message' => "No Sales Executives or Area Sales Officers found in this region.",
                    ], 404);
                }
    
                $salesReport = $employees->map(function ($emp) use ($month, $year, &$totalSalesForPeriod) {
                    $orders = Order::where('created_by', $emp->id)
                        ->where('status', 'Delivered')
                        ->whereYear('created_at', $year)
                        ->whereMonth('created_at', $month)
                        ->get();
    
                    $totalSales = $orders->sum('invoice_total');
                    $totalSalesForPeriod += $totalSales;
    
                    $employeeType = match ($emp->employee_type_id) {
                        1 => 'Sales Executive',
                        2 => 'Area Sales Officer',
                        3 => 'District Sales Manager',
                        default => 'Unknown',
                    };
    
                    return [
                        'employee_id' => $emp->id,
                        'employee_name' => $emp->name,
                        'employee_code' => $emp->employee_code,
                        'employee_type_id' => $emp->employee_type_id,
                        'employee_type' => $employeeType,
                        'total_sales_report' => (float) $totalSales,
                        'orders' => $orders->map(function ($order) {
                            return [
                                'order_id' => $order->id,
                                'created_at' => $order->created_at ? $order->created_at->format('d/m/Y') : null,
                                'invoice_total' => (float) $order->invoice_total,
                            ];
                        }),
                    ];
                });
    
            } elseif ($employee->employee_type_id == 5) { 
                $employees = Employee::whereIn('employee_type_id', [3, 4])->get();
    
                if ($employees->isEmpty()) {
                    return response()->json([
                        'success' => false,
                        'statusCode' => 404,
                        'message' => "No District Sales Managers or Regional Sales Managers found.",
                    ], 404);
                }
    
                $salesReport = $employees->map(function ($emp) use ($month, $year, &$totalSalesForPeriod) {
                    $orders = Order::where('created_by', $emp->id)
                        ->where('status', 'Delivered')
                        ->whereYear('created_at', $year)
                        ->whereMonth('created_at', $month)
                        ->get();
    
                    $totalSales = $orders->sum('invoice_total');
                    $totalSalesForPeriod += $totalSales;
    
                    $employeeType = match ($emp->employee_type_id) {
                        3 => 'District Sales Manager',
                        4 => 'Regional Sales Manager',
                        default => 'Unknown',
                    };
    
                    return [
                        'employee_id' => $emp->id,
                        'employee_name' => $emp->name,
                        'employee_code' => $emp->employee_code,
                        'employee_type_id' => $emp->employee_type_id,
                        'employee_type' => $employeeType,
                        'total_sales_report' => (float) $totalSales,
                        'orders' => $orders->map(function ($order) {
                            return [
                                'order_id' => $order->id,
                                'created_at' => $order->created_at ? $order->created_at->format('d/m/Y') : null,
                                'invoice_total' => (float) $order->invoice_total,
                            ];
                        }),
                    ];
                });
            }
    
            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => "Sales report fetched successfully for $month/$year.",
                'data' => [
                    'total_sales_for_period' => (float) $totalSalesForPeriod,
                    'sales_report' => $salesReport,
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
    
    public function salesReportDetails(Request $request, $employee_id)
    {
        try {
            $employee = Auth::user();
    
            if (!$employee) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 401,
                    'message' => "User not authenticated.",
                ], 401);
            }
    
            if ($employee->employee_type_id == 3) { 
                $allowedEmployeeTypes = [1]; 
            } elseif ($employee->employee_type_id == 4) { 
                $allowedEmployeeTypes = [2, 3]; 
            } elseif ($employee->employee_type_id == 5) { 
                $allowedEmployeeTypes = [3, 4]; 
            } else {
                return response()->json([
                    'success' => false,
                    'statusCode' => 403,
                    'message' => "Unauthorized access.",
                ], 403);
            }
    
            $salesEmployee = Employee::where('id', $employee_id)
                ->whereIn('employee_type_id', $allowedEmployeeTypes)
                ->first();
    
            if (!$salesEmployee) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 404,
                    'message' => "Employee not found or access not allowed.",
                ], 404);
            }
    
            $month = $request->input('month', date('m'));
            $year = $request->input('year', date('Y'));
    
            $orders = Order::where('created_by', $salesEmployee->id)
                ->where('status', 'Delivered')
                ->whereYear('created_at', $year)
                ->whereMonth('created_at', $month)
                ->with('dealer:id,dealer_name') 
                ->get();
    
            $totalSalesAmount = $orders->sum('invoice_total');
    
            $ordersData = $orders->map(function ($order) {
                return [
                    'order_id' => $order->id,
                    'created_at' => $order->created_at ? $order->created_at->format('d/m/Y') : null,
                    'dealer_name' => $order->dealer ? $order->dealer->dealer_name : 'N/A',
                    'invoice_total' => (float) $order->invoice_total,
                ];
            });
    
            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => "Sales report details fetched successfully for $month/$year.",
                'data' => [
                    'employee_details' => [
                        'employee_id' => $salesEmployee->id,
                        'employee_code' => $salesEmployee->employee_code,
                        'employee_name' => $salesEmployee->name,
                        'email' => $salesEmployee->email,
                        'phone' => $salesEmployee->phone,
                        'total_sales_amount' => (float) $totalSalesAmount,
                    ],
                    'orders' => $ordersData,
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
    
    
    // public function orderReportListing(Request $request)
    // {
    //     try {
    //         // Get logged-in employee
    //         $employee = Auth::user();
    //         if (!$employee) {
    //             return response()->json([
    //                 'success' => false,
    //                 'statusCode' => 401,
    //                 'message' => "User not authenticated.",
    //             ], 401);
    //         }

    //         // Get Sales Executives in the same district
    //         $salesExecutives = Employee::where('district', $employee->district)
    //             ->where('employee_type_id', 1) // Only Sales Executives
    //             ->get();

    //         if ($salesExecutives->isEmpty()) {
    //             return response()->json([
    //                 'success' => false,
    //                 'statusCode' => 404,
    //                 'message' => "No Sales Executives found in this district.",
    //             ], 404);
    //         }

    //         // Get month & year from request, default to current month & year
    //         $month = $request->input('month', date('m'));
    //         $year = $request->input('year', date('Y'));

    //         // Initialize total order count
    //         $totalOrdersForPeriod = 0;

    //         // Fetch order report for each Sales Executive
    //         $reportData = $salesExecutives->map(function ($se) use ($month, $year, &$totalOrdersForPeriod) {
    //             // Count delivered orders
    //             $orderCount = Order::where('created_by', $se->id)
    //                 ->where('status', '!=' , 'Pending')
    //                 ->whereYear('created_at', $year)
    //                 ->whereMonth('created_at', $month)
    //                 ->count();

    //             // Increment total orders for the period
    //             $totalOrdersForPeriod += $orderCount;

    //             return [
    //                 'employee_id' => $se->id,
    //                 'employee_name' => $se->name,
    //                 'employee_code' => $se->employee_code,
    //                 'total_orders' => $orderCount,
    //             ];
    //         });

    //         return response()->json([
    //             'success' => true,
    //             'statusCode' => 200,
    //             'message' => "Order report listing fetched successfully for $month/$year.",
    //             'data' => [
    //                 'total_orders_for_period' => $totalOrdersForPeriod,
    //                 'order_report' => $reportData,
    //             ],
    //         ], 200);

    //     } catch (Exception $e) {
    //         return response()->json([
    //             'success' => false,
    //             'statusCode' => 500,
    //             'message' => $e->getMessage(),
    //         ], 500);
    //     }
    // }
    // public function orderReportDetails(Request $request, $employee_id)
    // {
    //     try {
    //         $employee = Employee::find($employee_id);
    
    //         if (!$employee) {
    //             return response()->json([
    //                 'success' => false,
    //                 'statusCode' => 404,
    //                 'message' => "Employee not found.",
    //             ], 404);
    //         }
    
    //         $month = $request->input('month', date('m'));
    //         $year = $request->input('year', date('Y'));
    
    //         $totalOrders = Order::where('created_by', $employee->id)
    //             ->where('status', '!=', 'Pending')
    //             ->whereYear('created_at', $year)
    //             ->whereMonth('created_at', $month)
    //             ->count();
    
    //         $orders = Order::where('created_by', $employee->id)
    //             ->where('status', '!=', 'Pending')
    //             ->whereYear('created_at', $year)
    //             ->whereMonth('created_at', $month)
    //             ->with('dealer:id,dealer_name') 
    //             ->orderBy('created_at', 'desc')
    //             ->get();
    
    //         $orderData = $orders->map(function ($order) {
    //             return [
    //                 'order_id' => $order->id,
    //                 'dealer_name' => optional($order->dealer)->dealer_name,
    //                 'created_at' => \Carbon\Carbon::parse($order->created_at)->format('d/m/Y'), 
    //                 'status' => $order->status,
    //                 'amount' => ($order->status === 'Delivered') ? (float) $order->invoice_total : (float) $order->total_amount,
    //             ];
    //         });
    
    //         return response()->json([
    //             'success' => true,
    //             'statusCode' => 200,
    //             'message' => "Order details fetched successfully for $month/$year.",
    //             'data' =>[
    //                 'employee' => [
    //                     'id' => $employee->id,
    //                     'name' => $employee->name,
    //                     'employee_code' => $employee->employee_code,
    //                     'email' => $employee->email,
    //                     'phone' => $employee->phone,
    //                     'total_orders' => $totalOrders,
    //                 ],
    //                 'orders' => $orderData,
    //             ],
    //         ], 200);
    
    //     } catch (Exception $e) {
    //         return response()->json([
    //             'success' => false,
    //             'statusCode' => 500,
    //             'message' => $e->getMessage(),
    //         ], 500);
    //     }
    // }
    public function orderReportListing(Request $request)
    {
        try {
            $employee = Auth::user();

            if (!$employee) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 401,
                    'message' => "User not authenticated.",
                ], 401);
            }

            if ($employee->employee_type_id == 3) { 
                $allowedEmployeeTypes = [1]; 
            } elseif ($employee->employee_type_id == 4) { 
                $allowedEmployeeTypes = [2, 3]; 
            } elseif ($employee->employee_type_id == 5) { 
                $allowedEmployeeTypes = [3, 4]; 
            } else {
                return response()->json([
                    'success' => false,
                    'statusCode' => 403,
                    'message' => "Unauthorized access.",
                ], 403);
            }

            $employees = Employee::whereIn('employee_type_id', $allowedEmployeeTypes)
                ->where('district', $employee->district)
                ->get();

            if ($employees->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 404,
                    'message' => "No employees found in this district.",
                ], 404);
            }

            $month = $request->input('month', date('m'));
            $year = $request->input('year', date('Y'));

            $totalOrdersForPeriod = 0;

            $reportData = $employees->map(function ($emp) use ($month, $year, &$totalOrdersForPeriod) {
                $orderCount = Order::where('created_by', $emp->id)
                    ->where('status', '!=', 'Pending')
                    ->whereYear('created_at', $year)
                    ->whereMonth('created_at', $month)
                    ->count();

                $totalOrdersForPeriod += $orderCount;

                return [
                    'employee_id' => $emp->id,
                    'employee_name' => $emp->name,
                    'employee_code' => $emp->employee_code,
                    'total_orders' => $orderCount,
                ];
            });

            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => "Order report listing fetched successfully for $month/$year.",
                'data' => [
                    'total_orders_for_period' => $totalOrdersForPeriod,
                    'order_report' => $reportData,
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
    public function orderReportDetails(Request $request, $employee_id)
    {
        try {
            $loggedInEmployee = Auth::user();

            if (!$loggedInEmployee) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 401,
                    'message' => "User not authenticated.",
                ], 401);
            }

            if ($loggedInEmployee->employee_type_id == 3) { 
                $allowedEmployeeTypes = [1]; 
            } elseif ($loggedInEmployee->employee_type_id == 4) { 
                $allowedEmployeeTypes = [2, 3]; 
            } elseif ($loggedInEmployee->employee_type_id == 5) { 
                $allowedEmployeeTypes = [3, 4]; 
            } else {
                return response()->json([
                    'success' => false,
                    'statusCode' => 403,
                    'message' => "Unauthorized access.",
                ], 403);
            }

            $employee = Employee::whereIn('employee_type_id', $allowedEmployeeTypes)
                ->where('id', $employee_id)
                ->first();

            if (!$employee) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 404,
                    'message' => "Employee not found or access denied.",
                ], 404);
            }

            $month = $request->input('month', date('m'));
            $year = $request->input('year', date('Y'));

            $totalOrders = Order::where('created_by', $employee->id)
                ->where('status', '!=', 'Pending')
                ->whereYear('created_at', $year)
                ->whereMonth('created_at', $month)
                ->count();

            $orders = Order::where('created_by', $employee->id)
                ->where('status', '!=', 'Pending')
                ->whereYear('created_at', $year)
                ->whereMonth('created_at', $month)
                ->with('dealer:id,dealer_name') 
                ->orderBy('created_at', 'desc')
                ->get();

            $orderData = $orders->map(function ($order) {
                return [
                    'order_id' => $order->id,
                    'dealer_name' => optional($order->dealer)->dealer_name,
                    'created_at' => \Carbon\Carbon::parse($order->created_at)->format('d/m/Y'), 
                    'status' => $order->status,
                    'amount' => ($order->status === 'Delivered') ? (float) $order->invoice_total : (float) $order->total_amount,
                ];
            });

            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => "Order details fetched successfully for $month/$year.",
                'data' => [
                    'employee' => [
                        'id' => $employee->id,
                        'name' => $employee->name,
                        'employee_code' => $employee->employee_code,
                        'email' => $employee->email,
                        'phone' => $employee->phone,
                        'total_orders' => $totalOrders,
                    ],
                    'orders' => $orderData,
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


    
    // public function leadReportListing(Request $request)
    // {
    //     try {
    //         $employee = Auth::user();
    //         if (!$employee) {
    //             return response()->json([
    //                 'success' => false,
    //                 'statusCode' => 401,
    //                 'message' => "User not authenticated.",
    //             ], 401);
    //         }
    
    //         $salesExecutives = Employee::where('district', $employee->district)
    //             ->where('employee_type_id', 1) 
    //             ->get();
    
    //         if ($salesExecutives->isEmpty()) {
    //             return response()->json([
    //                 'success' => false,
    //                 'statusCode' => 404,
    //                 'message' => "No Sales Executives found in this district.",
    //             ], 404);
    //         }
    
    //         $month = $request->input('month', date('m'));
    //         $year = $request->input('year', date('Y'));
    
    //         $totalOpenedLeads = 0;
    //         $totalWonLeads = 0;
    //         $totalLostLeads = 0;
    
    //         $reportData = $salesExecutives->map(function ($se) use ($month, $year, &$totalOpenedLeads, &$totalWonLeads, &$totalLostLeads) {
    //             $openedLeads = Lead::where('created_by', $se->id)
    //                 ->whereIn('status', ['Opened', 'Follow Up'])
    //                 ->whereYear('created_at', $year)
    //                 ->whereMonth('created_at', $month)
    //                 ->count();
    
    //             $wonLeads = Lead::where('created_by', $se->id)
    //                 ->where('status', 'Won')
    //                 ->whereYear('created_at', $year)
    //                 ->whereMonth('created_at', $month)
    //                 ->count();
    
    //             $lostLeads = Lead::where('created_by', $se->id)
    //                 ->where('status', 'Lost')
    //                 ->whereYear('created_at', $year)
    //                 ->whereMonth('created_at', $month)
    //                 ->count();
    
    //             $totalOpenedLeads += $openedLeads;
    //             $totalWonLeads += $wonLeads;
    //             $totalLostLeads += $lostLeads;
    
    //             return [
    //                 'employee_id' => $se->id,
    //                 'employee_name' => $se->name,
    //                 'employee_code' => $se->employee_code,
    //                 'total_leads' => [
    //                     'opened' => $openedLeads,
    //                     'won' => $wonLeads,
    //                     'lost' => $lostLeads,
    //                 ],
    //             ];
    //         });
    
    //         return response()->json([
    //             'success' => true,
    //             'statusCode' => 200,
    //             'message' => "Lead report listing fetched successfully for $month/$year.",
                
    //             'data' => [
    //                 'total_leads_for_period' => [
    //                     'opened' => $totalOpenedLeads,
    //                     'won' => $totalWonLeads,
    //                     'lost' => $totalLostLeads,
    //                 ],
    //                 'lead_report' =>$reportData,
    //             ]
                
    //         ], 200);
    
    //     } catch (Exception $e) {
    //         return response()->json([
    //             'success' => false,
    //             'statusCode' => 500,
    //             'message' => $e->getMessage(),
    //         ], 500);
    //     }
    // }
    // public function leadReportDetails(Request $request, $employee_id)
    // {
    //     try {
    //         // Get the logged-in employee
    //         $employee = Auth::user();
    //         if (!$employee) {
    //             return response()->json([
    //                 'success' => false,
    //                 'statusCode' => 401,
    //                 'message' => "User not authenticated.",
    //             ], 401);
    //         }
    //         if ($employee->employee_type_id == 3) { 
    //             // DSM can only see Sales Executives (SE)
    //             $allowedEmployeeTypes = [1]; 
    //         } elseif ($employee->employee_type_id == 4) { 
    //             // RSM can see both ASOs and DSM
    //             $allowedEmployeeTypes = [2, 3]; 
    //         } else {
    //             return response()->json([
    //                 'success' => false,
    //                 'statusCode' => 403,
    //                 'message' => "Unauthorized access.",
    //             ], 403);
    //         }
    //         // Find the Sales Executive
    //         $salesEmployee = Employee::where('id', $employee_id)
    //             ->whereIn('employee_type_id', $allowedEmployeeTypes)
    //             ->first();

    //         if (!$salesEmployee) {
    //             return response()->json([
    //                 'success' => false,
    //                 'statusCode' => 404,
    //                 'message' => "Employee not found or not authorized.",
    //             ], 404);
    //         }

    //         // Get month & year from request, default to current month & year
    //         $month = $request->input('month', date('m'));
    //         $year = $request->input('year', date('Y'));

    //         // Count leads based on status
    //         $openedLeads = Lead::where('created_by', $salesEmployee->id)
    //             ->whereIn('status', ['Opened', 'Follow Up'])
    //             ->whereYear('created_at', $year)
    //             ->whereMonth('created_at', $month)
    //             ->count();

    //         $wonLeads = Lead::where('created_by', $salesEmployee->id)
    //             ->where('status', 'Won')
    //             ->whereYear('created_at', $year)
    //             ->whereMonth('created_at', $month)
    //             ->count();

    //         $lostLeads = Lead::where('created_by', $salesEmployee->id)
    //             ->where('status', 'Lost')
    //             ->whereYear('created_at', $year)
    //             ->whereMonth('created_at', $month)
    //             ->count();

    //         // Fetch the lead details
    //         $leadDetails = Lead::where('created_by', $salesEmployee->id)
    //             ->whereYear('created_at', $year)
    //             ->whereMonth('created_at', $month)
    //             ->with('customerType') // Load customer type relation
    //             ->get()
    //             ->map(function ($lead) {
    //                 return [
    //                     'customer_name' => $lead->customer_name,
    //                     'customer_type' => $lead->customerType->name ?? 'N/A',
    //                     'created_at' => $lead->created_at->format('d/m/Y'),
    //                     'location' => $lead->location,
    //                     'status' => $lead->status,
    //                 ];
    //             });

    //         return response()->json([
    //             'success' => true,
    //             'statusCode' => 200,
    //             'message' => "Lead report details fetched successfully for {$salesEmployee->name}.",
    //             'data' => [
    //                 'employee' => [
    //                     'employee_id' => $salesEmployee->id,
    //                     'employee_name' => $salesEmployee->name,
    //                     'employee_code' => $salesEmployee->employee_code,
    //                     'email' => $salesEmployee->email,
    //                     'phone' => $salesEmployee->phone,
    //                 ],
    //                 'total_leads' => [
    //                     'opened' => $openedLeads,
    //                     'won' => $wonLeads,
    //                     'lost' => $lostLeads,
    //                 ],
    //                 'leads' => $leadDetails,
    //             ],
                    
    //         ], 200);

    //     } catch (Exception $e) {
    //         return response()->json([
    //             'success' => false,
    //             'statusCode' => 500,
    //             'message' => $e->getMessage(),
    //         ], 500);
    //     }
    // }
    public function leadReportListing(Request $request)
    {
        try {
            $loggedInEmployee = Auth::user();

            if (!$loggedInEmployee) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 401,
                    'message' => "User not authenticated.",
                ], 401);
            }
            if ($loggedInEmployee->employee_type_id == 3) { 
                $allowedEmployeeTypes = [1]; 
            } elseif ($loggedInEmployee->employee_type_id == 4) { 
                $allowedEmployeeTypes = [2, 3]; 
            } elseif ($loggedInEmployee->employee_type_id == 5) { 
                $allowedEmployeeTypes = [3, 4]; 
            } else {
                return response()->json([
                    'success' => false,
                    'statusCode' => 403,
                    'message' => "Unauthorized access.",
                ], 403);
            }

            $employees = Employee::whereIn('employee_type_id', $allowedEmployeeTypes)
                ->where('district_id', $loggedInEmployee->district_id)
                ->get();
            if ($employees->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 404,
                    'message' => "No employees found in this district.",
                ], 404);
            }

            $month = $request->input('month', date('m'));
            $year = $request->input('year', date('Y'));

            $totalOpenedLeads = 0;
            $totalWonLeads = 0;
            $totalLostLeads = 0;

            $reportData = $employees->map(function ($employee) use ($month, $year, &$totalOpenedLeads, &$totalWonLeads, &$totalLostLeads) {
                $openedLeads = Lead::where('created_by', $employee->id)
                    ->whereIn('status', ['Opened', 'Follow Up'])
                    ->whereYear('created_at', $year)
                    ->whereMonth('created_at', $month)
                    ->count();
                $wonLeads = Lead::where('created_by', $employee->id)
                    ->where('status', 'Won')
                    ->whereYear('created_at', $year)
                    ->whereMonth('created_at', $month)
                    ->count();

                $lostLeads = Lead::where('created_by', $employee->id)
                    ->where('status', 'Lost')
                    ->whereYear('created_at', $year)
                    ->whereMonth('created_at', $month)
                    ->count();

                $totalOpenedLeads += $openedLeads;
                $totalWonLeads += $wonLeads;
                $totalLostLeads += $lostLeads;
                $total_leads = $openedLeads+$wonLeads+$lostLeads;

                return [
                    'employee_id' => $employee->id,
                    'employee_name' => $employee->name,
                    'employee_code' => $employee->employee_code,
                    'total_leads' => $total_leads,
                ];
            });

            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => "Lead report listing fetched successfully for $month/$year.",
                'data' => [
                    'total_leads_for_period' => [
                        'opened' => $totalOpenedLeads,
                        'won' => $totalWonLeads,
                        'lost' => $totalLostLeads,
                    ],
                    'lead_report' => $reportData,
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
    public function leadReportDetails(Request $request, $employee_id)
    {
        try {
            $loggedInEmployee = Auth::user();

            if (!$loggedInEmployee) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 401,
                    'message' => "User not authenticated.",
                ], 401);
            }

            if ($loggedInEmployee->employee_type_id == 3) { 
                $allowedEmployeeTypes = [1]; 
            } elseif ($loggedInEmployee->employee_type_id == 4) { 
                $allowedEmployeeTypes = [2, 3]; 
            } elseif ($loggedInEmployee->employee_type_id == 5) { 
                $allowedEmployeeTypes = [3, 4]; 
            } else {
                return response()->json([
                    'success' => false,
                    'statusCode' => 403,
                    'message' => "Unauthorized access.",
                ], 403);
            }

            $salesEmployee = Employee::where('id', $employee_id)
                ->whereIn('employee_type_id', $allowedEmployeeTypes)
                ->first();

            if (!$salesEmployee) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 404,
                    'message' => "Employee not found or not authorized.",
                ], 404);
            }

            $month = $request->input('month', date('m'));
            $year = $request->input('year', date('Y'));

            $leadCounts = Lead::where('created_by', $salesEmployee->id)
                ->whereYear('created_at', $year)
                ->whereMonth('created_at', $month)
                ->selectRaw("
                    SUM(CASE WHEN status IN ('Opened', 'Follow Up') THEN 1 ELSE 0 END) as opened,
                    SUM(CASE WHEN status = 'Won' THEN 1 ELSE 0 END) as won,
                    SUM(CASE WHEN status = 'Lost' THEN 1 ELSE 0 END) as lost
                ")
                ->first();

            $leadDetails = Lead::where('created_by', $salesEmployee->id)
                ->whereYear('created_at', $year)
                ->whereMonth('created_at', $month)
                ->with('customerType') 
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($lead) {
                    return [
                        'id' => $lead->id,
                        'customer_name' => $lead->customer_name,
                        'customer_type' => optional($lead->customerType)->name ?? 'N/A',
                        'created_at' => $lead->created_at->format('d/m/Y'),
                        'location' => $lead->location,
                        'status' => $lead->status,
                    ];
                });

            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => "Lead report details fetched successfully for {$salesEmployee->name}.",
                'data' => [
                    'employee' => [
                        'employee_id' => $salesEmployee->id,
                        'employee_name' => $salesEmployee->name,
                        'employee_code' => $salesEmployee->employee_code,
                        'email' => $salesEmployee->email,
                        'phone' => $salesEmployee->phone,
                    ],
                    'total_leads' => [
                        'opened' => (int) $leadCounts->opened,
                        'won' => (int) $leadCounts->won,
                        'lost' => (int) $leadCounts->lost,
                    ],
                    'leads' => $leadDetails,
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

    public function sendForApproval($orderId)
    {
        try {
            $order = Order::find($orderId);

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 404,
                    'message' => 'Order not found'
                ], 404);
            }

            if (Auth::user()->employee_type_id !== 2) { 
                return response()->json([
                    'success' => false,
                    'statusCode' => 403,
                    'message' => 'Unauthorized access'
                ], 403);
            }

            $order->update([
                'send_for_approval' => 1,
                'send_for_approval_by' => Auth::id(),
            ]);

            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Order sent for approval successfully'
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error in sendForApproval: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => 'An error occurred while sending order for approval',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function orderApprovalList()
    {
       
        try {
            $user = Auth::user();
           
            if ($user->employee_type_id !== 5) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 403,
                    'message' => 'Access denied. Only Sales Managers can view this list.'
                ], 403);
            }


            $employeeIds = Employee::whereIn('employee_type_id', [2, 3, 4])->pluck('id');
            if ($employeeIds->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 404,
                    'message' => 'No employees found for ASO, DSM, or RSM'
                ], 404);
            }


            $orders = Order::where(function ($query) use ($employeeIds) {
                    $query->whereIn('created_by', $employeeIds)
                        ->whereNull('send_for_approval')
                        ->where('dealer_flag_order', '0');
                })
                ->orWhere(function ($query) {
                    $query->whereNotNull('created_by_dealer')
                        ->where('dealer_flag_order', '1')
                        ->where('send_for_approval', '1');
                })
                ->with(['createdBy', 'dealer', 'orderType', 'paymentTerm']) 
                ->orderBy('created_at', 'desc')
                ->get();

            if ($orders->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 404,
                    'message' => 'No orders found for approval'
                ], 404);
            }

            $formattedOrders = $orders->map(function ($order) {
                $dealerName = null;
    
                if ($order->created_by_dealer) {
                    $dealer = Dealer::find($order->created_by_dealer);
                    $dealerName = $dealer ? $dealer->dealer_name : 'N/A';
                } else {
                    $dealerName = $order->dealer->dealer_name ?? 'N/A';
                }
    
                return [
                    'id'            => $order->id,
                    'created_at'    => Carbon::parse($order->created_at)->format('d/m/Y'),
                    'dealer_name'   => $dealerName,
                    'order_status'  => $order->status,
                    'total_amount'  => (int) $order->total_amount,
                ];
            });
    
            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Order approval list retrieved successfully',
                'data' => $formattedOrders
            ], 200);

        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => 'An error occurred while retrieving the order approval list.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
 
}