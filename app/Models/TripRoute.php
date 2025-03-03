<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TripRoute extends Model
{


    use HasFactory;
    protected $table = 'routes';
    protected $fillable = ['district_id', 'locations'];
  
    protected $casts = [
        'locations' => 'array', 
    ];

    public function district()
    {
        return $this->belongsTo(District::class, 'district_id');
    }


    public function assignedRoutes()
    {
        return $this->hasMany(AssignRoute::class);
    }
}

    // use HasFactory;

    // protected $table = 'trip_route';

    // protected $fillable = [
    //     'district_id',
    //     'route_name',
    //     'location_name',
    //     'sub_locations', 
    //     'status',
    // ];
    // public $timestamps = false;

    // public function assignRoutes()
    // {
    //     return $this->hasMany(AssignRoute::class, 'trip_route_id');
    // }

    // public function dealers()
    // {
    //     return $this->hasMany(Dealer::class, 'trip_route_id');
    // }
    // public function district()
    // {
    //     return $this->belongsTo(District::class, 'district_id');
    // }