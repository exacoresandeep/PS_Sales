<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RescheduledRoute extends Model
{
    use HasFactory;

    protected $table = 'rescheduled_routes'; 
    public $timestamps = false;
    protected $fillable = [
        'employee_id',
        'original_route_name',
        'rescheduled_date',
        'new_locations',
        'new_route_name'
    ];

    protected $casts = [
        'rescheduled_date' => 'date',
        // 'new_locations' => 'array' 
    ];

    public function customers()
    {
        return $this->hasMany(RescheduledRouteCustomer::class, 'rescheduled_route_id');
    }
   
    public function assignRoute()
    {
        return $this->belongsTo(AssignRoute::class, 'assigned_route_id');
    }

  
    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }
}

