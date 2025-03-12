<?php

namespace App\Http\Controllers;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Dealer;
use App\Models\ProductType;
use App\Models\OutstandingPayment;
use Illuminate\Support\Facades\Auth;

class AccountsController extends Controller
{
    public function index()
    {
        return view('accounts.order-request.index');
    }
    
    public function orderList(Request $request)
    {
       
        $orders = Order::with(['dealer', 'dealers', 'createdBy.employeeType'])
        ->where(function ($query) {
            $query->where('dealer_flag_order', '1')
                ->where(function ($subQuery) {
                    $subQuery->where('send_for_approval', '1')
                        ->orWhereNull('send_for_approval');
                })
                ->where(function ($subQuery) {
                    $subQuery->where('order_approved', '!=', '0') 
                        ->orWhere('status', '!=', 'Rejected')
                        ->orWhereIn('order_approved_by', function ($subQuery) {
                            $subQuery->select('id')
                                ->from('users')
                                ->where('role_id', 2); 
                        });
                });
        })
        ->orWhere(function ($query) {
            $query->where('dealer_flag_order', '0')
                ->where(function ($subQuery) {
                    $subQuery->where('order_approved', '!=', '0') 
                        ->orWhere('status', '!=', 'Rejected')
                        ->orWhereIn('order_approved_by', function ($subQuery) {
                            $subQuery->select('id')
                                ->from('users')
                                ->where('role_id', 2); 
                        });
                });
        })
        ->orderBy('id', 'desc')
        ->get();
    

           
        return DataTables::of($orders)
            ->addIndexColumn()
            ->addColumn('date', function ($order) {
                return $order->created_at->format('d/m/Y');
            })
            ->addColumn('order_id', function ($order) {
                return 'OD00' . $order->id;
            })
            ->addColumn('dealer_name', function ($order) {
                if ($order->created_by_dealer) {
                    return $order->dealers?->dealer_name ?? 'N/A';
                }
                return $order->dealer?->dealer_name ?? 'N/A';
            })
            ->addColumn('dealer_code', function ($order) {
                if ($order->created_by_dealer) {
                    return $order->dealers?->dealer_code ?? 'N/A';
                }
                return $order->dealer?->dealer_code ?? 'N/A';
            })
            ->addColumn('employee_type', function ($order) {
                if ($order->dealer_flag_order == 1) {
                    return '-';
                } elseif ($order->createdBy?->employeeType) {
                    return $order->createdBy->employeeType->name;
                }
                return 'N/A';
            })
            ->addColumn('employee_name_code', function ($order) {
                if ($order->dealer_flag_order == 1) {
                    return $order->dealer?->dealer_name ?? '-';
                } elseif ($order->createdBy) {
                    return $order->createdBy->name . ' - ' . $order->createdBy->employee_code;
                }
                return 'N/A';
            })
            ->addColumn('amount', function ($order) {
                return number_format($order->total_amount, 2);
            })
            ->addColumn('status', function ($order) {
                if ($order->order_approved == 1) {
                    return '<span class="badge bg-success">Approved</span>';
                } elseif ($order->order_approved == 2) {
                    return '<span class="badge bg-danger">Rejected</span>';
                }
                return '<span class="badge bg-warning">Pending</span>';
            })
            ->addColumn('action', function ($order) {
                // $viewUrl = route('accounts.orders.index', $order->id);
                // $isDisabled = ($order->order_approved == 1 || $order->order_approved == 2) ? 'disabled' : '';

                return '<button class="btn btn-info btn-sm view-order" data-id="' . $order->id . '" title="View">
                            <i class="fa fa-eye"></i>
                        </button>';
            })
            ->rawColumns(['status','action'])
            ->make(true);
    }
    public function approveOrder(Request $request, $id)
    {
        $order = Order::findOrFail($id);
    
        if ($order->order_approved != 0) {
            return response()->json(['success' => false, 'message' => 'This order has already been processed.'], 400);
        }
    
        $request->validate([
            'payment_term' => 'required|string|max:255',
            'remarks' => 'nullable|string|max:500',
        ]);
    
        $order->order_approved = '1'; 
        $order->order_approved_by = Auth::id();
        $order->order_payment_terms = $request->payment_term;
        $order->order_remarks = $request->remarks;
        $order->save();
    
        return response()->json(['success' => true, 'message' => 'Order approved successfully']);
    }
    

