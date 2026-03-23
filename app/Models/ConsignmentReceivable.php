<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConsignmentReceivable extends Model
{
    protected $fillable = [
        'stock_transfer_id', 'branch_id', 'dr_number',
        'total_amount', 'amount_paid', 'amount_returned',
        'balance', 'status', 'delivery_date', 'created_by',
    ];

    protected $casts = [
        'total_amount'    => 'decimal:2',
        'amount_paid'     => 'decimal:2',
        'amount_returned' => 'decimal:2',
        'balance'         => 'decimal:2',
        'delivery_date'   => 'date',
    ];

    public function branch()      { return $this->belongsTo(Branch::class); }
    public function transfer()    { return $this->belongsTo(StockTransfer::class, 'stock_transfer_id'); }
    public function sales()       { return $this->hasMany(ConsignmentSale::class); }
    public function payments()    { return $this->hasMany(ConsignmentPayment::class); }
    public function creator()     { return $this->belongsTo(User::class, 'created_by'); }

    public function recalculate(): void
    {
        $paid              = $this->payments()->sum('amount');
        $this->amount_paid = $paid;
        $this->balance     = $this->total_amount - $paid - $this->amount_returned;
        $this->status      = match(true) {
            $this->balance <= 0                              => 'paid',
            $paid > 0 || $this->amount_returned > 0         => 'partial',
            default                                          => 'open',
        };
        $this->save();
    }

    public function applyReturn(float $amount): void
    {
        $this->amount_returned += $amount;
        $this->recalculate();
    }
}