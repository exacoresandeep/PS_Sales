<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AssignRoute;
use App\Models\Dealer;
use App\Models\TripRoute;
use App\Models\DealerTripActivity;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RouteController extends Controller
{
    public function getTodaysTrip(Request $request)
    {
        try {
            $employeeId = Auth::id(); 
            $todayDate = now()->toDateString();

            $trips = AssignRoute::with(['tripRoute', 'dealers'])
                ->where('employee_id', $employeeId)
                ->whereDate('assign_date', $todayDate)
                ->get()
                ->map(function ($trip) {
                    return [
                        'employee_id' => $trip->employee_id,
                        'trip_route_id' => $trip->trip_route_id,
                        'route_name' => $trip->tripRoute->route_name ?? null,
                        'assign_date' => $trip->assign_date,
                        'status' => $trip->status,
                        'dealers' => $trip->dealers->map(function ($dealer) use ($trip) {
                            $dealerActivity = DealerTripActivity::where('assign_route_id', $trip->id)
                                ->where('dealer_id', $dealer->id)
                                ->first();

                            return [
                                'id' => $dealer->id,
                                'dealer_code' => $dealer->dealer_code,
                                'dealer_name' => $dealer->dealer_name,
                                'activity_status' => $dealerActivity->activity_status ?? 'Pending',
                            ];
                        }),
                    ];
                });


            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Today\'s routes retrieved successfully.',
                'data' => $trips,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    public function viewTripDetails(Request $request, $dealerId)
    {
        try {
            $employeeId = Auth::id();

            $tripDetails = DealerTripActivity::with(['assignRoute', 'dealer'])
                ->where('dealer_id', $dealerId)
                ->whereHas('assignRoute', function($query) use ($employeeId) {
                    $query->where('employee_id', $employeeId);
                })
                ->get();

            if ($tripDetails->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 404,
                    'message' => 'No trip details found for the provided dealer and authenticated employee.',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Trip details retrieved successfully.',
                'data' => $tripDetails,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => 'Failed to retrieve trip details. Please try again later.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    public function updateDealerTripActivity(Request $request, $dealerId)
    {
        try {
            $request->validate([
                'assign_route_id' => 'required|exists:assign_route,id',
                'record_details' => 'required|string',
                'attachments' => 'nullable|array',
                'activity_status' => 'required|in:Pending,Completed', 
            ]);

            $dealerTripActivity = DealerTripActivity::firstOrNew([
                'assign_route_id' => $request->assign_route_id,
                'dealer_id' => $dealerId,
            ]);

            $dealerTripActivity->record_details = $request->record_details;
            $dealerTripActivity->activity_status = $request->activity_status;

            if ($request->hasFile('attachments')) {
                $attachments = [];
                foreach ($request->file('attachments') as $file) {
                    $filePath = $file->store('Trip', 'public');
                    $attachments[] = $filePath;  
                }
                $dealerTripActivity->attachments = json_encode($attachments); 
            }

            if ($request->activity_status === 'Completed') {
                $dealerTripActivity->completed_date = now();
            }


            $dealerTripActivity->save();

            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Dealer trip activity updated successfully.',
                'data' => $dealerTripActivity,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => 'Failed to update dealer trip activity. Please try again later.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function addDealerToRoute(Request $request, $tripRouteId)
    {
        try {
            $request->validate([
                'dealer_code' => 'required|string|max:10|unique:dealers,dealer_code',
                'dealer_name' => 'required|string|max:255',
                'phone' => 'required|string|max:15',
                'email' => 'required|email|max:255',
                'address' => 'required|string|max:500',
                'user_zone' => 'required|string|max:100',
                'pincode' => 'required|string|max:6',
                'state' => 'required|string|max:100',
                'district' => 'required|string|max:100',
                'taluk' => 'required|string|max:100',
            ]);

            $dealer = Dealer::create([
                'dealer_code' => $request->dealer_code,
                'dealer_name' => $request->dealer_name,
                'phone' => $request->phone,
                'email' => $request->email,
                'address' => $request->address,
                'user_zone' => $request->user_zone,
                'pincode' => $request->pincode,
                'state' => $request->state,
                'district' => $request->district,
                'taluk' => $request->taluk,
                'trip_route_id' => $tripRouteId, 
            ]);

            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Dealer added successfully to the route.',
                'data' => $dealer,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function getRoutesByDistrict($district_id)
    {
        try {
            $routes = TripRoute::where('district_id', $district_id)
                ->select('id as route_id', 'route_name', 'location_name', 'sub_locations', 'status')
                ->get();

            if ($routes->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 400,
                    'message' => 'No routes found for the given district.',
                ], 400);
            }
            foreach ($routes as $route) {
                $route->sub_locations = json_decode($route->sub_locations);
            }

            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Routes fetched successfully',
                'data' => $routes,
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
