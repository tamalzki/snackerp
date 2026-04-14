<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('daily_cash_entries', function (Blueprint $table) {
            $table->string('category', 120)->nullable()->after('type');
        });
    }

    public function down(): void
    {
        Schema::table('daily_cash_entries', function (Blueprint $table) {
            $table->dropColumn('category');
        });
    }
};
