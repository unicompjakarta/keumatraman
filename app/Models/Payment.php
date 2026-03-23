<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Bill;
use App\Models\PaymentItem;
class Payment extends Model
{

    protected $fillable = [
        'bill_id',
        'payment_date',
        'payment_method_id',
    ];

    public function bill()
    {
        return $this->belongsTo(Bill::class);
    }

    public function items()
    {
        return $this->hasMany(PaymentItem::class);
    }
}
