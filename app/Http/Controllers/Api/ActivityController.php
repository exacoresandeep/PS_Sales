<?php

namespace App\Http\Controllers\Api;

use App\Models\Activity;
use App\Models\Employee;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\ActivityType;
use App\Models\Dealer;
use App\Models\District;
use App\Models\AssignRoute;
use Illuminate\Support\Facades\Auth;
use Exception;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Log;

class ActivityController extends Controller
{
    public function index()
    {
        try {
            $user = Auth::user();

            if ($user === null) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 400,
                    'message' => 'Unauthorized access',
                ], 400);
            }

            $activities = Activity::where('employee_id', $user->id)
                ->with(['activityType', 'dealer']) 
                ->orderBy('assigned_date', 'desc')
                ->get();
            
            $activitiesData = $activities->map(function ($activity) {
                return [
                    'id' => $activity->id,
                    'assigned_date' => $activity->assigned_date,
                    'completed_date' => $activity->completed_date,
                    'status' => $activity->status,
                    'activity_type' => [
                        'id' => $activity->activityType->id,
                        'name' => $activity->activityType->name,
                    ],
                    'dealer' => [
                        'id' => $activity->dealer->id,
                        'dealer_code' => $activity->dealer->dealer_code,
                        'dealer_name' => $activity->dealer->dealer_name,
                    ],
                ];
            });
            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Activities retrieved successfully!',
                'data' => $activitiesData,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    public function updateActivity(Request $request, $activityId)
    {
        try {

            $user = Auth::user();

            if ($user === null) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 400,
                    'message' => 'Unauthorized access',
                ], 400);
            }

            $validatedData = $request->validate([
                'record_details' => 'required|string', 
                'attachments' => 'nullable|array',
                'attachments.*' => 'string',
            ]);

            $activity = Activity::find($activityId);

            if (!$activity) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 400,
                    'message' => 'Activity not found',
                ], 400);
            }

            if ($activity->employee_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 400,
                    'message' => 'Forbidden: You can only update your own activities.',
                ], 400);
            }

            $activity->record_details = $validatedData['record_details'];
            $activity->attachments = json_encode($validatedData['attachments']);
            $activity->completed_date = now(); 
            $activity->status = 'Completed';
            $activity->save();  

            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Activity updated successfully!',
                'data' => $activity,
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    // public function viewActivity($activityId)
    // {
    //     try {
    //         $user = Auth::user();
    //         if ($user === null) {
    //             return response()->json([
    //                 'success' => false,
    //                 'statusCode' => 400,
    //                 'message' => 'Unauthorized access',
    //             ], 400);
    //         }

    //         $activity = Activity::with(['activityType', 'dealer']) 
    //             ->find($activityId);

    //         if (!$activity) {
    //             return response()->json([
    //                 'success' => false,
    //                 'statusCode' => 404,
    //                 'message' => 'Activity not found',
    //             ], 404);
    //         }

    //         if ($activity->employee_id !== $user->id) {
    //             return response()->json([
    //                 'success' => false,
    //                 'statusCode' => 400,
    //                 'message' => 'Forbidden: You can only view your own activities.',
    //             ], 400);
    //         }

    //         return response()->json([
    //             'success' => true,
    //             'statusCode' => 200,
    //             'message' => 'Activity retrieved successfully!',
    //             'data' => $activity,
    //         ], 200);

    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'success' => false,
    //             'statusCode' => 500,
    //             'message' => $e->getMessage(),
    //         ], 500);
    //     }
    // }
    public function viewActivity($activityId)
    {
        try {
            $user = Auth::user();
            if ($user === null) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 400,
                    'message' => 'Unauthorized access',
                ], 400);
            }

            $activity = Activity::with(['activityType', 'dealer'])
                ->find($activityId);

            if (!$activity) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 404,
                    'message' => 'Activity not found',
                ], 404);
            }

            $activityEmployee = Employee::find($activity->employee_id);
            if (!$activityEmployee) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 404,
                    'message' => 'Activity creator not found',
                ], 404);
            }

            if (!in_array($user->employee_type_id, [1, 3])) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 403,
                    'message' => 'Forbidden: You do not have permission to view this activity.',
                ], 403);
            }

            if ($user->employee_type_id === 1 && $activity->employee_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 403,
                    'message' => 'Forbidden: You can only view your own activities.',
                ], 403);
            }

            if ($user->employee_type_id === 3 && !in_array($activityEmployee->employee_type_id, [1, 3])) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 403,
                    'message' => 'Forbidden: You can only view activities of SEs and your own.',
                ], 403);
            }

            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Activity retrieved successfully!',
                'data' => $activity,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function activityReportListing(Request $request)
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

            $salesExecutives = Employee::where('district', $employee->district)
                ->where('employee_type_id', 1) 
                ->get();

            if ($salesExecutives->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 404,
                    'message' => "No Sales Executives found in this district.",
                ], 404);
            }

            $month = $request->input('month', date('m'));
            $year = $request->input('year', date('Y'));

            $totalActivitiesForPeriod = 0;

            $reportData = $salesExecutives->map(function ($se) use ($month, $year, &$totalActivitiesForPeriod) {
                $activityCount = Activity::where('employee_id', $se->id)
                    ->whereYear('assigned_date', $year)
                    ->whereMonth('assigned_date', $month)
                    ->count();

                $totalActivitiesForPeriod += $activityCount;

                return [
                    'employee_id' => $se->id,
                    'employee_name' => $se->name,
                    'employee_code' => $se->employee_code,
                    'total_activities' => $activityCount,
                ];
            });

            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => "Activity report listing fetched successfully for $month/$year.",
                'data' => [
                    'total_activities_for_period' => $totalActivitiesForPeriod,
                    'activity_report' => $reportData,
                ],
                
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    public function activityReportDetails(Request $request, $employee_id)
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

            $salesExecutive = Employee::find($employee_id);
            if (!$salesExecutive || $salesExecutive->district !== $employee->district) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 404,
                    'message' => "Sales Executive not found in your district.",
                ], 404);
            }

            $month = $request->input('month', date('m'));
            $year = $request->input('year', date('Y'));

            $activities = Activity::where('employee_id', $salesExecutive->id)
                ->whereYear('assigned_date', $year)
                ->whereMonth('assigned_date', $month)
                ->with(['activityType', 'dealer']) 
                ->get();

            $totalActivities = $activities->count();

            $activityList = $activities->map(function ($activity) {
                return [
                    'activity_id' => $activity->id,
                    'activity_type' => $activity->activityType ? $activity->activityType->name : null,
                    'dealer_code' => $activity->dealer ? $activity->dealer->dealer_code : null,
                    'dealer_name' => $activity->dealer ? $activity->dealer->dealer_name : null,
                    'completed_date' => $activity->status === 'Pending' ? null : ($activity->completed_date ? $activity->completed_date->format('d/m/Y') : null),
                    'assigned_date' => $activity->status === 'Pending' ? $activity->assigned_date->format('d/m/Y') : null,
                    'status' => $activity->status,
                ];
            });

            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => "Activity report details fetched successfully for $month/$year.",
                'data' =>[
                    'employee_details' => [
                        'employee_id' => $salesExecutive->id,
                        'employee_name' => $salesExecutive->name,
                        'employee_code' => $salesExecutive->employee_code,
                        'email' => $salesExecutive->email,
                        'phone' => $salesExecutive->phone,
                        'total_activities' => $totalActivities,
                    ],
                    'activities' => $activityList,
                ],        
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => $e->getMessage(),
            ], 500);
        }
    }


    public function activityTypeIndex()
    {
        return view('sales.activity.activity-type-index'); 
    }

    public function activityTypeStore(Request $request)
    {
        $request->validate([
            'activity_name' => 'required|string|max:255',
            'status' => 'required|in:1,2',
        ]);

        $activity_type = ActivityType::create([
            'name' => $request->activity_name,
            'status' => $request->status,
        ]);

        return response()->json([
            'message' => 'Activity Type created successfully!',
            'activity_type' => $activity_type
        ], 201);
    }

    public function getActivityTypes(Request $request)
    {
        if ($request->ajax()) {
            $query = ActivityType::whereIn('status', ['1', '2'])
                    ->whereNull('deleted_at') 
                    ->orderBy('id', 'desc');
    
            return DataTables::of($query)
                ->addIndexColumn()
                ->make(true);
        }
        return abort(403, 'Unauthorized access');
    }

    public function editActivityType(ActivityType $activity_type)
    {
        return response()->json(['activity_type' => $activity_type]);
    }

    public function updateActivityType(Request $request, ActivityType $activity_type)
    {
        $request->validate([
            'activity_name' => 'required|string|max:255',
            'status' => 'required|in:1,2',
        ]);

        $activity_type->update([
            'name' => $request->activity_name,
            'status' => $request->status,
        ]);

        return response()->json([
            'message' => 'Activity Type updated successfully!',
            'activity_type' => $activity_type
        ]);
    }

    public function deleteActivityType(ActivityType $activity_type)
    {
        $activity_type->delete(); 

        return response()->json(['message' => 'Activity Type deleted successfully!']);
    }

    public function getEmployeesByDealer($dealer_id)
    {
        $dealer = Dealer::find($dealer_id);

        if (!$dealer) {
            return response()->json([], 404);
        }

        $employees = AssignRoute::where('id', $dealer->assigned_route_id)
            ->where('employee_type_id', 1) 
            ->with('employee:id,name')
            ->get()
            ->pluck('employee'); 

        return response()->json($employees);
    }

    public function getDealersByDistrict($district_id)
    {
        $dealers = Dealer::where('district_id', $district_id)
            ->select('id', 'dealer_name', 'dealer_code', 'assigned_route_id')
            ->get();

        return response()->json($dealers);
    }

    public function activityIndex()
    {
        $activityTypes = ActivityType::all();
        $districts = District::select('id', 'name')->get();
        return view('sales.activity.index', compact('activityTypes', 'districts'));
    }
    public function list(Request $request)
    {
        $query = Activity::with(['activityType', 'dealer', 'employee'])->whereNull('deleted_at');

        if ($request->activity_type) {
            $query->where('activity_type_id', $request->activity_type);
        }
        if ($request->dealer) {
            $query->whereHas('dealer', function ($q) use ($request) {
                $q->where('dealer_name', 'LIKE', "%{$request->dealer}%")
                ->orWhere('dealer_code', 'LIKE', "%{$request->dealer}%");
            });
        }
        if ($request->district) {
            $query->whereHas('employee', function ($q) use ($request) {
                $q->where('district_id', $request->district);
            });
        }
        if ($request->employee) {
            $query->where('employee_id', $request->employee);
        }
        if ($request->assigned_date) {
            $query->whereDate('assigned_date', $request->assigned_date);
        }
        if ($request->due_date) {
            $query->whereDate('due_date', $request->due_date);
        }

        $activities = $query->get(); 

        return DataTables::of($activities)
            ->addIndexColumn()
            ->addColumn('activity_type_name', function ($activity) {
                return optional($activity->activityType)->name ?? '-';
            })
            ->addColumn('dealer_name', function ($activity) {
                return optional($activity->dealer)->dealer_name 
                    ? optional($activity->dealer)->dealer_name . ' (' . optional($activity->dealer)->dealer_code . ')' 
                    : '-';
            })
            ->addColumn('employee_name', function ($activity) {
                return optional($activity->employee)->name ?? '-';
            })
            ->addColumn('status', function ($activity) {
                $status = $activity->status ?? 'Pending';
                $dueDate = $activity->due_date;
                $today = now()->toDateString();
            
                $statusBadge = match ($status) {
                    'Completed' => '<span class="badge bg-success text-white">Completed</span>',
                    'Pending' => '<span class="badge bg-warning text-dark">Pending</span>',
                    default => '<span class="badge bg-secondary text-white">' . $status . '</span>',
                };
            
                $overdueButton = '';
                if ($status == 'Pending' && $dueDate < $today) {
                    $overdueButton = '<span class="badge bg-danger text-white">Overdue</span>';
                }
            
                return $statusBadge . $overdueButton;
            })
            ->addColumn('action', function ($activity) {
                return '
                    <button class="btn btn-sm btn-info" onclick="handleAction(' . $activity->id . ', \'view\')" title="View">
                        <i class="fa fa-eye"></i>
                    </button>
                    <button class="btn btn-sm btn-warning" onclick="handleAction(' . $activity->id . ', \'edit\')" title="Edit">
                        <i class="fa fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-danger deleteActivity" data-id="' . $activity->id . '" title="Delete">
                        <i class="fa fa-trash"></i>
                    </button>
                ';
            })
            ->rawColumns(['action','status'])
            ->make(true);


    }

    public function store(Request $request)
    {
        $request->validate([
            'activity_type_id' => 'required|exists:activity_types,id',
            'dealer_id' => 'required|exists:dealers,id',
            'employee_id' => 'required|exists:employees,id',
            'assigned_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:assigned_date',
            'instruction' => 'required|string',
        ]);

        $activity = Activity::create([
            'activity_type_id' => $request->activity_type_id,
            'dealer_id' => $request->dealer_id,
            'employee_id' => $request->employee_id,
            'assigned_date' => $request->assigned_date,
            'due_date' => $request->due_date,
            'instructions' => $request->instruction,
            'status' => 'Pending',
        ]);

        return response()->json(['message' => 'Activity created successfully!', 'activity' => $activity]);
    }
    public function view($id)
    {
        $activity = Activity::with(['activityType', 'dealer', 'employee'])->find($id);
        if (!$activity) {
            return response()->json(['error' => 'Activity not found'], 404);
        }
    
        return response()->json(['activity' => $activity]);
    }
    public function edit($id)
    {
        $activity = Activity::with(['activityType', 'employee', 'dealer'])->find($id);

        if (!$activity) {
            return response()->json(['error' => 'Activity not found'], 404);
        }
    
        return response()->json(['activity' => $activity]);
    }

    public function update(Request $request, Activity $activity)
    {
        $request->validate([
            'activity_type_id' => 'required|exists:activity_types,id',
            'dealer_id' => 'required|exists:dealers,id',
            'employee_id' => 'required|exists:employees,id',
            'assigned_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:assigned_date',
            'instruction' => 'required|string',
        ]);

        $activity->update([
            'activity_type_id' => $request->activity_type_id,
            'dealer_id' => $request->dealer_id,
            'employee_id' => $request->employee_id,
            'assigned_date' => $request->assigned_date,
            'due_date' => $request->due_date,
            'instructions' => $request->instruction,
            'status' => 'Pending',
        ]);

        return response()->json(['message' => 'Activity updated successfully!', 'activity' => $activity]);
    }

    public function delete(Activity $activity)
    {
        $activity->delete(); 
        return response()->json(['message' => 'Activity deleted successfully!']);
    }

}
