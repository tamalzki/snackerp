<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyCashBankDayCell extends Model
{
    protected $table = 'daily_cash_bank_day_cells';

    protected $fillable = [
        'daily_cash_day_id',
        'daily_cash_bank_name_id',
        'movement',
        'amount',
        'detail',
    ];

    protected $casts = ['amount' => 'decimal:2'];

    public const MOVEMENT_DEPOSIT = 'deposit';

    public const MOVEMENT_WITHDRAWAL = 'withdrawal';

    public const MOVEMENT_OTHER = 'other';

    /** @return list<string> */
    public static function movements(): array
    {
        return [self::MOVEMENT_DEPOSIT, self::MOVEMENT_WITHDRAWAL, self::MOVEMENT_OTHER];
    }

    public function day(): BelongsTo
    {
        return $this->belongsTo(DailyCashDay::class, 'daily_cash_day_id');
    }

    public function bankName(): BelongsTo
    {
        return $this->belongsTo(DailyCashBankName::class, 'daily_cash_bank_name_id');
    }
}
