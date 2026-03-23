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
    Schema::create('raw_material_purchases', function (Blueprint $table) {
        $table->id();
        $table->string('supplier_name', 150);
        $table->decimal('total_cost', 15, 4)->default(0);
        $table->date('purchase_date');
        $table->foreignId('created_by')->constrained('users');
        $table->timestamps();
    });
}

public function down(): void
{
    Schema::dropIfExists('raw_material_purchases');
}
};
