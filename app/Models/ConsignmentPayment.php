<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConsignmentPayment extends Model
{
    protected $fillable = [
        'consignment_receivable_id', 'consignment_sale_id', 'branch_id',
        'amount', 'payment_date', 'reference', 'notes', 'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'date',
    ];

    public function receivable()
    {
        return $this->belongsTo(ConsignmentReceivable::class, 'consignment_receivable_id');
    }

    public function sale()
    {
        return $this->belongsTo(ConsignmentSale::class, 'consignment_sale_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isWithSaleEntry(): bool
    {
        return $this->consignment_sale_id !== null;
    }
}
