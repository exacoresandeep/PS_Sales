<?php

namespace App\Http\Controllers\Dealer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Dealer;
use App\Models\Employee;
use App\Models\Payment;
use App\Models\ProductType;
use App\Models\CreditNote;
use App\Models\OutstandingPaymentCommitment;
use App\Models\OutstandingPayment;
use App\Models\AssignRoute;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Exception;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;

class DealerOrderController extends Controller
{
    public function index(Request $request)
    {
        try {
           
            $dealer = Auth::user();
            if($dealer)
            {
                $orders = Order::where('created_by_dealer', $dealer->id)
                ->where('dealer_flag_order', "1")
                ->with([
                    'dealer:id,dealer_name,dealer_code',
                    'orderItems:id,order_id,total_quantity' // Include order items to sum total_quantity
                ])
                ->select('id', 'total_amount', 'status', 'created_at', 'created_by_dealer')
                ->orderBy('id', 'desc')
                ->get()
                ->map(function ($order) {
                    $order->total_amount = (float) sprintf("%.2f", $order->total_amount);
                    $order->total_quantity = $order->orderItems->sum('total_quantity'); // Summing up total quantity
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
                            'total_quantity' => $order->total_quantity,
                            'status' => $order->status,
                            'created_at' => $order->created_at->format('d/m/Y'),
                            'dealer' => [
                                'name' => $order->dealers->dealer_name,
                                'dealer_code' => $order->dealers->dealer_code, 
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
            $dealer = Auth::user();

            if (!$dealer) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 401,
                    'message' => "User not Authenticated",
                ], 401);
            }

            $validatedData = $request->validate([
                'order_type' => 'nullable|exists:order_types,id',
                'payment_terms_id' => 'required|exists:payment_terms,id',
                'billing_date' => 'required|string',
                'total_amount' => 'nullable|numeric',
                'additional_information' => 'nullable|string',
                'status' => 'nullable|in:Pending,Dispatched,Delivered',
                'vehicle_category_id' => 'required|integer',
             
                'vehicle_number' => [
                    Rule::requiredIf($request->vehicle_category_id == 1),'nullable', 'string'
                ],
                'driver_name' => [
                    Rule::requiredIf($request->vehicle_category_id == 1),'nullable', 'string'
                ],
                'driver_phone' => [
                    Rule::requiredIf($request->vehicle_category_id == 1),'nullable', 'string'
                ],
                // 'vehicle_type' => 'nullable|string',
                'order_items' => 'required|array',
                'order_items.*.product_id' => 'required|exists:products,id',
                'order_items.*.product_details' => 'nullable|array',
                'attachment' => 'nullable|array',
                'attachment.*' => 'nullable|string',
            ]);

            $validatedData['billing_date'] = Carbon::createFromFormat('d-m-Y', $validatedData['billing_date'])->format('Y-m-d');
        
            $validatedData['created_by'] = null;
            $validatedData['created_by_dealer'] = $dealer->id;
            $validatedData['dealer_flag_order'] = '1';

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
                    'payment_terms_id' => $order->payment_terms_id,
                    'billing_date' => Carbon::parse($order->billing_date)->format('d/m/Y'),
                    'total_amount' => round($order->total_amount, 2),
                    'additional_information' => $order->additional_information,
                    'status' => $order->status,
                    'created_by_dealer' => $order->created_by_dealer,
                    'dealer_flag_order' => $order->dealer_flag_order,
                    'vehicle_category_id' => $order->vehicle_category_id,
                    // 'vehicle_type' => $order->vehicle_type,
                    'vehicle_number' => $order->vehicle_number,
                    'driver_name' => $order->driver_name,
                    'driver_phone' => $order->driver_phone,
                    'updated_at' => Carbon::parse($order->updated_at)->format('d/m/Y'),
                    'created_at' => Carbon::parse($order->created_at)->format('d/m/Y'),
                    'id' => $order->id,

            ];
            
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
                'dealers:id,dealer_name,dealer_code',
                'orderItems.product:id,product_name',
                'orderItems',
                'paymentTerm:id,name',
                'vehicleCategory:id,vehicle_category_name'
            ])->findOrFail($orderId);
    
            $order->billing_date = $order->billing_date ? Carbon::parse($order->billing_date)->format('d/m/Y') : null;
            $order->created_at = Carbon::parse($order->created_at)->format('d/m-Y');
            $order->updated_at = Carbon::parse($order->updated_at)->format('d/m-Y');
    
