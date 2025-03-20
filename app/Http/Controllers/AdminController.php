<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Employee;
use App\Models\EmployeeType;
use App\Models\District;
use Yajra\DataTables\Facades\DataTables;
use Redirect;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Hash;
use PhpOffice\PhpSpreadsheet\IOFactory;


class AdminController extends Controller
{

    // public function loadContent($page)
    // {
    //     $validPages = [
    //         'dashboard',
    //         'activity-management',
    //         'route-management',
    //         'target-management',
    //         '404'
    //     ];
    //     if (!in_array($page, $validPages)) {
    //         return response()->json(['error' => 'Page not found.'], 404);
    //     }

    //     switch ($page) {
    //         case 'target-management': 
    //             $targets = Target::all();
    //             return view('admin.target.index', compact('targets'));
    //         case 'group-approvals':
    //             return view('admin.group-approvals');
    //         case 'approved-groups':
    //             return view('admin.approved-groups');
    //         case 'rejected-groups':
    //             return view('admin.rejected-groups');
    //         case 'pincode':
    //             $states=State::all();
    //             return view('admin.pincode',compact('states'));
    //         case 'districts':
    //             $states=State::all();
    //             return view('admin.districts',compact('states'));
    //         default:
    //             return view('admin.' . $page);
    //     }
    // }
    
    public function login()
    {
        return view('login');
    }

    public function doLogin(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $credentials = $request->only('username', 'password');
        if (Auth::attempt($credentials)) {
            $user = Auth::user();

            switch ($user->role_id) {
                case 1: // Super Admin
                    return redirect()->route('admin.dashboard');
                case 2: // Sales
                    return redirect()->route('sales.dashboard');
                case 3: // Accounts
                    return redirect()->route('accounts.dashboard');
                default:
                    Auth::logout();
                    return back()->with('error', 'Unauthorized role access');
            }
        }

        return back()->with('error', 'Invalid Username or Password');
    }

    public function dashboard()
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login');
        }

        switch ($user->role_id) {
            case 1: return view('admin.dashboard', compact('user')); 
            case 2: return view('sales.dashboard', compact('user')); 
            case 3: return view('accounts.dashboard', compact('user')); 
            default:
                Auth::logout();
                return redirect()->route('login')->with('error', 'Unauthorized role access');
        }
    }

    public function logout(Request $request)
    {
        Cookie::queue(Cookie::forget('selectedLink'));

        if (Auth::check()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return redirect()->route('login');
    }
    public function employeeIndex()
    {
        return view('admin.users.employee-index');
    }
    public function employeeList(Request $request)
    {
        $employees = Employee::with('reportingManager') // Load the reporting manager relationship
            ->select([
                'id', 'employee_code', 'name', 'email', 'phone', 
                'district', 'area', 'designation', 'reporting_manager', 
                'address', 'emergency_contact'
            ]);

        return DataTables::of($employees)
            ->addColumn('reporting_manager', function ($employee) {
                return $employee->reportingManager ? $employee->reportingManager->name : 'N/A';
            })
            ->make(true);
    }
    public function importEmployees(Request $request)
    {
        if (!$request->hasFile('file')) {
            return response()->json(['message' => 'No file uploaded'], 400);
        }
    
        $file = $request->file('file');
        $extension = $file->getClientOriginalExtension();
    
        // Allowed file types
        if (!in_array($extension, ['csv', 'xlsx', 'xls'])) {
            return response()->json(['message' => 'Invalid file format. Upload CSV or Excel file.'], 400);
        }
    
        try {
            DB::transaction(function () use ($file) {
                $spreadsheet = IOFactory::load($file->getPathname());
                $sheet = $spreadsheet->getActiveSheet();
                $rows = $sheet->toArray();
    
                // Skip first row (Header)
                unset($rows[0]);
    
                foreach ($rows as $row) {
                    $employeeCode = $row[0]; // Employee ID
                    $name = $row[1];
                    $email = $row[2];
                    $phone = $row[3];
                    $districtName = $row[4]; // District Name from CSV
                    $area = $row[5];
                    $designation = trim($row[6]);
                    $reportingManagerName = $row[7];
                    $address = $row[8];
                    $emergencyContact = $row[9];

                    if (empty($designation)) {
                        continue; // Skip this row
                    }
                    // Check if employee already exists
                    if (Employee::where('employee_code', $employeeCode)->exists()) {
                        continue; // Skip existing employee
                    }
                    $district = District::where('name', $districtName)->first();
                    $districtId = $district ? $district->id : null;
    
                    // Check if Designation Exists in EmployeeTypes
                    $employeeType = EmployeeType::firstOrCreate(
                        ['type_name' => $designation],
                        ['created_at' => now(), 'updated_at' => now()]
                    );
                   
                    // Get Reporting Manager ID from Name
                    $reportingManager = Employee::where('name', $reportingManagerName)->first();
                    $reportingManagerId = $reportingManager ? $reportingManager->id : null;
    
                    // Generate Password (first 3 letters of name + employee code)
                    $passwordString = strtoupper(substr($name, 0, 3)) . $employeeCode;
                    
                    $hashedPassword = Hash::make($passwordString);
    
                    // Insert Employee
                    Employee::create([
                        'employee_code' => $employeeCode,
                        'name' => $name,
                        'email' => $email,
                        'phone' => $phone,
                        'district_id' => $districtId, // Store district ID if found
                        'district' => $districtName,
                        'area' => $area,
                        'designation' => $designation,
                        'employee_type_id' => $employeeType->id,
                        'reporting_manager' => $reportingManagerId,
                        'address' => $address,
                        'emergency_contact' => $emergencyContact,
                        'password' => $hashedPassword, // Store encrypted password
                        'status' => '1',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            });
    
            return response()->json(['message' => 'Employees imported successfully'], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error: ' . $e->getMessage()], 500);
        }
    }
    

}