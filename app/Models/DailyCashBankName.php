<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DailyCashBankName extends Model
{
    protected $fillable = ['name', 'sort_order'];

    protected $casts = ['sort_order' => 'integer'];

    public function dayCells(): HasMany
    {
        return $this->hasMany(DailyCashBankDayCell::class);
    }
}
