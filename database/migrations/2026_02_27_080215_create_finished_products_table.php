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
    Schema::create('finished_products', function (Blueprint $table) {
        $table->id();
        $table->string('name', 150)->unique();
        $table->decimal('current_stock', 15, 4)->default(0);
        $table->decimal('average_cost', 15, 4)->default(0);
        $table->decimal('selling_price', 15, 4)->default(0);
        $table->decimal('low_stock_threshold', 15, 4)->default(0);
        $table->softDeletes();
        $table->timestamps();
    });
}

public function down(): void
{
    Schema::dropIfExists('finished_products');
}
};
