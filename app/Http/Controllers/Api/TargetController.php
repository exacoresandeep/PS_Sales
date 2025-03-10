<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Target;
use App\Models\Lead;
use App\Models\Order;
use App\Models\Employee;
use App\Models\District;
use App\Models\Regions;
use App\Models\EmployeeType;
use App\Models\RescheduledRoute;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Log;
use Exception;

class TargetController extends Controller
{
    public function index()
    {
        $targets = Target::all(); 
        $employeeTypes = EmployeeType::all();
        return view('sales.target.index', compact('targets','employeeTypes'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'employee_type' => 'required|exists:employee_types,id',
            'employee_id' => 'required|exists:employees,id',
            'year' => 'required|numeric',
            'month' => 'required|string',
            'unique_lead' => 'required|integer|min:0',
            'customer_visit' => 'required|integer|min:0',
            'aashiyana' => 'required|integer|min:0',
            'order_quantity' => 'required|integer|min:0'
        ]);

        $target = Target::create([
            'employee_type_id' => $request->employee_type,
            'employee_id' => $request->employee_id,
            'year' => $request->year,
            'month' => $request->month,
            'unique_lead' => $request->unique_lead,
            'customer_visit' => $request->customer_visit,
            'aashiyana' => $request->aashiyana,
            'order_quantity' => $request->order_quantity,
            'status' => 1
        ]);

        return response()->json(['message' => 'Target created successfully!', 'target' => $target], 200);
    }
    
    // each employee target
    // public function getTargets(Request $request)
    // {

    //     // $month = $request->month != "" ? $request->month : Carbon::now()->month;
    //     $monthNumber = $request->month ?? Carbon::now()->month;
    //     $month = $request->month ? Carbon::createFromDate(null, $request->month, 1)->format('F') : Carbon::now()->format('F');
    //     $year  = $request->year  != "" ? $request->year : Carbon::now()->year;
    //     $employeeId = Auth::id();

    //     $targetQuery = Target::where('employee_id', $employeeId)
    //                         ->where('month', $month)
    //                         ->where('year', $year);
    //     $target = $targetQuery->first();
    //     $target = $target ? $target->toArray() : null;
    //     $uniqueLeadsQuery = Lead::where('created_by', $employeeId)
    //                             ->whereYear('created_at', $year)
    //                             ->whereMonth('created_at', $monthNumber);
    //     $uniqueLeads = $uniqueLeadsQuery->count();

    //     $customerVisitCount = RescheduledRoute::where('employee_id', $employeeId)
    //         ->whereYear('assign_date', $year)
    //         ->whereMonth('assign_date', $monthNumber)
    //         ->get()
    //         ->sum(function ($route) {
    //             $customers = collect(json_decode($route->customers ?? '[]', true));
    //             return $customers->where('scheduled', true)->where('status', 'Completed')->count();
    //         });

    //     $aashiyanaQuery = Order::where('created_by', $employeeId)
    //                         ->whereYear('created_at', $year)
    //                         ->whereMonth('created_at', $monthNumber)
    //                         ->where('payment_terms_id', 3);
    //     $aashiyanaCount = $aashiyanaQuery->count();

    //     $ordersQuery = Order::where('created_by', $employeeId)
    //                         ->whereYear('created_at', $year)
    //                         ->whereMonth('created_at', $monthNumber)
    //                         ->where('status', 'Delivered');
    //     $orders = $ordersQuery->pluck('id');

    //     $achievedOrderQuantityQuery = DB::table('order_items')
    //                                     ->whereIn('order_id', $orders);
    //     $achievedOrderQuantity = $achievedOrderQuantityQuery->sum('total_quantity');

    //     $response = [
    //         'target' => $target,
    //         'achieved' => [
    //             'unique_leads' => $uniqueLeads,
    //             'customer_visit' => $customerVisitCount, 
    //             'aashiyana' => $aashiyanaCount,
    //             'order_quantity' => (int) $achievedOrderQuantity,
    //         ],
    //     ];

    //     return response()->json([
    //         'success' => true,
    //         'statusCode' => 200,
    //         'message' => 'Target data retrieved successfully.',
    //         'data' => $response,
    //     ], 200);
    // }

