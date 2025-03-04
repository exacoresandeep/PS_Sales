<?php

namespace App\Http\Controllers\Api;

use App\Models\Activity;
use App\Models\Employee;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\ActivityType;
use Illuminate\Support\Facades\Auth;
use Exception;

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
        return view('admin.activity.activity-type-index'); 
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
        // Server-side processing for large datasets
        if ($request->ajax()) {
            $activity_types = ActivityType::whereIn('status', [1,2])
                                          ->orderBy('id', 'desc')
                                          ->get();
            return response()->json(['data' => $activity_types]);
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

}
