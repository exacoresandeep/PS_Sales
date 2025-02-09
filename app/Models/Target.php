<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Target extends Model
{
    use HasFactory;
    
    protected $table = 'target';

    protected $fillable = [
        'employee_id', 
        'customer_types_id',        
        'target_type_flag',        
        'month',
        'year',
        'ton_quantity',
        'no_quantity',
        'created_by',
    ];
    public function customerType()
    {
        return $this->belongsTo(CustomerType::class, 'customer_types_id');
    }

}
