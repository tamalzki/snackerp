<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $legacyKeys = ['metro_capital_savings', 'metro_capital_hand_others', 'metro_capital_cfb_cash'];

        DB::table('daily_cash_entries')
            ->whereIn('category', $legacyKeys)
            ->where(function ($q) {
                $q->whereRaw('ABS(amount) <= 0.005')->orWhereNull('amount');
            })
            ->delete();

        DB::table('daily_cash_entries')
            ->whereIn('category', $legacyKeys)
            ->update(['category' => 'metro_capital_cfb_others']);
    }

    public function down(): void
    {
        // Irreversible: the three retired capital lines have been folded into "Others".
    }
};
