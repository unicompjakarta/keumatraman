<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MonthlyFinancialReport extends Model
{
    protected $fillable = [
        'branch_id',
        'month',
        'year',
        'employee_total',
        'employee_contributor_total',
        'monthly_target_amount',
        'opening_balance',
        'central_fund_received',
        'total_sent_amount',
        'mandatory_amount',
        'sunnah_amount',
        'sent_date',
        'closing_balance',
        'status',
        'is_locked',
        'notes',
    ];

    protected $casts = [
        'monthly_target_amount' => 'decimal:2',
        'opening_balance' => 'decimal:2',
        'central_fund_received' => 'decimal:2',
        'total_sent_amount' => 'decimal:2',
        'mandatory_amount' => 'decimal:2',
        'sunnah_amount' => 'decimal:2',
        'closing_balance' => 'decimal:2',
        'sent_date' => 'date',
        'is_locked' => 'boolean',
    ];

    protected $appends = [
        'expense_total',
        'proposal_total',
        'total_balance',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(MonthlyFinancialReportItem::class);
    }

    public function expenseItems(): HasMany
    {
        return $this->hasMany(MonthlyFinancialReportItem::class)
            ->where('type', 'expense')
            ->orderByRaw('COALESCE(sort_order, 999999), id');
    }

    public function proposalItems(): HasMany
    {
        return $this->hasMany(MonthlyFinancialReportItem::class)
            ->where('type', 'proposal')
            ->orderByRaw('COALESCE(sort_order, 999999), id');
    }

    public function getExpenseTotalAttribute(): float
    {
        if ($this->relationLoaded('items')) {
            return (float) $this->items
                ->where('type', 'expense')
                ->sum(fn ($item) => (float) $item->amount);
        }

        return (float) $this->items()
            ->where('type', 'expense')
            ->sum('amount');
    }

    public function getProposalTotalAttribute(): float
    {
        if ($this->relationLoaded('items')) {
            return (float) $this->items
                ->where('type', 'proposal')
                ->sum(fn ($item) => (float) $item->amount);
        }

        return (float) $this->items()
            ->where('type', 'proposal')
            ->sum('amount');
    }

    public function getTotalBalanceAttribute(): float
    {
        return (float) $this->opening_balance + (float) $this->central_fund_received;
    }

    public function recalculateClosingBalance(): void
    {
        $expenseTotal = (float) $this->items()
            ->where('type', 'expense')
            ->sum('amount');

        $this->closing_balance =
            ((float) $this->opening_balance + (float) $this->central_fund_received)
            - $expenseTotal;

        $this->save();
    }
}
