<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConsignmentSaleItem extends Model
{
    protected $fillable = [
        'consignment_sale_id', 'finished_product_id',
        'qty_sold', 'unit_price', 'cost_snapshot', 'total_price',
    ];

    protected $casts = [
        'qty_sold'      => 'decimal:4',
        'unit_price'    => 'decimal:4',
        'cost_snapshot' => 'decimal:4',
        'total_price'   => 'decimal:2',
    ];

    public function finishedProduct() { return $this->belongsTo(FinishedProduct::class); }
    public function sale()            { return $this->belongsTo(ConsignmentSale::class, 'consignment_sale_id'); }
}