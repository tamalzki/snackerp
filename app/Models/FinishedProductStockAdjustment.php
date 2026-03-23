<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinishedProductStockAdjustment extends Model
{
    protected $fillable = [
        'finished_product_id',
        'quantity_before',
        'quantity_after',
        'difference',
        'reason',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'quantity_before' => 'decimal:4',
        'quantity_after' => 'decimal:4',
        'difference' => 'decimal:4',
    ];

    public function finishedProduct(): BelongsTo
    {
        return $this->belongsTo(FinishedProduct::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
