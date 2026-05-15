<?php

use App\Models\DailyCashDay;
use App\Models\DailyCashEntry;
use App\Services\DailyCashLedgerService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\App;

/**
 * Legacy worksheet category keys removed when Expense — Assorted / Suman layout changed.
 */
return new class extends Migration
{
    /** @var list<string> */
    private array $obsoleteCategories = [
        'metro_exp_assorted_salaries_wages',
        'metro_exp_suman_ingredients',
        'metro_exp_suman_cooking_fuel',
        'metro_exp_suman_transportation',
        'metro_exp_suman_electricity_water',
        'metro_exp_suman_salaries_wages',
        'metro_exp_suman_repair_maintenance',
        'metro_exp_suman_label_packaging',
    ];

    public function up(): void
    {
        $dayIds = DailyCashEntry::query()
            ->whereIn('category', $this->obsoleteCategories)
            ->distinct()
            ->pluck('daily_cash_day_id');

        DailyCashEntry::query()->whereIn('category', $this->obsoleteCategories)->delete();

        if ($dayIds->isEmpty()) {
            return;
        }

        /** @var DailyCashLedgerService $ledger */
        $ledger = App::make(DailyCashLedgerService::class);

        DailyCashDay::query()
            ->whereIn('id', $dayIds)
            ->orderBy('date')
            ->each(fn (DailyCashDay $day) => $ledger->syncOpeningBalancesForwardFrom($day));
    }

    public function down(): void
    {
        // Deleted ledger rows are not restored.
    }
};
