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
        $latitude = $request->latitude;
        $longitude = $request->longitude;

        $attendance = Attendance::where('employee_id', $employeeId)
                                ->where('date', $date)
                                ->first();

        if ($attendance) {
            return response()->json([
                'success' => false,
                'statusCode' => 400,
                'message' => 'Already punched in for today',
            ]);
        }

        $attendance = Attendance::create([
            'employee_id' => $employeeId,
            'date' => $date,
            'punch_in' => Carbon::now('Asia/Kolkata')->format('H:i:s'),
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
                'message' => 'No punch-in record found for today',
                'data' => null
            ]);
        }

        $attendance->update([
            'punch_out' => Carbon::now('Asia/Kolkata')->format('H:i:s'),
        ]);

        return response()->json([
            'success' => true,
            'statusCode' => 200,
            'message' => 'Punched out successfully',
            'data' => $attendance
        ]);
    }

   
    public function autoPunchOut()
    {
        $date = Carbon::today()->format('Y-m-d');
        $currentTime = Carbon::now();

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
        ]);
    }
}
