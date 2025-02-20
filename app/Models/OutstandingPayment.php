<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OutstandingPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'dealer_id',
        'order_id',
        'invoice_number',
        'invoice_date',
        'invoice_total',
        'due_date',
        'paid_amount',
        'outstanding_amount',
        'payment_doc_number',
        'payment_date',
        'payment_amount_applied',
        'status'
    ];

    public function dealer()
    {
        return $this->belongsTo(Dealer::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
