<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Deposit extends Model
{
    protected $fillable = [
        'source_type',
        'source_id',
        'amount',
        'deposit_date',
        'reference',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'deposit_date' => 'date',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function source()
    {
        if ($this->source_type === 'cash') {
            return $this->belongsTo(CashAccount::class, 'source_id');
        }

        return $this->belongsTo(BankAccount::class, 'source_id');
    }

    public function getSourceNameAttribute(): string
    {
        if ($this->source_type === 'cash') {
            return CashAccount::find($this->source_id)?->name ?? '—';
        }
        $bank = BankAccount::find($this->source_id);
        if (! $bank) {
            return '—';
        }

        return trim($bank->bank_name.($bank->account_name ? ' — '.$bank->account_name : ''));
    }
}
