<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Employee extends Authenticatable
{
    use HasFactory, HasApiTokens;

    protected $fillable = [
        'employee_code', 'name', 'designation', 'email', 'phone', 
        'employee_type_id', 'password', 'address', 'photo', 'emergency_contact', 'district', 'area', 'reporting_manager'
    ];

    public function employeeType()
    {
        return $this->belongsTo(EmployeeType::class, 'employee_type_id');
    }
    public function reportingManager()
    {
        return $this->belongsTo(Employee::class, 'reporting_manager');
    }
    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }
    public function district()
    {
        return $this->belongsTo(District::class);
    }

    public function region()
    {
        return $this->hasOneThrough(Regions::class, District::class, 'id', 'id', 'district_id', 'regions_id');
    }
    
}
