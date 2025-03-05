<?php

namespace App\Models;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Dealer extends Model
{
    use HasApiTokens, HasFactory;

    protected $fillable = [
        'dealer_code',
        'dealer_name',
        'gst_no',
        'pan_no',
        'phone',
        'email',
        'address',
        'password',
        'user_zone',
        'pincode',
        'state',
        'district_id',
        'district',
        'taluk',
        'location',
        'assigned_route_id',
    ];

    public function orders()
    {
        return $this->hasMany(Order::class);
    }
    public function district()
    {
        return $this->belongsTo(District::class, 'district_id');
    }
}
