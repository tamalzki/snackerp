<?php

namespace App\Support;

use App\Models\DailyCashDay;
use App\Models\DailyCashEntry;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Daily worksheet layout (Metro Ace style): fixed Income rows + Expense buckets with sub-lines.
 *
 * Row identity is stored on {@see DailyCashEntry::$category} using keys from config `daily_cashflow.metro_daily_sheet`.
 */
final class DailyCashMetroLedger
{
    /** @return list<array<string, mixed>> */
    public static function sheetDefinition(): array
    {
        $rows = config('daily_cashflow.metro_daily_sheet', []);

        return is_array($rows) ? $rows : [];
    }

    /** @return list<string> */
    public static function managedCategoryKeys(): array
    {
        $keys = [];
        foreach (self::sheetDefinition() as $row) {
            if (($row['kind'] ?? '') === 'line' && ! empty($row['category_key'])) {
                $keys[] = (string) $row['category_key'];
            }
        }

        return array_values(array_unique($keys));
    }

    public static function isManagedCategory(?string $category): bool
    {
        if ($category === null || $category === '') {
            return false;
        }

        return in_array($category, self::managedCategoryKeys(), true);
    }

    /** Stored {@see DailyCashEntry::$category} for discretionary “Others” on the worksheet. */
    public const DISCRETIONARY_METRO_OTHERS_CATEGORY = 'metro_discretionary_others';

    /** Income “Others” or any expense / other line whose key ends with `_others` (not discretionary). */
    public static function isMetroOthersCategory(?string $categoryKey): bool
    {
        if ($categoryKey === null || $categoryKey === '') {
            return false;
        }
        if ($categoryKey === self::DISCRETIONARY_METRO_OTHERS_CATEGORY) {
            return false;
        }
        if ($categoryKey === 'metro_income_others') {
            return true;
        }

        return str_ends_with($categoryKey, '_others');
    }

    /** “Specify…” field for worksheet Add/Edit (includes discretionary Others). */
    public static function worksheetNeedsSpecifyOtherField(?string $categoryKey): bool
    {
        if ($categoryKey === null || $categoryKey === '') {
            return false;
        }
        if ($categoryKey === self::DISCRETIONARY_METRO_OTHERS_CATEGORY) {
            return true;
        }

        return self::isMetroOthersCategory($categoryKey);
    }

    /** Use aggregated worksheet label (“Other — …”) for pooled Others lines + discretionary Others. */
    public static function worksheetUsesOthersAggregateLabel(?string $categoryKey): bool
    {
        if ($categoryKey === null || $categoryKey === '') {
            return false;
        }
        if ($categoryKey === self::DISCRETIONARY_METRO_OTHERS_CATEGORY) {
            return true;
        }

        return self::isMetroOthersCategory($categoryKey);
    }

    public static function worksheetCategoryMatchesType(?string $categoryKey, string $ledgerType): bool
    {
        if ($categoryKey === null || $categoryKey === '') {
            return false;
        }
        foreach (self::sheetDefinition() as $row) {
            if (($row['kind'] ?? '') === 'line'
                && (string) ($row['category_key'] ?? '') === $categoryKey
                && (string) ($row['type'] ?? '') === $ledgerType) {
                return true;
            }
        }

        return false;
    }

