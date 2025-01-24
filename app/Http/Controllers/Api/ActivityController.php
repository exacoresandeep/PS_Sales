<?php

namespace App\Http\Controllers\Api;

use App\Models\Activity;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

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

            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Activities retrieved successfully!',
                'data' => $activities,
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
                'attachments' => 'required|array',
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

            if ($activity->employee_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 400,
                    'message' => 'Forbidden: You can only view your own activities.',
                ], 400);
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
}
