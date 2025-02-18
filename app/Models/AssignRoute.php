<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssignRoute extends Model
{
    use HasFactory;

    protected $table = 'assign_route';

    protected $fillable = [
        'employee_id',
        'trip_route_id',
        'assign_date',
        'status',

    ];

    public function tripRoute()
    {
        return $this->belongsTo(TripRoute::class, 'trip_route_id', 'id');
    }

    public function dealers()
    {
        return $this->hasMany(Dealer::class, 'trip_route_id', 'trip_route_id');
    }
}
