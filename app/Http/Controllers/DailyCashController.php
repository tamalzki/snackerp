<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\CashAccount;
use App\Models\DailyCashDay;
use App\Models\DailyCashEntry;
use App\Models\Deposit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DailyCashController extends Controller
{
    // Compute the closing balance of a day (opening + net)
    private function closingBalance(DailyCashDay $day): float
    {
        $day->load('entries');
        return (float) $day->opening_balance + $day->net();
    }

    // Resolve opening balance for a new date from previous day's closing
    private function resolveOpeningBalance(string $date): float
    {
        $prev = DailyCashDay::where('date', '<', $date)->orderByDesc('date')->first();
        if (! $prev) return 0;
        return max(0, $this->closingBalance($prev));
    }

    // List recent days; auto-create today if missing
    public function index(Request $request)
    {
        $tab = $request->get('tab', 'daily');

        // Always ensure today exists
        $today = Carbon::today()->toDateString();
        if (! DailyCashDay::where('date', $today)->exists()) {
            DailyCashDay::create([
                'date'            => $today,
                'opening_balance' => $this->resolveOpeningBalance($today),
            ]);
        }

        $days        = null;
        $monthly     = null;
        $annual      = null;
        $filterYear  = (int) $request->get('year', now()->year);
        $years       = DailyCashDay::selectRaw('YEAR(date) as yr')->groupBy('yr')->orderByDesc('yr')->pluck('yr');

        if ($tab === 'monthly') {
            $monthly = $this->buildMonthlySummary($filterYear);
        } elseif ($tab === 'annual') {
            $annual = $this->buildAnnualSummary();
        } else {
            $days = DailyCashDay::orderByDesc('date')->paginate(30);
        }

        return view('daily-cash.index', compact('days', 'monthly', 'annual', 'tab', 'filterYear', 'years'));
    }

    private function entryTotals($entries): array
    {
        $capital       = (float) $entries->where('type', 'CAPITAL')->sum('amount');
        $income        = (float) $entries->where('type', 'INCOME')->sum('amount');
        $expenses      = (float) $entries->whereIn('type', ['EXPENSES', 'PURCHASES'])->sum('amount');
        $discretionary = (float) $entries->where('type', 'DISCRETIONARY')->sum('amount');
        $savings       = (float) $entries->where('type', 'SAVINGS')->sum('amount');
        return compact('capital', 'income', 'expenses', 'discretionary', 'savings') + [
            'net' => $capital + $income - $expenses - $discretionary - $savings,
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

            if ($days->isEmpty()) continue;

            $allEntries = $days->flatMap(fn($d) => $d->entries);
            $totals     = $this->entryTotals($allEntries);

            // Build day-level rows for the accordion detail
            $dayRows = $days->map(function ($day) {
                $t = $this->entryTotals($day->entries);
                return array_merge(['day' => $day], $t);
            })->all();

            $rows[] = array_merge(
                ['label' => Carbon::createFromDate($year, $m, 1)->format('F Y'),
                 'month_key' => "m{$year}{$m}",
                 'days'  => $dayRows],
                $totals
            );
        }
        return $rows;
    }

    private function buildAnnualSummary(): array
    {
        $rows  = [];
        $years = DailyCashDay::selectRaw('YEAR(date) as yr')->groupBy('yr')->orderByDesc('yr')->pluck('yr');

        foreach ($years as $year) {
            $monthRows = [];
            for ($m = 1; $m <= 12; $m++) {
                $days = DailyCashDay::with('entries')
                    ->whereYear('date', $year)
                    ->whereMonth('date', $m)
                    ->orderBy('date')
                    ->get();
                if ($days->isEmpty()) continue;
                $allEntries = $days->flatMap(fn($d) => $d->entries);
                $t = $this->entryTotals($allEntries);
                $monthRows[] = array_merge(
                    ['label' => Carbon::createFromDate($year, $m, 1)->format('F'), 'year' => $year, 'month' => $m],
                    $t
                );
            }

            $allEntries = DailyCashEntry::whereHas('day', fn($q) => $q->whereYear('date', $year))->get();
            $totals     = $this->entryTotals($allEntries);

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
            DailyCashDay::create([
                'date'            => $today,
                'opening_balance' => $this->resolveOpeningBalance($today),
            ]);
        }
        $day = DailyCashDay::where('date', $today)->first();
        return redirect()->route('daily-cash.show', $day);
    }

    // Show a single day
    public function show(DailyCashDay $dailyCash)
    {
        $dailyCash->load('entries');

        $prev = DailyCashDay::where('date', '<', $dailyCash->date)->orderByDesc('date')->first();
        $next = DailyCashDay::where('date', '>', $dailyCash->date)->orderBy('date')->first();

        $recentDays  = DailyCashDay::orderByDesc('date')->take(10)->get();
        $bankAccounts = BankAccount::orderBy('bank_name')->get();
        $cashAccounts = CashAccount::orderBy('name')->get();
        $dayDeposits  = Deposit::where('deposit_date', $dailyCash->date)->latest()->get();

        return view('daily-cash.show', compact('dailyCash', 'prev', 'next', 'recentDays', 'bankAccounts', 'cashAccounts', 'dayDeposits'));
    }

    // Create a specific date's record
    public function store(Request $request)
    {
        $request->validate(['date' => 'required|date|unique:daily_cash_days,date']);
        $day = DailyCashDay::create([
            'date'            => $request->date,
            'opening_balance' => $this->resolveOpeningBalance($request->date),
        ]);
        return redirect()->route('daily-cash.show', $day)->with('success', 'Day created.');
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
                'notes'           => 'nullable|string|max:500',
            ]);
            $dailyCash->update($request->only('opening_balance', 'notes'));
        }
        return back()->with('success', 'Updated.');
    }

    // Add an entry to a day
    public function storeEntry(Request $request, DailyCashDay $dailyCash)
    {
        $request->validate([
            'type'        => 'required|in:CAPITAL,INCOME,EXPENSES,PURCHASES,DISCRETIONARY,SAVINGS,OTHER',
            'description' => 'required|string|max:255',
            'amount'      => 'required|numeric|min:0',
        ]);

        $max = $dailyCash->entries()->max('sort_order') ?? 0;
        $dailyCash->entries()->create([
            'type'        => $request->type,
            'description' => strtoupper($request->description),
            'amount'      => $request->amount,
            'sort_order'  => $max + 1,
        ]);

        return back()->with('success', 'Entry added.');
    }

    // Update an entry
    public function updateEntry(Request $request, DailyCashDay $dailyCash, DailyCashEntry $entry)
    {
        abort_if($entry->daily_cash_day_id !== $dailyCash->id, 404);
        $request->validate([
            'type'        => 'required|in:CAPITAL,INCOME,EXPENSES,PURCHASES,DISCRETIONARY,SAVINGS,OTHER',
            'description' => 'required|string|max:255',
            'amount'      => 'required|numeric|min:0',
        ]);
        $entry->update([
            'type'        => $request->type,
            'description' => strtoupper($request->description),
            'amount'      => $request->amount,
        ]);
        return back()->with('success', 'Entry updated.');
    }

    // Delete an entry
    public function destroyEntry(DailyCashDay $dailyCash, DailyCashEntry $entry)
    {
        abort_if($entry->daily_cash_day_id !== $dailyCash->id, 404);
        $entry->delete();
        return back()->with('success', 'Entry deleted.');
    }

    // Deposit to bank: creates a Deposit record + SAVINGS entry on this day
    public function depositToBank(Request $request, DailyCashDay $dailyCash)
    {
        $request->validate([
            'source_type'  => 'required|in:cash,bank',
            'source_id'    => 'required|integer',
            'amount'       => 'required|numeric|min:0.01',
            'reference'    => 'nullable|string|max:100',
            'notes'        => 'nullable|string|max:500',
        ]);

        DB::transaction(function () use ($request, $dailyCash) {
            // Resolve account name for the SAVINGS entry description
            $accountName = $request->source_type === 'cash'
                ? (CashAccount::find($request->source_id)?->name ?? 'Cash')
                : (function () use ($request) {
                    $b = BankAccount::find($request->source_id);
                    return $b ? trim($b->bank_name . ($b->account_name ? ' — ' . $b->account_name : '')) : 'Bank';
                })();

            // Create the formal Deposit record
            Deposit::create([
                'source_type'  => $request->source_type,
                'source_id'    => $request->source_id,
                'amount'       => $request->amount,
                'deposit_date' => $dailyCash->date,
                'reference'    => $request->reference,
                'notes'        => $request->notes ?? ('From Daily Cash — ' . $dailyCash->date->format('M d, Y')),
                'created_by'   => auth()->id(),
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
                'type'        => 'SAVINGS',
                'description' => strtoupper('DEPOSIT — ' . $accountName),
                'amount'      => $request->amount,
                'sort_order'  => $max + 1,
            ]);
        });

        return back()->with('success', 'Deposit recorded and added to savings entries.');
    }
}
