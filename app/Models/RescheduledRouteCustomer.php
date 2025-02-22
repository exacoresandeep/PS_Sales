<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RescheduledRouteCustomer extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $fillable = [
        'customer_id', 
        'rescheduled_route_id',
        'customer_type', 
        'customer_name', 
        'location', 
        'route_name', 
        'assigned_route_id', 
        'status', 
        'week_start', 
        'original_day', 
        'rescheduled_day',
        'visited_at'
    ];
    protected $casts = [
        'visited_at' => 'date', 
    ];
}
