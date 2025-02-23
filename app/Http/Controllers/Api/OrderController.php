<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\AssignRoute;
use App\Models\Dealer;
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
                'order_type' => 'required|exists:order_types,id',
                'customer_type_id' => 'nullable|exists:customer_types,id',
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

   
    // public function dealerOrderList(Request $request)
    // {
    //     try {
    //         if ($request->has('search_key')) {
    //             return $this->orderFilter($request);
    //         }

    //         $employee = Auth::user();

    //         if ($employee) {
    //             $orders = Order::join('dealers', 'orders.created_by_dealer', '=', 'dealers.id') 
    //                     ->where('orders.dealer_flag_order', '1') 
    //                     ->select([
    //                         'orders.id',
    //                         'orders.total_amount',
    //                         'orders.status',
    //                         'orders.created_at',
    //                         'dealers.id as dealer_id',
    //                         'dealers.dealer_name',
    //                         'dealers.dealer_code'
    //                     ])
    //                     ->get()
    //                     ->map(function ($order) {
    //                         $order->total_amount = (float) sprintf("%.2f", $order->total_amount);
    //                         return $order;
    //                     });
             
    //             return response()->json([
    //                 'success' => true,
    //                 'statusCode' => 200,
    //                 'message' => 'Dealer-created orders fetched successfully',
    //                 'data' => $orders->map(function ($order) {
    //                     return [
    //                         'id' => $order->id,
    //                         'total_amount' => $order->total_amount,
    //                         'status' => $order->status,
    //                         'created_at' => $order->created_at->format('d/m/Y'),
    //                         'dealer' => [
    //                             'id' => $order->dealer_id,
    //                             'name' => $order->dealer_name,
    //                             'code' => $order->dealer_code,
    //                         ],
    //                     ];
    //                 }),
    //             ], 200);
    //         } else {
    //             return response()->json([
    //                 'success' => false,
    //                 'statusCode' => 401,
    //                 'message' => "User not Authenticated",
    //             ], 401);
    //         }
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
                'paymentTerm:id,name',
                'vehicleCategory:id,vehicle_category_name' 
            ])->findOrFail($orderId);
            $dealerId = $order->created_by_dealer;
            $totalOutstandingPayments = OutstandingPayment::where('dealer_id', $dealerId)->sum('outstanding_amount');
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
                'total_outstanding_payments' => $totalOutstandingPayments
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
    
            // Get all assigned route IDs for the logged-in employee
            $assignedRoutes = AssignRoute::where('employee_id', $employee->id)->pluck('id')->toArray();
    
            if (empty($assignedRoutes)) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 404,
                    'message' => "No assigned routes found for the employee.",
                    'data' => []
                ], 404);
            }
    
            // Get all dealers that belong to these assigned routes
            $dealers = Dealer::whereIn('assigned_route_id', $assignedRoutes)->pluck('id')->toArray();
    
            if (empty($dealers)) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 404,
                    'message' => "No dealers found for the assigned routes.",
                    'data' => []
                ], 404);
            }
    
            // Fetch orders created by these dealers
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
                ->get() // Fetch results
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
    

    //dealerOrderStatusUpdate
    public function dealerOrderStatusUpdate(Request $request, $orderId)
    {
        try {
            // Validate request data
            $validatedData = $request->validate([
                'status' => 'required|in:Accepted,Rejected',
                'reason_for_rejection' => 'required_if:status,Rejected|nullable|string|max:255',
            ]);
    
            // Get the logged-in employee
            $employee = Auth::user();
    
            if (!$employee) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 401,
                    'message' => "User not Authenticated",
                ], 401);
            }
    
            // Get assigned routes for the logged-in employee
            $assignedRoutes = AssignRoute::where('employee_id', $employee->id)->pluck('id')->toArray();
    
            if (empty($assignedRoutes)) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 404,
                    'message' => "No assigned routes found for the employee.",
                ], 404);
            }
    
            // Get dealers linked to those routes
            $dealers = Dealer::whereIn('assigned_route_id', $assignedRoutes)->pluck('id')->toArray();
    
            if (empty($dealers)) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 404,
                    'message' => "No dealers found for the assigned routes.",
                ], 404);
            }
    
            // Find the order created by a dealer assigned to these routes
            $order = Order::where('id', $orderId)
                ->whereIn('created_by_dealer', $dealers)
                ->first();
    
            if (!$order) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 404,
                    'message' => "Order with ID $orderId not found or not accessible.",
                ], 404);
            }
    
            // Prepare update data
            $updateData = ['status' => $validatedData['status']];
            if ($validatedData['status'] === 'Rejected') {
                $updateData['reason_for_rejection'] = $validatedData['reason_for_rejection'];
            } else {
                $updateData['reason_for_rejection'] = null;
            }
    
            // Update the order status
            $order->update($updateData);
    
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
    
        }catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 400,
                'message' => "Validation error",
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

            // Get assigned routes for the logged-in employee
            $assignedRoutes = AssignRoute::where('employee_id', $employee->id)->pluck('id')->toArray();

            if (empty($assignedRoutes)) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 404,
                    'message' => "No assigned routes found for the employee.",
                    'data' => []
                ], 404);
            }

            // Get dealers linked to those routes
            $dealers = Dealer::whereIn('assigned_route_id', $assignedRoutes)->pluck('id')->toArray();

            if (empty($dealers)) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 404,
                    'message' => "No dealers found for the assigned routes.",
                    'data' => []
                ], 404);
            }

            // Fetch dealer-created orders based on the search key
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
    //         // Get logged-in employee
    //         $employee = Auth::user();

    //         if (!$employee) {
    //             return response()->json([
    //                 'success' => false,
    //                 'statusCode' => 401,
    //                 'message' => "User not Authenticated",
    //             ], 401);
    //         }

    //         // Get assigned routes for the employee
    //         $assignedRoutes = AssignRoute::where('employee_id', $employee->id)->pluck('id')->toArray();

    //         if (empty($assignedRoutes)) {
    //             return response()->json([
    //                 'success' => false,
    //                 'statusCode' => 404,
    //                 'message' => "No assigned routes found for the employee.",
    //             ], 404);
    //         }

    //         // Get dealers in these assigned routes
    //         $dealers = Dealer::whereIn('assigned_route_id', $assignedRoutes)->pluck('id')->toArray();

    //         if (empty($dealers)) {
    //             return response()->json([
    //                 'success' => false,
    //                 'statusCode' => 404,
    //                 'message' => "No dealers found for the assigned routes.",
    //             ], 404);
    //         }

    //         // Fetch outstanding payments for these dealers
    //         $outstandingPayments = OutstandingPayment::whereIn('dealer_id', $dealers)
    //             ->where('outstanding_amount', '>', 0) // Only fetch unpaid amounts
    //             ->with('dealer:id,dealer_name,dealer_code') // Load dealer details
    //             ->orderBy('due_date', 'asc')
    //             ->get();

    //         // Format response data
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
    
    public function outstandingPaymentsList()
    {
        try {
            // Get logged-in employee
            $employee = Auth::user();
    
            if (!$employee) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 401,
                    'message' => "User not Authenticated",
                ], 401);
            }
    
            $dealers = [];
    
            if ($employee->employee_type_id == 2) { // ASO (Area Sales Officer)
                // Get assigned routes for the ASO
                $assignedRoutes = AssignRoute::where('employee_id', $employee->id)->pluck('id')->toArray();
    
                if (empty($assignedRoutes)) {
                    return response()->json([
                        'success' => false,
                        'statusCode' => 404,
                        'message' => "No assigned routes found for the employee.",
                    ], 404);
                }
    
                // Get dealers in these assigned routes
                $dealers = Dealer::whereIn('assigned_route_id', $assignedRoutes)->pluck('id')->toArray();
    
            } elseif ($employee->employee_type_id == 3) { // DSM (District Sales Manager)
                // Get dealers in the same district as the DSM
                $dealers = Dealer::where('district', $employee->district)->pluck('id')->toArray();
            }
    
            if (empty($dealers)) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 404,
                    'message' => "No dealers found for the given criteria.",
                ], 404);
            }
    
            // Fetch outstanding payments for these dealers
            $outstandingPayments = OutstandingPayment::whereIn('dealer_id', $dealers)
                ->where('outstanding_amount', '>', 0) // Only fetch unpaid amounts
                ->with('dealer:id,dealer_name,dealer_code') // Load dealer details
                ->orderBy('due_date', 'asc')
                ->get();
    
            // Format response data
            $paymentsData = $outstandingPayments->map(function ($payment) {
                return [
                    'order_id' => $payment->order_id,
                    'invoice_number' => $payment->invoice_number,
                    'invoice_date' => $payment->invoice_date ? \Carbon\Carbon::parse($payment->invoice_date)->format('d/m/Y') : null,
                    'due_date' => $payment->due_date ? \Carbon\Carbon::parse($payment->due_date)->format('d/m/Y') : null,
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

            // Find the outstanding payment details for the given Order ID
            $outstandingPayment = OutstandingPayment::with([
                'dealer:id,dealer_name,dealer_code',
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

            // Format response
            $order = $outstandingPayment->order;

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
                    'invoice_number' => $outstandingPayment->invoice_number,
                    'invoice_date' => $outstandingPayment->invoice_date ? \Carbon\Carbon::parse($outstandingPayment->invoice_date)->format('d/m/Y') : null,
                    'due_date' => $outstandingPayment->due_date ? \Carbon\Carbon::parse($outstandingPayment->due_date)->format('d/m/Y') : null,
                    'invoice_total' => (float) $outstandingPayment->invoice_total,
                    'paid_amount' => (float) $outstandingPayment->paid_amount,
                    'outstanding_amount' => (float) $outstandingPayment->outstanding_amount,
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
            $validatedData = $request->validate([
                'commitments' => 'required|array|min:1',
                'commitments.*.committed_date' => 'required|date|after_or_equal:today',
                'commitments.*.committed_amount' => 'required|numeric|min:1',
            ]);

            // Fetch outstanding payment
            $outstandingPayment = OutstandingPayment::findOrFail($outstandingPaymentId);

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

    public function salesExecutiveSalesReport(Request $request)
    {
        try {
            // Get logged-in employee
            $employee = Auth::user();

            if (!$employee) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 401,
                    'message' => "User not authenticated.",
                ], 401);
            }

            // Fetch Sales Executives in the same district
            $salesExecutives = Employee::where('district', $employee->district)
                ->where('employee_type_id', 1) // Assuming Sales Executives have employee_type_id = 4
                ->get();

            if ($salesExecutives->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 404,
                    'message' => "No Sales Executives found in this district.",
                ], 404);
            }

            // Get month and year from request, default to current month & year
            $month = $request->input('month', date('m'));
            $year = $request->input('year', date('Y'));

            // Initialize total sales for the chosen period
            $totalSalesForPeriod = 0;

            // Prepare sales data for each Sales Executive
            $salesReport = $salesExecutives->map(function ($se) use ($month, $year, &$totalSalesForPeriod) {
                // Fetch delivered orders for this SE within the selected month & year
                $orders = Order::where('created_by', $se->id)
                    ->where('status', 'delivered')
                    ->whereYear('created_at', $year)
                    ->whereMonth('created_at', $month)
                    ->get();

                // Sum the total invoice amount for delivered orders
                $totalSales = $orders->sum('invoice_total');
                $totalSalesForPeriod += $totalSales; // Add to overall total

                return [
                    'employee_id' => $se->id,
                    'employee_name' => $se->name,
                    'employee_code' => $se->employee_code,
                    'total_sales_report' => (float) $totalSales,
                    'orders' => $orders->map(function ($order) {
                        return [
                            'order_id' => $order->id,
                            'created_at' => $order->created_at->toDateTimeString(), // Proper timestamp format
                            'invoice_total' => (float) $order->invoice_total,
                        ];
                    }),
                ];
            });

            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => "Sales report fetched successfully for $month/$year.",
                'total_sales_for_period' => (float) $totalSalesForPeriod, // Total sales for the chosen period
                'data' => $salesReport,
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