    /**
     * JSON-safe tree for Add/Edit Entry modals (worksheet drill-down).
     *
     * @return array<string, mixed>
     */
    public static function worksheetEntryFormTree(): array
    {
        $capitalLines = [];
        $incomeBuckets = [];
        $expenseBuckets = [];
        $discretionaryBuckets = [];
        $savingsBuckets = [];
        $otherBuckets = [];
        $currentHeading = '';

        foreach (self::sheetDefinition() as $row) {
            if (($row['kind'] ?? '') === 'heading') {
                $currentHeading = (string) ($row['title'] ?? '');

                continue;
            }
            if (($row['kind'] ?? '') !== 'line') {
                continue;
            }

            $type = (string) $row['type'];
            $key = (string) $row['category_key'];
            $label = (string) ($row['category_display'] ?? $row['label'] ?? $key);
            $needsOther = self::worksheetNeedsSpecifyOtherField($key);
            $line = ['key' => $key, 'label' => $label, 'needsOther' => $needsOther];

            if ($type === 'CAPITAL') {
                $capitalLines[] = $line;
            } elseif ($type === 'INCOME') {
                $n = count($incomeBuckets);
                if ($n === 0 || ($incomeBuckets[$n - 1]['heading'] ?? '') !== $currentHeading) {
                    $incomeBuckets[] = ['heading' => $currentHeading !== '' ? $currentHeading : 'Income', 'lines' => [$line]];
                } else {
                    $incomeBuckets[$n - 1]['lines'][] = $line;
                }
            } elseif ($type === 'EXPENSES') {
                $n = count($expenseBuckets);
                if ($n === 0 || ($expenseBuckets[$n - 1]['heading'] ?? '') !== $currentHeading) {
                    $expenseBuckets[] = ['heading' => $currentHeading !== '' ? $currentHeading : 'Expense', 'lines' => [$line]];
                } else {
                    $expenseBuckets[$n - 1]['lines'][] = $line;
                }
            } elseif ($type === 'DISCRETIONARY') {
                $n = count($discretionaryBuckets);
                if ($n === 0 || ($discretionaryBuckets[$n - 1]['heading'] ?? '') !== $currentHeading) {
                    $discretionaryBuckets[] = ['heading' => $currentHeading !== '' ? $currentHeading : 'Discretionary', 'lines' => [$line]];
                } else {
                    $discretionaryBuckets[$n - 1]['lines'][] = $line;
                }
            } elseif ($type === 'SAVINGS') {
                $n = count($savingsBuckets);
                if ($n === 0 || ($savingsBuckets[$n - 1]['heading'] ?? '') !== $currentHeading) {
                    $savingsBuckets[] = ['heading' => $currentHeading !== '' ? $currentHeading : 'Savings', 'lines' => [$line]];
                } else {
                    $savingsBuckets[$n - 1]['lines'][] = $line;
                }
            } elseif ($type === 'OTHER') {
                $n = count($otherBuckets);
                if ($n === 0 || ($otherBuckets[$n - 1]['heading'] ?? '') !== $currentHeading) {
                    $otherBuckets[] = ['heading' => $currentHeading !== '' ? $currentHeading : 'Other', 'lines' => [$line]];
                } else {
                    $otherBuckets[$n - 1]['lines'][] = $line;
                }
            }
        }

        return [
            'managed_keys' => self::managedCategoryKeys(),
            'capital' => ['type' => 'CAPITAL', 'lines' => $capitalLines],
            'income' => ['type' => 'INCOME', 'buckets' => $incomeBuckets],
            'expense' => ['type' => 'EXPENSES', 'buckets' => $expenseBuckets],
            'discretionary' => ['type' => 'DISCRETIONARY', 'buckets' => $discretionaryBuckets],
            'savings' => ['type' => 'SAVINGS', 'buckets' => $savingsBuckets],
            'other' => ['type' => 'OTHER', 'buckets' => $otherBuckets],
        ];
    }

    /**
     * Stub rows to insert when missing (one entry per category_key per day).
     *
     * @return list<array{type: string, category_key: string, label: string, sort: int}> label is used as stored description on stub rows.
     */
    public static function stubLines(): array
    {
        $out = [];
        $sort = 0;
        foreach (self::sheetDefinition() as $row) {
            if (($row['kind'] ?? '') !== 'line') {
                continue;
            }
            $sort++;
            $catKey = (string) $row['category_key'];
            $catDisplay = (string) ($row['category_display'] ?? $row['label'] ?? $catKey);
            $out[] = [
                'type' => (string) $row['type'],
                'category_key' => $catKey,
                'label' => $catDisplay,
                'sort' => $sort,
            ];
        }

        return $out;
    }

    /**
     * @return Collection<int, DailyCashEntry>
     */
    public static function nonMetroEntries(DailyCashDay $day): Collection
    {
        return $day->entries->filter(function (DailyCashEntry $e) {
            // Worksheet "Capital" row aggregates every CAPITAL entry (category optional).
            if ($e->type === 'CAPITAL') {
                return false;
            }

            return ! self::isManagedCategory($e->category);
        })->values();
    }

