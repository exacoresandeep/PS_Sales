<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lead extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'customer_type', 
        'customer_name', 
        'city', 
        'location', 
        'phone', 
        'address', 
        'district_id', 
        'trip_route_id', 
        'type_of_visit', 
        'construction_type', 
        'stage_of_construction', 
        'follow_up_date', 
        'lost_volume', 
        'lost_to_competitor', 
        'reason_for_lost', 
        'status', 
        'created_by',
    ];

    protected $casts = [
        'attachments' => 'array', 
    ];
    public function customerType()
    {
        return $this->belongsTo(CustomerType::class, 'customer_type');
    }
    // public function district()
    // {
    //     return $this->belongsTo(District::class, 'district_id');
    // }
    public function tripRoute()
    {
        return $this->belongsTo(TripRoute::class, 'trip_route_id');
    }
    public function orders()
    {
        return $this->hasMany(Order::class, 'lead_id');
    }
}
