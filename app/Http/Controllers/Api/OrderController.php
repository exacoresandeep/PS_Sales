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

            $orders = Order::where('created_by', $employee->id)
                           ->with([
                               'orderType:id,name',
                               'dealer:id,dealer_name,phone,email',
                               'orderItems.product:id,product_name',
                               'lead:id,customer_type,customer_name,email,phone,address,instructions,record_details,status',
                               'lead.customerType:id,name'  
                           ])->get();
                        
            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Orders fetched successfully',
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
        dd($request->all());
        try {
            $searchKey = $request->input('search_key');  
            
            $query = Order::query();

            // Filter by status (All, Pending, Completed)
            if (isset($searchKey['status']) && in_array($searchKey['status'], ['All', 'Pending', 'Completed'])) {
                if ($searchKey['status'] !== 'All') {
                    $query->where('status', $searchKey['status']);
                }
            }

            // Filter by date range (today, weekly, monthly, financial year, or choose_date)
            if (isset($searchKey['date_range'])) {
                $dateRange = $searchKey['date_range'];

                if ($dateRange === 'today') {
                    $query->whereDate('created_at', Carbon::today());
                } elseif ($dateRange === 'weekly') {
                    $query->whereBetween('created_at', [
                        Carbon::now()->startOfWeek(),
                        Carbon::now()->endOfWeek()
                    ]);
                } elseif ($dateRange === 'monthly') {
                    $query->whereMonth('created_at', Carbon::now()->month);
                } elseif ($dateRange === 'financial_year') {
                    $startOfYear = Carbon::now()->startOfYear()->subMonths(3);  // Assume financial year starts from April
                    $endOfYear = Carbon::now()->endOfYear()->addMonths(9);
                    $query->whereBetween('created_at', [$startOfYear, $endOfYear]);
                } elseif (isset($searchKey['choose_date'])) {
                    // If choose_date is provided, convert it to a date and filter
                    $chosenDate = Carbon::createFromFormat('d-m-Y', $searchKey['choose_date']);
                    $query->whereDate('created_at', $chosenDate);
                }
            }

            // Get the filtered orders
            $orders = $query->with([
                'orderType:id,name',
                'dealer:id,dealer_name,phone,email',
                'orderItems.product:id,product_name',
                'lead:id,customer_type,customer_name,email,phone,address,instructions,record_details,status',
                'lead.customerType:id,name'
            ])->get();

            // Return the response with the filtered orders
            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Filtered orders fetched successfully',
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