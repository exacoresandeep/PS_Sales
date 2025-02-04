<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Lead;
use Exception;

class LeadController extends Controller
{
    
    public function index($customer_type_id,Request $request)
    {
        try {
            $user = Auth::user();
            if ($user !== null) {
                $query = Lead::with('customerType')
                            ->where('created_by', $user->id)
                            ->where('customer_type', $customer_type_id);

                if ($request->has('search_key') && !empty($request->search_key)) {
                    $searchKey = $request->search_key;

                    $query->where(function ($q) use ($searchKey) {
                        $q->where('customer_name', 'like', '%' . $searchKey . '%')
                        ->orWhere('email', 'like', '%' . $searchKey . '%')
                        ->orWhere('phone', 'like', '%' . $searchKey . '%');
                    });
                }

                $leads = $query->orderBy('customer_name', 'asc')->get();

                if ($leads->isEmpty()) {
                    return response()->json([
                        'success' => true,
                        'statusCode' => 200,
                        'message' => 'No leads found matching the filter.',
                        'data' => [],
                    ], 200);
                }
                $formattedLeads = $leads->map(function ($lead) {
                    return [
                        'id' => $lead->id,
                        'customer_type' => [
                            'id' => $lead->customerType->id,
                            'name' => $lead->customerType->name,
                        ],
                        'customer_name' => $lead->customer_name,
                        'email' => $lead->email,
                        'phone' => $lead->phone,
                        'address' => $lead->address,
                        'instructions' => $lead->instructions,
                        'record_details' => $lead->record_details,
                        'attachments' => $lead->attachments,
                        'latitude' => $lead->latitude,
                        'longitude' => $lead->longitude,
                        'status' => $lead->status,
                        'created_by' => $lead->created_by,
                        'created_at' => $lead->created_at,
                    ];
                });

                return response()->json([
                    'success' => true,
                    'statusCode' => 200,
                    'message' => 'Leads retrieved successfully!',
                    'data' => $formattedLeads,
                ], 200);

            } else {
                return response()->json([
                    'success' => false,
                    'statusCode' => 401,
                    'message' => 'Unauthorized access.',
                ], 401);
            }
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => $e->getMessage(),
            ], 500);
        }
    }


    public function store(Request $request)
    {
        try {

            $validatedData = $request->validate([
                'customer_type' => 'required|exists:customer_types,id',
                'customer_name' => 'required|string',
                'email' => 'required|email', 
                'phone' => 'required|string',
                'address' => 'required|string',
                'instructions' => 'nullable|string',
                'record_details' => 'nullable|string',
                'attachments' => 'nullable|array',
                'attachments.*' => 'nullable|string', 
                'latitude' => 'required|numeric',
                'longitude' => 'required|numeric',
                'status' => 'required|in:Opened,Follow Up,Converted,Deal Dropped',
            ]);

            $existingLead = Lead::where('email', $request->email)
                            ->orWhere('phone', $request->phone)
                            ->first();

            if ($existingLead) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 409,
                    'message' => 'Lead with the same email or phone number already exists!',
                ], 409);
            }


            $validatedData['created_by'] = Auth::id();

            $lead = Lead::create($validatedData);

            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Lead created successfully!',
                'data' => $lead,
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    public function show($leadId)
    {
        try {
            $lead = Lead::with('customerType') 
                        ->where('created_by', Auth::id()) 
                        ->findOrFail($leadId); 

            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Lead retrieved successfully!',
                'data' => $lead,
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function updateLead(Request $request, $leadId)
    {
        try {
           
            $validatedData = $request->validate([
                'status' => 'required|in:Opened,Follow Up,Converted,Deal Dropped',
                'record_details' => 'nullable|string',
                'attachments' => 'nullable|array', 
                'attachments.*' => 'file|mimes:jpg,jpeg,png,pdf,docx|max:2048',
            ]);

            $lead = Lead::where('created_by', Auth::id())->findOrFail($leadId);

            $attachments = $lead->attachments ?? []; 

            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $path = $file->store('leads', 'public'); 
                    $attachments[] = $path;
                }
            }


            $lead->update([
                'status' => $validatedData['status'],
                'record_details' => $validatedData['record_details'] ?? $lead->record_details,
                'attachments' => $attachments, 
            ]);

            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Lead updated successfully!',
                'data' => $lead,
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
  



}
