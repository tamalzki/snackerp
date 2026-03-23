<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductRestock extends Model
{
    protected $fillable = [
        'finished_product_id',
        'quantity',
        'unit_cost',
        'total_cost',
        'restock_date',
        'supplier',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'quantity'     => 'decimal:4',
        'unit_cost'    => 'decimal:4',
        'total_cost'   => 'decimal:4',
        'restock_date' => 'date',
    ];

    public function finishedProduct()
    {
        return $this->belongsTo(FinishedProduct::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}