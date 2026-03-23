<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductionBatchItem extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'production_batch_id',
        'raw_material_id',
        'quantity_used',
        'cost_snapshot',
        'total_cost',
    ];

    protected $casts = [
        'quantity_used' => 'decimal:4',
        'cost_snapshot' => 'decimal:4',
        'total_cost'    => 'decimal:4',
    ];

    public function rawMaterial()
    {
        return $this->belongsTo(RawMaterial::class);
    }

    public function batch()
    {
        return $this->belongsTo(ProductionBatch::class, 'production_batch_id');
    }
}