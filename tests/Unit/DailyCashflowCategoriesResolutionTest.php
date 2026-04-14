<?php

namespace Tests\Unit;

use App\Support\DailyCashflowCategories;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class DailyCashflowCategoriesResolutionTest extends TestCase
{
    public function test_bank_withdrawal_ignores_subcategory_and_preset(): void
    {
        $req = Request::create('/fake', 'POST', [
            'type' => 'CASH_FROM_BANK',
            'category_preset' => 'none',
            'subcategory_key' => 'utilities',
        ]);

        [$cat, $sub] = DailyCashflowCategories::resolveCategoryAndSubcategoryForEntryRequest($req, 'INCOME', true);

        $this->assertSame(DailyCashflowCategories::CASH_FROM_BANK, $cat);
        $this->assertNull($sub);
    }

    public function test_auto_subcategory_yields_null_override(): void
    {
        $req = Request::create('/fake', 'POST', [
            'type' => 'EXPENSES',
            'category_preset' => 'none',
            'subcategory_key' => 'auto',
        ]);

        [$cat, $sub] = DailyCashflowCategories::resolveCategoryAndSubcategoryForEntryRequest($req, 'EXPENSES', false);

        $this->assertNull($sub);
        $this->assertNull($cat);
    }

    public function test_subcategory_sets_override_and_matching_ledger_category(): void
    {
        $req = Request::create('/fake', 'POST', [
            'type' => 'EXPENSES',
            'category_preset' => 'none',
            'subcategory_key' => 'utilities',
        ]);

        [$cat, $sub] = DailyCashflowCategories::resolveCategoryAndSubcategoryForEntryRequest($req, 'EXPENSES', false);

        $this->assertSame('utilities', $sub);
        $this->assertSame('utilities', $cat);
    }

    public function test_optional_ledger_tag_takes_precedence_over_sub_derived_category(): void
    {
        $req = Request::create('/fake', 'POST', [
            'type' => 'INCOME',
            'category_preset' => 'income_water',
            'subcategory_key' => 'sales',
        ]);

        [$cat, $sub] = DailyCashflowCategories::resolveCategoryAndSubcategoryForEntryRequest($req, 'INCOME', false);

        $this->assertSame('income_water', $cat);
        $this->assertSame('sales', $sub);
    }

    public function test_income_plus_requires_custom_piece(): void
    {
        $this->expectException(ValidationException::class);

        $req = Request::create('/fake', 'POST', [
            'type' => 'INCOME',
            'category_preset' => 'income_plus',
            'category_custom_piece' => '',
            'subcategory_key' => 'auto',
        ]);

        DailyCashflowCategories::resolveCategoryAndSubcategoryForEntryRequest($req, 'INCOME', false);
    }

    public function test_invalid_subcategory_key_throws(): void
    {
        $this->expectException(ValidationException::class);

        $req = Request::create('/fake', 'POST', [
            'type' => 'EXPENSES',
            'category_preset' => 'none',
            'subcategory_key' => 'not_a_real_lexicon_key',
        ]);

        DailyCashflowCategories::resolveCategoryAndSubcategoryForEntryRequest($req, 'EXPENSES', false);
    }

    public function test_uncategorized_subcategory_stored(): void
    {
        $req = Request::create('/fake', 'POST', [
            'type' => 'EXPENSES',
            'category_preset' => 'none',
            'subcategory_key' => 'uncategorized',
        ]);

        [$cat, $sub] = DailyCashflowCategories::resolveCategoryAndSubcategoryForEntryRequest($req, 'EXPENSES', false);

        $this->assertSame('uncategorized', $sub);
        $this->assertNull($cat);
    }

    public function test_preset_not_allowed_for_type_throws(): void
    {
        $this->expectException(ValidationException::class);

        $req = Request::create('/fake', 'POST', [
            'type' => 'INCOME',
            'category_preset' => 'utilities',
            'subcategory_key' => 'auto',
        ]);

        DailyCashflowCategories::resolveCategoryAndSubcategoryForEntryRequest($req, 'INCOME', false);
    }

    public function test_income_subcategory_only_does_not_set_category_when_group_has_no_category_value(): void
    {
        $req = Request::create('/fake', 'POST', [
            'type' => 'INCOME',
            'category_preset' => 'none',
            'subcategory_key' => 'sales',
        ]);

        [$cat, $sub] = DailyCashflowCategories::resolveCategoryAndSubcategoryForEntryRequest($req, 'INCOME', false);

        $this->assertSame('sales', $sub);
        $this->assertNull($cat);
    }
}
