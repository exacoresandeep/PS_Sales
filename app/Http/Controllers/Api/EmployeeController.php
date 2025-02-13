<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
// use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class EmployeeController extends Controller
{
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'employee_code' => 'required|string|unique:employees,employee_code',
                'name' => 'required|string',
                'designation' => 'required|string',
                'area' => 'nullable|string',
                'email' => 'required|email|unique:employees,email',
                'phone' => 'required|string',
                'password' => 'required|string',
                'address' => 'nullable|string',
                'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
                'emergency_contact' => 'nullable|string',
                'employee_type_id' => 'required|exists:employee_types,id',
                'reporting_manager' => 'nullable|exists:employees,id',
            ]);

            if ($request->hasFile('photo')) {
                $photoPath = $request->file('photo')->store('photos', 'public');
            } else {
                $photoPath = null; 
            }

            $employee = Employee::create([
                'employee_code' => $validated['employee_code'],
                'name' => $validated['name'],
                'designation' => $validated['designation'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'password' => Hash::make($validated['password']),
                'address' => $validated['address'],
                'photo' => $photoPath,
                'emergency_contact' => $validated['emergency_contact'],
                'employee_type_id' => $validated['employee_type_id'],
            ]);

            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Employee created successfully',
                'data' => $employee,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    public function show(Request $request)
    {
        try {
            $employee = $request->user();
            $employee = Employee::with('employeeType', 'reportingManager:id,name')->where('id', $employee->id)->first();

            if (!$employee) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 400,
                    'message' => 'Employee not found',
                ], 400);
            }

            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Employee details retrieved successfully',
                'data' => $employee,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    public function getEmployeesByType($employeeTypeId)
    {
        $employees = Employee::where('employee_type_id', $employeeTypeId)->get();
        return response()->json($employees);
    }

}
