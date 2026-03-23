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
    Schema::create('consignment_sale_items', function (Blueprint $table) {
        $table->id();
        $table->foreignId('consignment_sale_id')->constrained()->cascadeOnDelete();
        $table->foreignId('finished_product_id')->constrained()->cascadeOnDelete();
        $table->decimal('qty_sold', 12, 4);
        $table->decimal('unit_price', 12, 4);
        $table->decimal('cost_snapshot', 12, 4)->default(0);
        $table->decimal('total_price', 12, 2);
        $table->timestamps();
    });
}
public function down(): void { Schema::dropIfExists('consignment_sale_items'); }
};
