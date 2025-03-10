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
    public function activity_management(Request $request)
    {
        return view('sales.activity-management');
    }
    public function route_management(Request $request)
    {
        return view('sales.route-management');
    }
    public function target_management(Request $request)
    {
        $employeeTypes = EmployeeType::all();

        return view('sales.target-management', ['employeeTypes' => $employeeTypes ?? collect([])]);
    }

}