<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Employee;
use App\Models\Target;
use App\Models\EmployeeType;
use Redirect;
use Illuminate\Support\Facades\Cookie;
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
        return view('admin.login');
    }

    public function doLogin(Request $request)
    {
        $request->validate([
            'employee_code' => 'required|string',
            'password' => 'required',
        ]);
        $employee = Employee::where('employee_code', $request->employee_code)->first();
        
        if ($employee && password_verify($request->password, $employee->password)) {
            Auth::login($employee);
            return redirect()->route('admin.dashboard');
        }else{
        }

        return back()->with('error', 'Invalid Employee Code or Password');
    }

    public function dashboard()
    {
        return view('layouts.app');
    }

    public function logout(Request $request)
    {
        Cookie::queue(Cookie::forget('selectedLink'));
        if (Auth::check()) {
            Auth::user()->tokens()->delete(); 
            Auth::logout(); 
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return redirect()->route('admin.login');
        }
    
        return redirect()->route('admin.login');

    }
    public function activity_management(Request $request)
    {
        return view('admin.activity-management');
    }
    public function route_management(Request $request)
    {
        return view('admin.route-management');
    }
    public function target_management(Request $request)
{
    $employeeTypes = EmployeeType::all();

    // Force an empty array if it's null
    return view('admin.target-management', ['employeeTypes' => $employeeTypes ?? collect([])]);
}

}