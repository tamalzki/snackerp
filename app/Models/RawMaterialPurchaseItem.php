<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RawMaterialPurchaseItem extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'purchase_id',
        'raw_material_id',
        'quantity',
        'cost_per_unit',
        'total_cost',
    ];

    protected $casts = [
        'quantity'     => 'decimal:4',
        'cost_per_unit'=> 'decimal:4',
        'total_cost'   => 'decimal:4',
    ];

    public function rawMaterial()
    {
        return $this->belongsTo(RawMaterial::class);
    }

    public function purchase()
    {
        return $this->belongsTo(RawMaterialPurchase::class, 'purchase_id');
    }
}