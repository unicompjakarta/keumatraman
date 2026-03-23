<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Student;
use App\Models\BillItem;
use App\Models\Payment;

class Bill extends Model
{   protected $guarded = [];
    public function student()
{
    return $this->belongsTo(Student::class);
}

public function items()
{
    return $this->hasMany(BillItem::class);
}

public function payments()
{
    return $this->hasMany(Payment::class);
}
}