    /**
     * Build rows for the daily worksheet table (headings + lines with totals).
     *
     * @return list<array<string, mixed>>
     */
    public static function buildSheetRows(DailyCashDay $day): array
    {
        $built = [];
        foreach (self::sheetDefinition() as $row) {
            $kind = $row['kind'] ?? '';
            if ($kind === 'heading') {
                $built[] = [
                    'kind' => 'heading',
                    'title' => (string) ($row['title'] ?? ''),
                ];

                continue;
            }
            if ($kind !== 'line') {
                continue;
            }

            $type = (string) $row['type'];
            $categoryKey = (string) $row['category_key'];
            $categoryDisplay = (string) ($row['category_display'] ?? $row['label'] ?? $categoryKey);

            $matches = $type === 'CAPITAL'
                ? $day->entries->filter(fn (DailyCashEntry $e) => $e->type === 'CAPITAL')->sortBy('id')->values()
                : $day->entries
                    ->filter(fn (DailyCashEntry $e) => $e->type === $type && $e->category === $categoryKey)
                    ->sortBy('id')
                    ->values();

            $amount = (float) $matches->sum('amount');
            $representative = $matches->first(function (DailyCashEntry $e) {
                return abs((float) $e->amount) > 0.005;
            }) ?? $matches->first();

            $displayLabel = self::worksheetUsesOthersAggregateLabel($categoryKey)
                ? self::aggregatedOthersCategoryLabel($categoryDisplay, $matches)
                : $categoryDisplay;

            $built[] = [
                'kind' => 'line',
                'type' => $type,
                'category_key' => $categoryKey,
                'category_display' => $displayLabel,
                'type_word' => self::worksheetTypeWord($type),
                'amount' => $amount,
                'entry_count' => $matches->count(),
                'representative_entry' => $representative,
            ];
        }

        return $built;
    }

    /**
     * Monthly Metro worksheet for one calendar month: same rows/columns as the daily sheet;
     * amounts are totals of matching ledger lines in that month only.
     *
     * @return array<string, mixed>
     */
    public static function buildMonthlyMetroSheetForMonth(int $year, int $month): array
    {
        $month = max(1, min(12, $month));

        $entries = DailyCashEntry::query()
            ->with('day')
            ->whereHas('day', fn ($q) => $q->whereYear('date', $year)->whereMonth('date', $month))
            ->get();

        $sections = [];
        $currentSection = null;

        foreach (self::sheetDefinition() as $row) {
            $kind = $row['kind'] ?? '';
            if ($kind === 'heading') {
                if ($currentSection !== null) {
                    $sections[] = $currentSection;
                }
                $currentSection = [
                    'heading' => (string) ($row['title'] ?? ''),
                    'lines' => [],
                ];

                continue;
            }
            if ($kind !== 'line' || $currentSection === null) {
                continue;
            }

            $type = (string) $row['type'];
            $categoryKey = (string) $row['category_key'];
            $categoryDisplayBase = (string) ($row['category_display'] ?? $row['label'] ?? $categoryKey);

            $monthMatches = self::filterEntriesForSheetLine($entries, $year, $month, $type, $categoryKey);

            $amount = round((float) $monthMatches->sum('amount'), 2);

            $displayLabel = self::worksheetUsesOthersAggregateLabel($categoryKey)
                ? self::aggregatedOthersCategoryLabel($categoryDisplayBase, $monthMatches)
                : $categoryDisplayBase;

            $currentSection['lines'][] = [
                'type' => $type,
                'category_key' => $categoryKey,
                'category_display' => $displayLabel,
                'type_word' => self::worksheetTypeWord($type),
                'primary_amount_column' => self::primaryAmountColumn($type),
                'amount' => $amount,
            ];
        }

        if ($currentSection !== null) {
            $sections[] = $currentSection;
        }

        $t = self::totalsForEntries($entries);
        $footerTotals = [
            'capital' => round((float) $t['capital'], 2),
            'income' => round((float) $t['income'], 2),
            'expenses' => round((float) $t['expenses'], 2),
            'discretionary' => round((float) $t['discretionary'], 2),
            'savings' => round((float) $t['savings'], 2),
            'other' => round((float) $t['other'], 2),
        ];
        $monthNet = round((float) $t['net'], 2);

        return [
            'year' => $year,
            'month' => $month,
            'period_title' => Carbon::createFromDate($year, $month, 1)->format('F Y'),
            'sections' => $sections,
            'footer_totals' => $footerTotals,
            'month_net' => $monthNet,
            'has_entries' => $entries->isNotEmpty(),
        ];
    }

