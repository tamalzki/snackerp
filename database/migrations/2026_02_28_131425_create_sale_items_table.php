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
    Schema::create('sale_items', function (Blueprint $table) {
        $table->id();
        $table->foreignId('sale_id')
              ->constrained('sales')
              ->cascadeOnDelete();
        $table->foreignId('finished_product_id')
              ->constrained('finished_products');
        $table->decimal('quantity', 15, 4);
        $table->decimal('unit_price', 15, 4);
        $table->decimal('cost_snapshot', 15, 4);
        $table->decimal('total_price', 15, 2);
    });
}

public function down(): void
{
    Schema::dropIfExists('sale_items');
}
};
