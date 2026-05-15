<?php

namespace Tests\Feature;

use App\Models\DailyCashDay;
use App\Models\DailyCashEntry;
use App\Support\DailyCashflowCategories;
use App\Support\DailyCashMetroLedger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * End-to-end accuracy checks for daily cash storage and derived totals (daily net, monthly Metro, annual grid).
 */
class DailyCashFlowAccuracyTest extends TestCase
{
    use RefreshDatabase;

    public function test_daily_net_matches_standard_cashflow_formula(): void
    {
        $day = DailyCashDay::create([
            'date' => '2026-06-10',
            'opening_balance' => 1000,
        ]);

        DailyCashEntry::create([
            'daily_cash_day_id' => $day->id,
            'type' => 'CAPITAL',
            'category' => 'capital_contribution',
            'description' => 'CAPITAL',
            'amount' => 500,
            'sort_order' => 1,
        ]);
        DailyCashEntry::create([
            'daily_cash_day_id' => $day->id,
            'type' => 'INCOME',
            'category' => 'metro_income_suman',
            'description' => 'SUMAN',
            'amount' => 200,
            'sort_order' => 2,
        ]);
        DailyCashEntry::create([
            'daily_cash_day_id' => $day->id,
            'type' => 'EXPENSES',
            'category' => 'metro_exp_assorted_ingredients',
            'description' => 'INGREDIENTS',
            'amount' => 50,
            'sort_order' => 3,
        ]);
        DailyCashEntry::create([
            'daily_cash_day_id' => $day->id,
            'type' => 'DISCRETIONARY',
            'category' => 'dining_out',
            'description' => 'LUNCH',
            'amount' => 10,
            'sort_order' => 4,
        ]);
        DailyCashEntry::create([
            'daily_cash_day_id' => $day->id,
            'type' => 'SAVINGS',
            'category' => 'cash_bank_investment',
            'description' => 'SAVE',
            'amount' => 20,
            'sort_order' => 5,
        ]);
        DailyCashEntry::create([
            'daily_cash_day_id' => $day->id,
            'type' => 'OTHER',
            'category' => null,
            'description' => 'MISC',
            'amount' => 5,
            'sort_order' => 6,
        ]);

        $day->load('entries');

        // net = capital + income - expenses - discretionary - savings - other
        $expected = 500.0 + 200.0 - 50.0 - 10.0 - 20.0 - 5.0;
        $this->assertSame($expected, $day->net());
        $this->assertSame(500.0, $day->capital());
        $this->assertSame(200.0, $day->income());
        $this->assertSame(50.0, $day->expenses());
        $this->assertSame(10.0, $day->discretionary());
        $this->assertSame(20.0, $day->savings());
        $this->assertSame(5.0, $day->other());
    }

    public function test_bank_withdrawal_splits_from_operating_income(): void
    {
        $day = DailyCashDay::create([
            'date' => '2026-06-11',
            'opening_balance' => 0,
        ]);
        DailyCashEntry::create([
            'daily_cash_day_id' => $day->id,
            'type' => 'INCOME',
            'category' => DailyCashflowCategories::CASH_FROM_BANK,
            'description' => 'ATM',
            'amount' => 300,
            'sort_order' => 1,
        ]);
        DailyCashEntry::create([
            'daily_cash_day_id' => $day->id,
            'type' => 'INCOME',
            'category' => 'metro_income_suman',
            'description' => 'SUMAN',
            'amount' => 100,
            'sort_order' => 2,
        ]);
        $day->load('entries');

        $this->assertSame(400.0, $day->income());
        $this->assertSame(300.0, $day->cashFromBankWithdrawals());
        $this->assertSame(100.0, $day->incomeExcludingBankWithdrawals());
    }

    public function test_monthly_helpers_sum_entire_calendar_month(): void
    {
        $d1 = DailyCashDay::create(['date' => '2026-07-05', 'opening_balance' => 0]);
        DailyCashEntry::create([
            'daily_cash_day_id' => $d1->id,
            'type' => 'INCOME',
            'category' => 'income_water',
            'description' => 'WATER',
            'amount' => 40,
            'sort_order' => 1,
        ]);

        $d2 = DailyCashDay::create(['date' => '2026-07-20', 'opening_balance' => 0]);
        DailyCashEntry::create([
            'daily_cash_day_id' => $d2->id,
            'type' => 'INCOME',
            'category' => 'income_water',
            'description' => 'WATER',
            'amount' => 60,
            'sort_order' => 1,
        ]);

        $d1->load('entries');
        $this->assertSame(100.0, $d1->monthlyIncome());
        $this->assertSame(100.0, $d1->monthlyNet());
    }

