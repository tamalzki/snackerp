<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\CashAccount;
use App\Models\DailyCashDay;
use App\Models\DailyCashEntry;
use App\Models\Deposit;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DailyCashController extends Controller
{
    // Compute the closing balance of a day (opening + net)
    private function closingBalance(DailyCashDay $day): float
    {
        $day->load('entries');

        return (float) $day->opening_balance + $day->net();
    }

    /** First day of the cashflow period that contains $date (e.g. Mar 1 each year). */
    private function cashflowPeriodStart(Carbon $date): Carbon
    {
        $m = (int) config('daily_cashflow.period_start_month', 3);
        $d = (int) config('daily_cashflow.period_start_day', 1);

        if ($date->month > $m || ($date->month === $m && $date->day >= $d)) {
            return Carbon::create($date->year, $m, $d)->startOfDay();
        }

        return Carbon::create($date->year - 1, $m, $d)->startOfDay();
    }

    /**
     * Opening for a new day: closing balance after all prior days in the same period,
     * using the first day’s stored opening as the anchor and summing each prior day’s net
     * (capital, income, expenses, savings, etc.).
     */
    private function resolveOpeningBalance(string $date): float
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

    /** Keep stored opening_balance on later days aligned after this day’s closing changes. */
    private function syncOpeningBalancesForwardFrom(DailyCashDay $day): void
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

    /**
     * Heal rows that still show ~0 opening while earlier days in the period imply a carry.
     * Zero-net days already contribute net 0 to the chain; this fixes missed sync / old rows.
     */
    private function alignStaleZeroOpening(DailyCashDay $day): void
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

    /** Whether this calendar day can be opened in Daily Cash (within FY, not future). */
    private function isDayEncodable(Carbon $d): bool
    {
        $periodStart = $this->cashflowPeriodStart($d);
        $fyEnd = $periodStart->copy()->addYear()->subDay();
        $maxNav = Carbon::today()->min($fyEnd);

        return $d->gte($periodStart) && $d->lte($maxNav);
    }

    /** Same weekday, previous week (arrow left). */
    private function weekPrevNav(DailyCashDay $dailyCash): ?array
    {
        $prev = Carbon::parse($dailyCash->date)->copy()->subWeek();
        if (! $this->isDayEncodable($prev)) {
            return null;
        }

        $prevDay = DailyCashDay::where('date', $prev->toDateString())->first();

        return [
            'url' => $prevDay
                ? route('daily-cash.show', $prevDay, absolute: false)
                : route('daily-cash.open-date', ['date' => $prev->format('Y-m-d')], absolute: false),
            'label' => $prev->format('F j'),
        ];
    }

    /** Same weekday, next week (arrow right). */
    private function weekNextNav(DailyCashDay $dailyCash): ?array
    {
        $next = Carbon::parse($dailyCash->date)->copy()->addWeek();
        if (! $this->isDayEncodable($next)) {
            return null;
        }

        $nextDay = DailyCashDay::where('date', $next->toDateString())->first();

        return [
            'url' => $nextDay
                ? route('daily-cash.show', $nextDay, absolute: false)
                : route('daily-cash.open-date', ['date' => $next->format('Y-m-d')], absolute: false),
            'label' => $next->format('F j'),
        ];
    }

    /**
     * One ISO week (Mon → Sun) containing the viewed day, stopping at **today** (no future dates).
     *
     * @return list<array{date: Carbon, day: DailyCashDay|null, inRange: bool}>
     */
    private function buildWeekStrip(DailyCashDay $dailyCash): array
    {
        $view = Carbon::parse($dailyCash->date)->startOfDay();
        $weekStart = $view->copy()->startOfWeek(Carbon::MONDAY);
        $weekEnd = $weekStart->copy()->addDays(6);
        $today = Carbon::today()->startOfDay();
        $lastDay = $weekEnd->copy()->min($today);

        $existing = DailyCashDay::query()
            ->where('date', '>=', $weekStart->toDateString())
            ->where('date', '<=', $lastDay->toDateString())
            ->get()
            ->keyBy(fn ($d) => $d->date->format('Y-m-d'));

        $strip = [];
        for ($d = $weekStart->copy(); $d->lte($lastDay); $d->addDay()) {
            $key = $d->format('Y-m-d');
            $strip[] = [
                'date' => $d->copy(),
                'day' => $existing->get($key),
                'inRange' => $this->isDayEncodable($d),
            ];
        }

        return $strip;
    }

    private function formatDayRangeLabel(Carbon $first, Carbon $last): string
    {
        if ($first->equalTo($last)) {
            return $first->format('F j, Y');
        }
        if ($first->month === $last->month && $first->year === $last->year) {
            return $first->format('F j').' – '.$last->format('j, Y');
        }
        if ($first->year === $last->year) {
            return $first->format('F j').' – '.$last->format('F j, Y');
        }

        return $first->format('F j, Y').' – '.$last->format('F j, Y');
    }

    // List recent days; auto-create today if missing
    public function index(Request $request)
    {
        $tab = $request->get('tab', 'daily');

        // Always ensure today exists
        $today = Carbon::today()->toDateString();
        if (! DailyCashDay::where('date', $today)->exists()) {
            $boot = DailyCashDay::create([
                'date' => $today,
                'opening_balance' => $this->resolveOpeningBalance($today),
            ]);
            $this->syncOpeningBalancesForwardFrom($boot);
        }

        $days = null;
        $monthly = null;
        $annual = null;
        $filterYear = (int) $request->get('year', now()->year);
        $years = DailyCashDay::selectRaw('YEAR(date) as yr')->groupBy('yr')->orderByDesc('yr')->pluck('yr');
        if ($tab === 'monthly') {
            $monthly = $this->buildMonthlySummary($filterYear);
        } elseif ($tab === 'annual') {
            $annual = $this->buildAnnualSummary();
        } else {
            $days = DailyCashDay::with('entries')->orderByDesc('date')->paginate(30);
        }

        return view('daily-cash.index', compact('days', 'monthly', 'annual', 'tab', 'filterYear', 'years'));
    }

    private function entryTotals($entries): array
    {
        $capital = (float) $entries->where('type', 'CAPITAL')->sum('amount');
        $income = (float) $entries->where('type', 'INCOME')->sum('amount');
        $expenses = (float) $entries->whereIn('type', ['EXPENSES', 'PURCHASES'])->sum('amount');
        $discretionary = (float) $entries->where('type', 'DISCRETIONARY')->sum('amount');
        $savings = (float) $entries->where('type', 'SAVINGS')->sum('amount');
        $other = (float) $entries->where('type', 'OTHER')->sum('amount');

        return compact('capital', 'income', 'expenses', 'discretionary', 'savings', 'other') + [
            'net' => $capital + $income - $expenses - $discretionary - $savings - $other,
        ];
    }

    private function buildMonthlySummary(int $year): array
    {
        $rows = [];
        for ($m = 1; $m <= 12; $m++) {
            $days = DailyCashDay::with('entries')
                ->whereYear('date', $year)
                ->whereMonth('date', $m)
                ->orderBy('date')
                ->get();

            if ($days->isEmpty()) {
                continue;
            }

            $allEntries = $days->flatMap(fn ($d) => $d->entries);
            $totals = $this->entryTotals($allEntries);

            // Build day-level rows for the accordion detail
            $dayRows = $days->map(function ($day) {
                $t = $this->entryTotals($day->entries);

                return array_merge(['day' => $day], $t);
            })->all();

            $rows[] = array_merge(
                ['label' => Carbon::createFromDate($year, $m, 1)->format('F Y'),
                    'month_key' => "m{$year}{$m}",
                    'days' => $dayRows],
                $totals
            );
        }

        return $rows;
    }

    private function buildAnnualSummary(): array
    {
        $rows = [];
        $years = DailyCashDay::selectRaw('YEAR(date) as yr')->groupBy('yr')->orderByDesc('yr')->pluck('yr');

        foreach ($years as $year) {
            $monthRows = [];
            for ($m = 1; $m <= 12; $m++) {
                $days = DailyCashDay::with('entries')
                    ->whereYear('date', $year)
                    ->whereMonth('date', $m)
                    ->orderBy('date')
                    ->get();
                if ($days->isEmpty()) {
                    continue;
                }
                $allEntries = $days->flatMap(fn ($d) => $d->entries);
                $t = $this->entryTotals($allEntries);
                $monthRows[] = array_merge(
                    ['label' => Carbon::createFromDate($year, $m, 1)->format('F'), 'year' => $year, 'month' => $m],
                    $t
                );
            }

            $allEntries = DailyCashEntry::whereHas('day', fn ($q) => $q->whereYear('date', $year))->get();
            $totals = $this->entryTotals($allEntries);

            $rows[] = array_merge(
                ['label' => (string) $year, 'year_key' => "y{$year}", 'months' => $monthRows],
                $totals
            );
        }

        return $rows;
    }

    // Auto-redirect to today
    public function today()
    {
        $today = Carbon::today()->toDateString();
        if (! DailyCashDay::where('date', $today)->exists()) {
            $boot = DailyCashDay::create([
                'date' => $today,
                'opening_balance' => $this->resolveOpeningBalance($today),
            ]);
            $this->syncOpeningBalancesForwardFrom($boot);
        }
        $day = DailyCashDay::where('date', $today)->first();

        return redirect()->route('daily-cash.show', $day);
    }

    /** Create the day row if missing, then show (e.g. opening next/prev day that has no row yet). */
    public function openDate(string $date)
    {
        $d = Carbon::parse($date)->startOfDay();
        $periodStart = $this->cashflowPeriodStart($d);
        $fyEnd = $periodStart->copy()->addYear()->subDay();
        $maxDate = Carbon::today()->min($fyEnd);

        if ($d->lt($periodStart) || $d->gt($maxDate)) {
            return redirect()->route('daily-cash.today')
                ->with('error', 'That date is outside the allowed cash period (from '.$periodStart->format('M j, Y').' through '.$maxDate->format('M j, Y').').');
        }

        $day = DailyCashDay::firstOrCreate(
            ['date' => $d->toDateString()],
            ['opening_balance' => $this->resolveOpeningBalance($d->toDateString())]
        );
        if ($day->wasRecentlyCreated) {
            $this->syncOpeningBalancesForwardFrom($day);
        }

        return redirect()->route('daily-cash.show', $day);
    }

    // Show a single day
    public function show(DailyCashDay $dailyCash)
    {
        $dailyCash->load('entries');
        $this->alignStaleZeroOpening($dailyCash);
        $dailyCash->refresh();
        $dailyCash->load('entries');

        $prevWeekNav = $this->weekPrevNav($dailyCash);
        $nextWeekNav = $this->weekNextNav($dailyCash);
        $weekStrip = $this->buildWeekStrip($dailyCash);

        $weekRangeLabel = count($weekStrip) > 0
            ? $this->formatDayRangeLabel($weekStrip[0]['date'], $weekStrip[count($weekStrip) - 1]['date'])
            : '';

        $bankAccounts = BankAccount::orderBy('bank_name')->get();
        $dayDeposits = Deposit::where('deposit_date', $dailyCash->date)->latest()->get();

        return view('daily-cash.show', compact(
            'dailyCash',
            'prevWeekNav',
            'nextWeekNav',
            'weekStrip',
            'weekRangeLabel',
            'bankAccounts',
            'dayDeposits'
        ));
    }

    // Create a specific date's record
    public function store(Request $request)
    {
        $request->validate(['date' => 'required|date|unique:daily_cash_days,date']);
        $day = DailyCashDay::create([
            'date' => $request->date,
            'opening_balance' => $this->resolveOpeningBalance($request->date),
        ]);
        $this->syncOpeningBalancesForwardFrom($day);

        return redirect()->route('daily-cash.show', $day)->with('success', 'Day created. Later days in this period were synced if needed.');
    }

    // Update opening balance / notes
    public function update(Request $request, DailyCashDay $dailyCash)
    {
        if ($request->boolean('total_cash_mode')) {
            $request->validate(['total_cash' => 'required|numeric|min:0']);
            // Back-calculate: opening_balance = total_cash - net
            $dailyCash->load('entries');
            $openingBalance = (float) $request->total_cash - $dailyCash->net();
            $dailyCash->update(['opening_balance' => $openingBalance]);
        } else {
            $request->validate([
                'opening_balance' => 'required|numeric|min:0',
                'notes' => 'nullable|string|max:500',
            ]);
            $dailyCash->update($request->only('opening_balance', 'notes'));
        }

        $dailyCash->refresh();
        $this->syncOpeningBalancesForwardFrom($dailyCash);

        return back()->with('success', 'Updated. Opening balances on later days in this period were synced.');
    }

    // Add an entry to a day
    public function storeEntry(Request $request, DailyCashDay $dailyCash)
    {
        $request->validate([
            'type' => 'required|in:CAPITAL,INCOME,EXPENSES,PURCHASES,DISCRETIONARY,SAVINGS,OTHER',
            'description' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0.01',
        ]);

        $max = $dailyCash->entries()->max('sort_order') ?? 0;
        $dailyCash->entries()->create([
            'type' => $request->type,
            'description' => strtoupper($request->description),
            'amount' => $request->amount,
            'sort_order' => $max + 1,
        ]);

        $this->syncOpeningBalancesForwardFrom($dailyCash);

        return back()->with('success', 'Entry added. Later days in this period were synced to the new balances.');
    }

    // Update an entry
    public function updateEntry(Request $request, DailyCashDay $dailyCash, DailyCashEntry $entry)
    {
        abort_if($entry->daily_cash_day_id !== $dailyCash->id, 404);
        $request->validate([
            'type' => 'required|in:CAPITAL,INCOME,EXPENSES,PURCHASES,DISCRETIONARY,SAVINGS,OTHER',
            'description' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0.01',
        ]);
        $entry->update([
            'type' => $request->type,
            'description' => strtoupper($request->description),
            'amount' => $request->amount,
        ]);

        $this->syncOpeningBalancesForwardFrom($dailyCash);

        return back()->with('success', 'Entry updated. Later days in this period were synced to the new balances.');
    }

    // Delete an entry
    public function destroyEntry(DailyCashDay $dailyCash, DailyCashEntry $entry)
    {
        abort_if($entry->daily_cash_day_id !== $dailyCash->id, 404);
        $entry->delete();

        $this->syncOpeningBalancesForwardFrom($dailyCash);

        return back()->with('success', 'Entry deleted. Later days in this period were synced to the new balances.');
    }

    // Deposit to bank: creates a Deposit record + SAVINGS entry on this day
    public function depositToBank(Request $request, DailyCashDay $dailyCash)
    {
        $request->validate([
            'source_type' => 'required|in:cash,bank',
            'source_id' => 'required|integer',
            'amount' => 'required|numeric|min:0.01',
            'reference' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:500',
        ]);

        DB::transaction(function () use ($request, $dailyCash) {
            // Resolve account name for the SAVINGS entry description
            $accountName = $request->source_type === 'cash'
                ? (CashAccount::find($request->source_id)?->name ?? 'Cash')
                : (function () use ($request) {
                    $b = BankAccount::find($request->source_id);

                    return $b ? trim($b->bank_name.($b->account_name ? ' — '.$b->account_name : '')) : 'Bank';
                })();

            // Create the formal Deposit record
            Deposit::create([
                'source_type' => $request->source_type,
                'source_id' => $request->source_id,
                'amount' => $request->amount,
                'deposit_date' => $dailyCash->date,
                'reference' => $request->reference,
                'notes' => $request->notes ?? ('From Daily Cash — '.$dailyCash->date->format('M d, Y')),
                'created_by' => auth()->id(),
            ]);

            // Increment account balance
            if ($request->source_type === 'cash') {
                CashAccount::where('id', $request->source_id)->increment('balance', $request->amount);
            } else {
                BankAccount::where('id', $request->source_id)->increment('balance', $request->amount);
            }

            // Add a SAVINGS entry to the daily cash day
            $max = $dailyCash->entries()->max('sort_order') ?? 0;
            $dailyCash->entries()->create([
                'type' => 'SAVINGS',
                'description' => strtoupper('DEPOSIT — '.$accountName),
                'amount' => $request->amount,
                'sort_order' => $max + 1,
            ]);
        });

        $dailyCash->refresh();
        $this->syncOpeningBalancesForwardFrom($dailyCash);

        return back()->with('success', 'Deposit recorded. Later days in this period were synced to the new balances.');
    }
}
