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
            $employeeId = Auth::id(); // Get logged-in employee ID
            $today = Carbon::now()->format('l'); // Get today's day name (Monday, Tuesday, etc.)
            $weekStart = Carbon::now()->startOfWeek(Carbon::MONDAY)->format('Y-m-d');
    
            // Mapping days to route names
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
    
            // Check if today's route was rescheduled
            $rescheduledRoute = RescheduledRoute::where('employee_id', $employeeId)
                ->where('rescheduled_day', $today)
                ->whereDate('week_start', $weekStart)
                ->first();
    
            if ($rescheduledRoute) {
                // If rescheduled, use the new day and route
                $day = $rescheduledRoute->rescheduled_day;
                $routeName = $routeMapping[$day] ?? null;
                $assignedRouteId = $rescheduledRoute->assigned_route_id;
                $locations = explode(', ', $rescheduledRoute->locations);
    
                // Fetch Scheduled Customers
                $scheduledCustomers = RescheduledRouteCustomer::where('rescheduled_route_id', $rescheduledRoute->id)
                    ->get(['id', 'customer_name', 'location', 'customer_type'])
                    ->map(function ($customer) {
                        return array_merge($customer->toArray(), ['scheduled' => true]);
                    });
            } else {
                // If not rescheduled, use default assigned route
                $routeName = $routeMapping[$today] ?? null;
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
    
                // Fetch Scheduled Customers from default route (if applicable)
                $scheduledCustomers = RescheduledRouteCustomer::where('rescheduled_route_id', $assignedRouteId)
                    ->get(['id', 'customer_name', 'location', 'customer_type'])
                    ->map(function ($customer) {
                        return array_merge($customer->toArray(), ['scheduled' => true]);
                    });
            }
    
            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Today\'s route schedule fetched successfully.',
                'data' => [
                    'day' => $today,
                    'route_name' => $routeName,
                    'locations' => $locations,
                    'customers' => $scheduledCustomers->values(),
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    
    
    
public function currentWeekRoutes()
{
    try {
        $employeeId = Auth::id();
        $today = Carbon::now();
        $weekStart = $today->copy()->startOfWeek(Carbon::MONDAY);

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
            $scheduledCustomers = collect();
            $locations = [];

            // Check if the route was rescheduled
            $rescheduledRoute = RescheduledRoute::where('employee_id', $employeeId)
                ->where('original_day', $day)
                ->whereDate('week_start', $weekStart->format('Y-m-d'))
                ->first();

            if ($rescheduledRoute) {
                // If rescheduled, use the new day and route
                $day = $rescheduledRoute->rescheduled_day;
                $routeName = $routeMapping[$day] ?? $defaultRouteName;
                $assignedRouteId = $rescheduledRoute->assigned_route_id;
                







                $locations = explode(', ', $rescheduledRoute->locations);

                // Fetch Scheduled Customers
                $scheduledCustomers = collect(RescheduledRouteCustomer::where('rescheduled_route_id', $rescheduledRoute->id)
                    ->get(['customer_id', 'customer_name', 'location', 'customer_type'])
                    ->map(function ($customer) {
                        return array_merge($customer->toArray(), ['scheduled' => true]);
                    })
                );
                // print_r($scheduledCustomers->toArray());

                    // dump($scheduledCustomers);
            } else {
                // If not rescheduled, use the default assigned route
                $routeName = $defaultRouteName;
                $trip = AssignRoute::where('employee_id', $employeeId)
                    ->where('route_name', $routeName)
                    ->first();

                if (!$trip) {
                    continue;
                }

                $locations = explode(', ', $trip->locations);
                $assignedRouteId = $trip->id;
            }






            // Fetch Non-Scheduled Customers from Assigned Route (Even if Rescheduled)
            $dealers = Dealer::where('assigned_route_id', $assignedRouteId)
                ->whereNotIn('id', collect($scheduledCustomers->pluck('customer_id'))) // Exclude scheduled customers
                ->get(['id', 'dealer_name as customer_name', 'location'])
                ->map(function ($dealer) {
                    return array_merge($dealer->toArray(), ['customer_type' => 'Dealer', 'scheduled' => false]);
                });

            $leads = Lead::join('customer_types', 'leads.customer_type', '=', 'customer_types.id')
                ->where('leads.assigned_route_id', $assignedRouteId)
                ->whereNotIn('leads.id', collect($scheduledCustomers->pluck('customer_id'))) // Exclude scheduled customers
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

            $customers = $scheduledCustomers->merge($dealers)->merge($leads);
            if (isset($routeMapping[$day])) {
                $dayIndex = array_search($day, array_keys($routeMapping));
                $date = $weekStart->copy()->addDays($dayIndex)->format('d/m/y');
            } else {
                $date = $weekStart->format('d/m/y'); // Fallback
            }

            // Build the weekly routes array
            $weeklyRoutes[] = [
                'day' => $day,
                'date' => $date,
                'route_name' => $routeName,
                'locations' => $locations,
                'customers' => $customers->values(), // Ensure proper indexing
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
            'message' => $e->getMessage(),
        ], 500);
    }
}

 
    
    public function routeReschedule(Request $request)
    {
        $request->validate([
            'swaps' => 'required|array',
            'swaps.*' => 'array|min:2|max:2',
            'selected_customers' => 'required|array',
            'selected_customers.*.id' => 'required|integer', 
            'selected_customers.*.customer_type' => 'required|string',
            'selected_customers.*.assigned_route_id' => 'required|integer',
        ]);
    
        try {
            $employeeId = Auth::id();
            $weekStart = Carbon::now()->startOfWeek(Carbon::MONDAY);
    
            // Define route mapping
            $routeMapping = [
                'Monday' => 'R1',
                'Tuesday' => 'R2',
                'Wednesday' => 'R3',
                'Thursday' => 'R4',
                'Friday' => 'R5',
                'Saturday' => 'R6',
            ];
    
            foreach ($request->swaps as $swap) {
                [$day1, $day2] = $swap;
    
                // Validate the days exist in the mapping
                if (!isset($routeMapping[$day1]) || !isset($routeMapping[$day2])) {
                    return response()->json([
                        'success' => false,
                        'statusCode' => 400,
                        'message' => "Invalid days provided for swapping.",
                    ], 400);
                }
    
                $routeName1 = $routeMapping[$day1];
                $routeName2 = $routeMapping[$day2];
    
                // Fetch assigned routes
                $route1 = AssignRoute::where('employee_id', $employeeId)
                    ->where('route_name', $routeName1)
                    ->first();
    
                $route2 = AssignRoute::where('employee_id', $employeeId)
                    ->where('route_name', $routeName2)
                    ->first();
    
                if (!$route1 || !$route2) {
                    return response()->json([
                        'success' => false,
                        'statusCode' => 404,
                        'message' => "Routes not found for one or both days.",
                    ], 404);
                }
    
                RescheduledRoute::updateOrCreate(
                    ['employee_id' => $employeeId, 'original_day' => $day1],
                    [
                        'rescheduled_day' => $day2,
                        'route_name' => $route1->route_name,
                        'locations' => $route1->locations,
                        'week_start' => $weekStart->format('Y-m-d'),
                        'assigned_route_id' => $route1->id,
                    ]
                );
    
                RescheduledRoute::updateOrCreate(
                    ['employee_id' => $employeeId, 'original_day' => $day2],
                    [
                        'rescheduled_day' => $day1,
                        'route_name' => $route2->route_name,
                        'locations' => $route2->locations,
                        'week_start' => $weekStart->format('Y-m-d'),
                        'assigned_route_id' => $route2->id,
                    ]
                );
    
                $this->addSelectedCustomersToRescheduledRoute($request->selected_customers, $route1, $routeName1, $day1, $weekStart, 'pending');
                $this->addSelectedCustomersToRescheduledRoute($request->selected_customers, $route2, $routeName2, $day2, $weekStart, 'pending');
            }
    
            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Routes rescheduled successfully.',
            ], 200);
    
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
 

    private function addSelectedCustomersToRescheduledRoute($selectedCustomers, $route, $routeName, $day, $weekStart, $status)
{
    // Fetch the rescheduled route for the given day and employee
    $rescheduledRoute = RescheduledRoute::where('employee_id', Auth::id())
        ->where('original_day', $day)
        ->where('week_start', $weekStart->format('Y-m-d'))
        ->first();

    if (!$rescheduledRoute) {
        return;
    }

    // Filter customers based on assigned_route_id
    $filteredCustomers = collect($selectedCustomers)->filter(function ($customer) use ($route) {
        return $customer['assigned_route_id'] == $route->id;
    });

    foreach ($filteredCustomers as $customer) {
        RescheduledRouteCustomer::create([
            'customer_id' => $customer['id'],
            'customer_type' => $customer['customer_type'],
            'customer_name' => $customer['customer_name'],
            'location' => $customer['location'],
            'route_name' => $routeName,
            'assigned_route_id' => $route->id,
            'rescheduled_route_id' => $rescheduledRoute->id, // ✅ Store rescheduled_routes_id
            'status' => $status,
            'week_start' => $weekStart->format('Y-m-d'),
            'original_day' => $day,
            'rescheduled_day' => $day,
        ]);
    }
}
    
