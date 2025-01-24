<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lead extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'customer_type',
        'customer_name',
        'email',
        'phone',
        'address',
        'instructions',
        'record_details',
        'attachments',
        'latitude',
        'longitude',
        'status',
        'created_by',
    ];

    protected $casts = [
        'attachments' => 'array', 
    ];
    public function customerType()
    {
        return $this->belongsTo(CustomerType::class, 'customer_type');
    }
    public function orders()
    {
        return $this->hasMany(Order::class, 'lead_id');
    }
}
