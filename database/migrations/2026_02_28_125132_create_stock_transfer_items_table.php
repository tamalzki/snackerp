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
    Schema::create('stock_transfer_items', function (Blueprint $table) {
        $table->id();
        $table->foreignId('transfer_id')
              ->constrained('stock_transfers')
              ->cascadeOnDelete();
        $table->foreignId('finished_product_id')
              ->constrained('finished_products');
        $table->decimal('quantity', 15, 4);
        $table->decimal('cost_snapshot', 15, 4);
    });
}

public function down(): void
{
    Schema::dropIfExists('stock_transfer_items');
}
};
