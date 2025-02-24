<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AssignRoute;
use App\Models\Dealer;
use App\Models\TripRoute;
use App\Models\District;
use App\Models\EmployeeType;
use App\Models\DealerTripActivity;
use App\Models\RescheduledRoute;
use App\Models\RescheduledRouteCustomer;
use App\Models\Lead;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Log;


class RouteController extends Controller
{
    public function index()
    {
        $routes = TripRoute::all(); 
        $districts = District::all();
        $employeeTypes = EmployeeType::all();
    
        return view('admin.route.index', compact('routes', 'districts', 'employeeTypes'));
    }
    public function store(Request $request)
    {
        $request->validate([
            'district_id' => 'required|exists:districts,id',
            'route_name' => 'required|string|max:255',
            'location_name' => 'required|string|max:255',
            'sub_locations' => 'required|string', 
        ]);
        $subLocations = json_decode($request->sub_locations, true);

        if (!is_array($subLocations)) {
            return response()->json(['message' => 'Invalid sub locations format'], 422);
        }

    
        $route = new TripRoute();
        $route->district_id = $request->district_id;
        $route->route_name = $request->route_name;
        $route->location_name = $request->location_name;
        $route->sub_locations = json_encode($subLocations);
        $route->status = "1";
        $route->save();

        return response()->json(['message' => 'Route created successfully']);
    }

