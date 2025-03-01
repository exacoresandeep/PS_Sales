<?php

namespace App\Http\Controllers\Dealer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Dealer;
use App\Models\District;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class DealerController extends Controller
{
   
    public function login(Request $request)
    {
        try {
            $validated = $request->validate([
                'dealer_code' => 'required|string',
                'password' => 'required|string',
                'type' => 'required|string|in:Dealer',
            ]);

            $dealer = Dealer::where('dealer_code', $validated['dealer_code'])->first();

            if (!$dealer || !Hash::check($validated['password'], $dealer->password)) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 400,
                    'message' => 'Invalid credentials',
                ], 400);
            }

            $token = $dealer->createToken('Dealer API Token')->plainTextToken;

            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Login successful',
                'data' => [
                    'dealer' => [
                        'id' => $dealer->id,
                        'dealer_code' => $dealer->dealer_code,
                        'name' => $dealer->dealer_name,
                        'email' => $dealer->email,
                        'phone' => $dealer->phone,
                        'address' => $dealer->address,
                    ],
                    'token' => $token,
                    'status' => 'active',
                ],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => 'Something went wrong',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'dealer_code' => 'required|string|unique:dealers,dealer_code',
                'dealer_name' => 'required|string',
                'gst_no' => 'nullable|string|max:15|unique:dealers,gst_no',
                'pan_no' => 'nullable|string|max:10|unique:dealers,pan_no',
                'phone' => 'required|string|max:15|unique:dealers,phone',
                'email' => 'nullable|email|unique:dealers,email',
                'address' => 'nullable|string',
                'user_zone' => 'nullable|string',
                'pincode' => 'nullable|string|max:6',
                'state' => 'nullable|string',
                'district' => 'nullable|string',
                'taluk' => 'nullable|string',
                'location' => 'nullable|string',
                'assigned_route_id' => 'required|integer',
            ]);

            
            $district = District::where('name', $validated['district'])->first();
            if (!$district) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 400,
                    'message' => 'Invalid district. The district does not exist in the system.',
                ], 400);
            }

    
            $dealerCode = strtoupper($validated['dealer_code']);
            $dealerPrefix = substr($dealerCode, 0, 3);
            $gstNumber = $validated['gst_no'] ?? null;
            $gstSuffix = $gstNumber ? substr($gstNumber, -3) : "#2025";
            $password = $dealerPrefix . $gstSuffix;

            $hashedPassword = Hash::make($password);

            $dealer = Dealer::create(array_merge($validated, [
                'password' => $hashedPassword,
                'district_id' => $district->id,
            ]));

            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Dealer created successfully',
                'data' => $dealer
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 422,
                'message' => 'Validation error',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => 'Something went wrong',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    public function getDealerProfile(Request $request)
    {
        try {
            $dealer = $request->user();
    
            if (!$dealer) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 404,
                    'message' => 'Dealer not found',
                ], 404);
            }
    
            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Dealer profile retrieved successfully',
                'data' => [
                    'id' => $dealer->id,
                    'dealer_code' => $dealer->dealer_code,
                    'name' => $dealer->dealer_name,
                    'email' => $dealer->email,
                    'phone' => $dealer->phone,
                    'address' => $dealer->address,
                    'gst_no' => $dealer->gst_no,
                    'pan_no' => $dealer->pan_no,
                    'status' => $dealer->status,
                    'created_at' => $dealer->created_at,
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => 'Something went wrong',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
