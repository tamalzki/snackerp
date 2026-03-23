<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
{
    Schema::table('stock_transfers', function (Blueprint $table) {
        $table->string('dr_number', 50)->nullable()->after('id');
    });
}

public function down(): void
{
    Schema::table('stock_transfers', function (Blueprint $table) {
        $table->dropColumn('dr_number');
    });
}
};
