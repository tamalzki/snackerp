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
    Schema::create('product_restocks', function (Blueprint $table) {
        $table->id();
        $table->foreignId('finished_product_id')->constrained()->cascadeOnDelete();
        $table->decimal('quantity', 12, 4);
        $table->decimal('unit_cost', 12, 4);
        $table->decimal('total_cost', 12, 4);
        $table->date('restock_date');
        $table->string('supplier', 150)->nullable();
        $table->string('notes', 500)->nullable();
        $table->foreignId('created_by')->nullable()->constrained('users');
        $table->timestamps();
    });
}

public function down(): void
{
    Schema::dropIfExists('product_restocks');
}
};
