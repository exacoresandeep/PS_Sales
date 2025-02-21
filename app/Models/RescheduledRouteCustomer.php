<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RescheduledRouteCustomer extends Model
{
    use HasFactory;

    protected $table = 'rescheduled_route_customers';
    public $timestamps = false;
    
    protected $fillable = [
        'rescheduled_route_id',
        'customer_id',
        'customer_name',
        'customer_type',
        'location',
        'status'
    ];

    public function rescheduledRoute()
    {
        return $this->belongsTo(RescheduledRoute::class);
    }
}
