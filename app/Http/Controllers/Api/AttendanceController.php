<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AttendanceController extends Controller
{
    /**
     * Mark the attendance for punch-in.
     */
    public function punchIn(Request $request)
    {
        $employeeId = Auth::id();
        $date = Carbon::today()->format('Y-m-d');
        $latitude = $request->latitude;
        $longitude = $request->longitude;

        // Check if already punched in
        $attendance = Attendance::where('employee_id', $employeeId)
                                ->where('date', $date)
                                ->first();

        if ($attendance) {
            return response()->json([
                'success' => false,
                'statusCode' => 400,
                'message' => 'Already punched in for today',
                'data' => null
            ]);
        }

        // Create a new punch-in entry
        $attendance = Attendance::create([
            'employee_id' => $employeeId,
            'date' => $date,
            'punch_in' => Carbon::now()->format('H:i:s'),
            'latitude' => $latitude,
            'longitude' => $longitude,
        ]);

        return response()->json([
            'success' => true,
            'statusCode' => 200,
            'message' => 'Punched in successfully',
            'data' => $attendance
        ]);
    }

    /**
     * Mark the attendance for punch-out.
     */
    public function punchOut(Request $request)
    {
        $employeeId = Auth::id();
        $date = Carbon::today()->format('Y-m-d');
        
        // Find the punch-in record for today
        $attendance = Attendance::where('employee_id', $employeeId)
                                ->where('date', $date)
                                ->whereNotNull('punch_in')
                                ->first();

        if (!$attendance) {
            return response()->json([
                'success' => false,
                'statusCode' => 400,
                'message' => 'No punch-in record found for today',
                'data' => null
            ]);
        }

        // Update the punch-out time
        $attendance->update([
            'punch_out' => Carbon::now()->format('H:i:s'),
        ]);

        return response()->json([
            'success' => true,
            'statusCode' => 200,
            'message' => 'Punched out successfully',
            'data' => $attendance
        ]);
    }

    /**
     * Automatically punch out at 11:59 PM if no punch-out is recorded.
     */
    public function autoPunchOut()
    {
        $date = Carbon::today()->format('Y-m-d');
        $currentTime = Carbon::now();

        // Check if the time is 11:59 PM
        if ($currentTime->format('H:i') === '23:59') {
            $attendances = Attendance::whereNull('punch_out')
                                     ->whereDate('date', $date)
                                     ->get();

            foreach ($attendances as $attendance) {
                $attendance->update([
                    'punch_out' => '23:59',
                ]);
            }

            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Auto punch-out executed if necessary',
                'data' => $attendances
            ]);
        }

        return response()->json([
            'success' => true,
            'statusCode' => 200,
            'message' => 'No action required',
            'data' => null
        ]);
    }
}