    // new target function 
    public function getTargets(Request $request)
    {
        try {
            $monthNumber = $request->month ?? Carbon::now()->month;
            $month = $request->month ? Carbon::createFromDate(null, $request->month, 1)->format('F') : Carbon::now()->format('F');
            $year = $request->year ?? Carbon::now()->year;
            
            $employeeId = $request->employee_id ?? Auth::id();
    
            $employee = Employee::find($employeeId);
            if (!$employee) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 404,
                    'message' => "Employee not found.",
                ], 404);
            }
    
            $target = Target::where('employee_id', $employeeId)
                            ->where('month', $month)
                            ->where('year', $year)
                            ->first();
            $target = $target ? $target->toArray() : null;
    
            $uniqueLeads = Lead::where('created_by', $employeeId)
                                ->whereYear('created_at', $year)
                                ->whereMonth('created_at', $monthNumber)
                                ->count();
    
            $customerVisitCount = RescheduledRoute::where('employee_id', $employeeId)
                ->whereYear('assign_date', $year)
                ->whereMonth('assign_date', $monthNumber)
                ->get()
                ->sum(function ($route) {
                    $customers = collect(json_decode($route->customers ?? '[]', true));
                    return $customers->where('scheduled', true)->where('status', 'Completed')->count();
                });
    
            $aashiyanaCount = Order::where('created_by', $employeeId)
                                ->whereYear('created_at', $year)
                                ->whereMonth('created_at', $monthNumber)
                                ->where('payment_terms_id', 3)
                                ->count();
    
            $orders = Order::where('created_by', $employeeId)
                            ->whereYear('created_at', $year)
                            ->whereMonth('created_at', $monthNumber)
                            ->where('status', 'Delivered')
                            ->pluck('id');
    
            $achievedOrderQuantity = DB::table('order_items')
                                        ->whereIn('order_id', $orders)
                                        ->sum('total_quantity');
    
            $response = [
                'employee' =>[
                    'employee_id' => $employeeId,
                    'employee_name' => $employee->name,
                    'employee_type_id' => $employee->employee_type_id,
                ],
                'target' => $target,
                'achieved' => [
                    'unique_leads' => $uniqueLeads,
                    'customer_visit' => $customerVisitCount, 
                    'aashiyana' => $aashiyanaCount,
                    'order_quantity' => (int) $achievedOrderQuantity,
                ],
            ];
    
            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Target data retrieved successfully.',
                'data' => $response,
            ], 200);
        
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    
    // common function 
    // public function getTargets(Request $request)
    // {
    //     try {
    //         $monthNumber = $request->month ?? Carbon::now()->month;
    //         $month = $request->month ? Carbon::createFromDate(null, $request->month, 1)->format('F') : Carbon::now()->format('F');
    //         $year = $request->year ?? Carbon::now()->year;

    //         $employee = Auth::user();

    //         if (!$employee) {
    //             return response()->json([
    //                 'success' => false,
    //                 'statusCode' => 401,
    //                 'message' => "User not authenticated",
    //             ], 401);
    //         }

    //         // Initialize employee ID list
    //         $employeeIds = [$employee->id]; // Start with own target

    //         // Define access levels based on employee role
    //         if ($employee->employee_type_id == 3) { // DSM
    //             $employeeIds = Employee::where('district_id', $employee->district_id)
    //                 ->where('employee_type_id', 1) // Get all SEs in the district
    //                 ->pluck('id')->toArray();
    //             array_unshift($employeeIds, $employee->id); // Add DSM's own ID

    //         } elseif ($employee->employee_type_id == 4) { // RSM
    //             $region = Regions::whereHas('districts', function ($query) use ($employee) {
    //                 $query->where('id', $employee->district_id);
    //             })->first();

    //             if (!$region) {
    //                 return response()->json([
    //                     'success' => false,
    //                     'statusCode' => 404,
    //                     'message' => "Region not found for the employee's district.",
    //                 ], 404);
    //             }

    //             $districtsInRegion = District::where('regions_id', $region->id)->pluck('id')->toArray();
    //             $employeeIds = Employee::whereIn('district_id', $districtsInRegion)
    //                 ->whereIn('employee_type_id', [2, 3]) // Get all ASOs & DSMs in the region
    //                 ->pluck('id')->toArray();
    //             array_unshift($employeeIds, $employee->id); // Add RSM's own ID

    //         } elseif ($employee->employee_type_id == 5) { // SM
    //             $employeeIds = Employee::pluck('id')->toArray(); // Get all employees
    //             array_unshift($employeeIds, $employee->id); // Add SM's own ID
    //         }

    //         // Fetch all targets based on employee(s)
    //         $targets = Target::whereIn('employee_id', $employeeIds)
    //             ->where('month', $month)
    //             ->where('year', $year)
    //             ->get();

    //         if ($targets->isEmpty()) {
    //             return response()->json([
    //                 'success' => false,
    //                 'statusCode' => 404,
    //                 'message' => "No targets found for the selected period.",
    //             ], 404);
    //         }

    //         // Fetch achieved values for each target
    //         $responseData = [];

    //         foreach ($targets as $target) {
    //             $employeeId = $target->employee_id;
    //             $targetEmployee = Employee::find($employeeId);
    //             $uniqueLeads = Lead::where('created_by', $employeeId)
    //                 ->whereYear('created_at', $year)
    //                 ->whereMonth('created_at', $monthNumber)
    //                 ->count();

    //             $customerVisitCount = RescheduledRoute::where('employee_id', $employeeId)
    //                 ->whereYear('assign_date', $year)
    //                 ->whereMonth('assign_date', $monthNumber)
    //                 ->get()
    //                 ->sum(function ($route) {
    //                     $customers = collect(json_decode($route->customers ?? '[]', true));
    //                     return $customers->where('scheduled', true)->where('status', 'Completed')->count();
    //                 });

    //             $aashiyanaCount = Order::where('created_by', $employeeId)
    //                 ->whereYear('created_at', $year)
    //                 ->whereMonth('created_at', $monthNumber)
    //                 ->where('payment_terms_id', 3)
    //                 ->count();

    //             $orders = Order::where('created_by', $employeeId)
    //                 ->whereYear('created_at', $year)
    //                 ->whereMonth('created_at', $monthNumber)
    //                 ->where('status', 'Delivered')
    //                 ->pluck('id');

    //             $achievedOrderQuantity = DB::table('order_items')
    //                 ->whereIn('order_id', $orders)
    //                 ->sum('total_quantity');

    //             // Store data in response array
    //             $responseData[] = [
    //                 'employee_type_id' => $targetEmployee->employee_type_id ?? null,
    //                 'target' => $target,
    //                 'achieved' => [
    //                     'unique_leads' => $uniqueLeads,
    //                     'customer_visit' => $customerVisitCount,
    //                     'aashiyana' => $aashiyanaCount,
    //                     'order_quantity' => (int) $achievedOrderQuantity,
    //                 ]
    //             ];
    //         }
    //         return response()->json([
    //             'success' => true,
    //             'statusCode' => 200,
    //             'message' => 'Target data retrieved successfully.',
    //             'data' => $responseData,
    //         ], 200);

    //     } catch (Exception $e) {
    //         return response()->json([
    //             'success' => false,
    //             'statusCode' => 500,
    //             'message' => $e->getMessage(),
    //         ], 500);
    //     }
    // }


    public function targetList(Request $request)
    {
        $query = Target::with(['employee.employeeType'])->where('status', '1')->withTrashed();

        if ($request->has('employee_type') && !empty($request->employee_type)) {
            $query->whereHas('employee', function ($q) use ($request) {
                $q->where('employee_type_id', $request->employee_type);
            });
        }

        if ($request->has('employee_id') && !empty($request->employee_id)) {
            $query->where('employee_id', $request->employee_id);
        }

        if ($request->has('year') && !empty($request->year)) {
            $query->where('year', $request->year);
        }

        if ($request->has('month') && !empty($request->month)) {
            $query->where('month', $request->month);
        }

        return DataTables::of($query)
            ->filter(function ($query) use ($request) {
                if (!empty($request->search['value'])) {
                    $searchValue = $request->search['value'];
                    $query->whereHas('employee', function ($q) use ($searchValue) {
                        $q->where('name', 'like', "%{$searchValue}%");
                    })
                    ->orWhereHas('employee.employeeType', function ($q) use ($searchValue) {
                        $q->where('type_name', 'like', "%{$searchValue}%");
                    })
                    ->orWhere('year', 'like', "%{$searchValue}%")
                    ->orWhere('month', 'like', "%{$searchValue}%")
                    ->orWhere('unique_lead', 'like', "%{$searchValue}%")
                    ->orWhere('customer_visit', 'like', "%{$searchValue}%")
                    ->orWhere('aashiyana', 'like', "%{$searchValue}%")
                    ->orWhere('order_quantity', 'like', "%{$searchValue}%");
                }
            })
            ->addIndexColumn() 
            ->addColumn('employee_type', function ($target) {
                return optional($target->employee->employeeType)->type_name ?? '-';
            })
            ->addColumn('employee_name', function ($target) {
                return optional($target->employee)->name ?? '-';
            })
            ->addColumn('year', function ($target) {
                return $target->year ?? '-';
            })
            ->addColumn('month', function ($target) {
                return $target->month ?? '-';
            })
            ->addColumn('unique_lead', function ($target) {
                return $target->unique_lead ?? '0';
            })
            ->addColumn('customer_visit', function ($target) {
                return $target->customer_visit ?? '0';
            })
            ->addColumn('aashiyana', function ($target) {
                return $target->aashiyana ?? '0';
            })
            ->addColumn('order_quantity', function ($target) {
                return $target->order_quantity ?? '0';
            })
            ->addColumn('action', function ($target) {
                return '
                    <button class="btn btn-sm btn-info" onclick="handleAction(' . $target->id . ', \'view\')" title="View">
                        <i class="fa fa-eye"></i>
                    </button>
                    <button class="btn btn-sm btn-warning" onclick="handleAction(' . $target->id . ', \'edit\')" title="Edit">
                        <i class="fa fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-danger" onclick="deleteTarget(' . $target->id . ')" title="Delete">
                        <i class="fa fa-trash"></i>
                    </button>
                ';
            })
            ->rawColumns(['action'])
            ->make(true);
    }


    public function view($id)
    {
        $target = Target::join('employees', 'employees.id', '=', 'Target.employee_id')
            ->join('employee_types', 'employees.employee_type_id', '=', 'employee_types.id')
            ->where('Target.id', $id)
            ->select(
                'Target.id',
                'Target.created_at as from_date',
                'Target.*',
                'employees.name as employee_name',
                'employee_types.type_name as employee_type',
                DB::raw("CONCAT(Target.month, '-', Target.year) as to_date")
            )
            ->first(); // Fetch a single record

        if (!$target) {
            return response()->json(['success' => false, 'message' => 'Target not found.'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => view('sales.target.view', compact('target'))->render()
        ]);
    }
    public function update(Request $request)
    {
        $target = Target::find($request->id);

        if (!$target) {
            return response()->json(['error' => 'Target not found'], 404);
        }

        $target->update([
            'employee_id' => $request->employee_id,
            'year' => $request->year,
            'month' => $request->month,
            'unique_lead' => $request->unique_lead,
            'customer_visit' => $request->customer_visit,
            'aashiyana' => $request->aashiyana,
            'order_quantity' => $request->order_quantity,
        ]);

        return response()->json(['message' => 'Target updated successfully']);
    }
    public function viewTargets($id)
    {
        if (!$id) {
            return response()->json(['error' => 'Missing target ID.'], 400);
        }

        $target = Target::with('employee.employeeType')->find($id);
        if (!$target) {
            return response()->json(['error' => 'Target not found.'], 404);
        }

        $viewContent = view('sales.target.modal-view', compact('target'))->render();

        return response()->json([
            'target' => $target,
            'viewContent' => $viewContent
        ]);
    }

    public function destroy($id)
    {
        $target = Target::findOrFail($id);
        $target->status = '0';
        $target->save();
        $target->delete();

        return response()->json(['success' => true, 'message' => 'Target deleted successfully!']);
    }


}
