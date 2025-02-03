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

    $totalActiveHours = $this->calculateTotalActiveHours($employeeId, $date);

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
        $punchInTime = Carbon::parse($attendance->punch_in);

        $attendance->update([
            'punch_out' => $punchOutTime->format('H:i:s'),
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
                'total_active_hours' => $this->calculateTotalActiveHours($employeeId, $date),
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
                'total_active_hours' => $this->calculateTotalActiveHours($employeeId, $date),
            ]
        ]);
    }


    private function calculateTotalActiveHours($employeeId, $date)
    {
        $attendances = Attendance::where('employee_id', $employeeId)
                                ->where('date', $date)
                                ->whereNotNull('punch_out')
                                ->get();

        if ($attendances->isEmpty()) {
            return '00:00:00';
        }

        $totalSeconds = 0;

        foreach ($attendances as $attendance) {
            $punchIn = Carbon::parse($attendance->punch_in);
            $punchOut = Carbon::parse($attendance->punch_out);
            $totalSeconds += $punchIn->diffInSeconds($punchOut);
        }

        return gmdate("H:i:s", $totalSeconds);
    }

}
