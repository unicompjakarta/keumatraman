<?php

namespace App\Models;

use App\Models\Bill;
use App\Models\PaymentItem;
use App\Models\PaymentMethod;
use Illuminate\Database\Eloquent\Model;


class Payment extends Model
{

    protected $fillable = [
        'bill_id',
        'payment_date',
        'payment_method_id',
        'note',
    ];

    public function bill()
    {
        return $this->belongsTo(Bill::class);
    }

    public function items()
    {
        return $this->hasMany(PaymentItem::class);
    }
    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class);
    }
}
