<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Lead;
use App\Models\TripRoute;
use Exception;

class LeadController extends Controller
{
    
    // public function index($customer_type_id,Request $request)
    // {
    //     try {
    //         $user = Auth::user();
    //         if ($user !== null) {
    //             $query = Lead::with('customerType')
    //                         ->where('created_by', $user->id)
    //                         ->where('customer_type', $customer_type_id);

    //             if ($request->has('search_key') && !empty($request->search_key)) {
    //                 $searchKey = $request->search_key;

    //                 $query->where(function ($q) use ($searchKey) {
    //                     $q->where('customer_name', 'like', '%' . $searchKey . '%')
    //                     ->orWhere('phone', 'like', '%' . $searchKey . '%');
    //                 });
    //             }

    //             $leads = $query->orderBy('customer_name', 'asc')->get();

    //             if ($leads->isEmpty()) {
    //                 return response()->json([
    //                     'success' => true,
    //                     'statusCode' => 200,
    //                     'message' => 'No leads found matching the filter.',
    //                     'data' => [],
    //                 ], 200);
    //             }
    //             $formattedLeads = $leads->map(function ($lead) {
    //                 return [
    //                     'id' => $lead->id,
    //                     'customer_type' => [
    //                         'id' => $lead->customerType->id,
    //                         'name' => $lead->customerType->name,
    //                     ],
    //                     'customer_name' => $lead->customer_name,
    //                     'phone' => $lead->phone,
    //                     'address' => $lead->address,
    //                     'instructions' => $lead->instructions,
    //                     'record_details' => $lead->record_details,
    //                     'attachments' => $lead->attachments,
    //                     'latitude' => $lead->latitude,
    //                     'longitude' => $lead->longitude,
    //                     'status' => $lead->status,
    //                     'created_by' => $lead->created_by,
    //                     'created_at' => $lead->created_at,
    //                 ];
    //             });

    //             return response()->json([
    //                 'success' => true,
    //                 'statusCode' => 200,
    //                 'message' => 'Leads retrieved successfully!',
    //                 'data' => $formattedLeads,
    //             ], 200);

    //         } else {
    //             return response()->json([
    //                 'success' => false,
    //                 'statusCode' => 401,
    //                 'message' => 'Unauthorized access.',
    //             ], 401);
    //         }
    //     } catch (Exception $e) {
    //         return response()->json([
    //             'success' => false,
    //             'statusCode' => 500,
    //             'message' => $e->getMessage(),
    //         ], 500);
    //     }
    // }
    public function index(Request $request)
    {
        try {
            $user = Auth::user();
            if ($user !== null) {
                $leads = Lead::with(['customerType', 'district', 'tripRoute'])
                            ->where('created_by', $user->id)
                            ->orderBy('customer_name', 'asc')
                            ->get();
    
                if ($leads->isEmpty()) {
                    return response()->json([
                        'success' => true,
                        'statusCode' => 200,
                        'message' => 'No leads found',
                        'data' => [],
                    ], 200);
                }
    
                $formattedLeads = $leads->map(function ($lead) {
                    return [
                        'status' => $lead->status,
                        'customer_name' => $lead->customer_name,
                        'customer_type' => $lead->customerType ? [
                            'id' => $lead->customerType->id,
                            'name' => $lead->customerType->name,
                        ] : null,  
                        'district' => $lead->district ? [
                            'id' => $lead->district->id,
                            'name' => $lead->district->name,
                        ] : null,  
                        'route_name' => $lead->tripRoute ? $lead->tripRoute->route_name : null, 
                        'location_name' => $lead->tripRoute ? $lead->tripRoute->location_name : null,
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
                'phone' => 'required|string',
                'address' => 'required|string',
                'city' => 'required|string',
                'location' => 'required|string',
                'district_id' => 'required|exists:districts,id',
                'trip_route_id' => 'required|exists:trip_route,id',
            ]);

            $existingLead = Lead::where('phone', $request->phone)->first();
            if ($existingLead) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 409,
                    'message' => 'Lead with the same phone number already exists!',
                ], 409);
            }
            $tripRoute = TripRoute::where('id', $request->trip_route_id)
                        ->where('district_id', $request->district_id)
                        ->first();

            if (!$tripRoute) {
                return response()->json([
                'success' => false,
                'statusCode' => 404,
                'message' => 'Trip Route not found for the given district!',
                ], 404);
            }
            $subLocations = json_decode($tripRoute->sub_locations, true) ?? [];

            if (!in_array($request->location, $subLocations)) {
                $subLocations[] = $request->location;

                $tripRoute->update([
                    'sub_locations' => json_encode($subLocations),
                ]);
            }

            $validatedData['created_by'] = Auth::id();

            $lead = Lead::create($validatedData);
            $leadData = [
                'customer_type' => $lead->customerType->name ?? null,
                'customer_name' => $lead->customer_name,
                'address' => $lead->address,
                'city' => $lead->city,
                'location' => $lead->location,
                'phone' => $lead->phone,
                'district' => $lead->district->name ?? null,
                'route_name' => $tripRoute->route_name,
                'location_name' => $tripRoute->location_name,
                'status' => 'Opened',
                'created_at' => $lead->created_at->format('Y-m-d H:i:s'),

            ];
            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Lead created successfully!',
                'data' => $leadData,
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
    public function leadsList(Request $request)
    {
        try {
            $user = Auth::user();
            if ($user !== null) {
                $leads = Lead::with('customerType')
                            ->where('created_by', $user->id)
                            ->orderBy('customer_name', 'asc')
                            ->get();
                if ($leads->isEmpty()) {
                    return response()->json([
                        'success' => true,
                        'statusCode' => 200,
                        'message' => 'No leads found for the logged-in user.',
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

    public function getleadsFilter($customer_type_id, Request $request)
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
                        'status' => $lead->status,
                        'customer_name' => $lead->customer_name,
                        'customer_type' => $lead->customerType ? [
                            'id' => $lead->customerType->id,
                            'name' => $lead->customerType->name,
                        ] : null,
                        'district' => $lead->district ? [
                            'id' => $lead->district->id,
                            'name' => $lead->district->name,
                        ] : null,
                        'route_name' => $lead->tripRoute ? $lead->tripRoute->route_name : null,
                        'location_name' => $lead->tripRoute ? $lead->tripRoute->location_name : null,
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


  



}
