<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    protected $fillable = [
        'title',
        'category',
        'amount',
        'expense_date',
        'paid_from',
        'source_id',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'expense_date' => 'date',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getSourceNameAttribute(): string
    {
        if ($this->paid_from === 'cash') {
            return CashAccount::find($this->source_id)?->name ?? '—';
        }
        $bank = BankAccount::find($this->source_id);
        if (! $bank) {
            return '—';
        }

        return trim($bank->bank_name.($bank->account_name ? ' — '.$bank->account_name : ''));
    }
}
