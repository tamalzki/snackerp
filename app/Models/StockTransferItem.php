<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockTransferItem extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'transfer_id',
        'finished_product_id',
        'quantity',
        'cost_snapshot',
    ];

    protected $casts = [
        'quantity'      => 'decimal:4',
        'cost_snapshot' => 'decimal:4',
    ];

    public function finishedProduct()
    {
        return $this->belongsTo(FinishedProduct::class);
    }

    public function transfer()
    {
        return $this->belongsTo(StockTransfer::class, 'transfer_id');
    }
}