    /**
     * Annual cashflow: same Metro worksheet lines as Monthly/Daily; columns are January–December,
     * each month split into Income, Expense, Discretionary, Savings (worksheet lines post only to their type column).
     * Footer Total row sums the full ledger per month (capital+income in Income; expenses+OTHER type in Expense; etc.).
     *
     * @return array<string, mixed>
     */
    public static function buildAnnualCashflowGrid(int $year): array
    {
        $cols = ['income', 'expense', 'discretionary', 'savings'];

        $entries = DailyCashEntry::query()
            ->with('day')
            ->whereHas('day', fn ($q) => $q->whereYear('date', $year))
            ->get();

        $monthLabels = [];
        for ($m = 1; $m <= 12; $m++) {
            $monthLabels[$m] = Carbon::createFromDate($year, $m, 1)->format('F');
        }

        $sections = [];
        $currentSection = null;

        foreach (self::sheetDefinition() as $row) {
            $kind = $row['kind'] ?? '';
            if ($kind === 'heading') {
                if ($currentSection !== null) {
                    $sections[] = $currentSection;
                }
                $currentSection = [
                    'heading' => (string) ($row['title'] ?? ''),
                    'lines' => [],
                ];

                continue;
            }
            if ($kind !== 'line' || $currentSection === null) {
                continue;
            }

            $type = (string) $row['type'];
            $categoryKey = (string) $row['category_key'];
            $categoryDisplayBase = (string) ($row['category_display'] ?? $row['label'] ?? $categoryKey);

            $yearMatches = self::filterEntriesForSheetLine($entries, $year, null, $type, $categoryKey);

            $displayLabel = self::worksheetUsesOthersAggregateLabel($categoryKey)
                ? self::aggregatedOthersCategoryLabel($categoryDisplayBase, $yearMatches)
                : $categoryDisplayBase;

            $primaryCol = self::resolveAnnualCashflowAmountColumn($type);

            $monthsData = [];
            $rowSum = 0.0;
            for ($m = 1; $m <= 12; $m++) {
                $monthMatches = self::filterEntriesForSheetLine($entries, $year, $m, $type, $categoryKey);
                $amt = round((float) $monthMatches->sum('amount'), 2);
                $cell = array_fill_keys($cols, 0.0);
                $cell[$primaryCol] = $amt;
                $monthsData[$m] = $cell;
                $rowSum += $amt;
            }

            $currentSection['lines'][] = [
                'type' => $type,
                'category_key' => $categoryKey,
                'category_display' => $displayLabel,
                'primary_amount_column' => $primaryCol,
                'months' => $monthsData,
                'row_total' => round($rowSum, 2),
            ];
        }

        if ($currentSection !== null) {
            $sections[] = $currentSection;
        }

        $totalsMonths = [];
        $grandSum = 0.0;
        for ($m = 1; $m <= 12; $m++) {
            $monthEntries = $entries->filter(
                fn (DailyCashEntry $e) => (int) $e->day->date->format('Y') === $year
                    && (int) $e->day->date->format('n') === $m
            );
            $t = self::totalsForEntries($monthEntries);
            $totalsMonths[$m] = [
                'income' => round((float) $t['capital'] + (float) $t['income'], 2),
                'expense' => round((float) $t['expenses'] + (float) $t['other'], 2),
                'discretionary' => round((float) $t['discretionary'], 2),
                'savings' => round((float) $t['savings'], 2),
            ];
            foreach ($totalsMonths[$m] as $v) {
                $grandSum += $v;
            }
        }

        return [
            'year' => $year,
            'month_labels' => $monthLabels,
            'amount_columns' => [
                ['key' => 'income', 'label' => 'Income'],
                ['key' => 'expense', 'label' => 'Expense'],
                ['key' => 'discretionary', 'label' => 'Disc.'],
                ['key' => 'savings', 'label' => 'Savings'],
            ],
            'sections' => $sections,
            'totals_row' => [
                'label' => 'Totals',
                'months' => $totalsMonths,
                'row_total' => round($grandSum, 2),
            ],
            'has_entries' => $entries->isNotEmpty(),
        ];
    }

