<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentSubscription extends Model
{
    // Cukup gunakan fillable untuk keamanan (Mass Assignment)
    protected $fillable = [
        'student_id',
        'type',
        'price',
        'qty'
    ];

    /**
     * Relasi ke Student
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Relasi ke Product
     * Pastikan model Product sudah ada di App\Models
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
