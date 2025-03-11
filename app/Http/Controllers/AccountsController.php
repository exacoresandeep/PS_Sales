<?php

namespace App\Http\Controllers;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Order;

class AccountsController extends Controller
{
    public function index()
    {
        return view('accounts.order-request.index');
    }
    
    public function orderList(Request $request)
    {
       
        $orders = Order::with(['dealer','dealers', 'createdBy.employeeType'])
                ->where(function ($query) {
                    $query->where('dealer_flag_order', '1')
                        ->where('status', '!=', 'Rejected')
                        ->where(function ($subQuery) {
                            $subQuery->where('send_for_approval', '1')
                                ->orWhereNull('send_for_approval');
                        });
                })
                ->orWhere(function ($query) {
                    $query->where('dealer_flag_order', '0')
                        ->where('status', '!=', 'Rejected');
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
                return $order->status;
            })
            ->addColumn('action', function ($order) {
                $viewUrl = route('accounts.orders.index', $order->id);
                $approveUrl = route('accounts.orders.approve', $order->id);
                $rejectUrl = route('accounts.orders.reject', $order->id);
                return '<a href="' . $viewUrl . '" class="btn btn-info" title="View">
                        <i class="fa fa-eye"></i>
                    </a>
                    <button class="btn btn-success btn-sm approve-order" data-id="' . $order->id . '" title="Approve">
                        Approve
                    </button>
                    <button class="btn btn-danger btn-sm reject-order" data-id="' . $order->id . '" title="Reject">
                        Reject
                    </button>';
            })
            ->rawColumns(['action'])
            ->make(true);
    }
    public function approveOrder($id)
    {
        $order = Order::findOrFail($id);
        $order->status = 'Approved';
        $order->save();

        return response()->json(['success' => true, 'message' => 'Order approved successfully']);
    }

    public function rejectOrder($id)
    {
        $order = Order::findOrFail($id);
        $order->status = 'Rejected';
        $order->save();

        return response()->json(['success' => true, 'message' => 'Order rejected successfully']);
    }

    

}
