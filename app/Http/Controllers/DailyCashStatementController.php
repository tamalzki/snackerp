<?php

namespace App\Http\Controllers;

use App\Models\DailyCashDay;
use App\Models\DailyCashEntry;
use App\Services\DailyCashLedgerService;
use App\Support\CashflowSubcategoryClassifier;
use App\Support\DailyCashflowCategories;
use Illuminate\Http\Request;

class DailyCashStatementController extends Controller
{
    public function __construct(private DailyCashLedgerService $ledger) {}

    public function income(Request $request)
    {
        return $this->render($request, 'income', ['INCOME'], 'Income Statement');
    }

    public function expenses(Request $request)
    {
        return $this->render($request, 'expenses', ['EXPENSES', 'PURCHASES'], 'Expenses Statement');
    }

    public function discretionary(Request $request)
    {
        return $this->render($request, 'discretionary', ['DISCRETIONARY'], 'Discretionary Statement');
    }

    public function savings(Request $request)
    {
        return $this->render($request, 'savings', ['SAVINGS'], 'Savings Statement');
    }

    /**
     * @param  list<string>  $types
     */
    private function render(Request $request, string $viewKey, array $types, string $title)
    {
        $year = (int) $request->get('year', now()->year);
        $years = DailyCashDay::query()
            ->selectRaw('YEAR(date) as yr')
            ->groupBy('yr')
            ->orderByDesc('yr')
            ->pluck('yr');
        if ($years->isEmpty()) {
            $years = collect([now()->year]);
        }

        $entries = DailyCashEntry::query()
            ->with('day')
            ->whereIn('type', $types)
            ->whereHas('day', fn ($q) => $q->whereYear('date', $year))
            ->get()
            ->sortByDesc(fn (DailyCashEntry $e) => $e->day->date->format('Y-m-d').sprintf('%06d', $e->id))
            ->values();

        $defaultType = match ($viewKey) {
            'income' => 'INCOME',
            'expenses' => 'EXPENSES',
            'discretionary' => 'DISCRETIONARY',
            'savings' => 'SAVINGS',
            default => 'INCOME',
        };

        $typesForMeta = match ($viewKey) {
            'income' => ['INCOME'],
            'expenses' => ['EXPENSES', 'PURCHASES'],
            'discretionary' => ['DISCRETIONARY'],
            'savings' => ['SAVINGS'],
            default => [],
        };
        $statementEntryFormMeta = [];
        foreach ($typesForMeta as $t) {
            $statementEntryFormMeta[$t] = [
                'groups' => DailyCashflowCategories::categoryFormGroupsForType($t),
                'labels' => CashflowSubcategoryClassifier::designatedLabelsMapForType($t),
            ];
        }

        return view('daily-cash.statement', compact(
            'entries',
            'year',
            'years',
            'viewKey',
            'defaultType',
            'title',
            'statementEntryFormMeta'
        ));
    }

    public function storeEntry(Request $request)
    {
        $request->validate([
            'statement' => 'required|in:income,expenses,discretionary,savings',
            'entry_date' => 'required|date',
            'description' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0.01',
            'category_preset' => 'nullable|string|max:64',
            'category_custom_piece' => 'nullable|string|max:120',
            'subcategory_key' => 'nullable|string|max:64',
            'entry_type' => 'nullable|in:EXPENSES,PURCHASES',
        ]);

        $type = match ($request->statement) {
            'income' => 'INCOME',
            'expenses' => $request->input('entry_type') === 'PURCHASES' ? 'PURCHASES' : 'EXPENSES',
            'discretionary' => 'DISCRETIONARY',
            'savings' => 'SAVINGS',
            default => abort(422),
        };

        [$ok, $msg] = $this->ledger->validateDateForNewEntry($request->entry_date);
        if (! $ok) {
            return back()->withErrors(['entry_date' => $msg])->withInput();
        }

        $request->merge(['type' => $type]);

        [$category, $subcategoryOverride] = DailyCashflowCategories::resolveCategoryAndSubcategoryForEntryRequest(
            $request,
            $type,
            false
        );

        $day = $this->ledger->ensureDay($request->entry_date);
        $max = $day->entries()->max('sort_order') ?? 0;
        $day->entries()->create([
            'type' => $type,
            'category' => $category,
            'subcategory_override' => $subcategoryOverride,
            'description' => strtoupper($request->description),
            'amount' => $request->amount,
            'sort_order' => $max + 1,
        ]);

        $this->ledger->syncOpeningBalancesForwardFrom($day);

        return redirect()
            ->route('daily-cash.statements.'.$request->statement, ['year' => (int) date('Y', strtotime($request->entry_date))])
            ->with('success', 'Entry added on '.$request->entry_date.'. Carry-forward balances were updated.');
    }
}
