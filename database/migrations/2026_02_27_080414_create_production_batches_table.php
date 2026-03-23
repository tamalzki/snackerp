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
    Schema::create('production_batches', function (Blueprint $table) {
        $table->id();
        $table->foreignId('finished_product_id')->constrained('finished_products');
        $table->decimal('expected_output_qty', 15, 4);
        $table->decimal('actual_output_qty', 15, 4);
        $table->decimal('reject_qty', 15, 4)->default(0);
        $table->decimal('total_raw_material_cost', 15, 4)->default(0);
        $table->decimal('cost_per_unit', 15, 4)->default(0);
        $table->date('production_date');
        $table->foreignId('created_by')->constrained('users');
        $table->timestamps();
    });
}

public function down(): void
{
    Schema::dropIfExists('production_batches');
}

};
