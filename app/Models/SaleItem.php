<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaleItem extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'sale_id',
        'finished_product_id',
        'quantity',
        'unit_price',
        'cost_snapshot',
        'total_price',
    ];

    protected $casts = [
        'quantity'      => 'decimal:4',
        'unit_price'    => 'decimal:4',
        'cost_snapshot' => 'decimal:4',
        'total_price'   => 'decimal:2',
    ];

    public function finishedProduct()
    {
        return $this->belongsTo(FinishedProduct::class);
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function getLineProfitAttribute(): float
    {
        return (float) $this->total_price - ((float) $this->cost_snapshot * (float) $this->quantity);
    }
}