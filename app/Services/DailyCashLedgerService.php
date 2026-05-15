<?php

namespace App\Services;

use App\Models\DailyCashDay;
use App\Support\DailyCashMetroLedger;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DailyCashLedgerService
{
    public function closingBalance(DailyCashDay $day): float
    {
        $day->load('entries');

        return (float) $day->opening_balance + $day->net();
    }

    public function cashflowPeriodStart(Carbon $date): Carbon
    {
        $m = (int) config('daily_cashflow.period_start_month', 3);
        $d = (int) config('daily_cashflow.period_start_day', 1);

        if ($date->month > $m || ($date->month === $m && $date->day >= $d)) {
            return Carbon::create($date->year, $m, $d)->startOfDay();
        }

        return Carbon::create($date->year - 1, $m, $d)->startOfDay();
    }

    public function resolveOpeningBalance(string $date): float
    {
        $target = Carbon::parse($date)->startOfDay();
        $periodStart = $this->cashflowPeriodStart($target);

        if ($target->equalTo($periodStart)) {
            return 0.0;
        }

        $rows = DailyCashDay::with('entries')
            ->where('date', '>=', $periodStart->toDateString())
            ->where('date', '<', $target->toDateString())
            ->orderBy('date')
            ->get();

        if ($rows->isEmpty()) {
            return 0.0;
        }

        $carry = (float) $rows->first()->opening_balance + $rows->first()->net();
        foreach ($rows->slice(1)->all() as $row) {
            $carry += $row->net();
        }

        return round($carry, 2);
    }

    public function syncOpeningBalancesForwardFrom(DailyCashDay $day): void
    {
        $periodStart = $this->cashflowPeriodStart(Carbon::parse($day->date));
        $fyEnd = $periodStart->copy()->addYear()->subDay();
        $end = Carbon::today()->min($fyEnd);

        $anchor = $day->fresh(['entries']);
        if (! $anchor) {
            return;
        }

        $prevClosing = round($this->closingBalance($anchor), 2);
        $d = Carbon::parse($anchor->date)->copy()->addDay();

        while ($d->lte($end)) {
            $next = DailyCashDay::with('entries')->where('date', $d->toDateString())->first();
            if ($next) {
                $dayOpening = round($prevClosing, 2);
                if (abs((float) $next->opening_balance - $dayOpening) > 0.005) {
                    $next->update(['opening_balance' => $dayOpening]);
                }
                $prevClosing = round($dayOpening + $next->net(), 2);
            }
            $d->addDay();
        }
    }

    public function alignStaleZeroOpening(DailyCashDay $day): void
    {
        $t = Carbon::parse($day->date)->startOfDay();
        if ($t->equalTo($this->cashflowPeriodStart($t))) {
            return;
        }
        if (abs((float) $day->opening_balance) > 0.005) {
            return;
        }
        $expected = $this->resolveOpeningBalance($t->format('Y-m-d'));
        if (abs($expected) <= 0.005) {
            return;
        }
        $day->update(['opening_balance' => $expected]);
        $day->opening_balance = $expected;
        $this->syncOpeningBalancesForwardFrom($day);
    }

    public function isDayEncodable(Carbon $d): bool
    {
        $periodStart = $this->cashflowPeriodStart($d);
        $fyEnd = $periodStart->copy()->addYear()->subDay();
        $maxNav = Carbon::today()->min($fyEnd);

        return $d->gte($periodStart) && $d->lte($maxNav);
    }

    /**
     * @return array{0: bool, 1: string|null} [allowed, error message]
     */
    public function validateDateForNewEntry(string $date): array
    {
        $d = Carbon::parse($date)->startOfDay();
        if (! $this->isDayEncodable($d)) {
            $periodStart = $this->cashflowPeriodStart($d);
            $fyEnd = $periodStart->copy()->addYear()->subDay();
            $maxDate = Carbon::today()->min($fyEnd);

            return [false, 'That date is outside the allowed cash period (from '.$periodStart->format('M j, Y').' through '.$maxDate->format('M j, Y').').'];
        }

        return [true, null];
    }

    public function ensureDay(string $date): DailyCashDay
    {
        $d = Carbon::parse($date)->startOfDay();
        $day = DailyCashDay::firstOrCreate(
            ['date' => $d->toDateString()],
            ['opening_balance' => $this->resolveOpeningBalance($d->toDateString())]
        );
        if ($day->wasRecentlyCreated) {
            $this->syncOpeningBalancesForwardFrom($day);
        }

        return $day;
    }

    /**
     * Ensure fixed worksheet rows exist for this day (Metro layout). Idempotent.
     */
    public function ensureMetroSheetEntries(DailyCashDay $day): void
    {
        DB::transaction(function () use ($day) {
            foreach (DailyCashMetroLedger::stubLines() as $stub) {
                if ($stub['type'] === 'CAPITAL') {
                    $exists = $day->entries()->where('type', 'CAPITAL')->exists();
                } else {
                    $exists = $day->entries()
                        ->where('type', $stub['type'])
                        ->where('category', $stub['category_key'])
                        ->exists();
                }

                if ($exists) {
                    continue;
                }

                $day->entries()->create([
                    'type' => $stub['type'],
                    'category' => $stub['category_key'],
                    'subcategory_override' => null,
                    'description' => strtoupper($stub['label']),
                    'amount' => 0,
                    'sort_order' => $stub['sort'],
                ]);
            }
        });
    }
}
