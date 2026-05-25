<?php

namespace Tests\Unit;

use App\Models\DailyCashDay;
use App\Models\DailyCashEntry;
use App\Support\DailyCashflowCategories;
use App\Support\DailyCashMetroLedger;
use Tests\TestCase;

class DailyCashMetroLedgerTest extends TestCase
{
    public function test_managed_categories_include_config_lines(): void
    {
        $keys = DailyCashMetroLedger::managedCategoryKeys();

        $this->assertContains('metro_income_suman', $keys);
        $this->assertContains('capital_contribution', $keys);
        $this->assertContains('cash_from_bank', $keys);
        $this->assertContains('metro_capital_cfb_loan', $keys);
        $this->assertContains('metro_exp_farm_others', $keys);
        $this->assertContains('metro_exp_suman_suman', $keys);
        $this->assertContains('dining_out', $keys);
        $this->assertContains('metro_other_misc', $keys);
        $this->assertContains('metro_discretionary_others', $keys);
    }

    public function test_parent_column_label_from_heading(): void
    {
        $this->assertSame('CAPITAL', DailyCashMetroLedger::parentColumnLabelFromSectionHeading('Capital'));
        $this->assertSame('CAPITAL', DailyCashMetroLedger::parentColumnLabelFromSectionHeading('CAPITAL'));
        $this->assertSame('INCOME: ASSORTED', DailyCashMetroLedger::parentColumnLabelFromSectionHeading('INCOME: ASSORTED'));
        $this->assertSame('EXPENSE: ASSORTED', DailyCashMetroLedger::parentColumnLabelFromSectionHeading('EXPENSE: ASSORTED'));
        $this->assertSame('EXPENSE: WATER', DailyCashMetroLedger::parentColumnLabelFromSectionHeading('EXPENSE: WATER'));
        $this->assertSame('EXPENSE: FARM', DailyCashMetroLedger::parentColumnLabelFromSectionHeading('EXPENSE: FARM'));
        $this->assertSame('EXPENSE: SUMAN', DailyCashMetroLedger::parentColumnLabelFromSectionHeading('EXPENSE: SUMAN'));
    }

    public function test_worksheet_type_word_maps_ledger_types(): void
    {
        $this->assertSame('Income', DailyCashMetroLedger::worksheetTypeWord('INCOME'));
        $this->assertSame('Expense', DailyCashMetroLedger::worksheetTypeWord('EXPENSES'));
        $this->assertSame('Expense', DailyCashMetroLedger::worksheetTypeWord('PURCHASES'));
        $this->assertSame('Capital', DailyCashMetroLedger::worksheetTypeWord('CAPITAL'));
    }

    public function test_capital_worksheet_row_sums_all_capital_entries(): void
    {
        $day = new DailyCashDay;
        $day->setRelation('entries', collect([
            new DailyCashEntry([
                'type' => 'CAPITAL',
                'category' => null,
                'description' => 'LEGACY CAPITAL',
                'amount' => 100,
            ]),
            new DailyCashEntry([
                'type' => 'CAPITAL',
                'category' => 'capital_contribution',
                'description' => 'CAPITAL CONTRIBUTION',
                'amount' => 50,
            ]),
        ]));

        $rows = DailyCashMetroLedger::buildSheetRows($day);
        $capitalLine = collect($rows)->first(fn (array $r) => ($r['kind'] ?? '') === 'line'
            && ($r['category_key'] ?? '') === 'capital_contribution');

        $this->assertNotNull($capitalLine);
        $this->assertSame('Cash', $capitalLine['category_display']);
        $this->assertSame('Capital', $capitalLine['type_word']);
        $this->assertSame(150.0, (float) $capitalLine['amount']);
        $this->assertSame(2, $capitalLine['entry_count']);
    }

    public function test_worksheet_others_row_shows_other_prefix_with_single_detail(): void
    {
        $day = new DailyCashDay;
        $day->setRelation('entries', collect([
            new DailyCashEntry([
                'type' => 'INCOME',
                'category' => 'metro_income_others',
                'description' => 'OTHERS',
                'amount' => 0,
            ]),
            new DailyCashEntry([
                'type' => 'INCOME',
                'category' => 'metro_income_others',
                'description' => 'REFUND FROM VENDOR',
                'amount' => 50,
            ]),
        ]));

        $rows = DailyCashMetroLedger::buildSheetRows($day);
        $line = collect($rows)->first(fn (array $r) => ($r['kind'] ?? '') === 'line'
            && ($r['category_key'] ?? '') === 'metro_income_others');

        $this->assertNotNull($line);
        $this->assertSame('Other - REFUND FROM VENDOR', $line['category_display']);
    }

    public function test_worksheet_entry_form_tree_includes_income_and_expense_buckets(): void
    {
        $tree = DailyCashMetroLedger::worksheetEntryFormTree();

        $this->assertNotEmpty($tree['income']['buckets'] ?? []);
        $this->assertNotEmpty($tree['expense']['buckets'] ?? []);
        $this->assertNotEmpty($tree['discretionary']['buckets'] ?? []);
        $this->assertNotEmpty($tree['savings']['buckets'] ?? []);
        $this->assertNotEmpty($tree['other']['buckets'] ?? []);
        $this->assertTrue(DailyCashMetroLedger::isMetroOthersCategory('metro_income_others'));
        $this->assertTrue(DailyCashMetroLedger::isMetroOthersCategory('metro_exp_assorted_others'));
        $this->assertTrue(DailyCashMetroLedger::isMetroOthersCategory('metro_other_others'));
        $this->assertFalse(DailyCashMetroLedger::isMetroOthersCategory('metro_discretionary_others'));
        $this->assertTrue(DailyCashMetroLedger::worksheetNeedsSpecifyOtherField('metro_discretionary_others'));
        $this->assertTrue(DailyCashMetroLedger::worksheetUsesOthersAggregateLabel('metro_discretionary_others'));
    }

