<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Expense — Assorted: Placeholder line renamed to Salary & Wages (same ledger slot).
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('daily_cash_entries')
            ->where('category', 'metro_exp_assorted_placeholder')
            ->update(['category' => 'metro_exp_assorted_salaries_wages']);
    }

    public function down(): void
    {
        DB::table('daily_cash_entries')
            ->where('category', 'metro_exp_assorted_salaries_wages')
            ->update(['category' => 'metro_exp_assorted_placeholder']);
    }
};
