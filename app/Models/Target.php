<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Target extends Model
{
    use HasFactory;
    
    protected $table = 'targets';

    protected $fillable = [
        'employee_id',     
        'month',
        'year',
        'unique_lead',
        'customer_visit',
        'activity_visit',
        'aashiyana',
        'order_quantity',
        'created_by',
    ];
    public function customerType()
    {
        return $this->belongsTo(CustomerType::class, 'customer_types_id');
    }

}
