<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CashAccount extends Model
{
    protected $fillable = ['name', 'balance', 'notes'];

    protected $casts = ['balance' => 'decimal:2'];
}