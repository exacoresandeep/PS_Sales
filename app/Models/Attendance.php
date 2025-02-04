<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory;

    protected $table = 'attendance';

    protected $fillable = [
        'employee_id',
        'date',
        'punch_in',
        'punch_out',
        'latitude',
        'longitude',
        'total_active_hours',
    ];
    protected $casts = [
        'latitude' => 'string',
        'longitude' => 'string',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

}
