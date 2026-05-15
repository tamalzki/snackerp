<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\CashAccount;
use App\Models\DailyCashDay;
use App\Models\DailyCashEntry;
use App\Models\Deposit;
use App\Services\DailyCashLedgerService;
use App\Support\CashflowSubcategoryClassifier;
use App\Support\DailyCashflowCategories;
use App\Support\DailyCashMetroLedger;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class DailyCashController extends Controller
{
    public function __construct(private DailyCashLedgerService $ledger) {}

    /** Same weekday, previous week (arrow left). */
    private function weekPrevNav(DailyCashDay $dailyCash): ?array
    {
        $prev = Carbon::parse($dailyCash->date)->copy()->subWeek();
        if (! $this->ledger->isDayEncodable($prev)) {
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
        if (! $this->ledger->isDayEncodable($next)) {
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
                'inRange' => $this->ledger->isDayEncodable($d),
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
                'opening_balance' => $this->ledger->resolveOpeningBalance($today),
            ]);
            $this->ledger->syncOpeningBalancesForwardFrom($boot);
        }

        $days = null;
        $monthlyMetroMatrix = null;
        $annualCashflowGrid = null;

        [$metroPeriodYear, $metroPeriodMonth, $metroPeriodValue] = $this->resolveMetroStatementPeriod($request);

        $annualYear = (int) $request->get('year', now()->year);
        if ($annualYear < 2000 || $annualYear > 2100) {
            $annualYear = (int) now()->year;
        }

        $annualYears = DailyCashDay::query()
            ->selectRaw('YEAR(date) as yr')
            ->groupBy('yr')
            ->orderByDesc('yr')
            ->pluck('yr')
            ->map(fn ($y) => (int) $y)
            ->values()
            ->all();
        if ($annualYears === []) {
            $annualYears = [$annualYear];
        }
        if (! in_array($annualYear, $annualYears, true)) {
            $annualYears[] = $annualYear;
            rsort($annualYears);
        }

        $statementYear = match ($tab) {
            'monthly' => $metroPeriodYear,
            'annual' => $annualYear,
            default => (int) $request->get('year', now()->year),
        };

        if ($tab === 'monthly') {
            $monthlyMetroMatrix = DailyCashMetroLedger::buildMonthlyMetroSheetForMonth($metroPeriodYear, $metroPeriodMonth);
        } elseif ($tab === 'annual') {
            $annualCashflowGrid = DailyCashMetroLedger::buildAnnualCashflowGrid($annualYear);
        } else {
            $days = DailyCashDay::with('entries')->orderByDesc('date')->paginate(30);
        }

        $subcategoryOptionsByType = [];
        $subcategoryLabelsByType = [];
        foreach (array_keys(DailyCashEntry::$types) as $t) {
            $subcategoryOptionsByType[$t] = CashflowSubcategoryClassifier::designatedEditOptionsForType($t);
            $subcategoryLabelsByType[$t] = CashflowSubcategoryClassifier::designatedLabelsMapForType($t);
        }

        return view('daily-cash.index', compact('days', 'monthlyMetroMatrix', 'annualCashflowGrid', 'annualYear', 'annualYears', 'tab', 'statementYear', 'metroPeriodValue', 'subcategoryOptionsByType', 'subcategoryLabelsByType'));
    }

    /** Apply subcategory override to all ledger lines matching type + description + current effective subcategory for a calendar year. */
    public function bulkSubcategoryOverride(Request $request)
    {
        $validated = $request->validate([
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'type' => ['required', 'string', Rule::in(array_keys(DailyCashEntry::$types))],
            'description_norm' => ['required', 'string', 'max:2000'],
            'line_subcategory_key' => ['required', 'string', 'max:64'],
            'subcategory_key' => ['nullable', 'string', 'max:64'],
            'tab' => ['nullable', 'string', Rule::in(['daily', 'monthly', 'annual'])],
        ]);

        $type = $validated['type'];
        $norm = $this->normalizeLedgerDescriptionKey($validated['description_norm']);
        $lineKey = $validated['line_subcategory_key'];
        $newKey = $validated['subcategory_key'] ?? '';
        $newOverride = $newKey === '' ? null : $newKey;

        if ($newOverride !== null && ! CashflowSubcategoryClassifier::isValidKeyForType($type, $newOverride)) {
            return redirect()->route('daily-cash.index', [
                'tab' => $validated['tab'] ?? 'monthly',
                'year' => $validated['year'],
            ])->withErrors(['subcategory' => 'That subcategory is not valid for this entry type.']);
        }

        $ids = DailyCashEntry::query()
            ->where('type', $type)
            ->whereHas('day', fn ($q) => $q->whereYear('date', $validated['year']))
            ->get()
            ->filter(function (DailyCashEntry $e) use ($norm, $lineKey) {
                if ($this->normalizeLedgerDescriptionKey((string) $e->description) !== $norm) {
                    return false;
                }
                $eff = CashflowSubcategoryClassifier::resolve($e->type, (string) $e->description, $e->subcategory_override)['key'];

                return $eff === $lineKey;
            })
            ->pluck('id');

        if ($ids->isEmpty()) {
            return redirect()->route('daily-cash.index', [
                'tab' => $validated['tab'] ?? 'monthly',
                'year' => $validated['year'],
            ])->with('warning', 'No matching lines were updated. Refresh and try again if the report changed.');
        }

        DailyCashEntry::query()->whereIn('id', $ids)->update(['subcategory_override' => $newOverride]);

        $n = $ids->count();

        return redirect()->route('daily-cash.index', [
            'tab' => $validated['tab'] ?? 'monthly',
            'year' => $validated['year'],
        ])->with('success', 'Subcategory updated for '.$n.' ledger line'.($n === 1 ? '' : 's').'.');
    }

    /** @return array{0: int, 1: int, 2: string} year, month (1–12), period query value YYYY-MM */
    private function resolveMetroStatementPeriod(Request $request): array
    {
        $periodParam = $request->query('period');
        if (is_string($periodParam) && preg_match('/^(\d{4})-(\d{2})$/', $periodParam, $m)) {
            $y = (int) $m[1];
            $mo = (int) $m[2];
            if ($y >= 2000 && $y <= 2100 && $mo >= 1 && $mo <= 12) {
                return [$y, $mo, sprintf('%04d-%02d', $y, $mo)];
            }
        }

        $now = Carbon::now();

        return [(int) $now->year, (int) $now->month, $now->format('Y-m')];
    }

    /** Collapse whitespace; compare case-insensitively by uppercasing (monthly/yearly grouping). */
    private function normalizeLedgerDescriptionKey(string $description): string
    {
        $s = trim(preg_replace('/\s+/u', ' ', $description));

        return mb_strtoupper($s, 'UTF-8');
    }

    // Auto-redirect to today
    public function today()
    {
        $today = Carbon::today()->toDateString();
        if (! DailyCashDay::where('date', $today)->exists()) {
            $boot = DailyCashDay::create([
                'date' => $today,
                'opening_balance' => $this->ledger->resolveOpeningBalance($today),
            ]);
            $this->ledger->syncOpeningBalancesForwardFrom($boot);
        }
        $day = DailyCashDay::where('date', $today)->first();

        return redirect()->route('daily-cash.show', $day);
    }

    /** Create the day row if missing, then show (e.g. opening next/prev day that has no row yet). */
    public function openDate(string $date)
    {
        $d = Carbon::parse($date)->startOfDay();
        $periodStart = $this->ledger->cashflowPeriodStart($d);
        $fyEnd = $periodStart->copy()->addYear()->subDay();
        $maxDate = Carbon::today()->min($fyEnd);

        if ($d->lt($periodStart) || $d->gt($maxDate)) {
            return redirect()->route('daily-cash.today')
                ->with('error', 'That date is outside the allowed cash period (from '.$periodStart->format('M j, Y').' through '.$maxDate->format('M j, Y').').');
        }

        $day = DailyCashDay::firstOrCreate(
            ['date' => $d->toDateString()],
            ['opening_balance' => $this->ledger->resolveOpeningBalance($d->toDateString())]
        );
        if ($day->wasRecentlyCreated) {
            $this->ledger->syncOpeningBalancesForwardFrom($day);
        }

        return redirect()->route('daily-cash.show', $day);
    }

    // Show a single day
    public function show(DailyCashDay $dailyCash)
    {
        $dailyCash->load('entries');
        $this->ledger->alignStaleZeroOpening($dailyCash);
        $dailyCash->refresh();
        $this->ledger->ensureMetroSheetEntries($dailyCash);
        $dailyCash->refresh();
        $dailyCash->load('entries');

        $metroSheetRows = DailyCashMetroLedger::buildSheetRows($dailyCash);

        $prevWeekNav = $this->weekPrevNav($dailyCash);
        $nextWeekNav = $this->weekNextNav($dailyCash);
        $weekStrip = $this->buildWeekStrip($dailyCash);

        $weekRangeLabel = count($weekStrip) > 0
            ? $this->formatDayRangeLabel($weekStrip[0]['date'], $weekStrip[count($weekStrip) - 1]['date'])
            : '';

        $bankAccounts = BankAccount::orderBy('bank_name')->get();
        $dayDeposits = Deposit::where('deposit_date', $dailyCash->date)->latest()->get();

        $cashEntryFormMeta = ['labels' => [], 'groups' => []];
        foreach (array_keys(DailyCashEntry::$types) as $t) {
            $cashEntryFormMeta['labels'][$t] = CashflowSubcategoryClassifier::designatedLabelsMapForType($t);
            $cashEntryFormMeta['groups'][$t] = DailyCashflowCategories::categoryFormGroupsForType($t);
        }

        $dailyCashWorksheetEntryMeta = DailyCashMetroLedger::worksheetEntryFormTree();

        return view('daily-cash.show', compact(
            'dailyCash',
            'metroSheetRows',
            'prevWeekNav',
            'nextWeekNav',
            'weekStrip',
            'weekRangeLabel',
            'bankAccounts',
            'dayDeposits',
            'cashEntryFormMeta',
            'dailyCashWorksheetEntryMeta'
        ));
    }

    // Create a specific date's record
    public function store(Request $request)
    {
        $request->validate(['date' => 'required|date|unique:daily_cash_days,date']);
        $day = DailyCashDay::create([
            'date' => $request->date,
            'opening_balance' => $this->ledger->resolveOpeningBalance($request->date),
        ]);
        $this->ledger->syncOpeningBalancesForwardFrom($day);

        return redirect()->route('daily-cash.show', $day)->with('success', 'Day created. Later days in this period were synced if needed.');
    }

    // Update opening balance / notes
    public function update(Request $request, DailyCashDay $dailyCash)
    {
        if ($request->boolean('total_cash_mode')) {
            $request->validate(['total_cash' => 'required|numeric|min:0']);
            // Back-calculate: opening_balance = total_cash - net
            $dailyCash->load('entries');
            $openingBalance = round((float) $request->total_cash - $dailyCash->net(), 2);
            if ($openingBalance < 0) {
                return back()->withErrors([
                    'total_cash' => 'That total is lower than today’s net from entries. Use a larger figure or adjust the ledger lines first.',
                ]);
            }
            $dailyCash->update(['opening_balance' => $openingBalance]);
        } else {
            $request->validate([
                'opening_balance' => 'required|numeric|min:0',
                'notes' => 'nullable|string|max:500',
            ]);
            $dailyCash->update($request->only('opening_balance', 'notes'));
        }

        $dailyCash->refresh();
        $this->ledger->syncOpeningBalancesForwardFrom($dailyCash);

        return back()->with('success', 'Updated. Opening balances on later days in this period were synced.');
    }

    // Add an entry to a day
    public function storeEntry(Request $request, DailyCashDay $dailyCash)
    {
        $payload = $this->validatedDailyCashDayWorksheetStore($request);

        $max = $dailyCash->entries()->max('sort_order') ?? 0;
        $dailyCash->entries()->create([
            'type' => $payload['type'],
            'category' => $payload['category'],
            'subcategory_override' => $payload['subcategory_override'],
            'description' => $payload['description'],
            'amount' => $request->amount,
            'sort_order' => $max + 1,
        ]);

        $this->ledger->syncOpeningBalancesForwardFrom($dailyCash);

        return back()->with('success', 'Entry added. Later days in this period were synced to the new balances.');
    }

    // Update an entry
    public function updateEntry(Request $request, DailyCashDay $dailyCash, DailyCashEntry $entry)
    {
        abort_if($entry->daily_cash_day_id !== $dailyCash->id, 404);

        if ($request->filled('worksheet_category_key')) {
            $payload = $this->validatedDailyCashDayWorksheetUpdate($request);
        } else {
            $request->validate([
                'type' => ['required', 'string', Rule::in(array_merge(array_keys(DailyCashEntry::$types), ['CASH_FROM_BANK']))],
                'description' => 'required|string|max:255',
                'amount' => 'required|numeric|min:0',
                'category_preset' => 'nullable|string|max:64',
                'category_custom_piece' => 'nullable|string|max:120',
                'subcategory_key' => 'nullable|string|max:64',
            ]);

            $isBank = $request->type === 'CASH_FROM_BANK';
            $type = $isBank ? 'INCOME' : $request->type;

            [$category, $subcategoryOverride] = DailyCashflowCategories::resolveCategoryAndSubcategoryForEntryRequest(
                $request,
                $type,
                $isBank
            );

            $payload = [
                'type' => $type,
                'category' => $category,
                'subcategory_override' => $subcategoryOverride,
                'description' => strtoupper(trim((string) $request->description)),
            ];
        }

        $entry->update([
            'type' => $payload['type'],
            'category' => $payload['category'],
            'description' => $payload['description'],
            'amount' => $request->amount,
            'subcategory_override' => $payload['subcategory_override'],
        ]);

        $this->ledger->syncOpeningBalancesForwardFrom($dailyCash);

        return back()->with('success', 'Entry updated. Later days in this period were synced to the new balances.');
    }

    /**
     * Day view “Add entry”: worksheet drill-down (+ cash from bank). Stores metro category keys on entries.
     *
     * @return array{type: string, category: ?string, subcategory_override: ?string, description: string}
     */
    private function validatedDailyCashDayWorksheetStore(Request $request): array
    {
        $allowedTypes = ['CAPITAL', 'INCOME', 'EXPENSES', 'CASH_FROM_BANK'];

        $request->validate([
            'type' => ['required', 'string', Rule::in($allowedTypes)],
            'amount' => 'required|numeric|min:0.01',
            'worksheet_category_key' => 'nullable|string|max:64',
            'description' => 'nullable|string|max:255',
            'other_specify' => 'nullable|string|max:255',
        ]);

        $isBank = $request->type === 'CASH_FROM_BANK';
        $type = $isBank ? 'INCOME' : (string) $request->type;

        if ($isBank) {
            $request->validate(['description' => 'required|string|max:255']);

            return [
                'type' => $type,
                'category' => DailyCashflowCategories::CASH_FROM_BANK,
                'subcategory_override' => null,
                'description' => strtoupper(preg_replace('/\s+/', ' ', trim((string) $request->description))),
            ];
        }

        $managed = DailyCashMetroLedger::managedCategoryKeys();
        $key = (string) $request->input('worksheet_category_key', '');
        if ($key === '' || ! in_array($key, $managed, true)) {
            throw ValidationException::withMessages([
                'worksheet_category_key' => ['Choose a worksheet line for this entry.'],
            ]);
        }

        if (! DailyCashMetroLedger::worksheetCategoryMatchesType($key, $type)) {
            throw ValidationException::withMessages([
                'worksheet_category_key' => ['That line does not belong to the selected group.'],
            ]);
        }

        if (DailyCashMetroLedger::isMetroOthersCategory($key)) {
            $request->validate(['other_specify' => 'required|string|max:255']);

            return [
                'type' => $type,
                'category' => $key,
                'subcategory_override' => null,
                'description' => strtoupper(preg_replace('/\s+/', ' ', trim((string) $request->other_specify))),
            ];
        }

        return [
            'type' => $type,
            'category' => $key,
            'subcategory_override' => null,
            'description' => $this->normalizedWorksheetOptionalDescription($request),
        ];
    }

    /**
     * Day view “Edit entry” when submitted with worksheet fields (statement edits omit worksheet_category_key).
     *
     * @return array{type: string, category: ?string, subcategory_override: ?string, description: string}
     */
    private function validatedDailyCashDayWorksheetUpdate(Request $request): array
    {
        $allowedTypes = ['CAPITAL', 'INCOME', 'EXPENSES', 'CASH_FROM_BANK'];

        $request->validate([
            'type' => ['required', 'string', Rule::in($allowedTypes)],
            'amount' => 'required|numeric|min:0',
            'worksheet_category_key' => 'nullable|string|max:64',
            'description' => 'nullable|string|max:255',
            'other_specify' => 'nullable|string|max:255',
        ]);

        $isBank = $request->type === 'CASH_FROM_BANK';
        $type = $isBank ? 'INCOME' : (string) $request->type;

        if ($isBank) {
            $request->validate(['description' => 'required|string|max:255']);

            return [
                'type' => $type,
                'category' => DailyCashflowCategories::CASH_FROM_BANK,
                'subcategory_override' => null,
                'description' => strtoupper(preg_replace('/\s+/', ' ', trim((string) $request->description))),
            ];
        }

        $request->validate(['worksheet_category_key' => 'required|string|max:64']);

        $managed = DailyCashMetroLedger::managedCategoryKeys();
        $key = (string) $request->input('worksheet_category_key', '');
        if (! in_array($key, $managed, true)) {
            throw ValidationException::withMessages([
                'worksheet_category_key' => ['Choose a worksheet line for this entry.'],
            ]);
        }

        if (! DailyCashMetroLedger::worksheetCategoryMatchesType($key, $type)) {
            throw ValidationException::withMessages([
                'worksheet_category_key' => ['That line does not belong to the selected group.'],
            ]);
        }

        if (DailyCashMetroLedger::isMetroOthersCategory($key)) {
            $request->validate(['other_specify' => 'required|string|max:255']);

            return [
                'type' => $type,
                'category' => $key,
                'subcategory_override' => null,
                'description' => strtoupper(preg_replace('/\s+/', ' ', trim((string) $request->other_specify))),
            ];
        }

        return [
            'type' => $type,
            'category' => $key,
            'subcategory_override' => null,
            'description' => $this->normalizedWorksheetOptionalDescription($request),
        ];
    }

    /** Uppercase worksheet “free” description; empty when omitted (non–Cash-from-Bank, non-Others lines). */
    private function normalizedWorksheetOptionalDescription(Request $request): string
    {
        $raw = trim((string) $request->input('description', ''));

        return $raw === '' ? '' : strtoupper(preg_replace('/\s+/', ' ', $raw));
    }

    // Delete an entry
    public function destroyEntry(DailyCashDay $dailyCash, DailyCashEntry $entry)
    {
        abort_if($entry->daily_cash_day_id !== $dailyCash->id, 404);
        $entry->delete();

        $this->ledger->syncOpeningBalancesForwardFrom($dailyCash);

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

        $dailyCash->load('entries');
        $available = round((float) $dailyCash->opening_balance + $dailyCash->net(), 2);
        if ((float) $request->amount > $available + 0.005) {
            return back()->withErrors([
                'amount' => 'Maximum deposit today is '.number_format($available, 2).' (total available cash on hand).',
            ]);
        }

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
                'category' => 'cash_bank_investment',
                'description' => strtoupper('DEPOSIT — '.$accountName),
                'amount' => $request->amount,
                'sort_order' => $max + 1,
            ]);
        });

        $dailyCash->refresh();
        $this->ledger->syncOpeningBalancesForwardFrom($dailyCash);

        return back()->with('success', 'Deposit recorded. Later days in this period were synced to the new balances.');
    }
}
