<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VehicleType extends Model
{
    use HasFactory;

    // Define the table name if it differs from the default 'vehicle_types'
    protected $table = 'vehicle_type';

    // Specify the fillable fields to allow mass assignment
    protected $fillable = [
        'vehicle_type_name',
        'vehicle_category_id',
        'status',
    ];

    // Define the relationship with VehicleCategory
    public function category()
    {
        return $this->belongsTo(VehicleCategory::class, 'vehicle_category_id');
    }
}