    public function update(Request $request)
    {
        $request->validate([
            'district_id' => 'required|exists:districts,id',
            'route_name' => 'required|string|max:255',
            'location_name' => 'required|string|max:255',
            'sub_locations' => 'required|string',
        ]);
        $subLocations = json_decode($request->sub_locations, true);

        if (!is_array($subLocations)) {
            return response()->json(['message' => 'Invalid sub locations format'], 422);
        }

        $route = TripRoute::findOrFail($request->id);
        
        $route->district_id = $request->district_id;
        $route->route_name = $request->route_name;
        $route->location_name = $request->location_name;
        $route->sub_locations = json_encode($subLocations);
        $route->save();

        return response()->json(['message' => 'Route updated successfully']);
    }
    public function routesListing(Request $request)
    {
        $query = TripRoute::with('district'); 
    
        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('district_name', function ($route) {
                return optional($route->district)->name ?? '-'; 
            })
            ->addColumn('sub_locations', function ($route) {
                $subLocations = json_decode($route->sub_locations, true);
                return is_array($subLocations) ? implode(', ', $subLocations) : '-';
            })
            ->addColumn('action', function ($route) {
                return '
                    <button class="btn btn-sm btn-info" onclick="handleAction(' . $route->id . ', \'view\')" title="View">
                        <i class="fa fa-eye"></i>
                    </button>
                    <button class="btn btn-sm btn-warning" onclick="handleAction(' . $route->id . ', \'edit\')" title="Edit">
                        <i class="fa fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-danger" onclick="deleteRoute(' . $route->id . ')" title="Delete">
                        <i class="fa fa-trash"></i>
                    </button>
                ';
            })
            ->rawColumns(['action'])
            ->make(true);
    }
    public function getTodaysTrip(Request $request)
    {
        try {
            $employeeId = Auth::id(); 
            $todayDate = now()->toDateString();

            $trip = AssignRoute::with(['tripRoute', 'dealers'])
                ->where('employee_id', $employeeId)
                ->whereDate('assign_date', $todayDate)
                ->first();
            if (!$trip) {
                return response()->json([
                    'success' => true,
                    'statusCode' => 200,
                    'message' => 'No trip assigned for today.',
                    'data' => null,
                ], 200);
            }
    
            $tripData = [
                'employee_id' => $trip->employee_id,
                'trip_route_id' => $trip->trip_route_id,
                'route_name' => $trip->tripRoute->route_name ?? null,
                'assign_date' => $trip->assign_date,
                'location_name' => $trip->tripRoute->location_name ?? null,
                'status' => $trip->status,
            ];
    
            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => "Today's routes retrieved successfully.",
                'data' => $tripData, 
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


    public function todaysRouteSchedule()
    {
        try {
            $employeeId = Auth::id();
            $today = Carbon::now();
            $weekStart = $today->copy()->startOfWeek(Carbon::MONDAY);
            $weekEnd = $today->copy()->endOfWeek(Carbon::SUNDAY);
            $todayName = $today->format('l'); // Monday, Tuesday, etc.

            // Route mapping for default routes
            $routeMapping = [
                'Monday' => 'R1',
                'Tuesday' => 'R2',
                'Wednesday' => 'R3',
                'Thursday' => 'R4',
                'Friday' => 'R5',
                'Saturday' => 'R6',
            ];

            $scheduledCustomers = collect();
            $locations = [];
            $assignedRouteId = null;
            $routeName = $routeMapping[$todayName] ?? null;

            // Check if today's route has been rescheduled this week
            $rescheduledRoute = RescheduledRoute::where('employee_id', $employeeId)
                ->where('day', $todayName)
                ->whereBetween('assign_date', [$weekStart->format('Y-m-d'), $weekEnd->format('Y-m-d')])
                ->first();

            if ($rescheduledRoute) {
                // Use rescheduled route details
                $routeName = $rescheduledRoute->route_name;
                $assignedRouteId = $rescheduledRoute->assigned_route_id;
                $locations = $rescheduledRoute->locations;

                // Fetch rescheduled customers
                $scheduledCustomers = collect($rescheduledRoute->customers)->map(function ($customer) {
                    return array_merge($customer, ['scheduled' => true]);
                });
            } else {
                // Fetch default assigned route if no reschedule exists
                $trip = AssignRoute::where('employee_id', $employeeId)
                    ->where('route_name', $routeName)
                    ->first();

                if (!$trip) {
                    return response()->json([
                        'success' => false,
                        'statusCode' => 404,
                        'message' => 'No route assigned for today.',
                    ], 404);
                }

                $locations = explode(', ', $trip->locations);
                $assignedRouteId = $trip->id;

                // Fetch customers from assigned route
                $dealers = Dealer::where('assigned_route_id', $assignedRouteId)
                    ->get(['id', 'dealer_name as customer_name', 'location'])
                    ->map(function ($dealer) {
                        return array_merge($dealer->toArray(), ['customer_type' => 'Dealer', 'scheduled' => false]);
                    });

                $leads = Lead::join('customer_types', 'leads.customer_type', '=', 'customer_types.id')
                    ->where('leads.assigned_route_id', $assignedRouteId)
                    ->where(function ($query) {
                        $query->whereIn('leads.customer_type', [1, 2])
                            ->orWhere(function ($q) {
                                $q->where('leads.customer_type', 4)->where('leads.status', 'Follow Up');
                            });
                    })
                    ->get([
                        'leads.id',
                        'leads.customer_name',
                        'leads.location',
                        'customer_types.name as customer_type'
                    ])
                    ->map(function ($lead) {
                        return array_merge($lead->toArray(), ['scheduled' => false]);
                    });

                // Merge customers into one list
                $scheduledCustomers = $dealers->merge($leads);
            }

            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Today\'s route schedule fetched successfully.',
                'data' => [
                    'day' => $todayName,
                    'route_name' => $routeName,
                    'locations' => $locations,
                    'customers' => $scheduledCustomers->values(),
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => 'Error fetching today\'s route schedule.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function currentWeekRoutes()
    {
        try {
            $employeeId = Auth::id();
            $today = Carbon::now();
            $weekStart = $today->copy()->startOfWeek(Carbon::MONDAY);
            $weekEnd = $today->copy()->endOfWeek(Carbon::SUNDAY);
    
            $routeMapping = [
                'Monday' => 'R1',
                'Tuesday' => 'R2',
                'Wednesday' => 'R3',
                'Thursday' => 'R4',
                'Friday' => 'R5',
                'Saturday' => 'R6',
            ];
    
            $weeklyRoutes = [];
    
            foreach ($routeMapping as $day => $defaultRouteName) {
                $customers = collect();
                $locations = [];
                $routeName = $defaultRouteName;
                $assignedRouteId = null;
    
                $rescheduledRoute = RescheduledRoute::where('employee_id', $employeeId)
                    ->where('day', $day)
                    ->whereBetween('assign_date', [$weekStart->format('Y-m-d'), $weekEnd->format('Y-m-d')])
                    ->first();
    
                if ($rescheduledRoute) {
                    $routeName = $rescheduledRoute->route_name;
                    $assignedRouteId = $rescheduledRoute->assigned_route_id;
                    $locations = is_array($rescheduledRoute->locations) ? $rescheduledRoute->locations : [];
    
                    // Ensure customers are decoded properly
                    $rescheduledCustomers = is_array($rescheduledRoute->customers)
                        ? collect($rescheduledRoute->customers)
                        : collect(json_decode($rescheduledRoute->customers, true) ?? []);
    
                    // Mark rescheduled customers as scheduled
                    $rescheduledCustomers = $rescheduledCustomers->map(function ($customer) {
                        return array_merge($customer, ['scheduled' => true]);
                    });
    
                } else {
                    $trip = AssignRoute::where('employee_id', $employeeId)
                        ->where('route_name', $routeName)
                        ->first();
    
                    if (!$trip) {
                        continue;
                    }
    
                    $locations = explode(', ', $trip->locations);
                    $assignedRouteId = $trip->id;
                    $rescheduledCustomers = collect([]);
                }
    
                // Fetch all dealers assigned to this route
                $dealers = Dealer::where('assigned_route_id', $assignedRouteId)
                    ->get(['id', 'dealer_name as customer_name', 'location'])
                    ->map(function ($dealer) {
                        return array_merge($dealer->toArray(), ['customer_type' => 'Dealer', 'scheduled' => false]);
                    });
    
                // Fetch all leads assigned to this route
                $leads = Lead::join('customer_types', 'leads.customer_type', '=', 'customer_types.id')
                    ->where('leads.assigned_route_id', $assignedRouteId)
                    ->where(function ($query) {
                        $query->whereIn('leads.customer_type', [1, 2])
                            ->orWhere(function ($q) {
                                $q->where('leads.customer_type', 4)->where('leads.status', 'Follow Up');
                            });
                    })
                    ->get([
                        'leads.id',
                        'leads.customer_name',
                        'leads.location',
                        'customer_types.name as customer_type'
                    ])
                    ->map(function ($lead) {
                        return array_merge($lead->toArray(), ['scheduled' => false]);
                    });
    
                // Merge all customers, ensuring only rescheduled ones are marked as scheduled
                $customers = $dealers->merge($leads)->map(function ($customer) use ($rescheduledCustomers) {
                    $rescheduled = $rescheduledCustomers->firstWhere('id', (int) $customer['id']); // Ensure ID consistency
    
                    if ($rescheduled) {
                        return array_merge($customer, ['scheduled' => true]);
                    }
                    return $customer;
                });
    
                // Calculate the date for the day in the current week
                $dayIndex = array_search($day, array_keys($routeMapping));
                $date = $weekStart->copy()->addDays($dayIndex)->format('d/m/y');
    
                $weeklyRoutes[] = [
                    'day' => $day,
                    'date' => $date,
                    'assigned_route_id' => $assignedRouteId,
                    'route_name' => $routeName,
                    'locations' => $locations,
                    'customers' => $customers->values(),
                ];
            }
    
            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Weekly routes fetched successfully.',
                'data' => $weeklyRoutes,
            ], 200);
    
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => 'Error fetching weekly routes.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    

    
    public function routeReschedule(Request $request)
    {
        // Validate input format
        if (!is_array($request->all())) {
            return response()->json([
                'success' => false,
                'statusCode' => 400,
                'message' => 'Invalid data format. Expected an array of routes.',
                'data' => null
            ], 400);
        }
    
        $rescheduledRoutes = [];
        $alreadyRescheduledRoutes = [];
    
        // Get start and end of the current week
        $startOfWeek = Carbon::now()->startOfWeek()->format('Y-m-d');
        $endOfWeek = Carbon::now()->endOfWeek()->format('Y-m-d');
    
        foreach ($request->all() as $route) {
            // Validate route input
            $validator = Validator::make($route, [
                'employee_id' => 'required|integer',
                'day' => 'required|string',
                'date' => 'required|string',
                'assigned_route_id' => 'required|integer',
                'route_name' => 'required|string',
                'locations' => 'required|array',
                'customers' => 'nullable|array',
            ]);
    
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 422,
                    'message' => 'Validation error',
                    'data' => [
                        'errors' => $validator->errors()
                    ]
                ], 422);
            }
    
            // Convert date format (DD/MM/YY → YYYY-MM-DD)
            $assignDate = Carbon::createFromFormat('d/m/y', $route['date'])->format('Y-m-d');
    
            // Check if the same `employee_id` & `assigned_route_id` is already rescheduled within the current week
            $alreadyRescheduled = RescheduledRoute::where('employee_id', $route['employee_id'])
                ->where('assigned_route_id', $route['assigned_route_id'])
                ->whereBetween('assign_date', [$startOfWeek, $endOfWeek])
                ->exists();
    
            if ($alreadyRescheduled) {
                // Collect routes that were already rescheduled in the current week
                $alreadyRescheduledRoutes[] = [
                    'route_name' => $route['route_name'],
                    'day' => $route['day'],
                    'date' => $assignDate
                ];
                continue; // Skip inserting this route
            }
    
            // Insert new rescheduled route
            $reschedule = RescheduledRoute::create([
                'employee_id' => $route['employee_id'],
                'day' => $route['day'],
                'assign_date' => $assignDate,
                'assigned_route_id' => $route['assigned_route_id'],
                'route_name' => $route['route_name'],
                'locations' => $route['locations'],
                'customers' => $route['customers'] ?? [],
            ]);
    
            $rescheduledRoutes[] = $reschedule;
        }
    
        if (!empty($alreadyRescheduledRoutes)) {
            return response()->json([
                'success' => false,
                'statusCode' => 400,
                'message' => 'Some routes were already rescheduled this week.',
                'data' => [
                    'already_rescheduled' => $alreadyRescheduledRoutes
                ]
            ], 400);
        }
    
        return response()->json([
            'success' => true,
            'statusCode' => 200,
            'message' => 'Routes rescheduled successfully.',
            'data' => [
                'rescheduled_routes' => $rescheduledRoutes
            ]
        ], 200);
    }
    public function changeRouteStatus(Request $request)
    {
        try {
            // Validate request
            $request->validate([
                'customer_id' => 'required|integer',
                'customer_type' => 'required|string',
            ]);
    
            $customerId = (int) $request->customer_id;
            $customerType = $request->customer_type;
    
            // Normalize customer type: Treat all non-'Dealer' types as leads
            $isDealer = ($customerType === 'Dealer');
    
            // Find the rescheduled route containing the given customer
            $rescheduledRoute = RescheduledRoute::whereJsonContains('customers', function ($customer) use ($customerId, $isDealer) {
                return $customer['id'] == $customerId && ($isDealer ? $customer['customer_type'] === 'Dealer' : $customer['customer_type'] !== 'Dealer');
            })->first();
    
            if (!$rescheduledRoute) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 404,
                    'message' => 'Customer not found in rescheduled routes.',
                ], 404);
            }
    
            // Get customers from the rescheduled route as an array
            $customers = collect($rescheduledRoute->customers);
    
            // Use map() to update the correct customer
            $updatedCustomers = $customers->map(function ($customer) use ($customerId, $isDealer) {
                if ($customer['id'] == $customerId && ($isDealer ? $customer['customer_type'] === 'Dealer' : $customer['customer_type'] !== 'Dealer')) {
                    $customer['status'] = 'Completed';
                    $customer['visited_at'] = Carbon::now()->toDateTimeString();
                }
                return $customer;
            });
    
            // Save updated customers back into rescheduled route
            $rescheduledRoute->update(['customers' => $updatedCustomers->toArray()]);
    
            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Customer visit status updated successfully.',
                // 'data' => $updatedCustomers->firstWhere('id', $customerId),
            ], 200);
    
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => 'Error updating customer status.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    
        
    // public function changeRouteStatus(Request $request)
    // {
    //     try {
    //         $request->validate([
    //             'customer_id' => 'required|exists:rescheduled_route_customers,id',
    //         ]);

    //         $customer = RescheduledRouteCustomer::find($request->customer_id);

    //         if (!$customer) {
    //             return response()->json([
    //                 'success' => false,
    //                 'statusCode' => 404,
    //                 'message' => 'Customer not found in rescheduled routes.',
    //             ], 404);
    //         }

    //         $customer->update([
    //             'status' => 'Completed',
    //             'visited_at' => Carbon::now(),
    //         ]);

    //         return response()->json([
    //             'success' => true,
    //             'statusCode' => 200,
    //             'message' => 'Customer visit status updated successfully.',
    //             'data' => [
    //                 'customer_id' => $customer->id,
    //                 'customer_name' => $customer->customer_name,
    //                 'location' => $customer->location,
    //                 'customer_type' => $customer->customer_type,
    //                 'status' => $customer->status,
    //                 'visited_at' => $customer->visited_at,
    //             ]
    //         ], 200);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'success' => false,
    //             'statusCode' => 500,
    //             'message' => $e->getMessage(),
    //         ], 500);
    //     }
    // }

   
    public function getAllRoutesByDistrict($district_id)
    {
        $routes = TripRoute::where('district_id', $district_id)->get();
        return response()->json($routes);
    }
    public function getRoutesByDistrict($district_id)
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
            $routes = AssignRoute::where('district_id', $district_id)
                ->where('employee_id', $employee->id)
                ->select('id as assign_route_id', 'route_name', 'locations')
                ->get();

            if ($routes->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 400,
                    'message' => 'No routes found for the given district.',
                    'data' => [],
                ], 400);
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
