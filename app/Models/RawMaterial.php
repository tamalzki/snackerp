<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RawMaterial extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'category',
        'unit',
        'stock_quantity',
        'cost_per_unit',
        'low_stock_threshold',
    ];

    protected $casts = [
        'stock_quantity'      => 'decimal:4',
        'cost_per_unit'       => 'decimal:4',
        'low_stock_threshold' => 'decimal:4',
    ];

    public function isLowStock(): bool
    {
        return $this->low_stock_threshold > 0
            && $this->stock_quantity <= $this->low_stock_threshold;
    }
}