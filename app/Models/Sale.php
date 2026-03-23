<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    protected $fillable = [
        'branch_id',
        'sale_date',
        'total_amount',
        'total_cost',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'sale_date'    => 'date',
        'total_amount' => 'decimal:2',
        'total_cost'   => 'decimal:2',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function items()
    {
        return $this->hasMany(SaleItem::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getGrossProfitAttribute(): float
    {
        return (float) $this->total_amount - (float) $this->total_cost;
    }

    public function getMarginAttribute(): float
    {
        if ($this->total_amount == 0) return 0;
        return ($this->gross_profit / $this->total_amount) * 100;
    }
}