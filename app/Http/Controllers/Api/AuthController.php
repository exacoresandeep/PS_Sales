<?php

namespace App\Http\Controllers\Api;

use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\CustomerType;
use App\Models\Order;
use App\Models\OrderType;
use App\Models\Dealer;
use App\Models\Product;
use App\Models\AssignRoute;
use App\Models\ProductType;
use App\Models\PaymentTerms;
use App\Models\ProductDetails;
use App\Models\LeaveType;
use App\Models\VehicleCategory;
use App\Models\VehicleType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;


class AuthController extends Controller
{
    public function login(Request $request)
    {
        try {
            $validated = $request->validate([
                'employee_code' => 'required|string',
                'password' => 'required|string',
                'employee_type_id' => 'required|integer',
            ]);

            $employee = Employee::join('employee_types', 'employees.id', '=', 'employee_types.id')
            ->where('employee_code', $validated['employee_code'])
            ->where('employees.employee_type_id', $validated['employee_type_id'])
            ->select('employees.*', 'employee_types.id as type_id', 'employee_types.type_name') 
            ->first();

        if (!$employee || !Hash::check($validated['password'], $employee->password)) {
            return response()->json([
                'success' => false,
                'statusCode' => 400,
                'message' => 'Invalid credentials',
            ], 400);
        }

        $token = $employee->createToken('API Token')->plainTextToken;


        return response()->json([
            'success' => true,
            'statusCode' => 200,
            'message' => 'Login successful',
            'data' => [
                'employee' => [
                    'id' => $employee->id,
                    'employee_code' => $employee->employee_code,
                    'name' => $employee->name,
                    'designation' => $employee->designation,
                    'email' => $employee->email,
                    'phone' => $employee->phone,
                    'address' => $employee->address,
                    'photo' => $employee->photo,
                    'emergency_contact' => $employee->emergency_contact,

                ],
                'employee_type' => [
                    'id' => $employee->type_id,
                    'type_name' => $employee->type_name,
                ],
                'token' => $token,
                'status' => 'active',
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
    public function logout(Request $request)
    {
        try {
            $request->user()->tokens->each(function ($token) {
                $token->delete(); 
            });

            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Logout successful',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function getCustomerTypes()
    {
        try {
            $user = Auth::user();
            if ($user !== null) {
                $data = CustomerType::select('id as customer_type_id', 'name as customer_type_name')->get();
            } else {
                $data = [];
            }

            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Customer types fetched successfully',
                'data' => $data,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function getOrderTypes()
    {
        try {
            $user = Auth::user();
            if ($user !== null) {
                $data = OrderType::select('id as order_type_id', 'name as order_type_name')->get();
            } else {
                $data = [];
            }
            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Order types fetched successfully',
                'data' => $data,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    // Fetch Dealers
    // public function getDealers(Request $request)
    // {
    //     try {
            
    //         $user = Auth::user();
    //         if ($user !== null) {
    //             $query = Dealer::select(
    //                 'id as dealer_id',
    //                 'dealer_code',
    //                 'dealer_name',
    //                 'phone',
    //                 'email',
    //                 'address',
    //                 'user_zone',
    //                 'pincode',
    //                 'state',
    //                 'district',
    //                 'taluk'
    //             )->where('approver_id',$user->id);

                
    //             if ($request->has('search_key') && !empty($request->search_key)) {
    //                 $searchKey = $request->search_key;
    
    //                 $query->where(function ($q) use ($searchKey) {
    //                     $q->where('dealer_code', 'like', '%' . $searchKey . '%')
    //                       ->orWhere('dealer_name', 'like', '%' . $searchKey . '%');
    //                 });
    //             }
    
    //             $data = $query->orderBy('dealer_name', 'asc')->get();
    //         } else {
    //             $data = [];
    //         }

    //         return response()->json([
    //             'success' => true,
    //             'statusCode' => 200,
    //             'message' => 'Dealers fetched successfully',
    //             'data' => $data,
    //         ], 200);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'success' => false,
    //             'statusCode' => 500,
    //             'message' => $e->getMessage(),
    //         ], 500);
    //     }
    // }
    public function getDealers(Request $request)
    {
        try {
            // Get the logged-in employee
            $user = Auth::user();
    
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 401,
                    'message' => "User not authenticated.",
                ], 401);
            }
    
            // Get assigned route IDs for the current user
            $assignedRouteIds = AssignRoute::where('employee_id', $user->id)->pluck('id')->toArray();
    
            if (empty($assignedRouteIds)) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 404,
                    'message' => 'No assigned routes found for this user.',
                    'data' => [],
                ], 404);
            }
    
            // Query dealers based on assigned routes
            $query = Dealer::select(
                'id as dealer_id',
                'dealer_code',
                'dealer_name',
                'phone',
                'email',
                'address',
                'user_zone',
                'pincode',
                'state',
                'district',
                'taluk'
            )->whereIn('assigned_route_id', $assignedRouteIds); // Check dealers in assigned routes
    
            // Apply search filter if provided
            if ($request->has('search_key') && !empty($request->search_key)) {
                $searchKey = $request->search_key;
    
                $query->where(function ($q) use ($searchKey) {
                    $q->where('dealer_code', 'like', '%' . $searchKey . '%')
                      ->orWhere('dealer_name', 'like', '%' . $searchKey . '%');
                });
            }
    
            // Fetch dealers
            $data = $query->orderBy('dealer_name', 'asc')->get();
    
            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Dealers fetched successfully.',
                'data' => $data,
            ], 200);
    
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    
    // Fetch Products
    public function getProducts()
    {
        try {
            $user = Auth::user();
            if ($user !== null) {
                $data = Product::select('id as product_id', 'product_name')->get();
            } else {
                $data = [];
            }

            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Products fetched successfully',
                'data' => $data,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    public function getProductTypes(Request $request)
    {
        try {
            $user = Auth::user();
            $productId = $request->input('product_id');
        
            if ($user !== null) {
                if ($productId == 0) {
                    return response()->json([
                        'success' => false,
                        'statusCode' => 400,
                        'message' => 'Invalid product_id provided.',
                        'data' => []
                    ], 400);
                }
                $query = ProductType::select('product_id', 'id as product_type_id', 'type_name', 'rate');

                if ($productId) {
                    $query->where('product_id', $productId);
                }
                
                $data = $query->get();

                if ($data->isEmpty()) {
                    return response()->json([
                        'success' => false,
                        'statusCode' => 404,
                        'message' => 'No product types found for the given product_id.',
                    ], 404);
                }

            } else {
                $data = [];
            }

            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Product types fetched successfully',
                'data' => $data,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function getProductRate(Request $request)
    {
        try {

            $user = Auth::user();

            if ($user !== null) {
                $validated = $request->validate([
                    'product_type_id' => 'required|exists:product_types,id',
                    'product_id' => 'required|exists:products,id',
                ]);
    
                $data = DB::table('products_details')
                ->join('product_types', 'products_details.type_id', '=', 'product_types.id')
                ->select('products_details.rate', 'product_types.type_name')
                ->where('products_details.product_id', $validated['product_id']) 
                ->where('products_details.type_id', $validated['product_type_id'])
                ->first();

                if (!$data) {
                    return response()->json([
                        'success' => false,
                        'statusCode' => 400,
                        'message' => 'Product rate not found',
                    ], 400);
                }
                
            } else {
                $data = [];
            }

            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Rate fetched successfully',
                'data' => $data,
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    public function getLeaveTypes()
    {
        try {
            $user = Auth::user();

            if ($user !== null) {
                $data = LeaveType::select('id as leave_type_id', 'name as leave_type', 'status as leave_type_status')->get();
            } else {
                $data = [];
            }
            
            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Leave types fetched successfully',
                'data' => $data,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    public function getPaymentTerms()
    {
        try {
            $user = Auth::user();

            if ($user !== null) {
                $data = PaymentTerms::select('id as payment_terms_id', 'name as payment_terms_name')->get();
            } else {
                $data = [];
            }
            
            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Payment terms fetched successfully',
                'data' => $data,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function fileUpload(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|array',
            'file.*' => 'required|file|mimes:jpg,jpeg,png,pdf|max:2048', 
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'statusCode' => 422,
                'message' => $validator->errors(),
                'data' => [],
            ], 422);
        }
    
        $fileUrls = [];
        foreach ($request->file('file') as $file) {
            // $path = $file->store('uploads', 'public');  
            // $fileUrls[] = asset('storage/' . $path);
            $fileName = $file->hashName();

            $file->storeAs('uploads', $fileName, 'public');  

            $fileUrls[] = url('storage/uploads/' . $fileName);
        }
        // $fileUrls = [];
        // foreach ($request->file('file') as $file) {
        //     $fileName = time() . '_' . $file->getClientOriginalName();

        //     $file->move(public_path('uploads'), $fileName);

        //     $fileUrls[] = url('uploads/' . $fileName);
        // }
    
        return response()->json([
            'success' => true,
            'statusCode' => 200,
            'message' => 'Files uploaded successfully.',
            'data' => ['filePaths' => $fileUrls],
        ], 200);
    }
    
    public function getVehicleCategory()
    {
        try {
            $user = Auth::user(); 

            if ($user) {
                $data = VehicleCategory::select('id as vehicle_category_id', 'vehicle_category_name')
                    ->get();
            } else {
                $data = []; 
            }

            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Vehicle Category fetched successfully',
                'data' => $data, 
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    public function getVehicleTypeByCategory(Request $request)
    {
        try {
            $user = Auth::user();

            if ($user) {
                $validated = $request->validate([
                    'vehicle_category_id' => 'required|integer',
                ]);

                $data = VehicleType::where('vehicle_category_id', $validated['vehicle_category_id'])
                    ->select('id as vehicle_type_id', 'vehicle_type_name')
                    ->get();
            } else {
                $data = []; 
            }

            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Vehicle Types fetched successfully',
                'data' => $data,
            ], 200);

        } catch (\Exception $e) {
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
            'order_id' => 'required', 
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
                'statusCode' => 422,
                'data' => [],
                'success' => 'error',
            ], 422);
        }
        try {
            $user = Auth::user();
            if ($user !== null) {
                $data = Order::select('id', 'status', 'created_at as pending_time', 'accepted_time', 'rejected_time', 'dispatched_time', 'intransit_time', 'delivered_time')->get();
            } else {
                $data = [];
            }

            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Track order fetched successfully',
                'data' => $data,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => $e->getMessage(),
            ], 500);
        }
    }


    //Common Filter

    public function getFilteredOrders(Request $request)
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 401,
                    'message' => 'Unauthorized access',
                    'data' => []
                ], 401);
            }

            $query = Order::join('dealers', 'orders.dealer_id', '=', 'dealers.id')
                ->select(
                    'orders.id as order_id',
                    'orders.created_at',
                    'orders.total_amount',
                    'orders.status',
                    'dealers.dealer_code',
                    'dealers.dealer_name'
                )
                ->where('orders.created_by', $user->id);

            if ($request->has('search_key') && !empty($request->search_key)) {
                $searchKey = $request->search_key;

                if (strpos($searchKey, 'OD00') === 0) {
                    $searchKey = str_replace('OD00', '', $searchKey);

                    $query->where(function ($q) use ($searchKey) {
                        $q->where('orders.id', '=', $searchKey); 
                    });
                } else {
                    $query->where(function ($q) use ($searchKey) {
                        $q->where('orders.id', 'like', '%' . $searchKey . '%')
                        ->orWhere('dealers.dealer_code', 'like', '%' . $searchKey . '%')
                        ->orWhere('dealers.dealer_name', 'like', '%' . $searchKey . '%');
                    });
                }
            }


            $data = $query->orderBy('orders.id', 'desc')->get();

            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Filtered orders fetched successfully',
                'data' => $data,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function getDistricts()
    {
        try {
            $districts = DB::table('districts')
                ->select('id as district_id', 'name as district_name')
                ->orderBy('name', 'asc')
                ->get();

            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Districts fetched successfully',
                'data' => $districts,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => $e->getMessage(),
            ], 500);
        }
    }



}
