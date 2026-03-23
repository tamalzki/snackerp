<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FinishedProduct extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'type',
        'selling_price',
        'low_stock_threshold',
        'current_stock',
        'average_cost',
    ];

    protected $casts = [
        'current_stock' => 'decimal:4',
        'average_cost' => 'decimal:4',
        'selling_price' => 'decimal:4',
        'low_stock_threshold' => 'decimal:4',
    ];

    public function isLowStock(): bool
    {
        return $this->low_stock_threshold > 0
            && $this->current_stock <= $this->low_stock_threshold;
    }

    public function isManufactured(): bool
    {
        return $this->type === 'manufactured';
    }

    public function isResale(): bool
    {
        return $this->type === 'resale';
    }

    public function productionBatches()
    {
        return $this->hasMany(ProductionBatch::class);
    }

    public function branchInventory()
    {
        return $this->hasMany(BranchInventory::class);
    }

    public function restocks()
    {
        return $this->hasMany(ProductRestock::class);
    }

    public function stockAdjustments()
    {
        return $this->hasMany(FinishedProductStockAdjustment::class);
    }
}
