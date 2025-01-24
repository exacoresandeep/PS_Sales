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
        'phone',
        'email',
        'address',
        'user_zone',
        'pincode',
        'state',
        'district',
        'taluk',
    ];

    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}
