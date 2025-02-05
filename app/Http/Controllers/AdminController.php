<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Employee;
use Redirect;
class AdminController extends Controller
{
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
        if (Auth::check()) {
            Auth::user()->tokens()->delete(); 
            Auth::logout(); 
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return redirect()->route('admin.login');
        }
    
        return redirect()->route('admin.login');

    }
    public function activities(Request $request)
    {
        return view('admin.activity-management');
    }
}