    private static function resolveAnnualCashflowAmountColumn(string $ledgerType): string
    {
        return match ($ledgerType) {
            'CAPITAL', 'INCOME' => 'income',
            'EXPENSES', 'PURCHASES', 'OTHER' => 'expense',
            'DISCRETIONARY' => 'discretionary',
            'SAVINGS' => 'savings',
            default => 'expense',
        };
    }

    /**
     * @param  Collection<int, DailyCashEntry>  $entries
     * @return Collection<int, DailyCashEntry>
     */
    private static function filterEntriesForSheetLine(Collection $entries, int $year, ?int $month, string $type, string $categoryKey): Collection
    {
        return $entries->filter(function (DailyCashEntry $e) use ($year, $month, $type, $categoryKey) {
            $d = $e->day->date;
            if ((int) $d->format('Y') !== $year) {
                return false;
            }
            if ($month !== null && (int) $d->format('n') !== $month) {
                return false;
            }
            if ($type === 'CAPITAL') {
                return $e->type === 'CAPITAL';
            }

            return $e->type === $type && $e->category === $categoryKey;
        })->sortBy('id')->values();
    }

    /**
     * @param  Collection<int, DailyCashEntry>  $entries
     * @return array{capital: float, income: float, expenses: float, discretionary: float, savings: float, other: float, net: float}
     */
    private static function totalsForEntries(Collection $entries): array
    {
        $capital = (float) $entries->where('type', 'CAPITAL')->sum('amount');
        $income = (float) $entries->where('type', 'INCOME')->sum('amount');
        $expenses = (float) $entries->whereIn('type', ['EXPENSES', 'PURCHASES'])->sum('amount');
        $discretionary = (float) $entries->where('type', 'DISCRETIONARY')->sum('amount');
        $savings = (float) $entries->where('type', 'SAVINGS')->sum('amount');
        $other = (float) $entries->where('type', 'OTHER')->sum('amount');
        $net = $capital + $income - $expenses - $discretionary - $savings - $other;

        return compact('capital', 'income', 'expenses', 'discretionary', 'savings', 'other') + ['net' => $net];
    }

    /** Left-column group label: matches worksheet section heading (e.g. INCOME: ASSORTED). */
    public static function parentColumnLabelFromSectionHeading(string $heading): string
    {
        $t = trim($heading);
        if ($t === '') {
            return '';
        }

        return strtoupper($t);
    }

    /** Single-word label for the worksheet Type column (Capital / Income / Expense / …). */
    public static function worksheetTypeWord(string $ledgerType): string
    {
        return match ($ledgerType) {
            'CAPITAL' => 'Capital',
            'INCOME' => 'Income',
            'EXPENSES', 'PURCHASES' => 'Expense',
            'DISCRETIONARY' => 'Discretionary',
            'SAVINGS' => 'Savings',
            'OTHER' => 'Other',
            default => $ledgerType,
        };
    }

    /** Amount column key for coloring / cell placement. */
    public static function primaryAmountColumn(string $type): string
    {
        return match ($type) {
            'CAPITAL' => 'capital',
            'INCOME' => 'income',
            'EXPENSES', 'PURCHASES' => 'expenses',
            'DISCRETIONARY' => 'discretionary',
            'SAVINGS' => 'savings',
            'OTHER' => 'other',
            default => 'other',
        };
    }

    /**
     * @param  Collection<int, DailyCashEntry>  $matches
     */
    private static function aggregatedOthersCategoryLabel(string $baseDisplay, Collection $matches): string
    {
        $stubNorm = strtoupper(preg_replace('/\s+/', ' ', trim($baseDisplay)));

        $meaningful = $matches->filter(function (DailyCashEntry $e) use ($stubNorm) {
            if (abs((float) $e->amount) <= 0.005) {
                return false;
            }
            $d = strtoupper(preg_replace('/\s+/', ' ', trim((string) $e->description)));

            return $d !== '' && $d !== $stubNorm;
        })->sortBy('id')->values();

        if ($meaningful->isEmpty()) {
            return $baseDisplay;
        }

        $unique = $meaningful->map(fn (DailyCashEntry $e) => trim((string) $e->description))->unique()->values();
        if ($unique->count() === 1) {
            return 'Other - '.strtoupper($unique->first());
        }

        return 'Other — multiple';
    }
}
