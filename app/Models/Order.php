<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_type', 'order_category', 'lead_id','dealer_id', 'payment_terms', 
        'advance_amount', 'payment_date', 'utr_number', 'attachment', 'billing_date', 'reminder_date', 'total_amount', 'additional_information', 
        'status', 'vehicle_category', 'vehicle_type', 'vehicle_number', 
        'driver_name', 'driver_phone', 'created_by'
    ];
    protected $casts = [
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
        return $this->belongsTo(Dealer::class);
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
}
