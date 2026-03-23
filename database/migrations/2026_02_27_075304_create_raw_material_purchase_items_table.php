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
    Schema::create('raw_material_purchase_items', function (Blueprint $table) {
        $table->id();
        $table->foreignId('purchase_id')
              ->constrained('raw_material_purchases')
              ->cascadeOnDelete();
        $table->foreignId('raw_material_id')
              ->constrained('raw_materials');
        $table->decimal('quantity', 15, 4);
        $table->decimal('cost_per_unit', 15, 4);
        $table->decimal('total_cost', 15, 4);
    });
}

public function down(): void
{
    Schema::dropIfExists('raw_material_purchase_items');
}
};
