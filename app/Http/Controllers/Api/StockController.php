<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Warehouse;
use App\Models\ProductStock;
use Illuminate\Support\Facades\DB;

class StockController extends Controller
{
    public function stockList(Request $request)
    {
        $request->validate([
            'latitude'  => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);
    
        $latitude  = $request->latitude;
        $longitude = $request->longitude;
    
        $nearestWarehouse = Warehouse::select(
            'id',
            'warehouse_name',
            'latitude',
            'longitude',
            DB::raw("
                ( 6371 * acos( cos( radians($latitude) ) * cos( radians(latitude) ) 
                * cos( radians(longitude) - radians($longitude) ) + sin( radians($latitude) ) 
                * sin( radians(latitude) ) ) ) AS distance
            ")
        )
        ->orderBy('distance', 'ASC')
        ->first();
    
        if (!$nearestWarehouse) {
            return response()->json([
                'success' => false,
                'statusCode' => 404,
                'message' => 'No warehouse found nearby.',
            ], 404);
        }
    
        $stockItems = ProductStock::with(['productDetails.product', 'productDetails.productType'])
            ->where('warehouse_id', $nearestWarehouse->id)
            ->get();
    
        return response()->json([
            'success' => true,
            'statusCode' => 200,
            'message' => "Stock list fetched successfully.",
            'warehouse' => [
                'id'            => $nearestWarehouse->id,
                'name'          => $nearestWarehouse->warehouse_name,
                'latitude'      => $nearestWarehouse->latitude,
                'longitude'     => $nearestWarehouse->longitude,
                'distance_km'   => round($nearestWarehouse->distance, 2),
            ],
            'stocks' => $stockItems->map(function ($stock) {
                $stockQuantity = (float) $stock->quantity;
    
                $availabilityStatus = 'Out of Stock';
                if ($stockQuantity > 0 && $stockQuantity < 1000) {
                    $availabilityStatus = 'Low Stock';
                } elseif ($stockQuantity >= 1000) {
                    $availabilityStatus = 'In Stock';
                }
    
                return [
                    'product_details_id'  => $stock->product_details_id,
                    'product_name'        => $stock->productDetails->product_name,
                    'product_type'        => optional($stock->productDetails->productType)->type_name,
                    'item_profile'        => $stock->productDetails->item_profile,
                    'item_thickness'      => $stock->productDetails->item_thickness,
                    'primary_group'       => $stock->productDetails->primary_group,
                    'total_available_qty' => $stock->productDetails->total_available_quantity,
                    'stock_quantity'      => number_format($stockQuantity, 5, '.', ''),
                    'availability_status' => $availabilityStatus,
                ];
            }),
        ], 200);
    }
    public function getProductStockDetails($product_details_id)
    {
        $stockRecords = ProductStock::with(['warehouse', 'productDetails.product', 'productDetails.productType'])
            ->where('product_details_id', $product_details_id)
            ->get();

        if ($stockRecords->isEmpty()) {
            return response()->json([
                'success' => false,
                'statusCode' => 404,
                'message' => 'No stock records found for this product.',
            ], 404);
        }
        $firstStock = $stockRecords->first();
        return response()->json([
            'success' => true,
            'statusCode' => 200,
            'message' => 'Product stock details fetched successfully.',
            'product_details_id' => $product_details_id,
            'product_name'       => optional($firstStock->productDetails)->product_name,
            'product_type'       => optional($firstStock->productDetails->productType)->type_name,
            'item_profile'       => optional($firstStock->productDetails)->item_profile,
            'item_thickness'     => optional($firstStock->productDetails)->item_thickness,
            'primary_group'      => optional($firstStock->productDetails)->primary_group,
            'stock_updated_at'   => optional($firstStock->productDetails)->stock_updated_at_formatted,
            'stocks' => $stockRecords->map(function ($stock) {
                return [
                    'warehouse_id'       => $stock->warehouse_id,
                    'warehouse_name'     => $stock->warehouse->warehouse_name,
                    'stock_quantity'     => number_format((float) $stock->quantity, 5, '.', ''),
                    'availability_status' => $stock->quantity > 0 ? 'In Stock' : 'Out of Stock',
                ];
            }),
        ], 200);
    }
    public function stockFilter(Request $request)
    {
        $request->validate([
            'search_key' => 'required|string|in:All,In Stock,Out of Stock,Low Stock',
        ]);

        $searchKey = $request->search_key;

        $stockQuery = ProductStock::with(['productDetails.product', 'productDetails.productType', 'warehouse']);

        if ($searchKey == 'In Stock') {
            $stockQuery->where('quantity', '>=', 1000);
        } elseif ($searchKey == 'Low Stock') {
            $stockQuery->where('quantity', '>', 0)->where('quantity', '<', 1000);
        } elseif ($searchKey == 'Out of Stock') {
            $stockQuery->where('quantity', '=', 0);
        }

        $stockItems = $stockQuery->get();

        if ($stockItems->isEmpty()) {
            return response()->json([
                'success' => false,
                'statusCode' => 404,
                'message' => 'No stock found for the given filter.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'statusCode' => 200,
            'message' => "Stock filter applied successfully for '$searchKey'.",
            'search_key' => $searchKey,
            'stocks' => $stockItems->map(function ($stock) {
                $stockQuantity = (float) $stock->quantity;
    
                $availabilityStatus = 'Out of Stock';
                if ($stockQuantity > 0 && $stockQuantity < 1000) {
                    $availabilityStatus = 'Low Stock';
                } elseif ($stockQuantity >= 1000) {
                    $availabilityStatus = 'In Stock';
                }
    
                return [
                    'product_details_id'  => $stock->product_details_id,
                    'product_name'        => optional($stock->productDetails)->product_name ?? 'N/A',
                    'product_type'        => optional(optional($stock->productDetails)->productType)->type_name ?? 'N/A',
                    'warehouse_id'        => $stock->warehouse_id,
                    'warehouse_name'      => optional($stock->warehouse)->warehouse_name ?? 'N/A',
                    'stock_quantity'      => number_format($stockQuantity, 5, '.', ''),
                    'availability_status' => $availabilityStatus,
                ];
            }),
        ], 200);
    }


}
