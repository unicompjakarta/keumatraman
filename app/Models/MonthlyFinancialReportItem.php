<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MonthlyFinancialReportItem extends Model
{
    protected $fillable = [
        'monthly_financial_report_id',
        'type',
        'entry_date',
        'target_month',
        'target_year',
        'category',
        'description',
        'amount',
        'sort_order',
    ];

    protected $casts = [
        'entry_date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function report(): BelongsTo
    {
        return $this->belongsTo(MonthlyFinancialReport::class, 'monthly_financial_report_id');
    }
}
