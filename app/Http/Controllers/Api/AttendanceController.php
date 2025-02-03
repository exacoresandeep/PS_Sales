<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AttendanceController extends Controller
{
    public function punchIn(Request $request)
    {
        $employeeId = Auth::id();
        $date = Carbon::today()->format('Y-m-d');

        $attendance = Attendance::where('employee_id', $employeeId)
                                ->where('date', $date)
                                ->first();

        if (!$attendance) {
            $attendance = Attendance::create([
                'employee_id' => $employeeId,
                'date' => $date,
                'punch_in' => Carbon::now('Asia/Kolkata')->format('H:i:s'),
                'punch_out' => null,
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
            ]);

            // Set initial total active hours as 00:00:00
            $totalActiveHours = '00:00:00';
        } elseif ($attendance->punch_out !== null) {
            $attendance->update([
                'punch_out' => null,
            ]);

            $totalActiveHours = '00:00:00';
        } else {
            return response()->json([
                'success' => false,
                'statusCode' => 400,
                'message' => 'Already punched in. Punch out first before punching in again.',
            ]);
        }

        return response()->json([
            'success' => true,
            'statusCode' => 200,
            'message' => 'Punched in successfully',
            'data' => [
                'employee_id' => $attendance->employee_id,
                'date' => $attendance->date,
                'punch_in' => $attendance->punch_in,
                'punch_out' => $attendance->punch_out,
                'latitude' => $attendance->latitude,
                'longitude' => $attendance->longitude,
                'total_active_hours' => $totalActiveHours,
            ]
        ]);
    }

    public function punchOut(Request $request)
    {
        $employeeId = Auth::id();
        $date = Carbon::today()->format('Y-m-d');

        $attendance = Attendance::where('employee_id', $employeeId)
                                ->where('date', $date)
                                ->whereNotNull('punch_in')
                                ->first();

        if (!$attendance) {
            return response()->json([
                'success' => false,
                'statusCode' => 400,
                'message' => 'No punch-in record found for today.',
            ]);
        }

        if ($attendance->punch_out !== null) {
            return response()->json([
                'success' => false,
                'statusCode' => 400,
                'message' => 'Already punched out. Punch in first before punching out again.',
            ]);
        }

        $punchOutTime = Carbon::now('Asia/Kolkata');

        // Accept total_active_hours from the frontend
        $totalActiveHours = $request->input('total_active_hours'); 

        if (!$totalActiveHours) {
            return response()->json([
                'success' => false,
                'statusCode' => 400,
                'message' => 'Total active hours not provided.',
            ]);
        }

        // Store punch-out time & total active hours received from frontend
        $attendance->update([
            'punch_out' => $punchOutTime->format('H:i:s'),
            'total_active_hours' => $totalActiveHours, // Store the value directly from frontend
        ]);

        return response()->json([
            'success' => true,
            'statusCode' => 200,
            'message' => 'Punched out successfully',
            'data' => [
                'employee_id' => $attendance->employee_id,
                'date' => $attendance->date,
                'punch_in' => $attendance->punch_in,
                'punch_out' => $attendance->punch_out,
                'latitude' => $attendance->latitude,
                'longitude' => $attendance->longitude,
                'total_active_hours' => $attendance->total_active_hours, // Using the value passed from frontend
            ]
        ]);
    }

    public function getTodayAttendance()
    {
        $employeeId = Auth::id();
        $date = Carbon::today()->format('Y-m-d');

        $attendance = Attendance::where('employee_id', $employeeId)
                                ->where('date', $date)
                                ->first();

        if (!$attendance) {
            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'No attendance record found for today.',
                'data' => [
                    'punch_in' => null,
                    'punch_out' => null,
                    'total_active_hours' => '00:00:00',
                ]
            ]);
        }

        return response()->json([
            'success' => true,
            'statusCode' => 200,
            'message' => 'Attendance details retrieved successfully',
            'data' => [
                'punch_in' => $attendance->punch_in,
                'punch_out' => $attendance->punch_out,
                'total_active_hours' => $attendance->total_active_hours,
            ]
        ]);
    }
}
