<?php

namespace App\Http\Controllers\Api;

use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\CustomerType;
use App\Models\OrderType;
use App\Models\Dealer;
use App\Models\Product;
use App\Models\ProductType;
use App\Models\ProductDetails;
use App\Models\LeaveType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;


class AuthController extends Controller
{
    public function login(Request $request)
    {
        try {
            $validated = $request->validate([
                'employee_code' => 'required|string',
                'password' => 'required|string',
            ]);

            $employee = Employee::where('employee_code', $validated['employee_code'])->first();

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
                        'employee_type' => $employee->employeeType->type_name, 
                        'address' => $employee->address,
                        'photo' => $employee->photo,
                        'emergency_contact' => $employee->emergency_contact,
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
    public function getDealers(Request $request)
    {
        try {
            
            $user = Auth::user();
            if ($user !== null) {
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
                );
                if ($request->has('search_key') && !empty($request->search_key)) {
                    $searchKey = $request->search_key;
    
                    $query->where(function ($q) use ($searchKey) {
                        $q->where('dealer_code', 'like', '%' . $searchKey . '%')
                          ->orWhere('dealer_name', 'like', '%' . $searchKey . '%');
                    });
                }
    
                $data = $query->orderBy('dealer_name', 'asc')->get();
            } else {
                $data = [];
            }

            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Dealers fetched successfully',
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


    // public function getProductTypes(Request $request)
    // {
    //     try {
    //         $user = Auth::user();
    //         $productId = $request->input('product_id');
        
    //         if ($user !== null) {
    //             $query = ProductType::select('product_id as product_id', 'id as product_type_id', 'type_name as product_type_name');
                
    //             if ($productId) {
    //                 $query->where('product_id', $productId);
    //             }
    //             $data = $query->get();
   

    //         } else {
    //             $data = [];
    //         }

    //         return response()->json([
    //             'success' => true,
    //             'statusCode' => 200,
    //             'message' => 'Product types fetched successfully',
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
    


}
