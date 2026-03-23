<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class ConsignmentSale extends Model
{
    protected $fillable = [
        'consignment_receivable_id', 'branch_id',
        'sale_date_from', 'sale_date_to',
        'total_amount', 'total_cost', 'notes', 'created_by',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'sale_date_from' => 'date',
        'sale_date_to' => 'date',
    ];

    /**
     * Sales whose selling period overlaps [ $from, $to ] (inclusive dates).
     */
    public function scopeOverlappingPeriod(Builder $query, string $from, string $to): Builder
    {
        return $query->whereDate('sale_date_from', '<=', $to)
            ->whereDate('sale_date_to', '>=', $from);
    }

    public function overlapsPeriod(string $from, string $to): bool
    {
        return $this->sale_date_from->format('Y-m-d') <= $to
            && $this->sale_date_to->format('Y-m-d') >= $from;
    }

    public function periodLabel(): string
    {
        $a = $this->sale_date_from instanceof Carbon
            ? $this->sale_date_from
            : Carbon::parse($this->sale_date_from);
        $b = $this->sale_date_to instanceof Carbon
            ? $this->sale_date_to
            : Carbon::parse($this->sale_date_to);

        if ($a->toDateString() === $b->toDateString()) {
            return $a->format('M d, Y');
        }

        return $a->format('M d, Y').' – '.$b->format('M d, Y');
    }

    public function receivable()
    {
        return $this->belongsTo(ConsignmentReceivable::class, 'consignment_receivable_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function items()
    {
        return $this->hasMany(ConsignmentSaleItem::class);
    }

    public function payments()
    {
        return $this->hasMany(ConsignmentPayment::class, 'consignment_sale_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getGrossProfitAttribute(): float
    {
        return (float) $this->total_amount - (float) $this->total_cost;
    }
}