    public function test_non_metro_excludes_capital_pool_entries_and_legacy_bank(): void
    {
        $day = new DailyCashDay;
        $day->setRelation('entries', collect([
            new DailyCashEntry([
                'type' => 'CAPITAL',
                'category' => null,
                'description' => 'LEGACY',
                'amount' => 10,
            ]),
            new DailyCashEntry([
                'type' => 'INCOME',
                'category' => DailyCashflowCategories::CASH_FROM_BANK,
                'description' => 'ATM',
                'amount' => 300,
            ]),
            new DailyCashEntry([
                'type' => 'EXPENSES',
                'category' => null,
                'description' => 'LEGACY EXPENSE',
                'amount' => 20,
            ]),
        ]));

        $other = DailyCashMetroLedger::nonMetroEntries($day);

        $this->assertCount(1, $other);
        $this->assertSame('EXPENSES', $other->first()->type);
    }

    public function test_metro_sheet_definition_covers_annual_worksheet_lines(): void
    {
        $keys = [];
        $headings = [];
        $currentHeading = '';
        foreach (DailyCashMetroLedger::sheetDefinition() as $row) {
            if (($row['kind'] ?? '') === 'heading') {
                $currentHeading = (string) ($row['title'] ?? '');
                $headings[] = $currentHeading;

                continue;
            }
            if (($row['kind'] ?? '') === 'line' && ! empty($row['category_key'])) {
                $keys[] = (string) $row['category_key'];
            }
        }

        $this->assertContains('CAPITAL', $headings);
        $this->assertContains('INCOME: ASSORTED', $headings);
        $this->assertContains('EXPENSE: SUMAN', $headings);
        $this->assertContains('DISCRETIONARY', $headings);
        $this->assertContains('SAVINGS', $headings);
        $this->assertContains('ADJUSTMENTS', $headings);
        $this->assertContains('OTHER', $headings);
        $this->assertContains('metro_income_suman', $keys);
        $this->assertContains('metro_exp_assorted_ingredients', $keys);
        $this->assertContains('capital_contribution', $keys);
        $this->assertContains('cash_from_bank', $keys);
        $this->assertContains('adj_income_assorted', $keys);
        $this->assertContains('adj_exp_assorted', $keys);
        $this->assertContains('adj_others', $keys);
    }

    public function test_adjustment_lines_carry_signed_amount_into_net(): void
    {
        $day = new DailyCashDay;
        $day->setRelation('entries', collect([
            new DailyCashEntry([
                'type' => 'INCOME',
                'category' => 'income_water',
                'description' => 'WATER',
                'amount' => 100,
            ]),
            new DailyCashEntry([
                'type' => 'ADJUSTMENT',
                'category' => 'adj_income_assorted',
                'description' => 'OVERSTATEMENT',
                'amount' => -25,
            ]),
        ]));

        $this->assertSame(-25.0, (float) $day->adjustment());
        $this->assertSame(75.0, (float) $day->net());
    }

    public function test_custom_per_day_row_appears_under_section(): void
    {
        $day = new DailyCashDay;
        $day->setRelation('entries', collect([
            new DailyCashEntry([
                'type' => 'EXPENSES',
                'category' => 'custom:expense_water',
                'description' => 'CHLORINE TABLETS',
                'amount' => 80,
            ]),
        ]));

        $rows = DailyCashMetroLedger::buildSheetRows($day);
        $custom = collect($rows)->first(fn (array $r) => ($r['kind'] ?? '') === 'line'
            && ($r['is_custom'] ?? false)
            && ($r['section_slug'] ?? '') === 'expense_water');

        $this->assertNotNull($custom);
        $this->assertSame('CHLORINE TABLETS', $custom['category_display']);
        $this->assertSame(80.0, (float) $custom['amount']);
    }

    public function test_worksheet_entry_form_tree_exposes_adjustment_buckets(): void
    {
        $tree = DailyCashMetroLedger::worksheetEntryFormTree();

        $this->assertNotEmpty($tree['adjustment']['buckets'] ?? []);
        $slugs = collect($tree['adjustment']['buckets'])->pluck('section_slug')->all();
        $this->assertContains('income_assorted', $slugs);
        $this->assertContains('adjustments', $slugs);
    }

    public function test_section_slug_from_heading_normalizes_punctuation(): void
    {
        $this->assertSame('income_assorted', DailyCashMetroLedger::sectionSlugFromHeading('INCOME: ASSORTED'));
        $this->assertSame('expense_water', DailyCashMetroLedger::sectionSlugFromHeading('EXPENSE: WATER'));
        $this->assertSame('adjustments', DailyCashMetroLedger::sectionSlugFromHeading('ADJUSTMENTS'));
    }
}
