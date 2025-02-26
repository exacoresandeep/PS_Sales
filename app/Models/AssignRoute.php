<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssignRoute extends Model
{
    // use HasFactory;

    // protected $table = 'assign_route';

    // protected $fillable = [
    //     'employee_id',
    //     'trip_route_id',
    //     'assign_date',
    //     'status',

    // ];

    // public function tripRoute()
    // {
    //     return $this->belongsTo(TripRoute::class, 'trip_route_id', 'id');
    // }

    // public function dealers()
    // {
    //     return $this->hasMany(Dealer::class, 'trip_route_id', 'trip_route_id');
    // }


    use HasFactory;
    protected $table = 'assigned_routes';
    public $timestamps = false;
    protected $fillable = ['district_id', 'employee_id', 'route_name', 'locations'];
    
    public function district()
    {
        return $this->belongsTo(District::class);
    }
    public function dealers()
    {
        return $this->hasMany(Dealer::class, 'assigned_route_id', 'id');
    }
    public function leads()
    {
        return $this->hasMany(Lead::class, 'assigned_route_id', 'id');
    }
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
    public function assignRoute()
    {
        return $this->belongsTo(AssignRoute::class, 'assigned_route_id');
    }
}