            $responseData = [
                'id' => $order->id,
                'order_type' => $order->orderType->name ?? null,
                'dealer' => [
                    'id' => $order->dealers->id ?? null,
                    'name' => $order->dealers->dealer_name ?? null,
                    'code' => $order->dealers->dealer_code ?? null,
                ],
                'payment_terms' => [
                    'id' => $order->paymentTerm->id ?? null,
                    'name' => $order->paymentTerm->name ?? null,
                ],
                'billing_date' => $order->billing_date,
                'total_amount' => round($order->total_amount, 2),
                'additional_information' => $order->additional_information,
                'status' => $order->status,
                'created_by_dealer' => $order->created_by_dealer,
                'dealer_flag_order' => $order->dealer_flag_order,
                'vehicle' => [
                    'category_id' => $order->vehicle_category_id,
                    'category_name' => $order->vehicleCategory->vehicle_category_name ?? null,
                    'vehicle_number' => $order->vehicle_number,
                    'driver_name' => $order->driver_name,
                    'driver_phone' => $order->driver_phone,
                ],
                'attachments' => $order->attachment ?? [],
    
                'order_items' => $order->orderItems->map(function ($item) {

                    return [
                        'product_id' => $item->product_id,
                        'product_name' => $item->product->product_name ?? 'N/A',
                        'total_quantity' => $item->total_quantity,
                        'balance_quantity' => $item->balance_quantity,
                        'product_details' => collect($item->product_details)->map(function ($detail) {
                            return [
                                'product_type_id' => $detail['product_type_id'],
                                'quantity' => $detail['quantity'],
                                'rate' => $detail['rate'],
                                'product_type' => ProductType::where('id', $detail['product_type_id'])->value('type_name') ?? 'N/A',
                            ];
                        }),
                    ];
                }),
    
                'created_at' => $order->created_at,
                'updated_at' => $order->updated_at,
            ];
    
            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Order details fetched successfully',
                'data' => $responseData,
            ], 200);
    
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    public function trackOrder(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required|exists:orders,id', 
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
                'statusCode' => 422,
                'data' => [],
                'success' => false,
            ], 422);
        }

        try {
            $user = Auth::user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 401,
                    'message' => 'User not authenticated',
                ], 401);
            }

            $order = Order::where('id', $request->order_id)
                ->where('created_by_dealer', $user->id) 
                ->select('id', 'status', 'created_at', 'accepted_time', 'rejected_time', 'dispatched_time', 'intransit_time', 'delivered_time')
                ->first();

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 404,
                    'message' => 'Order not found',
                    'data' => [],
                ], 404);
            }

            // Format timestamps
            $formattedOrder = [
                'id' => $order->id,
                'status' => $order->status,
                'timestamps' => [
                    'pending_time' => $order->created_at ? Carbon::parse($order->created_at)->format('d/m/Y H:i:s') : null,
                    'accepted_time' => $order->accepted_time ? Carbon::parse($order->accepted_time)->format('d/m/Y H:i:s') : null,
                    'rejected_time' => $order->rejected_time ? Carbon::parse($order->rejected_time)->format('d/m/Y H:i:s') : null,
                    'dispatched_time' => $order->dispatched_time ? Carbon::parse($order->dispatched_time)->format('d/m/Y H:i:s') : null,
                    'intransit_time' => $order->intransit_time ? Carbon::parse($order->intransit_time)->format('d/m/Y H:i:s') : null,
                    'delivered_time' => $order->delivered_time ? Carbon::parse($order->delivered_time)->format('d/m/Y H:i:s') : null,
                ]
            ];

            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Order tracking details fetched successfully',
                'data' => $formattedOrder,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => $e->getMessage(),
            ], 500);
        }
    }


    public function monthlySalesTransaction(Request $request)
    {
        try {
            $dealer = Auth::user();

            if (!$dealer) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 401,
                    'message' => "User not Authenticated",
                ], 401);
            }

            $month = $request->input('month', Carbon::now()->format('m'));
            $year = $request->input('year', Carbon::now()->format('Y'));

            $assignedRouteIds = AssignRoute::whereIn('employee_id', function ($query) {
                    $query->select('id')
                        ->from('employees')
                        ->where('employee_type_id', 1); 
                })->pluck('id')->toArray();

            if (empty($assignedRouteIds)) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 404,
                    'message' => "No assigned routes found for Sales Executives.",
                    'data' => []
                ], 404);
            }
          
            if (!in_array($dealer->assigned_route_id, $assignedRouteIds)) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 403,
                    'message' => "Dealer is not in an assigned route of an SE.",
                    'data' => []
                ], 403);
            }

            $salesData = Order::where('created_by_dealer', $dealer->id)
                ->where('status', 'Delivered')
                ->whereMonth('created_at', $month)
                ->whereYear('created_at', $year)
                ->selectRaw('SUM(invoice_quantity) as total_quantity, SUM(invoice_total) as total_transaction')
                ->first();

            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Monthly Sales Transaction Data',
                'data' => [
                    'month' => $month,
                    'year' => $year,
                    'total_quantity' => (float) ($salesData->total_quantity ?? 0),
                    'total_transaction' => (float) ($salesData->total_transaction ?? 0),
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function outstandingPaymentsList()
    {
        try {
            $dealer = Auth::user(); 
            
            if (!$dealer) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 401,
                    'message' => "User not Authenticated",
                ], 401);
            }
            if (!$dealer->id) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 400,
                    'message' => "Dealer ID not found",
                    'data' => []
                ], 400);
            }

            $outstandingPayments = OutstandingPayment::where('dealer_id', $dealer->id)
                ->where('status', 'open')
                ->select('id','order_id', 'due_date', 'outstanding_amount')
                ->orderBy('due_date', 'asc')
                ->get()
                ->map(function ($payment) {
                    return [
                        'id' => $payment->id,
                        'order_id' => $payment->order_id,
                        'due_date' => $payment->due_date ? \Carbon\Carbon::parse($payment->due_date)->format('d/m/Y') : null,
                        'outstanding_amount' => $payment->outstanding_amount,
                    ];
                });
             

            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Outstanding Payments List',
                'data' => $outstandingPayments,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    // public function opDetails($orderId)
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
    //             'dealers:id,dealer_name,dealer_code',
    //             'orderItems.product:id,product_name',
    //             'orderItems',
    //             'paymentTerm:id,name',
    //             'vehicleCategory:id,vehicle_category_name'
    //         ])->findOrFail($orderId);

    //         $order->billing_date = $order->billing_date ? Carbon::parse($order->billing_date)->format('d-m-Y') : null;
    //         $order->created_at = Carbon::parse($order->created_at)->format('d-m-Y');
    //         $order->updated_at = Carbon::parse($order->updated_at)->format('d-m-Y');

    //         $outstandingPayments = OutstandingPayment::where('order_id', $orderId)
    //             ->where('status', 'open')
    //             ->select(
    //                 'id',
    //                 'order_id',
    //                 'invoice_number',
    //                 'invoice_date',
    //                 'invoice_total',
    //                 'due_date',
    //                 'paid_amount',
    //                 'outstanding_amount',
    //                 'payment_doc_number',
    //                 'payment_date',
    //                 'payment_amount_applied',
    //                 'status'
    //             )
    //             ->orderBy('due_date', 'asc')
    //             ->get();

    //         $outstandingPayments->each(function ($payment) {
    //             $payment->commitments = OutstandingPaymentCommitment::where('outstanding_payment_id', $payment->id)
    //                 ->select('id', 'committed_date', 'committed_amount')
    //                 ->orderBy('committed_date', 'asc')
    //                 ->get();
    //         });

    //         // Response data
    //         $responseData = [
    //             'id' => $order->id,
    //             'order_type' => $order->orderType->name ?? null,
    //             'dealer' => [
    //                 'id' => $order->dealers->id ?? null,
    //                 'name' => $order->dealers->dealer_name ?? null,
    //                 'code' => $order->dealers->dealer_code ?? null,
    //             ],
    //             'payment_terms' => [
    //                 'id' => $order->paymentTerm->id ?? null,
    //                 'name' => $order->paymentTerm->name ?? null,
    //             ],
    //             'billing_date' => $order->billing_date,
    //             'total_amount' => round($order->total_amount, 2),
    //             'additional_information' => $order->additional_information,
    //             'status' => $order->status,
    //             'created_by_dealer' => $order->created_by_dealer,
    //             'dealer_flag_order' => $order->dealer_flag_order,
    //             'vehicle' => [
    //                 'category_id' => $order->vehicle_category_id,
    //                 'category_name' => $order->vehicleCategory->vehicle_category_name ?? null,
    //                 'vehicle_number' => $order->vehicle_number,
    //                 'driver_name' => $order->driver_name,
    //                 'driver_phone' => $order->driver_phone,
    //             ],
    //             'attachments' => $order->attachment ?? [],

    //             'order_items' => $order->orderItems->map(function ($item) {
    //                 return [
    //                     'product_id' => $item->product_id,
    //                     'product_name' => $item->product->product_name ?? null,
    //                     'total_quantity' => $item->total_quantity,
    //                     'product_details' => $item->product_details ?? [],
    //                 ];
    //             }),

    //             'outstanding_payments' => $outstandingPayments->map(function ($payment) {
    //                 return [
    //                     'id' => $payment->id,
    //                     'invoice_number' => $payment->invoice_number,
    //                     'invoice_date' => $payment->invoice_date ? Carbon::parse($payment->invoice_date)->format('d-m-Y') : null,
    //                     'invoice_total' => $payment->invoice_total,
    //                     'due_date' => $payment->due_date ? Carbon::parse($payment->due_date)->format('d-m-Y') : null,
    //                     'paid_amount' => $payment->paid_amount,
    //                     'outstanding_amount' => $payment->outstanding_amount,
    //                     'payment_doc_number' => $payment->payment_doc_number,
    //                     'payment_date' => $payment->payment_date ? Carbon::parse($payment->payment_date)->format('d-m-Y') : null,
    //                     'payment_amount_applied' => $payment->payment_amount_applied,
    //                     'status' => $payment->status,
    //                     'commitments' => $payment->commitments->map(function ($commitment) {
    //                         return [
    //                             'id' => $commitment->id,
    //                             'committed_date' => $commitment->committed_date ? Carbon::parse($commitment->committed_date)->format('d-m-Y') : null,
    //                             'committed_amount' => $commitment->committed_amount,
    //                         ];
    //                     }),
    //                 ];
    //             }),

    //             'created_at' => $order->created_at,
    //             'updated_at' => $order->updated_at,
    //         ];

    //         return response()->json([
    //             'success' => true,
    //             'statusCode' => 200,
    //             'message' => 'Order details fetched successfully',
    //             'data' => $responseData,
    //         ], 200);

    //     } catch (Exception $e) {
    //         return response()->json([
    //             'success' => false,
    //             'statusCode' => 500,
    //             'message' => $e->getMessage(),
    //         ], 500);
    //     }
    // }
    public function opDetails($outstandingPaymentId)
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

            $outstandingPayment = OutstandingPayment::with('order')->findOrFail($outstandingPaymentId);

            if (!$outstandingPayment->order) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 404,
                    'message' => 'Order not found for the given Outstanding Payment ID.'
                ], 404);
            }
            if ($outstandingPayment->dealer_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 403,
                    'message' => 'Unauthorized: You do not have permission to view this order.'
                ], 403);
            }

            $order = Order::with([
                'orderType:id,name',
                'dealers:id,dealer_name,dealer_code',
                'orderItems.product:id,product_name',
                'orderItems',
                'paymentTerm:id,name',
                'vehicleCategory:id,vehicle_category_name'
            ])->findOrFail($outstandingPayment->order_id);

            $order->billing_date = $order->billing_date ? Carbon::parse($order->billing_date)->format('d-m-Y') : null;
            $order->created_at = Carbon::parse($order->created_at)->format('d-m-Y');
            $order->updated_at = Carbon::parse($order->updated_at)->format('d-m-Y');

            $outstandingPayments = OutstandingPayment::where('order_id', $order->id)
                ->where('status', 'open')
                ->where('dealer_id', $user->id)
                ->select(
                    'id',
                    'order_id',
                    'invoice_number',
                    'invoice_date',
                    'invoice_total',
                    'due_date',
                    'paid_amount',
                    'outstanding_amount',
                    'payment_doc_number',
                    'payment_date',
                    'payment_amount_applied',
                    'status'
                )
                ->orderBy('due_date', 'asc')
                ->get();

            $outstandingPayments->each(function ($payment) {
                $payment->commitments = OutstandingPaymentCommitment::where('outstanding_payment_id', $payment->id)
                    ->select('id', 'committed_date', 'committed_amount')
                    ->orderBy('committed_date', 'asc')
                    ->get();
            });
            $payments = Payment::where('order_id', $order->id)
                ->where('dealer_id', $user->id) 
                ->select('payment_date', 'payment_amount', 'payment_document_no')
                ->orderBy('payment_date', 'asc')
                ->get();

            $totalPaidAmount = $payments->sum('payment_amount');

            $totalOutstandingAmount = $order->invoice_total - $totalPaidAmount;

            $responseData = [
                'id' => $order->id,
                'order_type' => $order->orderType->name ?? null,
                'payment_terms' => [
                    'id' => $order->paymentTerm->id ?? null,
                    'name' => $order->paymentTerm->name ?? null,
                ],
                'billing_date' => $order->billing_date ? Carbon::parse($order->billing_date)->format('d/m/Y') : null,
                'total_amount' => round($order->total_amount, 2),
              
                'attachments' => $order->attachment ?? [],

                'order_items' => $order->orderItems->map(function ($item) {

                    return [
                        'product_id' => $item->product_id,
                        'product_name' => $item->product->product_name ?? 'N/A',
                        'total_quantity' => $item->total_quantity,
                        'balance_quantity' => $item->balance_quantity,
                        'product_details' => collect($item->product_details)->map(function ($detail) {
                            return [
                                'product_type_id' => $detail['product_type_id'],
                                'quantity' => $detail['quantity'],
                                'rate' => $detail['rate'],
                                'product_type' => ProductType::where('id', $detail['product_type_id'])->value('type_name') ?? 'N/A',
                            ];
                        }),
                    ];
                }),

                'outstanding_payments' => $outstandingPayments->map(function ($payment) {
                    return [
                        'id' => $payment->id,
                        'invoice_number' => $payment->invoice_number,
                        'invoice_amount' => $payment->invoice_total,
                        'due_date' => $payment->due_date ? Carbon::parse($payment->due_date)->format('d/m/Y') : null,
                        'commitments' => $payment->commitments->map(function ($commitment) {
                            return [
                                'id' => $commitment->id,
                                'committed_date' => $commitment->committed_date ? Carbon::parse($commitment->committed_date)->format('d/m/Y') : null,
                                'committed_amount' => $commitment->committed_amount,
                            ];
                        }),
                    ];
                })->toArray(),
                'payment_summary' => [
                    // 'total_paid_amount' => round($totalPaidAmount, 2),
                    'total_outstanding_amount' => round($totalOutstandingAmount, 2),
                    'payments' => $payments->map(function ($payment) {
                        return [
                            'payment_date' => $payment->payment_date ? Carbon::parse($payment->payment_date)->format('d/m/Y') : null,
                            'payment_amount' => round($payment->payment_amount, 2),
                            'payment_document_no' => $payment->payment_document_no,
                        ];
                    }),
                ],

                'created_at' => $order->created_at? Carbon::parse($order->created_at)->format('d/m/Y') : null,
            ];

            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Order details fetched successfully',
                'data' => $responseData,
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function orderRequestList(Request $request)
    {
        try {
            $dealer = Auth::user();

            if (!$dealer) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 401,
                    'message' => "User not Authenticated",
                ], 401);
            }

            $assignedRouteIds = AssignRoute::whereIn('employee_id', function ($query) {
                    $query->select('id')
                        ->from('employees')
                        ->where('employee_type_id', 1); 
                })->pluck('id')->toArray();

            if (empty($assignedRouteIds)) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 404,
                    'message' => "No assigned routes found for Sales Executives.",
                    'data' => []
                ], 404);
            }

            if (!in_array($dealer->assigned_route_id, $assignedRouteIds)) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 403,
                    'message' => "Dealer is not in an assigned route of an SE.",
                    'data' => []
                ], 403);
            }

            $salesExecutives = AssignRoute::where('id', $dealer->assigned_route_id)
                ->pluck('employee_id');
            if ($salesExecutives->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 404,
                    'message' => "No Sales Executives found for this dealer's assigned route.",
                    'data' => []
                ], 404);
            }

            $orders = Order::whereIn('created_by', $salesExecutives)
                ->where('dealer_id',$dealer->id)
                ->select('id', 'total_amount', 'status', 'created_at')
                ->orderBy('id', 'desc')
                ->get();
            $formattedOrders = $orders->map(function ($order) {
                return [
                    'id' => $order->id,
                    'created_at' => $order->created_at->format('d/m/Y'),
                    'total_amount' => round($order->total_amount, 2),
                    'status' => $order->status === 'Pending' ? 'Order Received' :
                                ($order->status === 'Accepted' ? 'Order Accepted' :
                                ($order->status === 'Rejected' ? 'Order Rejected' : ucfirst($order->status))),
                ];
            });

            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Order Request List fetched successfully',
                'data' => $formattedOrders,
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    public function orderRequestDetails($orderId)
    {
        try {
            $dealer = Auth::user();

            if (!$dealer) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 401,
                    'message' => "User not authenticated",
                ], 401);
            }

            // Fetch the order with required relationships
            $order = Order::with([
                'createdBy:id,name,employee_code',
                'orderType:id,name',
                'customerType:id,name',
                'lead.customerType:id,name',
                'lead:id,customer_name,phone,address,construction_type,stage_of_construction',
                'paymentTerm:id,name',
                'orderItems.product.productTypes:id,product_id,type_name',
                'orderItems.product:id,product_name',
                'dealers:id,dealer_name,dealer_code',
                'vehicleCategory:id,vehicle_category_name',
            ])
            ->where('dealer_id', $dealer->id) // Ensure order belongs to the logged-in dealer
            ->find($orderId);

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 404,
                    'message' => 'Order not found or unauthorized access',
                ], 404);
            }

            // Format response data
            $orderDetails = [
                'id' => $order->id,
                'order_placed_by' => [
                    'name' => $order->createdBy->name ?? ' ',
                    'employee_code' => $order->createdBy->employee_code ?? ' ',
                    'designation' => 'Sales Executive', // Fixed designation
                ],
                'order_date' => $order->created_at->format('d/m/Y'),
                'order_type' => $order->orderType->name ?? ' ',


                'customer_details' => [
                    'customer_type' => $order->customerType->name ?? ($order->lead->customerType->name ?? ' '),
                    'customer_name' => $order->lead->customer_name ?? ' ',
                    'phone' => $order->lead->phone ?? ' ',
                    'address' => $order->lead->address ?? ' ',
                    'construction_type' => $order->lead->construction_type ?? ' ',
                    'stage_of_construction' => $order->lead->stage_of_construction ?? ' ',
                ],

                'billing_date' => $order->billing_date ? Carbon::parse($order->billing_date)->format('d/m/Y') : ' ',
                'payment_terms' => $order->paymentTerm->name ?? ' ',
                'additional_information' => $order->additional_information ?? ' ',
                'status' => $order->status,

                

                'attachments' => $order->attachment ?? [],

                // Order Items
                'order_items' => $order->orderItems->map(function ($item) {

                    return [
                        'product_id' => $item->product_id,
                        'product_name' => $item->product->product_name ?? 'N/A',
                        'total_quantity' => $item->total_quantity,
                        'balance_quantity' => $item->balance_quantity,
                        'product_details' => collect($item->product_details)->map(function ($detail) {
                            return [
                                'product_type_id' => $detail['product_type_id'],
                                'quantity' => $detail['quantity'],
                                'rate' => $detail['rate'],
                                'product_type' => ProductType::where('id', $detail['product_type_id'])->value('type_name') ?? 'N/A',
                            ];
                        }),
                    ];
                }),

                'created_at' => $order->created_at->format('d/m/Y'),
            ];

            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Order details retrieved successfully',
                'data' => $orderDetails,
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => $e->getMessage(),
            ], 500);
        }
    }



    public function orderRequestStatusUpdate(Request $request, $orderId)
    {
        try {
            $dealer = Auth::user();

            if (!$dealer) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 401,
                    'message' => "User not Authenticated",
                ], 401);
            }

            $validatedData = $request->validate([
                'status' => 'required|in:Accepted,Rejected',
                'reason_for_rejection' => 'required_if:status,Rejected|nullable|string|max:255',
            ]);

            $order = Order::find($orderId);

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 404,
                    'message' => "Order not found",
                ], 404);
            }

            $salesExecutives = AssignRoute::where('id', $dealer->assigned_route_id)->pluck('employee_id');

            if (!$salesExecutives->contains($order->created_by)) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 403,
                    'message' => "You do not have permission to update this order's status.",
                ], 403);
            }

            // Update order status
            $order->status = $validatedData['status'];
            if ($validatedData['status'] === 'Rejected') {
                $order->reason_for_rejection = $validatedData['reason_for_rejection'];
            } else {
                $order->reason_for_rejection = null;
            }
            $order->save();

            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => "Order status updated successfully",
                'data' => [
                    'id' => $order->id,
                    'status' => $order->status,
                    'reason_for_rejection' => $order->reason_for_rejection,
                ],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    public function getSupport(Request $request)
    {
        try {
            $dealer = Auth::user();

            if (!$dealer) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 401,
                    'message' => "User not Authenticated",
                ], 401);
            }

            $seAssignedRoute = AssignRoute::where('id', $dealer->assigned_route_id)->first();

            if (!$seAssignedRoute) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 404,
                    'message' => "Assigned route not found for this dealer.",
                    'data' => []
                ], 404);
            }

            if (!$seAssignedRoute->parent_id) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 404,
                    'message' => "No ASO assigned for this dealer's route.",
                    'data' => []
                ], 404);
            }

            $aso = Employee::where('id', $seAssignedRoute->parent_id)
                ->where('employee_type_id', 2) 
                ->select('id', 'name', 'phone')
                ->first();

            if (!$aso) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 404,
                    'message' => "No ASO found for this dealer's assigned route.",
                    'data' => []
                ], 404);
            }

            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => "Support ASO fetched successfully",
                'data' => [
                    'aso_id' => $aso->id,
                    'name' => $aso->name,
                    'phone' => $aso->phone,
                ],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function paymentHistoryList(Request $request)
    {
        $dealer = Auth::user();

        if (!$dealer) {
            return response()->json([
                'success' => false,
                'statusCode' => 401,
                'message' => "User not Authenticated",
            ], 401);
        }
        $dealerId = $dealer->id;

        $payments = Payment::where('dealer_id', $dealerId)
            ->whereHas('order.paymentTerm', function ($query) {
                $query->whereIn('payment_terms_id', [1, 2]); 
            })
            ->with(['order.paymentTerm'])
            ->orderBy('payment_date', 'desc')
            ->get()
            ->map(function ($payment) {
                return [
                    'order_id'       => $payment->order->id ?? null, 
                    'payment_date'   => $payment->payment_date->format('d/m/y'),
                    'payment_total'  => $payment->payment_amount,
                    'payment_terms'  => $payment->order->paymentTerm->name ?? 'N/A',
                ];
            });

        return response()->json([
            'success'  => true,
            'statusCode' => 200,
            'message' => 'Payment history retrieved successfully',
            'data'    => $payments,
        ], 200);
    }

    public function paymentHistoryOrderDetails($orderId)
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
                'dealers:id,dealer_name,dealer_code',
                'orderItems.product:id,product_name',
                'orderItems',
                'paymentTerm:id,name',
                'vehicleCategory:id,vehicle_category_name'
            ])->findOrFail($orderId);

            $invoiceNumber = $order->invoice_number;
            $invoiceTotal = round($order->invoice_total, 2);
    
            $totalQuantity = $order->orderItems->sum('total_quantity');
    
            $paidAmount = Payment::where('order_id', $orderId)->sum('payment_amount');
    
            $outstandingPayment = $invoiceTotal - $paidAmount;
            // $paidAmount = Payment::where('order_id', $orderId)->sum('payment_amount');
    
            // $outstandingPayment = OutstandingPayment::where('order_id', $orderId)
            //     ->select('invoice_number', 'invoice_total', 'due_date', 'paid_amount', 'outstanding_amount')
            //     ->first();
    
            $trackingStatus = [
                'pending_time' => $order->created_at ? Carbon::parse($order->created_at)->format('d/m/Y H:i:s') : null,
                'accepted_time' => $order->accepted_time ? Carbon::parse($order->accepted_time)->format('d/m/Y H:i:s') : null,
                'rejected_time' => $order->rejected_time ? Carbon::parse($order->rejected_time)->format('d/m/Y H:i:s') : null,
                'dispatched_time' => $order->dispatched_time ? Carbon::parse($order->dispatched_time)->format('d/m/Y H:i:s') : null,
                'intransit_time' => $order->intransit_time ? Carbon::parse($order->intransit_time)->format('d/m/Y H:i:s') : null,
                'delivered_time' => $order->delivered_time ? Carbon::parse($order->delivered_time)->format('d/m/Y H:i:s') : null,
            ];
    
            $responseData = [
                'id' => $order->id,
                'order_type' => $order->orderType->name ?? null,
               'invoice_number' => $invoiceNumber,
                'invoice_total' => $invoiceTotal,
                'total_quantity' => $totalQuantity,
                'paid_amount' => round($paidAmount, 2),
                'outstanding_payment' => round($outstandingPayment, 2),
                'payment_terms' => [
                    'id' => $order->paymentTerm->id ?? null,
                    'name' => $order->paymentTerm->name ?? null,
                ],
                'billing_date' => $order->billing_date ? Carbon::parse($order->billing_date)->format('d/m/Y') : null,
                
                'vehicle' => [
                    'category_id' => $order->vehicle_category_id,
                    'category_name' => $order->vehicleCategory->vehicle_category_name ?? null,
                    'vehicle_number' => $order->vehicle_number,
                    'driver_name' => $order->driver_name,
                    'driver_phone' => $order->driver_phone,
                ],
                'attachments' => $order->attachment ?? [],
                'tracking_status' => $trackingStatus,
                'order_items' => $order->orderItems->map(function ($item) {

                    return [
                        'product_id' => $item->product_id,
                        'product_name' => $item->product->product_name ?? 'N/A',
                        'total_quantity' => $item->total_quantity,
                        'balance_quantity' => $item->balance_quantity,
                        'product_details' => collect($item->product_details)->map(function ($detail) {
                            return [
                                'product_type_id' => $detail['product_type_id'],
                                'quantity' => $detail['quantity'],
                                'rate' => $detail['rate'],
                                'product_type' => ProductType::where('id', $detail['product_type_id'])->value('type_name') ?? 'N/A',
                            ];
                        }),
                    ];
                }),
                'created_at' => Carbon::parse($order->created_at)->format('d/m/Y'),
            ];
    
            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Order details fetched successfully',
                'data' => $responseData,
            ], 200);
    
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    public function creditNoteList(Request $request)
    {
        $dealer = Auth::user();
    
        if (!$dealer) {
            return response()->json([
                'success' => false,
                'statusCode' => 400,
                'message' => 'You must be logged in to view this order.'
            ], 400);
        }
        $creditNotes = CreditNote::where('dealer_id', $dealer->id)
        ->select('order_id', 'credit_note_number', 'invoice_number', 'date', 'total_row_amount')
        ->orderBy('date', 'desc')
        ->get()
        ->map(function ($creditNote) {
            return [
                'order_id' => $creditNote->order_id,
                'credit_note_number' => $creditNote->credit_note_number,
                'invoice_number' => $creditNote->invoice_number,
                'date' => $creditNote->date->format('d/m/Y'),
                'total_row_amount' => $creditNote->total_row_amount,
            ];
        });

        return response()->json([
            'success' => true,
            'statusCode' => 200,
            'message' => 'Credit Notes retrieved successfully',
            'data' => $creditNotes
        ], 200);
    }
    public function creditNoteDetails($order_id)
    {
        $dealer = Auth::user();

        if (!$dealer) {
            return response()->json([
                'success' => false,
                'statusCode' => 401,
                'message' => 'You must be logged in to view this credit note.',
            ], 401);
        }

        $creditNote = CreditNote::where('order_id', $order_id)
            ->where('dealer_id', $dealer->id)
            ->first();

        if (!$creditNote) {
            return response()->json([
                'success' => false,
                'statusCode' => 404,
                'message' => 'Credit Note not found for this order or unauthorized access.',
                'data' => null,
            ], 404);
        }

        $order = Order::where('id', $order_id)
            ->where('dealer_id', $dealer->id) 
            ->select('order_type', 'payment_terms_id', 'billing_date', 'invoice_number')
            ->with('orderType:id,name', 'paymentTerm:id,name')
            ->first();

        if (!$order) {
            return response()->json([
                'success' => false,
                'statusCode' => 404,
                'message' => 'Order details not found or unauthorized access.',
            ], 404);
        }

        $response = [
            'order_id' => $creditNote->order_id,
            'order_type' => $order->orderType->name ?? 'N/A',
            'payment_type' => $order->paymentTerm->name ?? 'N/A',
            'credit_note_date' => $creditNote->date->format('d/m/Y'), 
            'credit_note_number' => $creditNote->credit_note_number ?? 'N/A',
            'billing_date' => $order->billing_date ? $order->billing_date->format('d/m/Y') : 'N/A',
            'invoice_number' => $order->invoice_number,
            'return_products' => collect($creditNote->returned_items)->map(function ($item) {
                return [
                    'rate' => $item['rate'],
                    'quantity' => $item['quantity'],
                    'product_type_id' => $item['product_type_id'],
                    'product_type' => ProductType::where('id', $item['product_type_id'])->value('type_name') ?? 'N/A',
                ];
            }),
            'total_return_quantity' => $creditNote->total_return_quantity,
            'total_amount' => $creditNote->total_row_amount,
        ];

        return response()->json([
            'success' => true,
            'statusCode' => 200,
            'message' => 'Credit Note details retrieved successfully',
            'data' => $response,
        ], 200);
    }

    


   
}
