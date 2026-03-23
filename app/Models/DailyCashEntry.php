<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyCashEntry extends Model
{
    protected $fillable = ['daily_cash_day_id', 'type', 'description', 'amount', 'sort_order'];

    protected $casts = ['amount' => 'decimal:2'];

    public static array $types = [
        'CAPITAL'       => 'Capital',
        'INCOME'        => 'Income',
        'EXPENSES'      => 'Expenses',
        'PURCHASES'     => 'Purchases',
        'DISCRETIONARY' => 'Discretionary',
        'SAVINGS'       => 'Savings',
        'OTHER'         => 'Other',
    ];

    public static array $typeColors = [
        'CAPITAL'       => 'primary',
        'INCOME'        => 'success',
        'EXPENSES'      => 'danger',
        'PURCHASES'     => 'warning',
        'DISCRETIONARY' => 'secondary',
        'SAVINGS'       => 'info',
        'OTHER'         => 'dark',
    ];

    public function day(): BelongsTo
    {
        return $this->belongsTo(DailyCashDay::class, 'daily_cash_day_id');
    }
}
