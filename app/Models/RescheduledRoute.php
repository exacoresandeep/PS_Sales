<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RescheduledRoute extends Model
{

    use HasFactory;

    protected $table = 'rescheduled_routes';
    public $timestamps = false;
    protected $fillable = ['employee_id','assigned_route_id', 'original_day', 'week_start', 'rescheduled_day', 'route_name', 'locations'];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}

