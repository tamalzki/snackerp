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
    Schema::create('branch_inventory', function (Blueprint $table) {
        $table->id();
        $table->foreignId('branch_id')->constrained('branches');
        $table->foreignId('finished_product_id')
              ->constrained('finished_products');
        $table->decimal('stock_quantity', 15, 4)->default(0);
        $table->decimal('cost_snapshot', 15, 4)->default(0);
        $table->timestamps();
        $table->unique(['branch_id', 'finished_product_id']);
    });
}

public function down(): void
{
    Schema::dropIfExists('branch_inventory');
}
};
