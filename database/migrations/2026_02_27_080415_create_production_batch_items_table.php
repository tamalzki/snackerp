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
    Schema::create('production_batch_items', function (Blueprint $table) {
        $table->id();
        $table->foreignId('production_batch_id')
              ->constrained('production_batches')
              ->cascadeOnDelete();
        $table->foreignId('raw_material_id')
              ->constrained('raw_materials');
        $table->decimal('quantity_used', 15, 4);
        $table->decimal('cost_snapshot', 15, 4);
        $table->decimal('total_cost', 15, 4);
    });
}

public function down(): void
{
    Schema::dropIfExists('production_batch_items');
}
};
