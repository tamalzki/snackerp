<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductionBatch extends Model
{
    protected $fillable = [
    'finished_product_id',
    'expected_output_qty',
    'actual_output_qty',
    'reject_qty',
    'total_raw_material_cost',
    'cost_per_unit',
    'production_date',
    'expiry_date',
    'created_by',
    ];

    protected $casts = [
    'expected_output_qty'     => 'decimal:4',
    'actual_output_qty'       => 'decimal:4',
    'reject_qty'              => 'decimal:4',
    'total_raw_material_cost' => 'decimal:4',
    'cost_per_unit'           => 'decimal:4',
    'production_date'         => 'date',
    'expiry_date'             => 'date',
    ];

    public function finishedProduct()
    {
        return $this->belongsTo(FinishedProduct::class);
    }

    public function items()
    {
        return $this->hasMany(ProductionBatchItem::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getRejectRateAttribute(): float
    {
        if (!$this->expected_output_qty) return 0;
        return round(($this->reject_qty / $this->expected_output_qty) * 100, 2);
    }

    public function isExpired(): bool
{
    return $this->expiry_date && $this->expiry_date->isPast();
}

public function isExpiringSoon(): bool
{
    return $this->expiry_date
        && !$this->isExpired()
        && $this->expiry_date->diffInDays(now()) <= 7;
}
}