    public function test_monthly_metro_sheet_totals_match_entries_for_that_month(): void
    {
        $day = DailyCashDay::create(['date' => '2026-08-01', 'opening_balance' => 0]);
        DailyCashEntry::create([
            'daily_cash_day_id' => $day->id,
            'type' => 'INCOME',
            'category' => 'metro_income_suman',
            'description' => 'SUMAN',
            'amount' => 75,
            'sort_order' => 1,
        ]);
        DailyCashEntry::create([
            'daily_cash_day_id' => $day->id,
            'type' => 'EXPENSES',
            'category' => 'metro_exp_water_supplies',
            'description' => 'SUPPLIES',
            'amount' => 25,
            'sort_order' => 2,
        ]);

        $matrix = DailyCashMetroLedger::buildMonthlyMetroSheetForMonth(2026, 8);
        $sumanIncome = null;
        foreach ($matrix['sections'] as $sec) {
            foreach ($sec['lines'] ?? [] as $ln) {
                if (($ln['category_key'] ?? '') === 'metro_income_suman') {
                    $sumanIncome = (float) ($ln['amount'] ?? 0);
                    break 2;
                }
            }
        }
        $waterExp = null;
        foreach ($matrix['sections'] as $sec) {
            foreach ($sec['lines'] ?? [] as $ln) {
                if (($ln['category_key'] ?? '') === 'metro_exp_water_supplies') {
                    $waterExp = (float) ($ln['amount'] ?? 0);
                    break 2;
                }
            }
        }

        $this->assertSame(75.0, $sumanIncome);
        $this->assertSame(25.0, $waterExp);
        $this->assertSame(50.0, $matrix['month_net']);
        $this->assertSame(75.0, $matrix['footer_totals']['income']);
        $this->assertSame(25.0, $matrix['footer_totals']['expenses']);
    }

    public function test_metro_daily_worksheet_capital_row_sums_all_capital_entries(): void
    {
        $day = DailyCashDay::create(['date' => '2026-09-01', 'opening_balance' => 0]);
        DailyCashEntry::create([
            'daily_cash_day_id' => $day->id,
            'type' => 'CAPITAL',
            'category' => null,
            'description' => 'LEGACY',
            'amount' => 80,
            'sort_order' => 1,
        ]);
        DailyCashEntry::create([
            'daily_cash_day_id' => $day->id,
            'type' => 'CAPITAL',
            'category' => 'capital_contribution',
            'description' => 'CONTRIBUTION',
            'amount' => 20,
            'sort_order' => 2,
        ]);
        $day->load('entries');

        $rows = DailyCashMetroLedger::buildSheetRows($day);
        $capitalLine = collect($rows)->first(fn (array $r) => ($r['kind'] ?? '') === 'line'
            && ($r['category_key'] ?? '') === 'capital_contribution');

        $this->assertNotNull($capitalLine);
        $this->assertSame(100.0, (float) $capitalLine['amount']);
    }

    public function test_annual_grid_places_amounts_on_metro_lines_and_footer_totals_full_ledger(): void
    {
        $dayMar = DailyCashDay::create(['date' => '2026-03-15', 'opening_balance' => 0]);
        DailyCashEntry::create([
            'daily_cash_day_id' => $dayMar->id,
            'type' => 'INCOME',
            'category' => 'metro_income_suman',
            'description' => 'SUMAN',
            'amount' => 1000,
            'sort_order' => 1,
        ]);
        DailyCashEntry::create([
            'daily_cash_day_id' => $dayMar->id,
            'type' => 'EXPENSES',
            'category' => 'metro_exp_assorted_ingredients',
            'description' => 'INGREDIENTS',
            'amount' => 400,
            'sort_order' => 2,
        ]);
        DailyCashEntry::create([
            'daily_cash_day_id' => $dayMar->id,
            'type' => 'CAPITAL',
            'category' => 'capital_contribution',
            'description' => 'CAPITAL',
            'amount' => 200,
            'sort_order' => 3,
        ]);
        DailyCashEntry::create([
            'daily_cash_day_id' => $dayMar->id,
            'type' => 'DISCRETIONARY',
            'category' => 'dining_out',
            'description' => 'OUT',
            'amount' => 50,
            'sort_order' => 4,
        ]);

        $grid = DailyCashMetroLedger::buildAnnualCashflowGrid(2026);

        $findLine = static function (array $grid, string $categoryKey): ?array {
            foreach ($grid['sections'] ?? [] as $sec) {
                foreach ($sec['lines'] ?? [] as $ln) {
                    if (($ln['category_key'] ?? '') === $categoryKey) {
                        return $ln;
                    }
                }
            }

            return null;
        };

        $ing = $findLine($grid, 'metro_exp_assorted_ingredients');
        $sum = $findLine($grid, 'metro_income_suman');
        $cap = $findLine($grid, 'capital_contribution');

        $this->assertNotNull($ing);
        $this->assertSame(400.0, $ing['months'][3]['expense']);
        $this->assertSame(1000.0, $sum['months'][3]['income']);
        $this->assertSame(200.0, $cap['months'][3]['income']);

        $ti = $grid['totals_row']['months'][3]['income'];
        $te = $grid['totals_row']['months'][3]['expense'];
        $td = $grid['totals_row']['months'][3]['discretionary'];
        $ts = $grid['totals_row']['months'][3]['savings'];
        $this->assertSame(1200.0, $ti);
        $this->assertSame(400.0, $te);
        $this->assertSame(50.0, $td);
        $this->assertSame(0.0, $ts);
    }

    public function test_purchases_type_counts_as_expense_like_expenses(): void
    {
        $day = DailyCashDay::create(['date' => '2026-10-01', 'opening_balance' => 0]);
        DailyCashEntry::create([
            'daily_cash_day_id' => $day->id,
            'type' => 'PURCHASES',
            'category' => 'raw_materials',
            'description' => 'STOCK',
            'amount' => 30,
            'sort_order' => 1,
        ]);
        $day->load('entries');

        $this->assertSame(30.0, $day->expenses());
        $this->assertSame(-30.0, $day->net());
    }
}
