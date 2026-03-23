<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('consignment_payments', function (Blueprint $table) {
            $table->foreignId('consignment_sale_id')
                ->nullable()
                ->after('consignment_receivable_id')
                ->constrained('consignment_sales')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('consignment_payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('consignment_sale_id');
        });
    }
};
