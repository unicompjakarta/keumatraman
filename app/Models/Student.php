<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Pic;
use App\Models\StudentSubscription;
use App\Models\Bill;

class Student extends Model
{
    // Tambahkan ini agar field bisa diisi lewat form (Mass Assignment)
    protected $guarded = [];

    public function pic()
{
    return $this->belongsTo(Student::class, 'pic_id');
}

    public function subscriptions()
    {
        return $this->hasMany(StudentSubscription::class);
    }

    public function bills()
    {
        return $this->hasMany(Bill::class);
    }

    public function children()
        {
            return $this->hasMany(Student::class, 'pic_id');
        }
}
