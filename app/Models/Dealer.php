<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Dealer extends Model
{
    use HasFactory;

    protected $fillable = [
        'dealer_code',
        'dealer_name',
        'approver_id',
        'gst_no',
        'pan_no',
        'phone',
        'email',
        'address',
        'user_zone',
        'pincode',
        'state',
        'district',
        'taluk',
        'assigned_route_id',
    ];

    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}
