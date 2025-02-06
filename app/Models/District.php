<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class District extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'status'];

    public function tripRoutes()
    {
        return $this->hasMany(TripRoute::class);
    }
}
