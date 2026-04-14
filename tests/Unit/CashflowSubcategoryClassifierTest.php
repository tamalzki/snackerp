<?php

namespace Tests\Unit;

use App\Support\CashflowSubcategoryClassifier;
use Tests\TestCase;

class CashflowSubcategoryClassifierTest extends TestCase
{
    public function test_resolve_uses_valid_override_despite_conflicting_description(): void
    {
        $r = CashflowSubcategoryClassifier::resolve('EXPENSES', 'FUEL FOR TRUCK', 'utilities');

        $this->assertSame('utilities', $r['key']);
        $this->assertSame('Utilities', $r['label']);
    }

    public function test_resolve_invalid_override_falls_back_to_keyword_classification(): void
    {
        $r = CashflowSubcategoryClassifier::resolve('EXPENSES', 'MERALCO BILL', 'not_a_lexicon_key_xyz');

        $this->assertSame('utilities', $r['key']);
        $this->assertSame('Utilities', $r['label']);
    }

    public function test_resolve_null_override_classifies_from_description(): void
    {
        $r = CashflowSubcategoryClassifier::resolve('PURCHASES', 'CARTON PACKAGING ORDER', null);

        $this->assertSame('packaging', $r['key']);
        $this->assertSame('Packaging', $r['label']);
    }

    public function test_classify_empty_description_is_uncategorized(): void
    {
        $r = CashflowSubcategoryClassifier::classify('EXPENSES', '   ');

        $this->assertSame('uncategorized', $r['key']);
        $this->assertSame('Uncategorized', $r['label']);
    }

    public function test_resolve_empty_description_with_valid_override_still_returns_override(): void
    {
        $r = CashflowSubcategoryClassifier::resolve('EXPENSES', '', 'bank_fees');

        $this->assertSame('bank_fees', $r['key']);
        $this->assertSame('Bank & fees', $r['label']);
    }

    public function test_resolve_empty_description_invalid_override_falls_back_to_uncategorized(): void
    {
        $r = CashflowSubcategoryClassifier::resolve('EXPENSES', '', 'stale_invalid_key');

        $this->assertSame('uncategorized', $r['key']);
        $this->assertSame('Uncategorized', $r['label']);
    }

    public function test_other_type_has_no_keywords_and_yields_uncategorized(): void
    {
        $r = CashflowSubcategoryClassifier::resolve('OTHER', 'ANY TEXT', null);

        $this->assertSame('uncategorized', $r['key']);
        $this->assertSame('Uncategorized', $r['label']);
    }

    public function test_uncategorized_key_is_valid(): void
    {
        $this->assertTrue(CashflowSubcategoryClassifier::isValidKeyForType('EXPENSES', 'uncategorized'));
    }

    public function test_unknown_key_is_invalid(): void
    {
        $this->assertFalse(CashflowSubcategoryClassifier::isValidKeyForType('EXPENSES', 'definitely_not_a_key'));
    }

    public function test_label_for_key_returns_null_for_wrong_type(): void
    {
        $this->assertNull(CashflowSubcategoryClassifier::labelForKey('INCOME', 'utilities'));
    }
}
