<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Lead;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\TripRoute;
use Exception;

class LeadController extends Controller
{

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
                        'id' => $lead->id,
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
                    'statusCode' => 400,
                    'message' => 'Lead with the same phone number already exists!',
                    'data' =>[],
                ], 400);
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
                'sub_location' => $lead->location,
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
            $lead = Lead::with(['customerType', 'district', 'tripRoute', 'orders.orderItems.product', 'orders.paymentTerm', 'orders.dealer'])
                        ->where('created_by', Auth::id()) 
                        ->findOrFail($leadId);
            $paymentTerms = $lead->orders
                ->pluck('paymentTerm')
                ->unique('id')
                ->filter() 
                ->map(function ($paymentTerm) {
                    return [
                        'id' => $paymentTerm->id,
                        'name' => $paymentTerm->name,
                    ];
                })->values(); 
            $paymentTerms = $paymentTerms->count() === 1 ? $paymentTerms->first() : ($paymentTerms->isEmpty() ? null : $paymentTerms);
            $dealers = $lead->orders
                ->pluck('dealer')
                ->unique('id')
                ->filter() 
                ->map(function ($dealer) {
                    return [
                        'id' => $dealer->id,
                        'name' => $dealer->dealer_name,
                    ];
                })->values();
            $dealers = $dealers->count() === 1 ? $dealers->first() : ($dealers->isEmpty() ? null : $dealers);
            $leadData = [
                'id' => $lead->id,
                'customer_type' => $lead->customerType ? [
                    'id' => $lead->customerType->id,
                    'name' => $lead->customerType->name,
                ] : null,
                'customer_name' => $lead->customer_name,
                'city' => $lead->city,
                'location' => $lead->location,
                'phone' => $lead->phone,
                'address' => $lead->address,
                'district' => $lead->district ? [
                    'id' => $lead->district->id,
                    'name' => $lead->district->name,
                ] : null,
                'trip_route' => $lead->tripRoute ? [
                    'id' => $lead->tripRoute->id,
                    'route_name' => $lead->tripRoute->route_name,
                    'location_name' => $lead->tripRoute->location_name,
                ] : null,
                'type_of_visit' => $lead->type_of_visit,
                'construction_type' => $lead->construction_type,
                'stage_of_construction' => $lead->stage_of_construction,
                'follow_up_date' => $lead->follow_up_date,
                'lead_score' => $lead->lead_score,
                'lead_source' => $lead->lead_source,
                'source_name' => $lead->source_name,
                'total_quantity' => $lead->total_quantity,
                'lost_volume' => $lead->lost_volume,
                'lost_to_competitor' => $lead->lost_to_competitor,
                'reason_for_lost' => $lead->reason_for_lost,
                'status' => $lead->status,
                'created_by' => $lead->created_by,
                'created_at' => $lead->created_at,
                'payment_terms' => $paymentTerms,
                'dealers' => $dealers,
                'orders' => $lead->orders->map(function ($order) {
                return [
                        'id' => $order->id,
                        'total_amount' => $order->total_amount,
                        'status' => $order->status,
                        'billing_date' => $order->billing_date,
                        'order_items' => $order->orderItems->map(function ($item) {
                            return [
                                'id' => $item->id,
                                'product_id' => $item->product_id,
                                'total_quantity' => $item->total_quantity,
                                'balance_quantity' => $item->balance_quantity,
                                'product_details' => $item->product_details,
                            ];
                        }),
                    ];
                }),
            ];

            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Lead retrieved successfully!',
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

    public function updateLead(Request $request, $leadId)
    {
        try {
            $validatedData = $request->validate([
                'type_of_visit' => 'nullable|string',
                'construction_type' => 'nullable|string',
                'stage_of_construction' => 'nullable|string',
                'follow_up_date' => 'nullable|date',
                'lead_score' => 'nullable|string',
                'lead_source' => 'nullable|string',
                'source_name' => 'nullable|string',
                'total_quantity' => 'nullable|numeric',
                'status' => 'required|in:Opened,Won,Lost',
                // 'lost_details.lost_volume' => 'nullable|required_if:status,Lost|numeric',
                // 'lost_details.lost_to_competitor' => 'nullable|required_if:status,Lost|string',
                // 'lost_details.reason_for_lost' => 'nullable|required_if:status,Lost|string',
                // 'order_details.customer_type_id' => 'nullable|exists:customer_types,id',
                // 'order_details.dealer_id' => 'nullable|exists:dealers,id',
                // 'order_details.dealer_flag_order' => 'nullable|numeric',
                // 'order_details.payment_terms_id' => 'nullable|exists:payment_terms,id',
                // 'order_details.total_amount' => 'nullable|numeric',
                // 'order_details.order_items' => 'nullable|array',
                // 'order_details.order_items.*.product_id' => 'required_with:order_details.order_items|exists:products,id',
                // 'order_details.order_items.*.total_quantity' => 'required_with:order_details.order_items|numeric',
                // 'order_details.order_items.*.balance_quantity' => 'required_with:order_details.order_items|numeric',
                // 'order_details.order_items.*.product_details' => 'nullable|array',
            ]);

            $lead = Lead::where('id', $leadId)
                ->where('created_by', Auth::id())
                ->firstOrFail();

            $lead->update([
                'type_of_visit' => $request->type_of_visit,
                'construction_type' => $request->construction_type,
                'stage_of_construction' => $request->stage_of_construction,
                'follow_up_date' => $request->follow_up_date,
                'lead_score' => $request->lead_score,
                'lead_source' => $request->lead_source,
                'source_name' => $request->source_name,
                'total_quantity' => $request->total_quantity,
                'status' => $request->status,
            ]);

            if ($request->status === 'Won' && !empty($request->order_details)) {
                $orderData = [
                    'customer_type_id' => $request->order_details['customer_type_id'],
                    'lead_id' => $lead->id,
                    'dealer_id' => $request->order_details['dealer_id'] ?? null,
                    'dealer_flag_order' => $request->order_details['dealer_flag_order'] ?? 0,
                    'payment_terms_id' => $request->order_details['payment_terms_id'],
                    'total_amount' => $request->order_details['total_amount'],
                    'billing_date' => now()->format('Y-m-d'),
                    'status' => 'Pending',
                    'created_by' => Auth::id(),
                ];

                $order = Order::create($orderData);

                foreach ($request->order_details['order_items'] as $item) {
                    $orderItemData = [
                        'order_id' => $order->id,
                        'product_id' => $item['product_id'],
                        'total_quantity' => $item['total_quantity'],
                        'balance_quantity' => $item['balance_quantity'],
                        'product_details' => $item['product_details'], 
                    ];
                    OrderItem::create($orderItemData);
                }
            }

            if ($request->status === 'Lost' && !empty($request->lost_details)) {
                $lead->update([
                    'lost_volume' => $request->lost_details['lost_volume'],
                    'lost_to_competitor' => $request->lost_details['lost_to_competitor'],
                    'reason_for_lost' => $request->lost_details['reason_for_lost'],
                ]);
            }

            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Lead updated successfully!',
                'data' => $lead,
                'order_details' => $order ?? null,
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 422,
                'message' => 'Validation error',
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => $e->getMessage(),
            ], 500);
        }
    }


    // public function updateLead(Request $request, $leadId)
    // {
    //     try {
    //         $validatedData = $request->validate([
    //             'type_of_visit' => 'nullable|string',
    //             'construction_type' => 'nullable|string',
    //             'stage_of_construction' => 'nullable|string',
    //             'follow_up_date' => 'nullable|date',
    //             'lead_score' => 'nullable|string',
    //             'lead_source' => 'nullable|string',
    //             'source_name' => 'nullable|string',
    //             'total_quantity' => 'nullable|numeric',
    //             'status' => 'required|in:Opened,Pending,Won,Lost',
    //             'lost_volume' => 'nullable|required_if:status,Lost|numeric',
    //             'lost_to_competitor' => 'nullable|required_if:status,Lost|string',
    //             'reason_for_lost' => 'nullable|required_if:status,Lost|string',
    //             'order_items' => 'nullable|array',
    //             'order_items.*.product_id' => 'required_with:order_items|exists:products,id',
    //             'order_items.*.product_details' => 'nullable|array',
    //         ]);

    //         $lead = Lead::where('id', $leadId)
    //                     ->where('created_by', Auth::id())
    //                     ->firstOrFail();

    //         $lead->update($validatedData);

    //         if ($request->status === 'Won') {
    //             $orderData = [
    //                 'customer_type_id' => $lead->customer_type,
    //                 'lead_id' => $lead->id,
    //                 'dealer_id' => $lead->dealer_id ?? null,
    //                 'dealer_flag_order' => 0, // Default value as per your requirement
    //                 'payment_terms_id' => 1, 
    //                 'total_amount' => 0,
    //                 'created_by' => Auth::id(),
    //             ];

    //             $order = Order::create($orderData);

    //             if (!empty($request->order_items)) {
    //                 foreach ($request->order_items as $item) {
    //                     $totalQuantity = 0;
    //                     if (!empty($item['product_details'])) {
    //                         foreach ($item['product_details'] as $productDetail) {
    //                             $totalQuantity += $productDetail['quantity'];
    //                         }
    //                     }

    //                     $order->orderItems()->create([
    //                         'order_id' => $order->id,
    //                         'product_id' => $item['product_id'],
    //                         'total_quantity' => $totalQuantity,
    //                         'balance_quantity' => $totalQuantity,
    //                         'product_details' => $item['product_details'] ?? [],
    //                     ]);
    //                 }
    //             }
    //         }

    //         if ($request->status === 'Lost') {
    //             $lead->update([
    //                 'lost_volume' => $request->lost_volume,
    //                 'lost_to_competitor' => $request->lost_to_competitor,
    //                 'reason_for_lost' => $request->reason_for_lost,
    //             ]);
    //         }

    //         return response()->json([
    //             'success' => true,
    //             'statusCode' => 200,
    //             'message' => 'Lead updated successfully!',
    //             'data' => $lead,
    //         ], 200);

    //     } catch (\Illuminate\Validation\ValidationException $e) {
    //         return response()->json([
    //             'success' => false,
    //             'statusCode' => 422,
    //             'message' => 'Validation error',
    //             'errors' => $e->errors(),
    //         ], 422);
    //     } catch (Exception $e) {
    //         return response()->json([
    //             'success' => false,
    //             'statusCode' => 500,
    //             'message' => $e->getMessage(),
    //         ], 500);
    //     }
    // }

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
    public function updateOpenLeads($leadId, Request $request)
    {
        try {
            $validated = $request->validate([
                'type_of_visit' => 'required|string',
                'construction_type' => 'required|string',
                'stage_of_construction' => 'required|string',
                'follow_up_date' => 'required|date',
                'lead_score' => 'required|string',
                'lead_source' => 'required|string',
                'source_name' => 'required|string',
                'total_quantity' => 'required|integer',
                'status' => 'required|string',
            ]);

            $lead = Lead::where('id', $leadId)
                        ->where('created_by', Auth::id())
                        ->firstOrFail();

            $lead->update([
                'type_of_visit' => $validated['type_of_visit'],
                'construction_type' => $validated['construction_type'],
                'stage_of_construction' => $validated['stage_of_construction'],
                'follow_up_date' => $validated['follow_up_date'],
                'lead_score' => $validated['lead_score'],
                'lead_source' => $validated['lead_source'],
                'source_name' => $validated['source_name'],
                'total_quantity' => $validated['total_quantity'],
                'status' => $validated['status'],
            ]);
            

            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Lead updated successfully!',
                'data' => $lead,
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 422,
                'message' => 'Validation error',
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    public function updateLostLeads($leadId, Request $request)
    {
        try {
            $validated = $request->validate([
                'lost_volume' => 'required|numeric', 
                'lost_to_competitor' => 'required|string',
                'reason_for_lost' => 'required|string',
            ]);

            $lead = Lead::where('id', $leadId)
                        ->where('created_by', Auth::id())
                        ->firstOrFail();

            $lead->update($validated);

            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'Lead updated successfully!',
                'data' => $lead,
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 422,
                'message' => 'Validation error',
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => $e->getMessage(),
            ], 500);
        }
    }




  



}
