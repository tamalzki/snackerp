<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RawMaterialPurchase extends Model
{
    protected $fillable = [
        'supplier_name',
        'total_cost',
        'purchase_date',
        'created_by',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'total_cost'    => 'decimal:4',
    ];

    public function items()
    {
        return $this->hasMany(RawMaterialPurchaseItem::class, 'purchase_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}