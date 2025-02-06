<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Employee;
use Redirect;
use Illuminate\Support\Facades\Cookie;
class AdminController extends Controller
{
<<<<<<< HEAD
    public function loadContent($page)
    {
        $validPages = [
            'dashboard',
            'activity-management',
            'route-management',
            'target-management',
            '404'
        ];
        if (!in_array($page, $validPages)) {
            return response()->json(['error' => 'Page not found.'], 404);
        }

        switch ($page) {
            case 'group-approvals':
                return view('admin.group-approvals');
            case 'approved-groups':
                return view('admin.approved-groups');
            case 'rejected-groups':
                return view('admin.rejected-groups');
            case 'pincode':
                $states=State::all();
                return view('admin.pincode',compact('states'));
            case 'districts':
                $states=State::all();
                return view('admin.districts',compact('states'));
            default:
                return view('admin.' . $page);
        }
    }
    // Show Admin Login Page
=======
>>>>>>> 7800af683f257abf8356d28da42a79e4ebe2c0c1
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
        return view('admin.dashboard');
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
        return view('admin.target-management');
    }
}