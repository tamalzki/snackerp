<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DailyCashDay extends Model
{
    protected $fillable = ['date', 'opening_balance', 'notes'];

    protected $casts = ['date' => 'date', 'opening_balance' => 'decimal:2'];

    public function entries(): HasMany
    {
        return $this->hasMany(DailyCashEntry::class)->orderBy('sort_order')->orderBy('id');
    }

    // --- Summary helpers ---

    public function totalByType(string|array $types): float
    {
        $types = (array) $types;
        return (float) $this->entries->whereIn('type', $types)->sum('amount');
    }

    public function capital(): float      { return $this->totalByType('CAPITAL'); }
    public function income(): float       { return $this->totalByType('INCOME'); }
    public function expenses(): float     { return $this->totalByType(['EXPENSES', 'PURCHASES']); }
    public function discretionary(): float{ return $this->totalByType('DISCRETIONARY'); }
    public function savings(): float      { return $this->totalByType('SAVINGS'); }

    public function net(): float
    {
        return $this->capital() + $this->income() - $this->expenses() - $this->discretionary() - $this->savings();
    }

    // Monthly totals (all days in the same month)
    public function monthlyTotalByType(string|array $types): float
    {
        $types = (array) $types;
        return (float) DailyCashEntry::whereIn('type', $types)
            ->whereHas('day', fn($q) => $q->whereYear('date', $this->date->year)
                                          ->whereMonth('date', $this->date->month))
            ->sum('amount');
    }

    public function monthlyCapital(): float      { return $this->monthlyTotalByType('CAPITAL'); }
    public function monthlyIncome(): float       { return $this->monthlyTotalByType('INCOME'); }
    public function monthlyExpenses(): float     { return $this->monthlyTotalByType(['EXPENSES', 'PURCHASES']); }
    public function monthlyDiscretionary(): float{ return $this->monthlyTotalByType('DISCRETIONARY'); }
    public function monthlySavings(): float      { return $this->monthlyTotalByType('SAVINGS'); }
    public function monthlyNet(): float
    {
        return $this->monthlyCapital() + $this->monthlyIncome()
             - $this->monthlyExpenses() - $this->monthlyDiscretionary() - $this->monthlySavings();
    }
}
