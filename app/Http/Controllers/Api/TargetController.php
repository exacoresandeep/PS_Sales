<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Target;
use App\Models\Lead;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TargetController extends Controller
{
    // public function getMonthlyTarget(Request $request)
    // {
    //     try {
            
    //         $currentYear = Carbon::now()->year;
    //         $employeeId = Auth::id();

    //         $targets = Target::where('month', $month)
    //             ->where('year', $currentYear)
    //             ->where('created_by', $employeeId)
    //             ->get();

    //         if ($targets->isEmpty()) {
    //             return response()->json([
    //                 'success' => true,
    //                 'statusCode' => 200,
    //                 'message' => 'No targets found for the selected month.',
    //                 'data' => null,
    //             ], 200);
    //         }

    //         $orders = Order::where('created_by', $employeeId)
    //         ->whereYear('created_at', $currentYear) 
    //         ->whereMonth('created_at', Carbon::parse($month)->month)
    //         ->where('status', 'Accepted') 
    //         ->pluck('id');

    //         $achievedTarget = DB::table('order_items') 
    //         ->whereIn('order_id', $orders) 
    //         ->sum('total_quantity'); 


    //         $response = [
    //             'targets' => $targets,
    //             'achieved_quantity' => $achievedTarget,
    //         ];

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
    //             'data' => $e->getMessage(), 
    //         ], 500);
    //     }
    // }
    public function getTargets(Request $request)
    {
        $month = $request->month != "" ? $request->month : Carbon::now()->month;
        $year  = $request->year  != "" ? $request->year : Carbon::now()->year;
        $employeeId = Auth::id();
       
        DB::enableQueryLog();
        $targetQuery = Target::where('employee_id', $employeeId)
                            ->where('month', $month)
                            ->where('year', $year);
        $target = $targetQuery->first();
        $target = $target ? $target->toArray() : [];

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

        return response()->json([
            'target' => $target,
            'achieved' => [
                'unique_leads' => $uniqueLeads,
                'customer_visit' => $customer_visit ?? 0, 
                'aashiyana' => $aashiyanaCount,
                'order_quantity' => $achievedOrderQuantity,
            ],
        ]);
    }


    public function targetList(Request $request)
    {   
        
        if ($request->ajax()) {
            $pageNumber = ($request->start / $request->length) + 1;
            $pageLength = $request->length;
            $skip = ($pageNumber - 1) * $pageLength;

            $orderColumnIndex = $request->order[0]['column'] ?? 0;
            $orderBy = $request->order[0]['dir'] ?? 'desc';
            $searchValue = $request->search['value'] ?? '';
            $columns = [
                'id',
                'employee_name',
                'target_tons'
            ];
            $orderColumn = $columns[$orderColumnIndex] ?? 'created_at';

            $query = Target::join('employees', 'employees.id', '=', 'Target.employee_id')
                ->join('employee_types', 'employees.employee_type_id', '=', 'employee_types.id')
                ->orderBy('Target.created_at', 'desc')
                ->orderBy($orderColumn, $orderBy)
                ->select('Target.id', 'Target.created_at as from_date', 'Target.*', 'employees.name as employee_name', 'employee_types.type_name as employee_type',DB::raw("CONCAT(Target.month, '-', Target.year) as to_date") );

            if ($searchValue) {
                $query->where(function ($query) use ($searchValue) {
                    $query->where('employee_name', 'like', '%' . $searchValue . '%');
                });
            }

            $recordsTotal = $query->count();
            $data = $query->skip($skip)->take($pageLength)->get();
            $recordsFiltered = $recordsTotal;
            
            if ($data->isEmpty()) {
                return response()->json([
                    "draw" => $request->draw,
                    "recordsTotal" => $recordsTotal,
                    "recordsFiltered" => $recordsFiltered,
                    'data' => [],
                    'query' => $query,
                ], 200);
            }

            $formattedData = $data->map(function ($row) {
                $action = '<a onclick="handleAction(\'' . $row->id . '\',\'view\')" title="view"><i class="fa fas fa-eye"></i></a>
                    <a onclick="editTarget(\'' . $row->id . '\')" title="edit"><i class="fa fa-pencil-square"></i></a>
                    <a onclick="deleteTarget(\'' . $row->id . '\')" title="delete"><i class="fa fas fa-trash"></i></a>';
                $status = $row->status == '1' ? "Active" : "Inactive";

                return [
                    'id' => $row->id,
                    'employee_type' => $row->employee_type,
                    'employee_name' => $row->employee_name,
                    'from_date' => $row->from_date,
                    'to_date' => $row->to_date,
                    'target_tons' => $row->target_quantity,
                    'target_numbers' => $row->target_quantity,
                    'action' => $action,
                ];
            });

            return response()->json([
                "draw" => $request->draw,
                "recordsTotal" => $recordsTotal,
                "recordsFiltered" => $recordsFiltered,
                'data' => $formattedData,
            ], 200);
        }
    }
    
    public function indexList(Request $request)
    {
        try {
            $month = $request->month != "" ? $request->month : Carbon::now()->month;
            $year  = $request->year  != "" ? $request->year : Carbon::now()->year;
            $employeeId = Auth::id();
            $currentYear = Carbon::now()->year;

            $targets = Target::with('customerType') 
                ->where('month', $month)
                ->where('year', $year)
                ->where('created_by', $employeeId)
                ->get();

            $response = [];

            foreach ($targets as $target) {
                if ($target->customerType) { 
                    $customerTypeId = $target->customerType->id;
            
                    $orders = Order::where('created_by', $employeeId)
                        ->where('customer_type_id', $customerTypeId)
                        ->whereYear('created_at', $currentYear)
                        ->whereMonth('created_at', Carbon::parse($month)->month)
                        ->where('status', 'Accepted')
                        ->pluck('id');
                    $achievedTarget = DB::table('order_items')
                        ->whereIn('order_id', $orders)
                        ->sum('total_quantity');
               
                    $response[] = [
                        'target_id' => $target->id,
                        'customer_type' => [
                            'id' => $customerTypeId,
                            'name' => $target->customerType->name ?? 'Unknown', 
                        ],

                        'target_type_flag' => $target->target_type_flag,
                        'ton_quantity' => $target->ton_quantity,
                        'no_quantity' => $target->ton_quantity,
                        'achieved_quantity' => $achievedTarget,
                        'status' => ($achievedTarget < $target->ton_quantity) ? 'Target Not Met' : 'Target Achieved'
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Target data retrieved successfully.',
                'data' => $response,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => 'An error occurred while retrieving target data.',
                'error' => $e->getMessage(), 
            ], 500);
        }
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
            'data' => view('admin.target.view', compact('target'))->render()
        ]);
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
}
