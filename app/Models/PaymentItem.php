<?php

namespace App\Models;

use App\Models\BillItem;
use Illuminate\Database\Eloquent\Model;

class PaymentItem extends Model
{

    protected $fillable = [
        'payment_id',
        'bill_item_id',
        'amount',
    ];

    public function payment()
{
    return $this->belongsTo(Payment::class);
}

public function billItem()
{
    return $this->belongsTo(BillItem::class);
}
}
