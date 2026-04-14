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
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

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
        $monthlyMatrix = null;
        $annualMatrix = null;
        $filterYear = (int) $request->get('year', now()->year);
        $years = DailyCashDay::selectRaw('YEAR(date) as yr')->groupBy('yr')->orderByDesc('yr')->pluck('yr');
        if ($tab === 'monthly') {
            $monthlyMatrix = $this->buildMonthlyCashFlowMatrix($filterYear);
        } elseif ($tab === 'annual') {
            $annualMatrix = $this->buildAnnualCashFlowMatrix();
        } else {
            $days = DailyCashDay::with('entries')->orderByDesc('date')->paginate(30);
        }

        $subcategoryOptionsByType = [];
        $subcategoryLabelsByType = [];
        foreach (array_keys(DailyCashEntry::$types) as $t) {
            $subcategoryOptionsByType[$t] = CashflowSubcategoryClassifier::designatedEditOptionsForType($t);
            $subcategoryLabelsByType[$t] = CashflowSubcategoryClassifier::designatedLabelsMapForType($t);
        }

        return view('daily-cash.index', compact('days', 'monthlyMatrix', 'annualMatrix', 'tab', 'filterYear', 'years', 'subcategoryOptionsByType', 'subcategoryLabelsByType'));
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

    /** Collapse whitespace; compare case-insensitively by uppercasing (monthly/yearly grouping). */
    private function normalizeLedgerDescriptionKey(string $description): string
    {
        $s = trim(preg_replace('/\s+/u', ' ', $description));

        return mb_strtoupper($s, 'UTF-8');
    }

    private function titleCaseDescriptionLabel(string $normalizedUpper): string
    {
        if ($normalizedUpper === '') {
            return '';
        }

        $lower = mb_strtolower($normalizedUpper, 'UTF-8');

        return mb_convert_case($lower, MB_CASE_TITLE, 'UTF-8');
    }

    /**
     * Spreadsheet-style monthly grid: rows = type + normalized description, columns = Jan–Dec.
     *
     * @return array<string, mixed>
     */
    private function buildMonthlyCashFlowMatrix(int $year): array
    {
        $entries = DailyCashEntry::query()
            ->with('day')
            ->whereHas('day', fn ($q) => $q->whereYear('date', $year))
            ->get();

        $linesMap = [];
        foreach ($entries as $e) {
            $m = (int) $e->day->date->format('n');
            $norm = $this->normalizeLedgerDescriptionKey((string) $e->description);
            $sub = CashflowSubcategoryClassifier::resolve($e->type, (string) $e->description, $e->subcategory_override);
            $key = $e->type.'|'.$norm.'|'.$sub['key'];
            if (! isset($linesMap[$key])) {
                $linesMap[$key] = [
                    'type' => $e->type,
                    'description_norm' => $norm,
                    'subcategory_key' => $sub['key'],
                    'subcategory_label' => $sub['label'],
                    'description_display' => $this->titleCaseDescriptionLabel($norm) ?: '—',
                    'amounts' => array_fill(1, 12, 0.0),
                ];
            }
            $linesMap[$key]['amounts'][$m] += (float) $e->amount;
        }

        $lines = $this->sortCashflowMatrixLines(array_values($linesMap));

        foreach ($lines as $i => $_) {
            $lines[$i]['row_total'] = round(array_sum($lines[$i]['amounts']), 2);
        }

        $netByMonth = array_fill(1, 12, 0.0);
        for ($m = 1; $m <= 12; $m++) {
            $subset = $entries->filter(fn ($e) => (int) $e->day->date->format('n') === $m);
            $netByMonth[$m] = $subset->isEmpty() ? 0.0 : round((float) ($this->entryTotals($subset)['net'] ?? 0.0), 2);
        }

        $yearTotalNet = round(array_sum($netByMonth), 2);

        $monthLabels = [1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Aug', 9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dec'];
        $columns = [];
        for ($m = 1; $m <= 12; $m++) {
            $columns[] = ['key' => $m, 'label' => $monthLabels[$m]];
        }

        return [
            'mode' => 'monthly',
            'year' => $year,
            'columns' => $columns,
            'lines' => $lines,
            'net_footer' => $netByMonth,
            'corner_net' => $yearTotalNet,
            'pencil_year' => $year,
            'data_tab' => 'monthly',
            'header_title' => 'Monthly cash flow',
            'header_metric_label' => 'Year net:',
            'info_text' => 'Rows group the same <strong>type</strong> and <strong>description</strong> (spacing and caps ignored). <strong>Subcategory</strong> uses a manual override when you set one (pencil); otherwise it follows description keywords. Columns are <strong>calendar months</strong> left to right.',
            'footer_row_label' => 'Net (month)',
            'scroll_hint' => 'Scroll sideways to see all months →',
            'empty_context' => (string) $year,
            'year_total_net' => $yearTotalNet,
            'net_by_month' => $netByMonth,
        ];
    }

    /**
     * Same table as monthly, but columns = calendar years (oldest → newest). Rows aggregate the same line key across years.
     *
     * @return array<string, mixed>
     */
    private function buildAnnualCashFlowMatrix(): array
    {
        $yearsAsc = DailyCashDay::query()
            ->selectRaw('YEAR(date) as yr')
            ->groupBy('yr')
            ->orderBy('yr')
            ->pluck('yr')
            ->map(fn ($y) => (int) $y)
            ->values()
            ->all();

        if ($yearsAsc === []) {
            return [
                'mode' => 'annual',
                'year' => null,
                'columns' => [],
                'lines' => [],
                'net_footer' => [],
                'corner_net' => 0.0,
                'pencil_year' => (int) now()->year,
                'data_tab' => 'annual',
                'header_title' => 'Annual cash flow',
                'header_metric_label' => 'Combined net (sum of year nets):',
                'info_text' => 'Same layout as <strong>Monthly</strong>; columns are <strong>calendar years</strong> (oldest to newest). Recategorize applies to the <strong>latest year</strong> shown — use Monthly for a single year or repeat for other years.',
                'footer_row_label' => 'Net (year)',
                'scroll_hint' => 'Scroll sideways to see all years →',
                'empty_context' => '',
                'year_total_net' => 0.0,
                'net_by_month' => [],
            ];
        }

        $yMin = min($yearsAsc);
        $yMax = max($yearsAsc);

        $entries = DailyCashEntry::query()
            ->with('day')
            ->whereHas('day', fn ($q) => $q->whereYear('date', '>=', $yMin)->whereYear('date', '<=', $yMax))
            ->get();

        $linesMap = [];
        foreach ($entries as $e) {
            $y = (int) $e->day->date->format('Y');
            $norm = $this->normalizeLedgerDescriptionKey((string) $e->description);
            $sub = CashflowSubcategoryClassifier::resolve($e->type, (string) $e->description, $e->subcategory_override);
            $key = $e->type.'|'.$norm.'|'.$sub['key'];
            if (! isset($linesMap[$key])) {
                $linesMap[$key] = [
                    'type' => $e->type,
                    'description_norm' => $norm,
                    'subcategory_key' => $sub['key'],
                    'subcategory_label' => $sub['label'],
                    'description_display' => $this->titleCaseDescriptionLabel($norm) ?: '—',
                    'amounts' => array_fill_keys($yearsAsc, 0.0),
                ];
            }
            $linesMap[$key]['amounts'][$y] = ($linesMap[$key]['amounts'][$y] ?? 0.0) + (float) $e->amount;
        }

        $lines = $this->sortCashflowMatrixLines(array_values($linesMap));

        foreach ($lines as $i => $_) {
            $lines[$i]['row_total'] = round(array_sum($lines[$i]['amounts']), 2);
        }

        $netFooter = [];
        foreach ($yearsAsc as $y) {
            $subset = $entries->filter(fn ($e) => (int) $e->day->date->format('Y') === $y);
            $netFooter[$y] = $subset->isEmpty() ? 0.0 : round((float) ($this->entryTotals($subset)['net'] ?? 0.0), 2);
        }

        $cornerNet = round(array_sum($netFooter), 2);

        $columns = [];
        foreach ($yearsAsc as $y) {
            $columns[] = ['key' => $y, 'label' => (string) $y];
        }

        return [
            'mode' => 'annual',
            'year' => null,
            'columns' => $columns,
            'lines' => $lines,
            'net_footer' => $netFooter,
            'corner_net' => $cornerNet,
            'pencil_year' => $yMax,
            'data_tab' => 'annual',
            'header_title' => 'Annual cash flow',
            'header_metric_label' => 'Combined net (sum of year nets):',
            'info_text' => 'Same layout as <strong>Monthly</strong>; columns are <strong>calendar years</strong> (oldest to newest). Recategorize uses the <strong>'.$yMax.'</strong> column by default (same line in other years is updated when you change entries there or recategorize per year on Monthly).',
            'footer_row_label' => 'Net (year)',
            'scroll_hint' => 'Scroll sideways to see all years →',
            'empty_context' => '',
            'year_total_net' => $cornerNet,
            'net_by_month' => [],
        ];
    }

    /**
     * @param  list<array{type: string, subcategory_label: string, description_display: string}>  $lines
     * @return list<array{type: string, subcategory_label: string, description_display: string}>
     */
    private function sortCashflowMatrixLines(array $lines): array
    {
        $typeOrder = array_keys(DailyCashEntry::$types);
        usort($lines, function ($a, $b) use ($typeOrder) {
            $ia = array_search($a['type'], $typeOrder, true);
            $ib = array_search($b['type'], $typeOrder, true);
            $ia = $ia === false ? 99 : $ia;
            $ib = $ib === false ? 99 : $ib;
            if ($ia !== $ib) {
                return $ia <=> $ib;
            }
            $sc = strcmp((string) ($a['subcategory_label'] ?? ''), (string) ($b['subcategory_label'] ?? ''));
            if ($sc !== 0) {
                return $sc;
            }

            return strcmp((string) $a['description_display'], (string) $b['description_display']);
        });

        return $lines;
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

    /** Workbook-style landing page (matches spreadsheet “Guide” tab). */
    public function guide()
    {
        return view('daily-cash.guide');
    }

    // Show a single day
    public function show(DailyCashDay $dailyCash)
    {
        $dailyCash->load('entries');
        $this->ledger->alignStaleZeroOpening($dailyCash);
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

        $cashEntryFormMeta = ['labels' => [], 'groups' => []];
        foreach (array_keys(DailyCashEntry::$types) as $t) {
            $cashEntryFormMeta['labels'][$t] = CashflowSubcategoryClassifier::designatedLabelsMapForType($t);
            $cashEntryFormMeta['groups'][$t] = DailyCashflowCategories::categoryFormGroupsForType($t);
        }

        return view('daily-cash.show', compact(
            'dailyCash',
            'prevWeekNav',
            'nextWeekNav',
            'weekStrip',
            'weekRangeLabel',
            'bankAccounts',
            'dayDeposits',
            'cashEntryFormMeta'
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
        $request->validate([
            'type' => ['required', 'string', Rule::in(array_merge(array_keys(DailyCashEntry::$types), ['CASH_FROM_BANK']))],
            'description' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0.01',
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

        $max = $dailyCash->entries()->max('sort_order') ?? 0;
        $dailyCash->entries()->create([
            'type' => $type,
            'category' => $category,
            'subcategory_override' => $subcategoryOverride,
            'description' => strtoupper($request->description),
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
        $request->validate([
            'type' => ['required', 'string', Rule::in(array_merge(array_keys(DailyCashEntry::$types), ['CASH_FROM_BANK']))],
            'description' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0.01',
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

        $entry->update([
            'type' => $type,
            'category' => $category,
            'description' => strtoupper($request->description),
            'amount' => $request->amount,
            'subcategory_override' => $subcategoryOverride,
        ]);

        $this->ledger->syncOpeningBalancesForwardFrom($dailyCash);

        return back()->with('success', 'Entry updated. Later days in this period were synced to the new balances.');
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
