<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TripRoute extends Model
{
    use HasFactory;

    protected $table = 'trip_route';

    protected $fillable = [
        'route_name',
        'status',
    ];

    public function assignRoutes()
    {
        return $this->hasMany(AssignRoute::class, 'trip_route_id');
    }

    public function dealers()
    {
        return $this->hasMany(Dealer::class, 'trip_route_id');
    }
}
