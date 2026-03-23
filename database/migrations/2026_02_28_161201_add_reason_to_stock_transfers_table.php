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
        $table->enum('reason', [
            'delivery',
            'pullout_expired',
            'pullout_return',
            'branch_to_branch',
        ])->default('delivery')->after('notes');
    });
}

public function down(): void
{
    Schema::table('stock_transfers', function (Blueprint $table) {
        $table->dropColumn('reason');
    });
}
};
