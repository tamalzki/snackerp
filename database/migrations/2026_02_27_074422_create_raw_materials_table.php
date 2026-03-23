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
    Schema::create('raw_materials', function (Blueprint $table) {
        $table->id();
        $table->string('name', 150)->unique();
        $table->enum('unit', ['kg', 'grams', 'liters', 'pcs']);
        $table->decimal('stock_quantity', 15, 4)->default(0);
        $table->decimal('cost_per_unit', 15, 4)->default(0);
        $table->decimal('low_stock_threshold', 15, 4)->default(0);
        $table->softDeletes();
        $table->timestamps();
    });
}

public function down(): void
{
    Schema::dropIfExists('raw_materials');
}
};
