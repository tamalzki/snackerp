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

    /** Per-day custom row entries store this prefix on {@see DailyCashEntry::$category}, followed by the section slug. */
    public const CUSTOM_CATEGORY_PREFIX = 'custom:';

    /** Slug for a worksheet section heading (used in custom: keys and meta tree). */
    public static function sectionSlugFromHeading(string $heading): string
    {
        $s = strtolower(trim($heading));
        $s = preg_replace('/[^a-z0-9]+/', '_', $s);

        return trim((string) $s, '_');
    }

    /** "custom:<slug>" identifier stored on per-day custom row entries. */
    public static function customCategoryKeyForSlug(string $sectionSlug): string
    {
        return self::CUSTOM_CATEGORY_PREFIX.$sectionSlug;
    }

    public static function isCustomCategory(?string $category): bool
    {
        return is_string($category) && str_starts_with($category, self::CUSTOM_CATEGORY_PREFIX);
    }

    public static function customCategorySlug(?string $category): ?string
    {
        if (! self::isCustomCategory($category)) {
            return null;
        }

        return substr((string) $category, strlen(self::CUSTOM_CATEGORY_PREFIX));
    }

    /**
     * Walks the metro_daily_sheet config and emits sections (heading + lines + derived slug + primary type).
     *
     * @return list<array{heading: string, slug: string, primary_type: string, lines: list<array<string, mixed>>}>
     */
    public static function sections(): array
    {
        $out = [];
        $current = null;
        foreach (self::sheetDefinition() as $row) {
            $kind = $row['kind'] ?? '';
            if ($kind === 'heading') {
                if ($current !== null) {
                    $out[] = $current;
                }
                $heading = (string) ($row['title'] ?? '');
                $current = [
                    'heading' => $heading,
                    'slug' => self::sectionSlugFromHeading($heading),
                    'primary_type' => '',
                    'lines' => [],
                ];

                continue;
            }
            if ($kind === 'line' && $current !== null) {
                $current['lines'][] = $row;
            }
        }
        if ($current !== null) {
            $out[] = $current;
        }

        foreach ($out as &$section) {
            $section['primary_type'] = self::primaryTypeForLines($section['lines']);
        }
        unset($section);

        return $out;
    }

    /**
     * Pick the section's "main" ledger type for custom rows: most frequent non-ADJUSTMENT type,
     * falling back to ADJUSTMENT if every line is an adjustment.
     *
     * @param  list<array<string, mixed>>  $lines
     */
    private static function primaryTypeForLines(array $lines): string
    {
        $counts = [];
        foreach ($lines as $line) {
            $t = (string) ($line['type'] ?? '');
            if ($t === '' || $t === 'ADJUSTMENT') {
                continue;
            }
            $counts[$t] = ($counts[$t] ?? 0) + 1;
        }
        if ($counts !== []) {
            arsort($counts);

            return (string) array_key_first($counts);
        }
        foreach ($lines as $line) {
            if (($line['type'] ?? '') === 'ADJUSTMENT') {
                return 'ADJUSTMENT';
            }
        }

        return 'OTHER';
    }

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
     * Worksheet CAPITAL line match: per stored category, plus legacy rows on the “cash from bank” line.
     * Uncategorized CAPITAL posts to the {@see capital_contribution} (Cash on hand) line.
     */
    public static function entryMatchesCapitalWorksheetCategory(DailyCashEntry $e, string $categoryKey): bool
    {
        if ($categoryKey === DailyCashflowCategories::CASH_FROM_BANK) {
            return ($e->category ?? '') === DailyCashflowCategories::CASH_FROM_BANK
                && (($e->type ?? '') === 'CAPITAL' || ($e->type ?? '') === 'INCOME');
        }

        if (($e->type ?? '') !== 'CAPITAL') {
            return false;
        }

        $cat = (string) ($e->category ?? '');

        if ($cat === $categoryKey) {
            return true;
        }

        return $categoryKey === 'capital_contribution' && $cat === '';
    }

    /**
     * Footnote amount on the “Cash from bank” worksheet line (withdrawals tag).
     *
     * @param  Collection<int, DailyCashEntry>  $matches
     */
    private static function bankWithdrawalsNoteAmount(string $worksheetCapitalCategoryKey, Collection $matches): float
    {
        if ($worksheetCapitalCategoryKey !== DailyCashflowCategories::CASH_FROM_BANK) {
            return 0.0;
        }

        return round((float) $matches
            ->filter(fn (DailyCashEntry $e) => ($e->category ?? '') === DailyCashflowCategories::CASH_FROM_BANK)
            ->sum('amount'), 2);
    }

    /**
     * JSON-safe tree for Add/Edit Entry modals (worksheet drill-down). Each line carries its
     * declared ledger type so per-group Adjustments rows resolve to ADJUSTMENT regardless of
     * which group dropdown the user picked. A synthetic "+ Custom row" entry is appended to
     * every bucket so users can name their own line on the fly.
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
        $adjustmentBuckets = [];
        $otherBuckets = [];

        foreach (self::sections() as $section) {
            $heading = $section['heading'];
            $slug = $section['slug'];
            $primaryType = $section['primary_type'];
            $customLine = self::syntheticCustomLine($slug, $primaryType);

            $linesByType = [
                'CAPITAL' => [],
                'INCOME' => [],
                'EXPENSES' => [],
                'DISCRETIONARY' => [],
                'SAVINGS' => [],
                'ADJUSTMENT' => [],
                'OTHER' => [],
            ];
            foreach ($section['lines'] as $row) {
                $type = (string) $row['type'];
                $key = (string) $row['category_key'];
                $label = (string) ($row['category_display'] ?? $row['label'] ?? $key);
                $linesByType[$type][] = [
                    'key' => $key,
                    'label' => $label,
                    'type' => $type,
                    'needsOther' => self::worksheetNeedsSpecifyOtherField($key),
                    'isCustom' => false,
                ];
            }

            $capitalSubset = $linesByType['CAPITAL'];
            if ($primaryType === 'CAPITAL') {
                $capitalSubset[] = $customLine;
            }
            $capitalLines = array_merge($capitalLines, $capitalSubset);

            self::pushBucket($incomeBuckets, $heading, $slug, 'INCOME', $linesByType['INCOME'], $primaryType, $customLine);
            self::pushBucket($expenseBuckets, $heading, $slug, 'EXPENSES', $linesByType['EXPENSES'], $primaryType, $customLine);
            self::pushBucket($discretionaryBuckets, $heading, $slug, 'DISCRETIONARY', $linesByType['DISCRETIONARY'], $primaryType, $customLine);
            self::pushBucket($savingsBuckets, $heading, $slug, 'SAVINGS', $linesByType['SAVINGS'], $primaryType, $customLine);
            self::pushBucket($otherBuckets, $heading, $slug, 'OTHER', $linesByType['OTHER'], $primaryType, $customLine);

            $adjLines = $linesByType['ADJUSTMENT'];
            if ($primaryType === 'ADJUSTMENT') {
                $adjLines[] = $customLine;
            }
            if ($adjLines !== []) {
                $adjustmentBuckets[] = [
                    'heading' => $heading !== '' ? $heading : 'Adjustments',
                    'section_slug' => $slug,
                    'lines' => $adjLines,
                ];
            }
        }

        return [
            'managed_keys' => self::managedCategoryKeys(),
            'capital' => ['type' => 'CAPITAL', 'lines' => $capitalLines],
            'income' => ['type' => 'INCOME', 'buckets' => $incomeBuckets],
            'expense' => ['type' => 'EXPENSES', 'buckets' => $expenseBuckets],
            'discretionary' => ['type' => 'DISCRETIONARY', 'buckets' => $discretionaryBuckets],
            'savings' => ['type' => 'SAVINGS', 'buckets' => $savingsBuckets],
            'adjustment' => ['type' => 'ADJUSTMENT', 'buckets' => $adjustmentBuckets],
            'other' => ['type' => 'OTHER', 'buckets' => $otherBuckets],
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $buckets
     * @param  list<array<string, mixed>>  $lines
     * @param  array<string, mixed>  $customLine
     */
    private static function pushBucket(array &$buckets, string $heading, string $slug, string $type, array $lines, string $primaryType, array $customLine): void
    {
        if ($primaryType === $type) {
            $lines[] = $customLine;
        }
        if ($lines === []) {
            return;
        }
        $buckets[] = [
            'heading' => $heading !== '' ? $heading : ucfirst(strtolower($type)),
            'section_slug' => $slug,
            'lines' => $lines,
        ];
    }

    /** @return array{key: string, label: string, type: string, needsOther: bool, isCustom: bool, section_slug: string} */
    private static function syntheticCustomLine(string $sectionSlug, string $primaryType): array
    {
        return [
            'key' => self::customCategoryKeyForSlug($sectionSlug),
            'label' => '+ Custom row…',
            'type' => $primaryType,
            'needsOther' => true,
            'isCustom' => true,
            'section_slug' => $sectionSlug,
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
            if (self::entryBelongsToMetroCapitalPool($e)) {
                return false;
            }
            if (self::isCustomCategory($e->category)) {
                return false;
            }

            return ! self::isManagedCategory($e->category);
        })->values();
    }

    /**
     * Build rows for the daily worksheet table (headings + lines with totals).
     * Per-day custom rows ("custom:<section_slug>" entries) are emitted at the bottom of their section.
     *
     * @return list<array<string, mixed>>
     */
    /** Heading used for the catch-all section listing entries no worksheet line or custom row claims (e.g. legacy `PURCHASES`-type entries logged via the Statement screens). */
    public const UNMATCHED_SECTION_HEADING = 'UNCATEGORIZED / OTHER LEDGER TYPES';

    public static function buildSheetRows(DailyCashDay $day): array
    {
        $built = [];
        $claimedIds = [];
        foreach (self::sections() as $section) {
            $heading = $section['heading'];
            $sectionSlug = $section['slug'];
            $primaryType = $section['primary_type'];

            $built[] = [
                'kind' => 'heading',
                'title' => $heading,
            ];

            foreach ($section['lines'] as $row) {
                $type = (string) $row['type'];
                $categoryKey = (string) $row['category_key'];
                $categoryDisplay = (string) ($row['category_display'] ?? $row['label'] ?? $categoryKey);

                $matches = $type === 'CAPITAL'
                    ? $day->entries
                        ->filter(fn (DailyCashEntry $e) => self::entryMatchesCapitalWorksheetCategory($e, $categoryKey))
                        ->sortBy('id')
                        ->values()
                    : $day->entries
                        ->filter(fn (DailyCashEntry $e) => $e->type === $type && $e->category === $categoryKey)
                        ->sortBy('id')
                        ->values();

                foreach ($matches as $m) {
                    $claimedIds[$m->id] = true;
                }

                $amount = (float) $matches->sum('amount');
                $representative = $matches->first(function (DailyCashEntry $e) {
                    return abs((float) $e->amount) > 0.005;
                }) ?? $matches->first();
                $meaningfulCount = $matches->filter(fn (DailyCashEntry $e) => abs((float) $e->amount) > 0.005)->count();

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
                    'meaningful_entry_count' => max($meaningfulCount, $matches->isEmpty() ? 0 : 1),
                    'representative_entry' => $representative,
                    'entries' => $matches,
                    'bank_withdrawals_in_capital' => $type === 'CAPITAL'
                        ? self::bankWithdrawalsNoteAmount($categoryKey, $matches)
                        : 0.0,
                    'is_custom' => false,
                    'section_slug' => $sectionSlug,
                ];
            }

            $customKey = self::customCategoryKeyForSlug($sectionSlug);
            $customEntries = $day->entries
                ->filter(fn (DailyCashEntry $e) => (string) ($e->category ?? '') === $customKey)
                ->sortBy('id')
                ->values();

            foreach ($customEntries as $entry) {
                $claimedIds[$entry->id] = true;
                $entryType = (string) ($entry->type ?? $primaryType);
                $built[] = [
                    'kind' => 'line',
                    'type' => $entryType,
                    'category_key' => $customKey,
                    'category_display' => (string) ($entry->description !== null && $entry->description !== ''
                        ? $entry->description
                        : 'Custom row'),
                    'type_word' => self::worksheetTypeWord($entryType),
                    'amount' => (float) $entry->amount,
                    'entry_count' => 1,
                    'representative_entry' => $entry,
                    'bank_withdrawals_in_capital' => 0.0,
                    'is_custom' => true,
                    'section_slug' => $sectionSlug,
                ];
            }
        }

        $unmatched = $day->entries
            ->reject(fn (DailyCashEntry $e) => isset($claimedIds[$e->id]))
            ->sortBy('id')
            ->values();

        if ($unmatched->isNotEmpty()) {
            $built[] = ['kind' => 'heading', 'title' => self::UNMATCHED_SECTION_HEADING, 'no_add' => true];

            foreach ($unmatched as $entry) {
                $entryType = (string) $entry->type;
                $built[] = [
                    'kind' => 'line',
                    'type' => $entryType,
                    'category_key' => (string) ($entry->category ?? ''),
                    'category_display' => (string) ($entry->description !== null && $entry->description !== ''
                        ? $entry->description
                        : self::worksheetTypeWord($entryType)),
                    'type_word' => self::worksheetTypeWord($entryType),
                    'amount' => (float) $entry->amount,
                    'entry_count' => 1,
                    'meaningful_entry_count' => 1,
                    'representative_entry' => $entry,
                    'entries' => collect([$entry]),
                    'bank_withdrawals_in_capital' => 0.0,
                    'is_custom' => true,
                    'section_slug' => '',
                ];
            }
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
        foreach (self::sections() as $section) {
            $heading = $section['heading'];
            $slug = $section['slug'];
            $built = ['heading' => $heading, 'lines' => []];

            foreach ($section['lines'] as $row) {
                $type = (string) $row['type'];
                $categoryKey = (string) $row['category_key'];
                $categoryDisplayBase = (string) ($row['category_display'] ?? $row['label'] ?? $categoryKey);

                $monthMatches = self::filterEntriesForSheetLine($entries, $year, $month, $type, $categoryKey);
                $amount = round((float) $monthMatches->sum('amount'), 2);

                $displayLabel = self::worksheetUsesOthersAggregateLabel($categoryKey)
                    ? self::aggregatedOthersCategoryLabel($categoryDisplayBase, $monthMatches)
                    : $categoryDisplayBase;

                $built['lines'][] = [
                    'type' => $type,
                    'category_key' => $categoryKey,
                    'category_display' => $displayLabel,
                    'type_word' => self::worksheetTypeWord($type),
                    'primary_amount_column' => self::primaryAmountColumn($type),
                    'amount' => $amount,
                    'bank_withdrawals_in_capital' => $type === 'CAPITAL'
                        ? self::bankWithdrawalsNoteAmount($categoryKey, $monthMatches)
                        : 0.0,
                    'is_custom' => false,
                ];
            }

            foreach (self::customRowsForSection($entries, $slug, $year, $month) as $customLine) {
                $built['lines'][] = $customLine;
            }

            $sections[] = $built;
        }

        $t = self::totalsForEntries($entries);
        $footerTotals = [
            'capital' => round((float) $t['capital'], 2),
            'income' => round((float) $t['income'], 2),
            'expenses' => round((float) $t['expenses'], 2),
            'discretionary' => round((float) $t['discretionary'], 2),
            'savings' => round((float) $t['savings'], 2),
            'adjustment' => round((float) $t['adjustment'], 2),
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
        $cols = ['income', 'expense', 'discretionary', 'savings', 'adjustment'];

        $entries = DailyCashEntry::query()
            ->with('day')
            ->whereHas('day', fn ($q) => $q->whereYear('date', $year))
            ->get();

        // One pass over the ledger: resolve each entry's month and index by type|category,
        // so worksheet lines don't rescan the full year 13 times each (timeout at scale).
        $monthOf = [];
        $byMonth = array_fill(1, 12, []);
        $byTypeCategory = [];
        $capitalCandidates = [];
        foreach ($entries as $e) {
            $m = (int) $e->day->date->format('n');
            $monthOf[spl_object_id($e)] = $m;
            $byMonth[$m][] = $e;
            $byTypeCategory[($e->type ?? '').'|'.($e->category ?? '')][] = $e;
            if (($e->type ?? '') === 'CAPITAL'
                || (($e->type ?? '') === 'INCOME' && ($e->category ?? '') === DailyCashflowCategories::CASH_FROM_BANK)) {
                $capitalCandidates[] = $e;
            }
        }

        $monthLabels = [];
        for ($m = 1; $m <= 12; $m++) {
            $monthLabels[$m] = Carbon::createFromDate($year, $m, 1)->format('F');
        }

        $sections = [];
        foreach (self::sections() as $section) {
            $built = ['heading' => $section['heading'], 'lines' => []];
            $slug = $section['slug'];

            foreach ($section['lines'] as $row) {
                $type = (string) $row['type'];
                $categoryKey = (string) $row['category_key'];
                $categoryDisplayBase = (string) ($row['category_display'] ?? $row['label'] ?? $categoryKey);

                if ($type === 'CAPITAL') {
                    $lineEntries = array_values(array_filter(
                        $capitalCandidates,
                        fn (DailyCashEntry $e) => self::entryMatchesCapitalWorksheetCategory($e, $categoryKey)
                    ));
                } else {
                    $lineEntries = $byTypeCategory[$type.'|'.$categoryKey] ?? [];
                }
                $yearMatches = collect($lineEntries);

                $displayLabel = self::worksheetUsesOthersAggregateLabel($categoryKey)
                    ? self::aggregatedOthersCategoryLabel($categoryDisplayBase, $yearMatches)
                    : $categoryDisplayBase;

                $primaryCol = self::resolveAnnualCashflowAmountColumn($type);

                $monthSums = array_fill(1, 12, 0.0);
                foreach ($lineEntries as $e) {
                    $monthSums[$monthOf[spl_object_id($e)]] += (float) $e->amount;
                }

                $monthsData = [];
                $rowSum = 0.0;
                for ($m = 1; $m <= 12; $m++) {
                    $amt = round($monthSums[$m], 2);
                    $cell = array_fill_keys($cols, 0.0);
                    $cell[$primaryCol] = $amt;
                    $monthsData[$m] = $cell;
                    $rowSum += $amt;
                }

                $built['lines'][] = [
                    'type' => $type,
                    'category_key' => $categoryKey,
                    'category_display' => $displayLabel,
                    'primary_amount_column' => $primaryCol,
                    'months' => $monthsData,
                    'row_total' => round($rowSum, 2),
                    'bank_withdrawals_in_capital' => $type === 'CAPITAL'
                        ? self::bankWithdrawalsNoteAmount($categoryKey, $yearMatches)
                        : 0.0,
                    'is_custom' => false,
                ];
            }

            foreach (self::customRowsForSectionAnnual($entries, $slug, $year, $cols) as $customLine) {
                $built['lines'][] = $customLine;
            }

            $sections[] = $built;
        }

        $totalsMonths = [];
        $netMonths = [];
        $carryoverMonths = [];
        $grandSum = 0.0;
        $running = 0.0;
        for ($m = 1; $m <= 12; $m++) {
            $t = self::totalsForEntries(collect($byMonth[$m]));
            $totalsMonths[$m] = [
                'income' => round((float) $t['capital'] + (float) $t['income'], 2),
                'expense' => round((float) $t['expenses'] + (float) $t['other'], 2),
                'discretionary' => round((float) $t['discretionary'], 2),
                'savings' => round((float) $t['savings'], 2),
                'adjustment' => round((float) $t['adjustment'], 2),
            ];
            foreach ($totalsMonths[$m] as $v) {
                $grandSum += $v;
            }
            $netMonths[$m] = round((float) $t['net'], 2);
            $running = round($running + $netMonths[$m], 2);
            $carryoverMonths[$m] = $running;
        }

        return [
            'year' => $year,
            'month_labels' => $monthLabels,
            'amount_columns' => [
                ['key' => 'income', 'label' => 'Income'],
                ['key' => 'expense', 'label' => 'Expense'],
                ['key' => 'discretionary', 'label' => 'Disc.'],
                ['key' => 'savings', 'label' => 'Savings'],
                ['key' => 'adjustment', 'label' => 'Adjust.'],
            ],
            'sections' => $sections,
            'totals_row' => [
                'label' => 'Totals',
                'months' => $totalsMonths,
                'row_total' => round($grandSum, 2),
            ],
            'net_row' => [
                'label' => 'Net (month)',
                'months' => $netMonths,
                'row_total' => $running,
            ],
            'carryover_row' => [
                'label' => 'Running balance (carry-over)',
                'months' => $carryoverMonths,
                'row_total' => $running,
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
            'ADJUSTMENT' => 'adjustment',
            default => 'expense',
        };
    }

    /**
     * Aggregate per-day custom rows ("custom:<slug>") into monthly/annual lines, grouped by (type, description).
     *
     * @param  Collection<int, DailyCashEntry>  $entries
     * @return list<array<string, mixed>>
     */
    private static function customRowsForSection(Collection $entries, string $sectionSlug, int $year, ?int $month): array
    {
        $customKey = self::customCategoryKeyForSlug($sectionSlug);
        $matches = $entries->filter(function (DailyCashEntry $e) use ($customKey, $year, $month) {
            if ((string) ($e->category ?? '') !== $customKey) {
                return false;
            }
            $d = $e->day->date;
            if ((int) $d->format('Y') !== $year) {
                return false;
            }
            if ($month !== null && (int) $d->format('n') !== $month) {
                return false;
            }

            return true;
        })->values();

        if ($matches->isEmpty()) {
            return [];
        }

        $groups = [];
        foreach ($matches as $entry) {
            $type = (string) ($entry->type ?? '');
            $desc = trim((string) ($entry->description ?? ''));
            $label = $desc !== '' ? $desc : 'Custom row';
            $bucketKey = $type.'|'.mb_strtoupper(preg_replace('/\s+/', ' ', $label), 'UTF-8');
            if (! isset($groups[$bucketKey])) {
                $groups[$bucketKey] = [
                    'type' => $type,
                    'category_key' => $customKey,
                    'category_display' => $label,
                    'type_word' => self::worksheetTypeWord($type),
                    'primary_amount_column' => self::primaryAmountColumn($type),
                    'amount' => 0.0,
                    'bank_withdrawals_in_capital' => 0.0,
                    'is_custom' => true,
                ];
            }
            $groups[$bucketKey]['amount'] = round($groups[$bucketKey]['amount'] + (float) $entry->amount, 2);
        }

        return array_values($groups);
    }

    /**
     * Annual variant: 12-month cells per (type, description) custom-row group.
     *
     * @param  Collection<int, DailyCashEntry>  $entries
     * @param  list<string>  $cols
     * @return list<array<string, mixed>>
     */
    private static function customRowsForSectionAnnual(Collection $entries, string $sectionSlug, int $year, array $cols): array
    {
        $customKey = self::customCategoryKeyForSlug($sectionSlug);
        $matches = $entries->filter(function (DailyCashEntry $e) use ($customKey, $year) {
            if ((string) ($e->category ?? '') !== $customKey) {
                return false;
            }

            return (int) $e->day->date->format('Y') === $year;
        })->values();

        if ($matches->isEmpty()) {
            return [];
        }

        $groups = [];
        foreach ($matches as $entry) {
            $type = (string) ($entry->type ?? '');
            $desc = trim((string) ($entry->description ?? ''));
            $label = $desc !== '' ? $desc : 'Custom row';
            $bucketKey = $type.'|'.mb_strtoupper(preg_replace('/\s+/', ' ', $label), 'UTF-8');
            $primaryCol = self::resolveAnnualCashflowAmountColumn($type);
            if (! isset($groups[$bucketKey])) {
                $monthsData = [];
                for ($m = 1; $m <= 12; $m++) {
                    $monthsData[$m] = array_fill_keys($cols, 0.0);
                }
                $groups[$bucketKey] = [
                    'type' => $type,
                    'category_key' => $customKey,
                    'category_display' => $label,
                    'primary_amount_column' => $primaryCol,
                    'months' => $monthsData,
                    'row_total' => 0.0,
                    'bank_withdrawals_in_capital' => 0.0,
                    'is_custom' => true,
                ];
            }
            $mIdx = (int) $entry->day->date->format('n');
            $groups[$bucketKey]['months'][$mIdx][$primaryCol] = round(
                $groups[$bucketKey]['months'][$mIdx][$primaryCol] + (float) $entry->amount,
                2
            );
            $groups[$bucketKey]['row_total'] = round($groups[$bucketKey]['row_total'] + (float) $entry->amount, 2);
        }

        return array_values($groups);
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
                return self::entryMatchesCapitalWorksheetCategory($e, $categoryKey);
            }

            return $e->type === $type && $e->category === $categoryKey;
        })->sortBy('id')->values();
    }

    /**
     * @param  Collection<int, DailyCashEntry>  $entries
     * @return array{capital: float, income: float, expenses: float, discretionary: float, savings: float, other: float, adjustment: float, net: float}
     */
    private static function totalsForEntries(Collection $entries): array
    {
        $capital = (float) $entries
            ->filter(fn (DailyCashEntry $e) => self::entryBelongsToMetroCapitalPool($e))
            ->sum('amount');
        $income = (float) $entries
            ->where('type', 'INCOME')
            ->filter(fn (DailyCashEntry $e) => ($e->category ?? '') !== DailyCashflowCategories::CASH_FROM_BANK)
            ->sum('amount');
        $expenses = (float) $entries->whereIn('type', ['EXPENSES', 'PURCHASES'])->sum('amount');
        $discretionary = (float) $entries->where('type', 'DISCRETIONARY')->sum('amount');
        $savings = (float) $entries->where('type', 'SAVINGS')->sum('amount');
        $other = (float) $entries->where('type', 'OTHER')->sum('amount');
        $adjustment = (float) $entries->where('type', 'ADJUSTMENT')->sum('amount');
        $net = $capital + $income - $expenses - $discretionary - $savings - $other + $adjustment;

        return compact('capital', 'income', 'expenses', 'discretionary', 'savings', 'other', 'adjustment') + ['net' => $net];
    }

    private static function entryBelongsToMetroCapitalPool(DailyCashEntry $e): bool
    {
        if (($e->type ?? '') === 'CAPITAL') {
            return true;
        }

        return ($e->type ?? '') === 'INCOME'
            && (($e->category ?? '') === DailyCashflowCategories::CASH_FROM_BANK);
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
            'ADJUSTMENT' => 'Adjustment',
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
            'ADJUSTMENT' => 'adjustment',
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