public function changeRouteStatus(Request $request)
{
    try {
        $request->validate([
            'customer_id' => 'required|exists:rescheduled_route_customers,id',
        ]);

        // Fetch the customer from the rescheduled route
        $customer = RescheduledRouteCustomer::find($request->customer_id);

        if (!$customer) {
            return response()->json([
                'success' => false,
                'statusCode' => 404,
                'message' => 'Customer not found in rescheduled routes.',
            ], 404);
        }

        // Update status to "Completed" with current date & time
        $customer->update([
            'status' => 'Completed',
            'visited_at' => Carbon::now(),
        ]);

        return response()->json([
            'success' => true,
            'statusCode' => 200,
            'message' => 'Customer visit status updated successfully.',
            'data' => [
                'customer_id' => $customer->id,
                'customer_name' => $customer->customer_name,
                'location' => $customer->location,
                'customer_type' => $customer->customer_type,
                'status' => $customer->status,
                'visited_at' => $customer->visited_at,
            ]
        ], 200);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'statusCode' => 500,
            'message' => $e->getMessage(),
        ], 500);
    }
}

 
    // public function changeRouteStatus(Request $request)
    // {
    //     try {
    //         $customerId = $request->input('customer_id');
    //         $status = $request->input('status');
    
    //         $currentWeekStart = Carbon::now()->startOfWeek(Carbon::MONDAY)->toDateString();
    
    //         $customer = RescheduledRouteCustomer::where('customer_id', $customerId)
    //             ->whereHas('rescheduledRoute', function ($query) use ($currentWeekStart) {
    //                 $query->whereDate('rescheduled_routes.rescheduled_date', '>=', $currentWeekStart);
    //             })
    //             ->join('rescheduled_routes', 'rescheduled_route_customers.rescheduled_route_id', '=', 'rescheduled_routes.id')
    //             ->orderBy('rescheduled_routes.rescheduled_date', 'desc')
    //             ->select('rescheduled_route_customers.*') 
    //             ->first();
    
    //         if (!$customer) {
    //             return response()->json([
    //                 'success' => false,
    //                 'statusCode' => 404,
    //                 'message' => 'Customer not found for the current week.',
    //             ], 404);
    //         }
    
    //         $customer->update(['status' => $status]);
    
    //         return response()->json([
    //             'success' => true,
    //             'statusCode' => 200,
    //             'message' => 'Customer status updated successfully.',
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
            $routes = TripRoute::where('district_id', $district_id)
                ->select('id as route_id', 'locations')
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
