<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pengajuan extends Model
{
    protected $fillable = [
        'tanggal',
        'keperluan',
        'keterangan',
        'jumlah',
        'status',
    ];
}
