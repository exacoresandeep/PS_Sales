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

     // public function currentWeekRoutes()
    // {
    //     try {
    //         $employeeId = Auth::id();
    //         $today = Carbon::now();
    //         $weekStart = $today->startOfWeek(Carbon::MONDAY);

    //         $routeMapping = [
    //             'Monday' => 'R1',
    //             'Tuesday' => 'R2',
    //             'Wednesday' => 'R3',
    //             'Thursday' => 'R4',
    //             'Friday' => 'R5',
    //             'Saturday' => 'R6',
    //         ];

    //         $weeklyRoutes = [];

    //         foreach ($routeMapping as $day => $routeName) {
    //             $date = $weekStart->copy()->addDays(array_search($day, array_keys($routeMapping)))->format('d/m/y');

    //             $rescheduledTrip = RescheduledRoute::where('employee_id', $employeeId)
    //                 ->where('original_route_name', $routeName)
    //                 ->whereDate('rescheduled_date', '>=', $weekStart->toDateString())
    //                 ->first();

    //             if ($rescheduledTrip) {
    //                 $routeName = $rescheduledTrip->new_route_name;
    //                 $locations = explode(', ', $rescheduledTrip->new_locations);
    //                 $routeId = $rescheduledTrip->id;
    //             } else {
    //                 $trip = AssignRoute::where('employee_id', $employeeId)
    //                     ->where('route_name', $routeName)
    //                     ->first();

    //                 if (!$trip) {
    //                     continue;
    //                 }

    //                 $locations = explode(', ', $trip->locations);
    //                 $routeId = $trip->id; 
    //             } // <-- **Added missing closing brace here**

    //             // Fetch Dealers
    //             $dealers = Dealer::whereIn('location', $locations)
    //                 ->get(['id', 'dealer_name as customer_name', 'location'])
    //                 ->map(function ($dealer) {
    //                     return array_merge($dealer->toArray(), ['customer_type' => 'Dealer']);
    //                 });

    //             // Fetch Leads
    //             $leads = Lead::join('customer_types', 'leads.customer_type', '=', 'customer_types.id')
    //                 ->where('leads.assigned_route_id', $routeId)
    //                 ->where(function ($query) {
    //                     $query->whereIn('leads.customer_type', [1, 2]) 
    //                         ->orWhere(function ($q) {
    //                             $q->where('leads.customer_type', 4)->where('leads.status', 'Follow Up');
    //                         });
    //                 })
    //                 ->get([
    //                     'leads.id',
    //                     'leads.customer_name',
    //                     'leads.location',
    //                     'customer_types.name as customer_type'
    //                 ]);

    //             // Merge customers
    //             $customers = $dealers->merge($leads);

    //             // Build weekly routes array
    //             $weeklyRoutes[] = [
    //                 'day' => $day,
    //                 'date' => $date,
    //                 'route_name' => $routeName,
    //                 'locations' => $locations,
    //                 'customers' => $customers,
    //             ];
    //         }

    //         return response()->json([
    //             'success' => true,
    //             'statusCode' => 200,
    //             'message' => 'Weekly routes fetched successfully.',
    //             'data' => $weeklyRoutes,
    //         ], 200);

    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'success' => false,
    //             'statusCode' => 500,
    //             'message' => $e->getMessage(),
    //         ], 500);
    //     }
    // }
    public function routeList()
    {
        try {
            $employeeId = Auth::id(); 
            $startDate = Carbon::now()->startOfWeek(); 
            $endDate = Carbon::now()->endOfWeek()->subDay(); 
            $today = Carbon::now()->toDateString(); 

            $trips = AssignRoute::with(['tripRoute'])
                ->where('employee_id', $employeeId)
                ->whereBetween('assign_date', [$startDate, $endDate]) 
                ->orderBy('assign_date', 'asc')
                ->get()
                ->map(function ($trip) use ($today) {
                    return [
                        'assign_route_id' => $trip->id,
                        'employee_id' => $trip->employee_id,
                        'trip_route_id' => $trip->trip_route_id,
                        'route_name' => $trip->tripRoute->route_name ?? null,
                        'location_name' => $trip->tripRoute->location_name ?? null,
                        'assign_date' => $trip->assign_date,
                        'assigned_day' => Carbon::parse($trip->assign_date)->format('l'),
                        'day' => $trip->assign_date,
                        'active_flag' => ($trip->assign_date >= $today) ? 'active' : 'inactive', 
                        'sub_locations' => json_decode($trip->sub_locations),
                    ];
                });

            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Routes fetched successfully.',
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

    public function todaysRouteSchedule()
    {
        try {
            $employeeId = Auth::id();
            $today = Carbon::now();
            $dayOfWeek = $today->format('l');
            $formattedDate = $today->format('d/m/Y');
    
            $routeMapping = [
                'Monday' => 'R1',
                'Tuesday' => 'R2',
                'Wednesday' => 'R3',
                'Thursday' => 'R4',
                'Friday' => 'R5',
                'Saturday' => 'R6',
            ];
    
            $routeName = $routeMapping[$dayOfWeek] ?? null;
    
            if (!$routeName) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 404,
                    'message' => 'No route assigned for today (Sunday).',
                ], 404);
            }
    
            $rescheduledTrip = RescheduledRoute::where('employee_id', $employeeId)
                ->where('original_route_name', $routeName)
                ->whereDate('rescheduled_date', $today->toDateString())
                ->first();
    
            if ($rescheduledTrip) {
                $routeName = $rescheduledTrip->new_route_name;
                $locations = explode(', ', $rescheduledTrip->new_locations);
                $routeId = $rescheduledTrip->id;
            } else {
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
                $routeId = $trip->id;
            }
    
            $rescheduledCustomers = RescheduledRouteCustomer::where('rescheduled_route_id', $routeId)
                ->get(['id', 'customer_id', 'customer_name', 'customer_type', 'location', 'status']);
    
            $tripData = [
                'employee_id' => $employeeId,
                'route_name' => $routeName,
                'locations' => $locations,
                'assigned_day' => $dayOfWeek,
                'assign_date' => $formattedDate,
                'rescheduled_customers' => $rescheduledCustomers, // Added this
            ];
    
            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Today’s route fetched successfully.',
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
   
    public function currentWeekRoutes()
    {
        try {
            $employeeId = Auth::id();
            $today = Carbon::now();
            $weekStart = $today->startOfWeek(Carbon::MONDAY);
    
            $routeMapping = [
                'Monday' => 'R1',
                'Tuesday' => 'R2',
                'Wednesday' => 'R3',
                'Thursday' => 'R4',
                'Friday' => 'R5',
                'Saturday' => 'R6',
            ];
    
            $weeklyRoutes = [];
    
            foreach ($routeMapping as $day => $routeName) {
                $date = $weekStart->copy()->addDays(array_search($day, array_keys($routeMapping)))->format('d/m/y');
    
                $rescheduledTrip = RescheduledRoute::where('employee_id', $employeeId)
                    ->where('original_route_name', $routeName)
                    ->whereDate('rescheduled_date', '>=', $weekStart->toDateString())
                    ->first();
    
                if ($rescheduledTrip) {
                    $routeName = $rescheduledTrip->new_route_name;
                    $locations = explode(', ', $rescheduledTrip->new_locations);
                    $routeId = $rescheduledTrip->id;
                } else {
                    $trip = AssignRoute::where('employee_id', $employeeId)
                        ->where('route_name', $routeName)
                        ->first();
    
                    if (!$trip) {
                        continue;
                    }
    
                    $locations = explode(', ', $trip->locations);
                    $routeId = $trip->id; 
                }
    
                // Fetch Rescheduled Route Customers
                $rescheduledCustomerIds = RescheduledRouteCustomer::where('rescheduled_route_id', $routeId)
                    ->pluck('customer_id')
                    ->toArray();
    
                // Fetch Dealers
                $dealers = Dealer::whereIn('location', $locations)
                    ->get(['id', 'dealer_name as customer_name', 'location'])
                    ->map(function ($dealer) use ($rescheduledCustomerIds) {
                        return array_merge($dealer->toArray(), [
                            'customer_type' => 'Dealer',
                            'rescheduled' => in_array($dealer->id, $rescheduledCustomerIds),
                        ]);
                    });
    
                // Ensure $dealers is a collection
                $dealers = collect($dealers);
    
                // Fetch Leads
                $leads = Lead::join('customer_types', 'leads.customer_type', '=', 'customer_types.id')
                    ->where('leads.assigned_route_id', $routeId)
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
                    ->map(function ($lead) use ($rescheduledCustomerIds) {
                        return array_merge($lead->toArray(), [
                            'rescheduled' => in_array($lead->id, $rescheduledCustomerIds),
                        ]);
                    });
    
                // Ensure $leads is a collection
                $leads = collect($leads);
    
                // Merge customers
                $customers = $dealers->merge($leads);
    
                // Build weekly routes array
                $weeklyRoutes[] = [
                    'day' => $day,
                    'date' => $date,
                    'route_name' => $routeName,
                    'locations' => $locations,
                    'customers' => $customers,
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
        try {
            DB::beginTransaction(); 
    
            $employeeId = Auth::id();
            $swaps = $request->input('swaps', []);
            $selectedCustomers = $request->input('selected_customers', []);
    
            $assignedRoutes = AssignRoute::where('employee_id', $employeeId)->get()->keyBy('route_name');
    
            $routeLocations = [];
            foreach ($assignedRoutes as $route) {
                $routeLocations[$route->route_name] = explode(', ', $route->locations);
            }
    
            foreach ($swaps as $swap) {
                [$day1, $day2] = $swap;
    
                $route1 = $this->getRouteNameFromDay($day1);
                $route2 = $this->getRouteNameFromDay($day2);
    
                if (!isset($routeLocations[$route1]) || !isset($routeLocations[$route2])) {
                    throw new \Exception("Invalid route names for swap: {$day1} <-> {$day2}");
                }
    
                [$routeLocations[$route1], $routeLocations[$route2]] = [$routeLocations[$route2], $routeLocations[$route1]];
            }
    
            foreach ($routeLocations as $routeName => $locations) {
                $rescheduledRoute = RescheduledRoute::updateOrCreate(
                    [
                        'employee_id' => $employeeId,
                        'original_route_name' => $routeName,
                        'rescheduled_date' => Carbon::now()->toDateString(),
                    ],
                    [
                        'new_route_name' => $routeName,
                        'new_locations' => implode(', ', $locations),
                    ]
                );
    
                foreach ($selectedCustomers as $customer) {
                    if (in_array($customer['location'], $locations)) {
                        RescheduledRouteCustomer::create([
                            'rescheduled_route_id' => $rescheduledRoute->id,
                            'customer_id' => $customer['id'],
                            'customer_name' => $customer['customer_name'],
                            'customer_type' => $customer['customer_type'],
                            'location' => $customer['location'],
                            'status' => 'pending'
                        ]);
                    }
                }
            }
    
            DB::commit();
    
            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Routes rescheduled successfully.',
            ], 200);
    
        } catch (\Exception $e) {
            DB::rollBack(); 
            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    private function getRouteNameFromDay($day)
    {
        $routeMapping = [
            'Monday' => 'R1',
            'Tuesday' => 'R2',
            'Wednesday' => 'R3',
            'Thursday' => 'R4',
            'Friday' => 'R5',
            'Saturday' => 'R6',
        ];
    
        return $routeMapping[$day] ?? null;
    }

    public function changeRouteStatus(Request $request)
    {
        try {
            $customerId = $request->input('customer_id');
            $status = $request->input('status');
    
            $currentWeekStart = Carbon::now()->startOfWeek(Carbon::MONDAY)->toDateString();
    
            $customer = RescheduledRouteCustomer::where('customer_id', $customerId)
                ->whereHas('rescheduledRoute', function ($query) use ($currentWeekStart) {
                    $query->whereDate('rescheduled_routes.rescheduled_date', '>=', $currentWeekStart);
                })
                ->join('rescheduled_routes', 'rescheduled_route_customers.rescheduled_route_id', '=', 'rescheduled_routes.id')
                ->orderBy('rescheduled_routes.rescheduled_date', 'desc')
                ->select('rescheduled_route_customers.*') 
                ->first();
    
            if (!$customer) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 404,
                    'message' => 'Customer not found for the current week.',
                ], 404);
            }
    
            $customer->update(['status' => $status]);
    
            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Customer status updated successfully.',
            ], 200);
    
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
   
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
