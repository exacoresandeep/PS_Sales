<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Target;
use App\Models\Lead;
use App\Models\Order;
use App\Models\EmployeeType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Log;

class TargetController extends Controller
{
    public function index()
    {
        $targets = Target::all(); 
        $employeeTypes = EmployeeType::all();
        return view('admin.target.index', compact('targets','employeeTypes'));
    }

    // public function create()
    // {
    //     $employeeTypes = DB::table('employee_types')->get();
    //     $employees = DB::table('employees')->get();
    //     return view('target-management', compact('employeeTypes', 'employees'));
    // }
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
            'order_quantity' => $request->order_quantity
        ]);

        return response()->json(['message' => 'Target created successfully!', 'target' => $target], 200);
    }
    public function getTargets(Request $request)
    {
        $month = $request->month != "" ? $request->month : Carbon::now()->month;
        $year  = $request->year  != "" ? $request->year : Carbon::now()->year;
        $employeeId = Auth::id();
       
        // DB::enableQueryLog();
        $targetQuery = Target::where('employee_id', $employeeId)
                            ->where('month', $month)
                            ->where('year', $year);
        $target = $targetQuery->first();
        $target = $target ? $target->toArray() : null;

        $uniqueLeadsQuery = Lead::where('created_by', $employeeId)
                                ->whereYear('created_at', $year)
                                ->whereMonth('created_at', $month);
            // dd($uniqueLeadsQuery->toSql(), $uniqueLeadsQuery->getBindings());
        $uniqueLeads = $uniqueLeadsQuery->count();
        $aashiyanaQuery = Order::where('created_by', $employeeId)
                            ->whereYear('created_at', $year)
                            ->whereMonth('created_at', $month)
                            ->where('payment_terms_id', 3);
        // dd($aashiyanaQuery->toSql(), $aashiyanaQuery->getBindings());

        $aashiyanaCount = $aashiyanaQuery->count();

        $ordersQuery = Order::where('created_by', $employeeId)
                            ->whereYear('created_at', $year)
                            ->whereMonth('created_at', $month)
                            ->where('status', 'Accepted');

        $orders = $ordersQuery->pluck('id');

        $achievedOrderQuantityQuery = DB::table('order_items')
                                        ->whereIn('order_id', $orders);

        $achievedOrderQuantity = $achievedOrderQuantityQuery->sum('total_quantity');
        $response = [
            'target' => $target,
            'achieved' => [
                'unique_leads' => $uniqueLeads,
                'customer_visit' => $customer_visit ?? 0, 
                'aashiyana' => $aashiyanaCount,
                'order_quantity' => $achievedOrderQuantity,
            ],
        ];
        return response()->json([
            'success' => true,
            'statusCode' => 200,
            'message' => 'Target data retrieved successfully.',
            'data' => $response,
        ], 200);
    }


    public function targetList(Request $request)
    {
        // Load related employee and employeeType to prevent N+1 query issues
        $query = Target::with(['employee.employeeType']);

        // Apply filters based on request parameters
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
            ->addIndexColumn() // Adds an auto-incrementing column (Sl.No)
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



    // public function targetList(Request $request)
    // {   
        
    //     if ($request->ajax()) {
    //         $pageNumber = ($request->start / $request->length) + 1;
    //         $pageLength = $request->length;
    //         $skip = ($pageNumber - 1) * $pageLength;

    //         $orderColumnIndex = $request->order[0]['column'] ?? 0;
    //         $orderBy = $request->order[0]['dir'] ?? 'desc';
    //         $searchValue = $request->search['value'] ?? '';
    //         $columns = [
    //             0 => 'targets.id', 
    //             1 => 'employee_types.type_name', 
    //             2 => 'employees.name', 
    //             3 => 'targets.year',
    //             4 => 'targets.month',
    //             5 => 'targets.unique_lead',
    //             6 => 'targets.customer_visit',
    //             7 => 'targets.aashiyana',
    //             8 => 'targets.order_quantity'
    //         ];
    //         $orderColumn = $columns[$orderColumnIndex] ?? 'targets.created_at';

    //         $query = Target::join('employees', 'employees.id', '=', 'targets.employee_id')
    //             ->join('employee_types', 'employees.employee_type_id', '=', 'employee_types.id')
    //             ->orderBy('targets.created_at', 'desc')
    //             ->orderBy($orderColumn, $orderBy)
    //             ->select('targets.id', 'targets.created_at as from_date', 'targets.*', 'employees.name as employee_name', 'employee_types.type_name as employee_type',DB::raw("CONCAT(Targets.month, '-', targets.year) as to_date") );

    //         if ($searchValue) {
    //             $query->where(function ($query) use ($searchValue) {
    //                 $query->where('employee_name', 'like', '%' . $searchValue . '%');
    //             });
    //         }

    //         $recordsTotal = $query->count();
    //         $data = $query->skip($skip)->take($pageLength)->get();
    //         $recordsFiltered = $recordsTotal;
            
    //         if ($data->isEmpty()) {
    //             return response()->json([
    //                 "draw" => $request->draw,
    //                 "recordsTotal" => $recordsTotal,
    //                 "recordsFiltered" => $recordsFiltered,
    //                 'data' => [],
    //                 'query' => $query,
    //             ], 200);
    //         }

    //         $formattedData = $data->map(function ($row) {
    //             $action = '<a onclick="handleAction(\'' . $row->id . '\',\'view\')" title="view"><i class="fa fas fa-eye"></i></a>
    //                 <a onclick="editTarget(\'' . $row->id . '\')" title="edit"><i class="fa fa-pencil-square"></i></a>
    //                 <a onclick="deleteTarget(\'' . $row->id . '\')" title="delete"><i class="fa fas fa-trash"></i></a>';
    //             $status = $row->status == '1' ? "Active" : "Inactive";

    //             return [
    //                 'id' => $row->id,
    //                 'employee_type' => $row->employee_type,
    //                 'employee_name' => $row->employee_name,
    //                 'year' => $row->year,
    //                 'month' => $row->month,
    //                 'unique_lead' => $row->unique_lead,
    //                 'customer_visit' => $row->customer_visit,
    //                 'aashiyana' => $row->aashiyana,
    //                 'order_quantity' => $row->order_quantity,
    //                 'action' => $action,
    //             ];
    //         });

    //         return response()->json([
    //             "draw" => $request->draw,
    //             "recordsTotal" => $recordsTotal,
    //             "recordsFiltered" => $recordsFiltered,
    //             'data' => $formattedData,
    //         ], 200);
    //     }
    // }
    
    // public function indexList(Request $request)
    // {
    //     try {
    //         $month = $request->month != "" ? $request->month : Carbon::now()->month;
    //         $year  = $request->year  != "" ? $request->year : Carbon::now()->year;
    //         $employeeId = Auth::id();
    //         $currentYear = Carbon::now()->year;

    //         $targets = Target::with('customerType') 
    //             ->where('month', $month)
    //             ->where('year', $year)
    //             ->where('created_by', $employeeId)
    //             ->get();

    //         $response = [];

    //         foreach ($targets as $target) {
    //             if ($target->customerType) { 
    //                 $customerTypeId = $target->customerType->id;
            
    //                 $orders = Order::where('created_by', $employeeId)
    //                     ->where('customer_type_id', $customerTypeId)
    //                     ->whereYear('created_at', $currentYear)
    //                     ->whereMonth('created_at', Carbon::parse($month)->month)
    //                     ->where('status', 'Accepted')
    //                     ->pluck('id');

    //                 // Calculate Achieved Target
    //                 $ton_achievedTarget = DB::table('order_items')
    //                     ->whereIn('order_id', $orders)
    //                     ->sum('total_quantity');

    //                 $no_achievedTarget = DB::table('leads')
    //                     ->whereIn('customer_type', (array) $customerTypeId) // Ensure it's an array
    //                     ->where('created_by', $employeeId)
    //                     ->whereYear('created_at', Carbon::now()->year) // Or use $currentYear if dynamic
    //                     ->whereMonth('created_at', Carbon::parse($month)->month)
    //                     ->count();

    //             if($target->target_type_flag=="ton"){
    //                 $achievedTarget=$ton_achievedTarget;
    //                 $targetQty=$target->ton_quantity;
    //             }else{
    //                $achievedTarget=$no_achievedTarget;
    //                $targetQty= $target->no_quantity;
    //             }
               
    //                 $response[] = [
    //                     'target_id' => $target->id,
    //                     'customer_type' => [
    //                         'id' => $customerTypeId,
    //                         'name' => $target->customerType->name ?? 'Unknown', 
    //                     ],

    //                     'target_type_flag' => $target->target_type_flag,
    //                     'ton_quantity' => $target->ton_quantity,
    //                     'no_quantity' => $target->no_quantity,
    //                     'achieved_quantity' => $achievedTarget,
    //                     'status' => ($achievedTarget < $targetQty) ? 'Target Not Met' : 'Target Achieved'
    //                 ];
    //             }
    //         }

    //         return response()->json([
    //             'success' => true,
    //             'statusCode' => 200,
    //             'message' => 'Target data retrieved successfully.',
    //             'data' => $response,
    //         ], 200);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'success' => false,
    //             'statusCode' => 500,
    //             'message' => 'An error occurred while retrieving target data.',
    //             'error' => $e->getMessage(), 
    //         ], 500);
    //     }
    // }

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
            'data' => view('admin.target.view', compact('target'))->render()
        ]);
    }
    public function update(Request $request)
    {
        $target = Target::findOrFail($request->id);
        
        $target->update([
            'employee_type' => $request->employee_type,
            'employee_id' => $request->employee_id,
            'year' => $request->year,
            'month' => $request->month,
            'unique_lead' => $request->unique_lead,
            'customer_visit' => $request->customer_visit,
            'aashiyana' => $request->aashiyana,
            'order_quantity' => $request->order_quantity,
        ]);
    
        return response()->json(['message' => 'Target updated successfully!']);
    }
    public function viewTargets(Request $request)
    {
        $target = Target::with(['employee.employeeType'])->find($request->id);

        if (!$target) {
            return response()->json(['success' => false, 'message' => 'Target not found!']);
        }

        $html = view('admin.targets.view-details', compact('target'))->render();

        return response()->json(['success' => true, 'html' => $html]);
    }
   
    public function delete($id)
    {
        $rowid = Target::find($id);

        if (!$rowid) {
            return response()->json(['success' => false, 'message' => 'Target not found.'], 404);
        }

        $rowid->delete();

        return response()->json(['success' => true, 'message' => 'Target deleted successfully.']);
    }
    public function destroy($id)
    {
        $target = Target::findOrFail($id);
        $target->delete();

        return response()->json(['message' => 'Target deleted successfully!']);
    }


}
