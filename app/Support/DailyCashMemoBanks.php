<?php

namespace App\Support;

use App\Models\DailyCashBankName;
use Illuminate\Support\Collection;

class DailyCashMemoBanks
{
    /** @return list<string> */
    public static function presetNames(): array
    {
        $raw = config('daily_cashflow.cash_in_bank_memo_banks', []);

        return collect(is_array($raw) ? $raw : [])->filter(fn ($name) => is_string($name) && trim($name) !== '')->values()->map(fn ($name) => trim($name))->all();
    }

    /** @return Collection<int, DailyCashBankName> */
    public static function syncPresetBanksOrdered(): Collection
    {
        $names = static::presetNames();
        foreach ($names as $i => $name) {
            DailyCashBankName::query()->updateOrCreate(
                ['name' => $name],
                ['sort_order' => $i],
            );
        }

        return DailyCashBankName::query()
            ->whereIn('name', $names)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    /** @return list<int> */
    public static function allowedBankNameIds(): array
    {
        return static::syncPresetBanksOrdered()->pluck('id')->map(fn ($id) => (int) $id)->unique()->values()->all();
    }
}
