<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\VehicleCategory;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_type',
        'order_category',
        'lead_id',
        'dealer_id',
        'dealer_flag_order',
        'reason_for_rejection',
        'payment_terms',
        'advance_amount',
        'payment_date',
        'utr_number',
        'attachment',
        'billing_date',
        'reminder_date',
        'amount',
        'discount',
        'total_amount',
        'additional_information',
        'status',
        'accepted_time',
        'rejected_time',
        'dispatched_time',
        'intransit_time',
        'delivered_time',
        'vehicle_category_id',
        'vehicle_type_id',
        'vehicle_number',
        'driver_name',
        'driver_phone',
        'created_by_dealer',
        'created_by',
    ];

    protected $casts = [
        'advance_amount' => 'float',
        'amount' => 'float',
        'discount' => 'float',
        'total_amount' => 'float',
        'payment_date' => 'date',
        'billing_date' => 'date',
        'reminder_date' => 'date',
        'accepted_time' => 'datetime',
        'rejected_time' => 'datetime',
        'dispatched_time' => 'datetime',
        'intransit_time' => 'datetime',
        'delivered_time' => 'datetime',
        'attachment' => 'array',
    ];


    public function orderType()
    {
        return $this->belongsTo(OrderType::class, 'order_type');
    }

    public function customerType()
    {
        return $this->belongsTo(CustomerType::class, 'customer_type');
    }

    public function dealer()
    {
        return $this->belongsTo(Dealer::class, 'dealer_id');
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(Employee::class, 'created_by');
    }
    public function lead()
    {
        return $this->belongsTo(Lead::class, 'lead_id');
    }
    public function vehicleCategory()
    {
        return $this->belongsTo(VehicleCategory::class, 'vehicle_category_id');
    }
}
