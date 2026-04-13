<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\MonthlyFinancialReport;

class Branch extends Model
{
    protected $fillable = [
        'name',
        'treasurer_name',
    ];

    public function monthlyReports(): HasMany
    {
        return $this->hasMany(MonthlyFinancialReport::class);
    }
}
