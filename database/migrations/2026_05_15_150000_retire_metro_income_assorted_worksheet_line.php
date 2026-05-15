<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Worksheet no longer includes metro_income_assorted; drop zero stubs and move balances to Others.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('daily_cash_entries')
            ->where('category', 'metro_income_assorted')
            ->where('type', 'INCOME')
            ->orderBy('id')
            ->lazyById()
            ->each(function (object $row): void {
                if (abs((float) $row->amount) > 0.005) {
                    DB::table('daily_cash_entries')->where('id', $row->id)->update(['category' => 'metro_income_others']);
                } else {
                    DB::table('daily_cash_entries')->where('id', $row->id)->delete();
                }
            });
    }

    public function down(): void
    {
        //
    }
};