    public function rejectOrder(Request $request, $id)
    {
        $order = Order::findOrFail($id);
        // $order->status = 'Rejected';
        $order->order_approved = '2'; 
        $order->order_approved_by = Auth::id();
        $order->reason_for_rejection = $request->remarks;
        $order->rejected_time = now();
        $order->save();

        return response()->json(['success' => true, 'message' => 'Order rejected successfully']);
    }

    public function viewOrder($id)
    {
        $order = Order::with(['dealer', 'createdBy.employeeType', 'orderItems','orderType','paymentTerm'])
            ->where('id', $id)
            ->first();

        if (!$order) {
            return response()->json(['success' => false, 'message' => 'Order not found!'], 404);
        }
        if ($order->dealer_flag_order == 1) {
            $dealerId = $order->created_by_dealer;
        } else {
            $dealerId = $order->dealer_id;
        }
        $totalOutstanding = OutstandingPayment::where('dealer_id', $dealerId)
            ->where('order_id',$order->id)
            ->first();
        if ($order->dealer_flag_order == 1) {
            $dealer = Dealer::where('id', $order->created_by_dealer)->first();
    
            $employeeType = '-';
            $employeeNameCode = '-';
            $dealerName = $dealer?->dealer_name ?? 'N/A';
            $dealerCode = $dealer?->dealer_code ?? 'N/A';
            $dealerPhone = $dealer?->phone ?? 'N/A';
            $dealerAddress = $dealer?->address ?? 'N/A';
        } else {
            $employeeType = $order->createdBy?->employeeType?->type_name ?? 'N/A';
            $employeeNameCode = $order->createdBy ? ($order->createdBy->name . ' - ' . $order->createdBy->employee_code) : 'N/A';
            $dealerName = $order->dealer?->dealer_name ?? 'N/A';
            $dealerCode = $order->dealer?->dealer_code ?? 'N/A';
            $dealerPhone = $order->dealer?->phone ?? 'N/A';
            $dealerAddress = $order->dealer?->address ?? 'N/A';
        }
        $orderItems = $order->orderItems->map(function ($item) {
            $product = $item->product;
        
            $productDetails = collect($item->product_details)->map(function ($detail) use ($product) {
                if (!isset($detail['product_type_id'])) {
                    return [
                        'product_name' => 'TATA TISCON' ?? 'N/A', // Use product relationship
                        'type_name' => 'N/A', 
                        'quantity' => (int) ($detail['quantity'] ?? 0),
                        'rate' => $detail['rate'] ?? 0
                    ];
                }
        
                $productType = ProductType::find($detail['product_type_id']);
        
                return [
                    'product_name' => 'TATA TISCON' ?? 'N/A',
                    'type_name' => $productType?->type_name ?? 'N/A',
                    'quantity' => (int) ($detail['quantity'] ?? 0),
                    'rate' => $detail['rate'] ?? 0
                ];
            });
        
            return $productDetails;
        })->flatten(1);
        

        $orderData = [
            'order_id' => 'OD00' . $order->id,
            'date' => $order->created_at->format('d/m/Y'),
            'employee_type' => $employeeType,
            'employee_name_code' => $employeeNameCode,
            'dealer_name' => $dealerName,
            'dealer_code' => $dealerCode,
            'dealer_phone' => $dealerPhone,
            'dealer_address' => $dealerAddress,
            'order_type' => $order->orderType?->name ?? 'N/A', 
            'payment_type' => $order->paymentTerm?->name ?? 'N/A',
            'billing_date' => optional($order->billing_date)->format('d/m/Y') ?? 'N/A',
            'status_badge' => $order->status,
            'reason_for_rejection' => $order->reason_for_rejection,
            'remarks' => $order->order_remarks,
            'order_approved' => $order->order_approved, 
            'payment_term' => $order->order_payment_terms, 
            'order_status' => $order->order_approved == 1 ? '<span class="badge bg-success">Approved</span>' : ($order->order_approved == 2 ? '<span class="badge bg-danger">Rejected</span>' : '<span class="badge bg-warning">Pending</span>'),
            'order_items' => $orderItems,
            'total_outstanding' => $totalOutstanding->outstanding_amount ?? '0.00'
        ];

        return response()->json(['success' => true, 'order' => $orderData]);
    }

    

}
