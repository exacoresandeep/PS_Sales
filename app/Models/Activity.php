<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Activity extends Model
{
    use HasFactory;

    protected $table = 'activities';

    protected $fillable = [
        'activity_type_id',
        'dealer_id',
        'employee_id',
        'assigned_date',
        'due_date',
        'instructions',
        'status',
        'record_details',
        'attachments',
        'completed_date'
    ];
    protected $casts = [
        'attachments' => 'array', 
        'completed_date' => 'date',
    ];
    

    public function activityType()
    {
        return $this->belongsTo(ActivityType::class);
    }

    public function dealer()
    {
        return $this->belongsTo(Dealer::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
