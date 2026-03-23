<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BranchInventory extends Model
{
    protected $table = 'branch_inventory';

    protected $fillable = [
        'branch_id',
        'finished_product_id',
        'stock_quantity',
        'cost_snapshot',
    ];

    protected $casts = [
        'stock_quantity' => 'decimal:4',
        'cost_snapshot'  => 'decimal:4',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function finishedProduct()
    {
        return $this->belongsTo(FinishedProduct::class);
    }
}