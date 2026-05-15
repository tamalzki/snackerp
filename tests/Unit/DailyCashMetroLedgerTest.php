<?php

namespace Tests\Unit;

use App\Models\DailyCashDay;
use App\Models\DailyCashEntry;
use App\Support\DailyCashMetroLedger;
use Tests\TestCase;

class DailyCashMetroLedgerTest extends TestCase
{
    public function test_managed_categories_include_config_lines(): void
    {
        $keys = DailyCashMetroLedger::managedCategoryKeys();

        $this->assertContains('metro_income_suman', $keys);
        $this->assertContains('capital_contribution', $keys);
        $this->assertContains('metro_exp_farm_others', $keys);
        $this->assertContains('metro_exp_suman_suman', $keys);
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
        $this->assertSame('Capital', $capitalLine['category_display']);
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
        $this->assertTrue(DailyCashMetroLedger::isMetroOthersCategory('metro_income_others'));
        $this->assertTrue(DailyCashMetroLedger::isMetroOthersCategory('metro_exp_assorted_others'));
    }

    public function test_non_metro_excludes_capital_entries(): void
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
        $this->assertContains('metro_income_suman', $keys);
        $this->assertContains('metro_exp_assorted_ingredients', $keys);
        $this->assertContains('capital_contribution', $keys);
    }
}
